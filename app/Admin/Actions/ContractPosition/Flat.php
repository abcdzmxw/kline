<?php

namespace App\Admin\Actions\ContractPosition;

use App\Exceptions\ApiException;
use App\Handlers\ContractTool;
use App\Jobs\HandleContractEntrust;
use App\Jobs\HandleFlatPosition;
use App\Models\ContractEntrust;
use App\Models\ContractOrder;
use App\Models\ContractPair;
use App\Models\ContractPosition;
use App\Models\ContractWearPositionRecord;
use App\Models\SustainableAccount;
use App\Models\User;
use App\Models\UserWallet;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Flat extends RowAction
{
    /**
     * @return string
     */
	protected $title = '平仓';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $id = $this->getKey();
        if(blank($id)){
            return $this->response()->error('Processed fail.');
        }

        $position = ContractPosition::query()->where('id',$id)->first();
        if(blank($position)){
            return $this->response()->error('Processed fail.');
        }
        if($position['avail_position'] == 0) return $this->response()->error('当前仓位张数为0');

        DB::beginTransaction();
        try{

            if($position['side'] == 1){
                // 先平仓
                $entrust = $this->dealManyPosition($position);
            }else{
                $entrust = $this->dealEmptyPosition($position);
            }

            if($entrust['side'] == 1){
                $this->dealProfit($entrust,'buy');
            }else{
                $this->dealProfit($entrust,'sell');
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return $this->response()->error($e->getMessage());
        }

        return $this->response()->success('Processed successfully.')->refresh();
    }

    private function dealProfit($entrust,$side,$system = 1)
    {
        $user = User::query()->find($entrust['user_id']);
        if($side == 'sell'){
            $log_type = $system == 1 ? 'system_close_long_position' : 'close_long_position';
        }else{
            $log_type = $system == 1 ? 'system_close_short_position' : 'close_short_position';
        }

        $balance = SustainableAccount::query()->where('user_id',$entrust['user_id'])->value('usable_balance');
        $settle_profit = $entrust['profit'];
        if($settle_profit < 0 && $balance < abs($settle_profit)){
            $settle_profit = -$balance;

            $position_side = $side == 'sell' ? 1 : 2;
            $open_position_price = ContractPosition::query()->where(['user_id'=>$entrust['user_id'],'contract_id'=>$entrust['contract_id'],'side'=>$position_side])->value('avg_price');

            ContractWearPositionRecord::query()->create([
                'user_id' => $entrust['user_id'],
                'contract_id' => $entrust['contract_id'],
                'symbol' => $entrust['symbol'],
                'position_side' => $position_side,
                'open_position_price' => $open_position_price,
                'close_position_price' => $entrust['avg_price'],
                'profit' => $entrust['profit'],
                'settle_profit' => $settle_profit,
                'loss' => $settle_profit - $entrust['profit'],
                'ts' => time(),
            ]);

            // 更新委托 记录穿仓
            $entrust->update(['settle_profit'=>$settle_profit,'is_wear'=>1]);
        }

        $user->update_wallet_and_log($entrust['margin_coin_id'],'usable_balance',$settle_profit,UserWallet::sustainable_account,$log_type,'',$entrust['contract_id']);
    }

    private function dealManyPosition($position,$system = 1)
    {
        $pair = ContractPair::query()->find($position['contract_id']);
        if(blank($pair)) return ;
        // 记录仓位保证金(平仓时直接抵消掉)
        $unit_amount = $position['unit_amount']; // 单张合约面值
        $margin = ($position['position_margin'] / $position['hold_position']) * $position['avail_position'];
        $unit_fee = PriceCalculate($unit_amount ,'*', $pair['maker_fee_rate'],5); // 单张合约手续费
        $fee = PriceCalculate($position['avail_position'] ,'*', $unit_fee,5);

        //用户创建平仓订单 多仓 卖出平多
        $order_data = [
            'user_id' => $position['user_id'],
            'order_no' => get_order_sn('PCB'),
            'contract_id' => $position['contract_id'],
            'contract_coin_id' => $position['contract_coin_id'],
            'margin_coin_id' => $position['margin_coin_id'],
            'symbol' => $position['symbol'],
            'unit_amount' => $unit_amount,
            'order_type' => 2,
            'side' => 2,
            'type' => 2, // 市价
            'entrust_price' => null,
            'amount' => $position['avail_position'],
            'lever_rate' => $position['lever_rate'],
            'margin' => $margin,
            'fee' => $fee,
            'hang_status' => 1,
            'trigger_price' => null,
            'ts' => time(),
            'system' => 1,
        ];
        $sell = ContractEntrust::query()->create($order_data);

        $surplus_amount = $sell['amount'];
        $cacheKey = 'swap:trade_detail_' . $position['symbol'];
        $cacheData = Cache::store('redis')->get($cacheKey);
        if(blank($cacheData)) return ;
        $entrust_price = $cacheData['price'];
        $many_position_profit = ContractTool::unRealProfit($position,$pair,$entrust_price,$position['avail_position']);

        if($surplus_amount <= 0) return ;
        $user = User::getOneSystemUser(); // 获取随机一个系统账户
        if(blank($user)) return ;

        //创建系统用户订单 对手单
        $order_data2 = [
            'user_id' => $user['user_id'],
            'order_no' => get_order_sn('PCB'),
            'contract_id' => $sell['contract_id'],
            'contract_coin_id' => $sell['contract_coin_id'],
            'margin_coin_id' => $sell['margin_coin_id'],
            'symbol' => $sell['symbol'],
            'unit_amount' => $sell['unit_amount'],
            'order_type' => 2,
            'side' => 1,
            'type' => 1,
            'entrust_price' => $entrust_price,
            'amount' => $surplus_amount,
            'lever_rate' => $sell['lever_rate'],
            'margin' => $margin,
            'fee' => $fee,
            'ts' => time(),
            'system' => 1,
        ];
        $buy = ContractEntrust::query()->create($order_data2);

        $exchange_amount = $buy['amount'];
        $unit_price = $buy['entrust_price'];

        $buy_traded_amount = $buy['traded_amount'] + $exchange_amount;
        $sell_traded_amount = $sell['traded_amount'] + $exchange_amount;
        $buy_margin = PriceCalculate(($exchange_amount * $unit_amount) ,'/', $buy['lever_rate'],5);
        $sell_margin = PriceCalculate(($exchange_amount * $unit_amount) ,'/', $sell['lever_rate'],5);
        $buy_fee = PriceCalculate($exchange_amount ,'*', $unit_fee,5);
        $sell_fee = PriceCalculate($exchange_amount ,'*', $unit_fee,5);
        //增加委托成交匹配记录
        ContractOrder::query()->create([
            'contract_id' => $buy['contract_id'],
            'symbol' => $buy['symbol'],
            'unit_amount' => $buy['unit_amount'],
            'order_type' => $buy['order_type'],
            'lever_rate' => $buy['lever_rate'],
            'buy_id' => $buy['id'],
            'sell_id' => $sell['id'],
            'buy_user_id' => $buy['user_id'],
            'sell_user_id' => $sell['user_id'],
            'unit_price' => $unit_price,
            'trade_amount' => $exchange_amount,
            'trade_buy_fee' => $buy_fee,
            'trade_sell_fee' => $sell_fee,
            'ts' => time(),
        ]);

        // 解冻合约账户保证金 & 扣除平仓手续费
        $log_type = $system == 1 ? 'system_close_position' : 'close_position';
        $log_type2 = $system == 1 ? 'system_close_position_fee' : 'close_position_fee';

        // 卖出平多
        $sell_user = User::query()->find($sell['user_id']);
        if(!blank($sell_user)){
            // 多仓
            $many_position = $position;
            $many_position->update([
                'hold_position' => $many_position['hold_position'] - $exchange_amount,
                'avail_position' => $many_position['avail_position'] - $exchange_amount,
                'position_margin' => $many_position['position_margin'] - $sell_margin,
            ]);

            $sell_user->update_wallet_and_log($sell['margin_coin_id'],'usable_balance',$sell_margin,UserWallet::sustainable_account,$log_type,'',$sell['contract_id']);
            $sell_user->update_wallet_and_log($sell['margin_coin_id'],'used_balance',-$sell_margin,UserWallet::sustainable_account,$log_type,'',$sell['contract_id']);
            $sell_user->update_wallet_and_log($sell['margin_coin_id'],'usable_balance',-$sell_fee,UserWallet::sustainable_account,$log_type2,'',$sell['contract_id']);
        }

        //更新买卖委托
        $buy->update(['traded_amount' => $buy_traded_amount, 'status' => ContractEntrust::status_completed]);
        $sell->update(['traded_amount' => $sell_traded_amount, 'avg_price'=>$entrust_price, 'profit'=>$many_position_profit,'status' => ContractEntrust::status_completed]);

        return $sell;
    }

    private function dealEmptyPosition($position,$system = 1)
    {
        $pair = ContractPair::query()->find($position['contract_id']);
        if(blank($pair)) return ;
        // 记录仓位保证金(平仓时直接抵消掉)
        $unit_amount = $position['unit_amount']; // 单张合约面值
        $margin = ($position['position_margin'] / $position['hold_position']) * $position['avail_position'];
        $unit_fee = PriceCalculate($unit_amount ,'*', $pair['maker_fee_rate'],5); // 单张合约手续费
        $fee = PriceCalculate($position['avail_position'] ,'*', $unit_fee,5);

        //用户创建平仓订单 空仓 买入平空
        $order_data = [
            'user_id' => $position['user_id'],
            'order_no' => get_order_sn('PCB'),
            'contract_id' => $position['contract_id'],
            'contract_coin_id' => $position['contract_coin_id'],
            'margin_coin_id' => $position['margin_coin_id'],
            'symbol' => $position['symbol'],
            'unit_amount' => $unit_amount,
            'order_type' => 2,
            'side' => 1,
            'type' => 2, // 市价
            'entrust_price' => null,
            'amount' => $position['avail_position'],
            'lever_rate' => $position['lever_rate'],
            'margin' => $margin,
            'fee' => $fee,
            'hang_status' => 1,
            'trigger_price' => null,
            'ts' => time(),
            'system' => 1,
        ];
        $buy = ContractEntrust::query()->create($order_data);

        $surplus_amount = $buy['amount'];
        $cacheKey = 'swap:trade_detail_' . $buy['symbol'];
        $cacheData = Cache::store('redis')->get($cacheKey);
        if(blank($cacheData)) return ;
        $entrust_price = $cacheData['price'];
        $empty_position_profit = ContractTool::unRealProfit($position,$pair,$entrust_price,$position['avail_position']);

        if($surplus_amount <= 0) return ;
        $user = User::getOneSystemUser(); // 获取随机一个系统账户
        if(blank($user)) return ;
        //创建对手委托
        $order_data2 = [
            'user_id' => $user['user_id'],
            'order_no' => get_order_sn('PCB'),
            'contract_id' => $buy['contract_id'],
            'contract_coin_id' => $buy['contract_coin_id'],
            'margin_coin_id' => $buy['margin_coin_id'],
            'symbol' => $buy['symbol'],
            'unit_amount' => $buy['unit_amount'],
            'order_type' => 2,
            'side' => 2,
            'type' => 1,
            'entrust_price' => $entrust_price,
            'amount' => $surplus_amount,
            'lever_rate' => $buy['lever_rate'],
            'ts' => time(),
            'system' => 1,
        ];
        $sell = ContractEntrust::query()->create($order_data2);

        $exchange_amount = $sell['amount'];
        $unit_price = $sell['entrust_price'];

        $buy_traded_amount = $buy['traded_amount'] + $exchange_amount;
        $sell_traded_amount = $sell['traded_amount'] + $exchange_amount;
        $buy_margin = PriceCalculate(($exchange_amount * $unit_amount) ,'/', $buy['lever_rate'],5);
        $sell_margin = PriceCalculate(($exchange_amount * $unit_amount) ,'/', $sell['lever_rate'],5);
        $buy_fee = PriceCalculate($exchange_amount ,'*', $unit_fee,5);
        $sell_fee = PriceCalculate($exchange_amount ,'*', $unit_fee,5);
        //增加委托成交匹配记录
        ContractOrder::query()->create([
            'contract_id' => $buy['contract_id'],
            'symbol' => $buy['symbol'],
            'unit_amount' => $buy['unit_amount'],
            'order_type' => $buy['order_type'],
            'lever_rate' => $buy['lever_rate'],
            'buy_id' => $buy['id'],
            'sell_id' => $sell['id'],
            'buy_user_id' => $buy['user_id'],
            'sell_user_id' => $sell['user_id'],
            'unit_price' => $unit_price,
            'trade_amount' => $exchange_amount,
            'trade_buy_fee' => $buy_fee,
            'trade_sell_fee' => $sell_fee,
            'ts' => time(),
        ]);

        // 解冻合约账户保证金 & 扣除平仓手续费
        $log_type = $system == 1 ? 'system_close_position' : 'close_position';
        $log_type2 = $system == 1 ? 'system_close_position_fee' : 'close_position_fee';
        // 买入平空
        $buy_user = User::query()->find($buy['user_id']);
        if(!blank($buy_user)){
            // 空仓
            $empty_position = $position;
            $empty_position->update([
                'hold_position' => $empty_position['hold_position'] - $exchange_amount,
                'avail_position' => $empty_position['avail_position'] - $exchange_amount,
                'position_margin' => $empty_position['position_margin'] - $buy_margin,
            ]);

            $buy_user->update_wallet_and_log($buy['margin_coin_id'],'usable_balance',$buy_margin,UserWallet::sustainable_account,$log_type,'',$buy['contract_id']);
            $buy_user->update_wallet_and_log($buy['margin_coin_id'],'used_balance',-$buy_margin,UserWallet::sustainable_account,$log_type,'',$buy['contract_id']);
            $buy_user->update_wallet_and_log($buy['margin_coin_id'],'usable_balance',-$buy_fee,UserWallet::sustainable_account,$log_type2,'',$buy['contract_id']);
        }

        //更新买卖委托
        $buy->update(['traded_amount' => $buy_traded_amount, 'avg_price'=>$entrust_price, 'profit'=>$empty_position_profit,'status' => ContractEntrust::status_completed]);
        $sell->update(['traded_amount' => $sell_traded_amount, 'status' => ContractEntrust::status_completed]);

        return $buy;
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
         return ['确认' .$this->title. '?'];
    }

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
}
