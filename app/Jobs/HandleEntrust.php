<?php

namespace App\Jobs;

use App\Models\InsideTradeBuy;
use App\Models\InsideTradeOrder;
use App\Models\InsideTradeSell;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\InsideTradeService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HandleEntrust implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务可以尝试的最大次数。
     *
     * @var int
     */
    public $tries = 3;

    private $entrust;

    /**
     * Create a new job instance.
     *
     * @param $entrust
     */
    public function __construct($entrust)
    {
        $this->entrust = $entrust;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $entrust = $this->entrust;
        if(blank($entrust)) return ;

        $where_data = [
            'quote_coin_id' => $entrust['quote_coin_id'],
            'base_coin_id' => $entrust['base_coin_id'],
            'entrust_price' => $entrust['entrust_price'],
            'user_id' => $entrust['user_id'],
        ];
        if($entrust['entrust_type'] == 1){
            if(!$entrust->can_trade()) return ;
            //限价交易 获取可交易卖单 撮单
            //市价交易 吃单
            //获取可交易列表
            $sellList = InsideTradeSell::getSellTradeList($entrust['type'],$where_data);
            if(blank($sellList)){
                //卖单盘口 没有可交易订单
                $flag = false;
                if( $entrust['type'] == 1 || $entrust['type'] == 3 ){
                    // 获取最新一条成交记录 即实时最新价格
                    $cacheKey = 'market:' . strtolower(str_before($entrust['symbol'],'/') . str_after($entrust['symbol'],'/')) . '_newPrice';
                    $realtime_price = Cache::store('redis')->get($cacheKey)['price'] ?? null;
                    if($entrust['entrust_price'] >= $realtime_price) $flag = true;
                }else{
                    $flag = true;
                }
                if($flag){
                    (new InsideTradeService())->handleBuyOrder($entrust);
                }
            }else{
                //有可交易订单 撮单
                DB::beginTransaction();
                try{

                    $this->handleBuyTrade($entrust,$sellList);

                    DB::commit();
                }catch (\Exception $e){
                    DB::rollBack();
                    throw $e;
                }
            }
        }else{
            if(!$entrust->can_trade()) return ;
            //限价交易 获取可交易买单 撮单
            //市价交易 吃单
            //获取可交易列表
            $buyList = InsideTradeBuy::getBuyTradeList($entrust['type'],$where_data);
            if(blank($buyList)){
                //卖单盘口 没有可交易订单
                $flag = false;
                if( $entrust['type'] == 1 || $entrust['type'] == 3 ){
                    // 获取最新一条成交记录 即实时最新价格
                    $cacheKey = 'market:' . strtolower(str_before($entrust['symbol'],'/') . str_after($entrust['symbol'],'/')) . '_newPrice';
                    $realtime_price = Cache::store('redis')->get($cacheKey)['price'] ?? null;
                    if($entrust['entrust_price'] <= $realtime_price) $flag = true;
                }else{
                    $flag = true;
                }
                if($flag){
                    (new InsideTradeService())->handleSellOrder($entrust);
                }
            }else{
                //有可交易订单 撮单
                DB::beginTransaction();
                try{

                    $this->handleSellTrade($entrust,$buyList);

                    DB::commit();
                }catch (\Exception $e){
                    DB::rollBack();
                    throw $e;
                }
            }
        }
    }

    public function handleBuyTrade($entrust,$sellList)
    {
        $maker_fee_rate = get_setting_value('maker_fee_rate','exchange',0.002); //交易手续费比率
        $taker_fee_rate = get_setting_value('taker_fee_rate','exchange',0.002); //交易手续费比率
        $entrust_amount = $entrust['amount'];
        $entrust_money = $entrust['money'];
        $entrust_traded_amount = $entrust['traded_amount']; //交易量 单位：exchange_coin
        $entrust_traded_money = $entrust['traded_money'];  //交易额 单位：base_coin
        foreach ($sellList as $tradeSell){
            //获取可交易量、可交易额
            if($entrust['type'] == 1 || $entrust['type'] == 3){
                //买单限价委托 可与卖单限价委托和市价委托交易
                $entrust_surplus_amount = $entrust['amount'] - $entrust_traded_amount; //剩余交易量 计量单位
                $exchange_amount = min($entrust_surplus_amount,$tradeSell['surplus_amount']);
                if($tradeSell['type'] == 1 || $tradeSell['type'] == 3){
                    $unit_price = min($entrust['entrust_price'],$tradeSell['entrust_price']); //成交价
                }else{
                    $unit_price = $entrust['entrust_price']; //成交价
                }
                $exchange_money = $exchange_amount * $unit_price;
                $entrust_amount -= $exchange_amount;
                $entrust_money -= $exchange_money;
                $entrust_traded_amount += $exchange_amount;
                $entrust_traded_money += $exchange_money;
            }else{
                //买单市价委托 只可与卖单限价委托交易
                $entrust_surplus_money = $entrust['money'] - $entrust_traded_money; //剩余交易额 计量单位
                $buy_amount = $entrust_surplus_money / $tradeSell['entrust_price']; //剩余交易量
                $exchange_amount = min($buy_amount,$tradeSell['surplus_amount']);
                $unit_price = $tradeSell['entrust_price']; //成交价
                $exchange_money = $exchange_amount * $unit_price;
                $entrust_amount -= $exchange_amount;
                $entrust_money -= $exchange_money;
                $entrust_traded_amount += $exchange_amount;
                $entrust_traded_money += $exchange_money;
            }

            //更新卖单
            $sell_traded_amount = $tradeSell['traded_amount'] + $exchange_amount;
            $sell_traded_money = $tradeSell['traded_money'] + $exchange_money;
            if($sell_traded_amount == $tradeSell['amount']){
                //卖单全部成交
                $tradeSell->update([
                    'traded_amount' => $sell_traded_amount,
                    'traded_money' => $sell_traded_money,
                    'status' => InsideTradeSell::status_completed,
                ]);
            }else{
                //卖单部分成交
                $tradeSell->update([
                    'traded_amount' => $sell_traded_amount,
                    'traded_money' => $sell_traded_money,
                    'status' => InsideTradeSell::status_trading,
                ]);
            }

            $buy_fee = PriceCalculate($exchange_amount ,'*', $taker_fee_rate,8);
            $sell_fee = PriceCalculate($exchange_money ,'*', $maker_fee_rate,8);
            //增加委托成交匹配记录
            InsideTradeOrder::query()->create([
                'buy_order_no' => $entrust['order_no'],
                'sell_order_no' => $tradeSell['order_no'],
                'buy_id' => $entrust['id'],
                'sell_id' => $tradeSell['id'],
                'buy_user_id' => $entrust['user_id'],
                'sell_user_id' => $tradeSell['user_id'],
                'unit_price' => $unit_price,
                'symbol' => $entrust['symbol'],
                'quote_coin_id' => $entrust['quote_coin_id'],
                'base_coin_id' => $entrust['base_coin_id'],
                'trade_amount' => $exchange_amount,
                'trade_money' => $exchange_money,
                'trade_buy_fee' => $buy_fee,
                'trade_sell_fee' => $sell_fee,
            ]);

            //更新用户钱包
            $buy_user = User::query()->find($entrust['user_id']);
            $sell_user = User::query()->find($tradeSell['user_id']);
            //买家得到base_coin_id 扣除quote_coin_id
            $buy_user->update_wallet_and_log($entrust['quote_coin_id'],'freeze_balance',-$exchange_money,UserWallet::asset_account,'entrust_exchange');
            $buy_user->update_wallet_and_log($entrust['base_coin_id'],'usable_balance',$exchange_amount - $buy_fee,UserWallet::asset_account,'entrust_exchange');
            //卖家得到quote_coin_id 扣除base_coin_id
            $sell_user->update_wallet_and_log($entrust['quote_coin_id'],'usable_balance',$exchange_money - $sell_fee,UserWallet::asset_account,'entrust_exchange');
            $sell_user->update_wallet_and_log($entrust['base_coin_id'],'freeze_balance',-$exchange_amount,UserWallet::asset_account,'entrust_exchange');

            //买单委托交易完成 退出循环 更新买单
            if($entrust['type'] == 1 || $entrust['type'] == 3){
                if($entrust_amount == 0) {
                    $entrust_update_data = [
                        'traded_amount' => $entrust_traded_amount,
                        'traded_money' => $entrust_traded_money,
                        'status' => InsideTradeBuy::status_completed,
                    ];
                    if( ($entrust_surplus_money = $entrust['money'] - $entrust_traded_money) > 0){
                        //买家多余冻结余额返还
                        $buy_user->update_wallet_and_log($entrust['quote_coin_id'],'usable_balance',$entrust_surplus_money,UserWallet::asset_account,'entrust_exchange');
                        $buy_user->update_wallet_and_log($entrust['quote_coin_id'],'freeze_balance',-$entrust_surplus_money,UserWallet::asset_account,'entrust_exchange');
                    }

                    $entrust->update($entrust_update_data);

                    break;
                }else{
                    $entrust->update([
                        'traded_amount' => $entrust_traded_amount,
                        'traded_money' => $entrust_traded_money,
                        'status' => InsideTradeBuy::status_trading,
                    ]);
                }
            }else{
                if($entrust_money == 0) {
                    $entrust->update([
                        'traded_amount' => $entrust_traded_amount,
                        'traded_money' => $entrust_traded_money,
                        'status' => InsideTradeBuy::status_completed,
                    ]);
                    break;
                }else{
                    $entrust->update([
                        'traded_amount' => $entrust_traded_amount,
                        'traded_money' => $entrust_traded_money,
                        'status' => InsideTradeBuy::status_trading,
                    ]);
                }
            }

        }

    }
    
    public function handleSellTrade($entrust,$buyList)
    {
        $maker_fee_rate = get_setting_value('maker_fee_rate','exchange',0.002); //交易手续费比率
        $taker_fee_rate = get_setting_value('taker_fee_rate','exchange',0.002); //交易手续费比率
        $entrust_amount = $entrust['amount'];
        $entrust_traded_amount = $entrust['traded_amount']; //交易量 单位：exchange_coin
        $entrust_traded_money = $entrust['traded_money'];  //交易额 单位：base_coin
        foreach ($buyList as $tradeBuy){
            //卖单限价委托 可与买单限价委托和市价委托交易 （卖单委托计量单位都是交易量amount）
            //卖单市价委托 只可与买单限价委托交易
            $entrust_surplus_amount = $entrust['amount'] - $entrust_traded_amount; //剩余交易量 计量单位
            if($tradeBuy['type'] == 1 || $tradeBuy['type'] == 3){
                if($entrust['type'] == 1 || $entrust['type'] == 3){
                    //$unit_price = max($entrust['entrust_price'],$tradeBuy['entrust_price']);
                    $unit_price = min($entrust['entrust_price'],$tradeBuy['entrust_price']);
                }else{
                    $unit_price = $tradeBuy['entrust_price'];
                }
                $exchange_amount = min($entrust_surplus_amount,$tradeBuy['surplus_amount']);
            }else{
                $unit_price = $entrust['entrust_price'];
                $buy_surplus_amount = $tradeBuy['money'] / $unit_price; //计算买单可交易量
                $exchange_amount = min($entrust_surplus_amount,$buy_surplus_amount);
            }
            $exchange_money = $exchange_amount * $unit_price;
            $entrust_amount -= $exchange_amount;
            $entrust_traded_amount += $exchange_amount;
            $entrust_traded_money += $exchange_money;

            $buy_traded_amount = $tradeBuy['traded_amount'] + $exchange_amount;
            $buy_traded_money = $tradeBuy['traded_money'] + $exchange_money;

            if( ($tradeBuy['type'] == 1 && $buy_traded_amount == $tradeBuy['amount']) || ($tradeBuy['type'] == 2 && $buy_traded_money == $tradeBuy['money']) ){
                //买单全部成交
                $tradeBuy->update([
                    'traded_amount' => $buy_traded_amount,
                    'traded_money' => $buy_traded_money,
                    'status' => InsideTradeBuy::status_completed,
                ]);
            }else{
                //买单部分成交
                $tradeBuy->update([
                    'traded_amount' => $buy_traded_amount,
                    'traded_money' => $buy_traded_money,
                    'status' => InsideTradeBuy::status_trading,
                ]);
            }

            $buy_fee = PriceCalculate($exchange_amount ,'*', $maker_fee_rate,8);
            $sell_fee = PriceCalculate($exchange_money ,'*', $taker_fee_rate,8);
            //增加委托成交匹配记录
            InsideTradeOrder::query()->create([
                'buy_order_no' => $tradeBuy['order_no'],
                'sell_order_no' => $entrust['order_no'],
                'buy_id' => $tradeBuy['id'],
                'sell_id' => $entrust['id'],
                'buy_user_id' => $tradeBuy['user_id'],
                'sell_user_id' => $entrust['user_id'],
                'unit_price' => $unit_price,
                'symbol' => $entrust['symbol'],
                'quote_coin_id' => $entrust['quote_coin_id'],
                'base_coin_id' => $entrust['base_coin_id'],
                'trade_amount' => $exchange_amount,
                'trade_money' => $exchange_money,
                'trade_buy_fee' => $buy_fee,
                'trade_sell_fee' => $sell_fee,
            ]);

            //更新用户钱包
            $buy_user = User::query()->find($tradeBuy['user_id']);
            $sell_user = User::query()->find($entrust['user_id']);
            //买家得到base_coin_id 扣除quote_coin_id
            $buy_user->update_wallet_and_log($entrust['quote_coin_id'],'freeze_balance',-$exchange_money,UserWallet::asset_account,'entrust_exchange');
            $buy_user->update_wallet_and_log($entrust['base_coin_id'],'usable_balance',$exchange_amount - $buy_fee,UserWallet::asset_account,'entrust_exchange');
            //卖家得到quote_coin_id 扣除base_coin_id
            $sell_user->update_wallet_and_log($entrust['quote_coin_id'],'usable_balance',$exchange_money - $sell_fee,UserWallet::asset_account,'entrust_exchange');
            $sell_user->update_wallet_and_log($entrust['base_coin_id'],'freeze_balance',-$exchange_amount,UserWallet::asset_account,'entrust_exchange');

            //卖单委托交易完成 退出循环
            if($entrust_amount == 0) {
                $entrust->update([
                    'traded_amount' => $entrust_traded_amount,
                    'traded_money' => $entrust_traded_money,
                    'status' => InsideTradeSell::status_completed,
                ]);
                break;
            }else{
                $entrust->update([
                    'traded_amount' => $entrust_traded_amount,
                    'traded_money' => $entrust_traded_money,
                    'status' => InsideTradeSell::status_trading,
                ]);
            }

        }

    }

}
