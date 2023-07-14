<?php

namespace App\Jobs;

use App\Exceptions\ApiException;
use App\Handlers\ContractTool;
use App\Models\ContractEntrust;
use App\Models\ContractOrder;
use App\Models\ContractPair;
use App\Models\ContractPosition;
use App\Models\User;
use App\Models\SustainableAccount;
use App\Models\UserWallet;
use App\Services\ContractService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HandleContractEntrust implements ShouldQueue
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
     * @return void
     */
    public function __construct($entrust)
    {
        $this->entrust = $entrust;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $entrust = $this->entrust;
        if(blank($entrust)) return ;

        $where_data = [
            'contract_id' => $entrust['contract_id'],
            'entrust_price' => $entrust['entrust_price'],
            'user_id' => $entrust['user_id'],
            'order_type' => $entrust['order_type'],
        ];
        if($entrust['side'] == 1){
            if(!$entrust->can_trade()) return ;
            //限价交易 获取可交易卖单 撮单
            //市价交易 吃单
            //获取可交易列表
            $sellList = ContractEntrust::getContractSellList($entrust['type'],$where_data);
            if(!blank($sellList)){
                //有可交易订单 撮单
                DB::beginTransaction();
                try{

                    $this->handleBuyTrade($entrust,$sellList);

                    DB::commit();
                }catch (\Exception $e){
                    DB::rollBack();
                    throw $e;
                }
            }else{
                $flag = false;
                if( $entrust['type'] == 1 || $entrust['type'] == 3 ){
                    // 获取最新一条成交记录 即实时最新价格
                    $trade_detail = Cache::store('redis')->get('swap:' . 'trade_detail_' . $entrust['symbol']);
                    $realtime_price = $trade_detail['price'];
                    if($entrust['entrust_price'] >= $realtime_price) $flag = true;
                }else{
                    $flag = true;
                }
                if($flag){
                    $service = new ContractService();
                    // 价格符合市场条件的 没有对手单时 系统成交
                    if ($entrust['order_type'] == 1 && $entrust['side'] == 1){
                        // 买入开多
                        $service->handleOpenBuyOrder($entrust);
                    }elseif ($entrust['order_type'] == 1 && $entrust['side'] == 2){
                        // 卖出开空
                        $service->handleOpenSellOrder($entrust);
                    }elseif ($entrust['order_type'] == 2 && $entrust['side'] == 1){
                        // 买入平空
                        $service->handleFlatBuyOrder($entrust);
                    }elseif ($entrust['order_type'] == 2 && $entrust['side'] == 2){
                        // 卖出平多
                        $service->handleFlatSellOrder($entrust);
                    }
                }
            }
        }else{
            if(!$entrust->can_trade()) return ;
            //限价交易 获取可交易买单 撮单
            //市价交易 吃单
            //获取可交易列表
            $buyList = ContractEntrust::getContractBuyList($entrust['type'],$where_data);
            if(!blank($buyList)){
                //有可交易订单 撮单
                DB::beginTransaction();
                try{

                    $this->handleSellTrade($entrust,$buyList);

                    DB::commit();
                }catch (\Exception $e){
                    DB::rollBack();
                    throw $e;
                }
            }else{
                $flag = false;
                if( $entrust['type'] == 1 || $entrust['type'] == 3 ){
                    // 获取最新一条成交记录 即实时最新价格
                    $trade_detail = Cache::store('redis')->get('swap:' . 'trade_detail_' . $entrust['symbol']);
                    $realtime_price = $trade_detail['price'];
                    if($entrust['entrust_price'] <= $realtime_price) $flag = true;
                }else{
                    $flag = true;
                }
                if($flag){
                    $service = new ContractService();
                    // 价格符合市场条件的 没有对手单时 系统成交
                    if ($entrust['order_type'] == 1 && $entrust['side'] == 1){
                        // 买入开多
                        $service->handleOpenBuyOrder($entrust);
                    }elseif ($entrust['order_type'] == 1 && $entrust['side'] == 2){
                        // 卖出开空
                        $service->handleOpenSellOrder($entrust);
                    }elseif ($entrust['order_type'] == 2 && $entrust['side'] == 1){
                        // 买入平空
                        $service->handleFlatBuyOrder($entrust);
                    }elseif ($entrust['order_type'] == 2 && $entrust['side'] == 2){
                        // 卖出平多
                        $service->handleFlatSellOrder($entrust);
                    }
                }
            }
        }
    }

    public function handleBuyTrade($entrust,$sellList)
    {
        $pair = ContractPair::query()->find($entrust['contract_id']);  //获取合约列表及配置数据
        if(blank($pair)) return; 
        $unit_amount = $entrust['unit_amount']; // 单张合约面值
        $unit_fee = $entrust['fee'] / $entrust['amount']; // 单张合约手续费
        $entrust_traded_amount = $entrust['traded_amount']; //委托已交易量
        foreach ($sellList as $sell){
            //获取可交易量、可交易额
            $buy_surplus_amount = $entrust['amount'] - $entrust_traded_amount; //剩余张数 计量单位
            $sell_surplus_amount = $sell['amount'] - $sell['traded_amount']; //剩余张数 计量单位
            $exchange_amount = min($buy_surplus_amount,$sell_surplus_amount); // 成交张数
            $entrust_traded_amount += $exchange_amount;
            if($entrust['type'] == 1 || $entrust['type'] == 3){
                //买单限价委托 可与卖单限价委托和市价委托交易
                if($sell['type'] == 1 || $sell['type'] == 3){
                    $unit_price = min($entrust['entrust_price'],$sell['entrust_price']); //成交价
                }else{
                    $unit_price = $entrust['entrust_price']; //成交价
                }
            }else{
                //买单市价委托 只可与卖单限价委托交易
                $unit_price = $sell['entrust_price']; //成交价
            }
            //计算保证金
            $buy_margin = PriceCalculate(($exchange_amount * $unit_amount) ,'/', $entrust['lever_rate'],5);
            $sell_margin = PriceCalculate(($exchange_amount * $unit_amount) ,'/', $sell['lever_rate'],5);
            $buy_fee = PriceCalculate($exchange_amount ,'*', $unit_fee,5);
            $sell_fee = PriceCalculate($exchange_amount ,'*', $unit_fee,5);
            //增加委托成交匹配记录
            ContractOrder::query()->create([
                'contract_id' => $entrust['contract_id'],
                'symbol' => $entrust['symbol'],
                'unit_amount' => $entrust['unit_amount'],
                'order_type' => $entrust['order_type'],
                'lever_rate' => $entrust['lever_rate'],
                'buy_id' => $entrust['id'],
                'sell_id' => $sell['id'],
                'buy_user_id' => $entrust['user_id'],
                'sell_user_id' => $sell['user_id'],
                'unit_price' => $unit_price,
                'trade_amount' => $exchange_amount,
                'trade_buy_fee' => $buy_fee,
                'trade_sell_fee' => $sell_fee,
                'ts' => time(),
            ]);

            if($entrust['order_type'] == 1){ //开仓
                // 更新用户合约账户保证金
                if(!blank($buy_user = User::query()->find($entrust['user_id']))){
                    //买家 委托冻结转为持仓保证金 扣除手续费
                    $buy_user->update_wallet_and_log($entrust['margin_coin_id'],'used_balance',$buy_margin,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    $buy_user->update_wallet_and_log($entrust['margin_coin_id'],'freeze_balance',-$buy_margin,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    $buy_user->update_wallet_and_log($entrust['margin_coin_id'],'freeze_balance',-$buy_fee,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    // 更新用户持仓信息
                    $buy_position = ContractPosition::getPosition(['user_id' => $entrust['user_id'], 'contract_id' => $entrust['contract_id'], 'side' => $entrust['side']]);
                    $buy_position->update([
                        'hold_position' => $buy_position['hold_position'] + $exchange_amount,
                        'avail_position' => $buy_position['avail_position'] + $exchange_amount,
                        'lever_rate' => $entrust['lever_rate'],
                        'unit_amount' => $entrust['unit_amount'],
                        'position_margin' => $buy_position['position_margin'] + $buy_margin,
                        //'fee' => $buy_position['fee'] + $buy_fee,
                        'avg_price' => ($buy_position['avg_price'] * $buy_position['hold_position'] + $unit_price * $exchange_amount) / ($buy_position['hold_position'] + $exchange_amount),
                    ]);
                }
                if(!blank($sell_user = User::query()->find($sell['user_id']))){
                    //卖家
                    $sell_user->update_wallet_and_log($entrust['margin_coin_id'],'used_balance',$sell_margin,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    $sell_user->update_wallet_and_log($entrust['margin_coin_id'],'freeze_balance',-$sell_margin,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    $sell_user->update_wallet_and_log($entrust['margin_coin_id'],'freeze_balance',-$sell_fee,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    // 更新用户持仓信息
                    $sell_position = ContractPosition::getPosition(['user_id' => $sell['user_id'], 'contract_id' => $sell['contract_id'], 'side' => $sell['side']]);
                    $sell_position->update([
                        'hold_position' => $sell_position['hold_position'] + $exchange_amount,
                        'avail_position' => $sell_position['avail_position'] + $exchange_amount,
                        'lever_rate' => $entrust['lever_rate'],
                        'unit_amount' => $entrust['unit_amount'],
                        'position_margin' => $sell_position['position_margin'] + $sell_margin,
                        //'fee' => $sell_position['fee'] + $sell_fee,
                        'avg_price' => ($sell_position['avg_price'] * $sell_position['hold_position'] + $unit_price * $exchange_amount) / ($sell_position['hold_position'] + $exchange_amount),
                    ]);
                }

                // 开仓无盈亏记录
                $buy_profit = null;
                $sell_profit = null;

            }else{  //平仓

                 

                // 更新用户持仓信息
                $buy_position = ContractPosition::getPosition(['user_id' => $entrust['user_id'], 'contract_id' => $entrust['contract_id'], 'side' => $entrust['side'] == 1?2:1]);
                $sell_position = ContractPosition::getPosition(['user_id' => $sell['user_id'], 'contract_id' => $sell['contract_id'], 'side' => $sell['side'] == 1?2:1]);

                // 小白
                //计算保证金（平仓保证金重新计算）
                /*$buy_margin = PriceCalculate(($exchange_amount * $buy_position['avg_price']) ,'/', $entrust['lever_rate'],5);
                $sell_margin = PriceCalculate(($exchange_amount * $sell_position['avg_price']) ,'/', $sell['lever_rate'],5);*/

                $buy_margin = PriceCalculate($buy_position['position_margin'] ,'/', $buy_position['hold_position'],5) * $exchange_amount;
                $sell_margin = PriceCalculate($sell_position['position_margin'] ,'/', $sell_position['hold_position'],5) * $exchange_amount;


                $buy_position->update([
                    'hold_position' => $buy_position['hold_position'] - $exchange_amount,
                    'freeze_position' => $buy_position['freeze_position'] - $exchange_amount,
                    'position_margin' => $buy_position['position_margin'] - $buy_margin,
                ]);
                $sell_position->update([
                    'hold_position' => $sell_position['hold_position'] - $exchange_amount,
                    'freeze_position' => $sell_position['freeze_position'] - $exchange_amount,
                    'position_margin' => $sell_position['position_margin'] - $sell_margin,
                ]);

                // 解冻合约账户保证金 & 扣除平仓手续费
                $log_type = 'close_position';
                $log_type2 = 'close_position_fee';
                if(!blank($buy_user = User::query()->find($entrust['user_id']))){
                    $buy_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',$buy_margin,UserWallet::sustainable_account,$log_type,'',$pair['id']);
                    $buy_user->update_wallet_and_log($pair['margin_coin_id'],'used_balance',-$buy_margin,UserWallet::sustainable_account,$log_type,'',$pair['id']);
                    $buy_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',-$buy_fee,UserWallet::sustainable_account,$log_type2,'',$pair['id']);
                }
                if(!blank($sell_user = User::query()->find($sell['user_id']))){
                    $sell_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',$sell_margin,UserWallet::sustainable_account,$log_type,'',$pair['id']);
                    $sell_user->update_wallet_and_log($pair['margin_coin_id'],'used_balance',-$sell_margin,UserWallet::sustainable_account,$log_type,'',$pair['id']);
                    $sell_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',-$sell_fee,UserWallet::sustainable_account,$log_type2,'',$pair['id']);
                }

                // TODO 合约账户盈亏结算
                /*
                 * 未实现盈亏计算
                 * 未实现盈亏，是用户当前持有的仓位的盈亏，未实现盈亏会随着最新成交价格变动而变化。
                 * 多仓盈亏 =（平仓均价-开仓均价）* 多仓合约张数 * 合约面值/开仓均价
                 * 空仓盈亏 =（开仓均价-平仓均价）* 空仓合约张数 * 合约面值/开仓均价
                 * 例：如用户持有1000张BTC永续合约多仓仓位（合约面值为1USD），持仓均价为5000USD/BTC. 若当前最新价格为8000USD/BTC，则现有的未实现盈亏= ( 8000-5000 ) * 1000*1 /5000 = 600USDT。
                 * */
                $log_type3 = 'close_long_position';
                $log_type4 = 'close_short_position';
                $buy_position_profit = ContractTool::unRealProfit($buy_position,$pair,$unit_price,$exchange_amount);
                $sell_position_profit = ContractTool::unRealProfit($sell_position,$pair,$unit_price,$exchange_amount);
                $buy_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',$buy_position_profit,UserWallet::sustainable_account,$log_type3,'',$pair['id']);
                $sell_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',$sell_position_profit,UserWallet::sustainable_account,$log_type4,'',$pair['id']);

                // 平仓 更新委托盈亏记录
                $buy_profit = blank($entrust['profit']) ? $buy_position_profit : $entrust['profit'] + $buy_position_profit;
                $sell_profit = blank($sell['profit']) ? $sell_position_profit : $sell['profit'] + $sell_position_profit;
            }

            // 更新卖单
            $sell_traded_amount = $sell['traded_amount'] + $exchange_amount;
            $avg_price = blank($sell['avg_price']) ? $unit_price : PriceCalculate(($sell['traded_amount'] * $sell['avg_price'] + $exchange_amount * $unit_price) ,'/', $sell_traded_amount);
            if($sell_traded_amount == $sell['amount']){
                //卖单全部成交
                $sell->update(['traded_amount' => $sell_traded_amount,'avg_price'=>$avg_price, 'profit'=>$sell_profit,'status' => ContractEntrust::status_completed]);
            }else{
                //卖单部分成交
                $sell->update(['traded_amount' => $sell_traded_amount,'avg_price'=>$avg_price, 'profit'=>$sell_profit,'status' => ContractEntrust::status_trading]);
            }

            // 更新买单 买单委托交易完成 退出循环
            $entrust_avg_price = blank($entrust['avg_price']) ? $unit_price : PriceCalculate(($entrust['traded_amount'] * $entrust['avg_price'] + $exchange_amount * $unit_price) ,'/', $entrust_traded_amount);
            if($entrust_traded_amount == $entrust['amount']) {
                $entrust->update(['traded_amount' => $entrust_traded_amount,'avg_price'=>$entrust_avg_price, 'profit'=>$buy_profit,'status' => ContractEntrust::status_completed]);
                break;
            }else{
                $entrust->update(['traded_amount' => $entrust_traded_amount,'avg_price'=>$entrust_avg_price, 'profit'=>$buy_profit,'status' => ContractEntrust::status_trading]);
            }

        }

    }

    public function handleSellTrade($entrust,$buyList)
    {
        $pair = ContractPair::query()->find($entrust['contract_id']);
        if(blank($pair)) return;
        $unit_amount = $entrust['unit_amount']; // 单张合约面值
        $unit_fee = $entrust['fee'] / $entrust['amount']; // 单张合约手续费
        $entrust_traded_amount = $entrust['traded_amount']; //委托已交易量
        foreach ($buyList as $buy){
            //卖单限价委托 可与买单限价委托和市价委托交易 （卖单委托计量单位都是交易量amount）
            //卖单市价委托 只可与买单限价委托交易
            $buy_surplus_amount = $buy['amount'] - $buy['traded_amount']; //剩余张数 计量单位
            $entrust_surplus_amount = $entrust['amount'] - $entrust_traded_amount; //剩余张数 计量单位
            $exchange_amount = min($buy_surplus_amount,$entrust_surplus_amount);
            $entrust_traded_amount += $exchange_amount;
            if($buy['type'] == 1 || $buy['type'] == 3){
                if($entrust['type'] == 1 || $entrust['type'] == 3){
                    //$unit_price = max($entrust['entrust_price'],$tradeBuy['entrust_price']);
                    $unit_price = min($entrust['entrust_price'],$buy['entrust_price']);
                }else{
                    $unit_price = $buy['entrust_price'];
                }
            }else{
                $unit_price = $entrust['entrust_price'];
            }

            $buy_margin = PriceCalculate(($exchange_amount * $unit_amount) ,'/', $buy['lever_rate'],5);
            $sell_margin = PriceCalculate(($exchange_amount * $unit_amount) ,'/', $entrust['lever_rate'],5);
            $buy_fee = PriceCalculate($exchange_amount ,'*', $unit_fee,5);
            $sell_fee = PriceCalculate($exchange_amount ,'*', $unit_fee,5);
            //增加委托成交匹配记录
            ContractOrder::query()->create([
                'contract_id' => $entrust['contract_id'],
                'symbol' => $entrust['symbol'],
                'unit_amount' => $entrust['unit_amount'],
                'order_type' => $entrust['order_type'],
                'lever_rate' => $entrust['lever_rate'],
                'buy_id' => $buy['id'],
                'sell_id' => $entrust['id'],
                'buy_user_id' => $buy['user_id'],
                'sell_user_id' => $entrust['user_id'],
                'unit_price' => $unit_price,
                'trade_amount' => $exchange_amount,
                'trade_buy_fee' => $buy_fee,
                'trade_sell_fee' => $sell_fee,
                'ts' => time(),
            ]);

            if($entrust['order_type'] == 1){
                // 更新用户合约账户保证金
                $buy_user = User::query()->find($buy['user_id']);
                if(!blank($buy_user)){
                    //买家
                    $buy_user->update_wallet_and_log($entrust['margin_coin_id'],'used_balance',$buy_margin,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    $buy_user->update_wallet_and_log($entrust['margin_coin_id'],'freeze_balance',-$buy_margin,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    $buy_user->update_wallet_and_log($entrust['margin_coin_id'],'freeze_balance',-$buy_fee,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                }
                $sell_user = User::query()->find($entrust['user_id']);
                if(!blank($sell_user)){
                    //卖家
                    $sell_user->update_wallet_and_log($entrust['margin_coin_id'],'used_balance',$sell_margin,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    $sell_user->update_wallet_and_log($entrust['margin_coin_id'],'freeze_balance',-$sell_margin,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                    $sell_user->update_wallet_and_log($entrust['margin_coin_id'],'freeze_balance',-$sell_fee,UserWallet::sustainable_account,'contract_deal','',$entrust['contract_id']);
                }

                // 更新用户持仓信息
                $buy_position = ContractPosition::getPosition(['user_id' => $buy['user_id'], 'contract_id' => $buy['contract_id'], 'side' => $buy['side']]);
                $sell_position = ContractPosition::getPosition(['user_id' => $entrust['user_id'], 'contract_id' => $entrust['contract_id'], 'side' => $entrust['side']]);
                $buy_position->update([
                    'hold_position' => $buy_position['hold_position'] + $exchange_amount,
                    'avail_position' => $buy_position['avail_position'] + $exchange_amount,
                    'lever_rate' => $entrust['lever_rate'],
                    'unit_amount' => $entrust['unit_amount'],
                    'position_margin' => $buy_position['position_margin'] + $buy_margin,
                    'avg_price' => ($buy_position['avg_price'] * $buy_position['hold_position'] + $unit_price * $exchange_amount) / ($buy_position['hold_position'] + $exchange_amount),
                ]);
                $sell_position->update([
                    'hold_position' => $sell_position['hold_position'] + $exchange_amount,
                    'avail_position' => $sell_position['avail_position'] + $exchange_amount,
                    'lever_rate' => $entrust['lever_rate'],
                    'unit_amount' => $entrust['unit_amount'],
                    'position_margin' => $sell_position['position_margin'] + $sell_margin,
                    'avg_price' => ($sell_position['avg_price'] * $sell_position['hold_position'] + $unit_price * $exchange_amount) / ($sell_position['hold_position'] + $exchange_amount),
                ]);

                // 开仓
                $buy_profit = null;
                $sell_profit = null;
            }else{
                // 更新用户持仓信息
                $buy_position = ContractPosition::getPosition(['user_id' => $buy['user_id'], 'contract_id' => $buy['contract_id'], 'side' => $buy['side'] == 1?2:1]);
                $sell_position = ContractPosition::getPosition(['user_id' => $entrust['user_id'], 'contract_id' => $entrust['contract_id'], 'side' => $entrust['side']  == 1?2:1]);

                // 小白
                //计算保证金（平仓保证金重新计算）
                // $buy_margin = PriceCalculate(($exchange_amount * $buy_position['avg_price']) ,'/', $entrust['lever_rate'],5);
                // $sell_margin = PriceCalculate(($exchange_amount * $sell_position['avg_price']) ,'/', $sell['lever_rate'],5);
                $buy_margin = PriceCalculate($buy_position['position_margin'] ,'/', $buy_position['hold_position'],5) * $exchange_amount;
                $sell_margin = PriceCalculate($sell_position['position_margin'] ,'/', $sell_position['hold_position'],5) * $exchange_amount;


                $buy_position->update([
                    'hold_position' => $buy_position['hold_position'] - $exchange_amount,
                    'freeze_position' => $buy_position['freeze_position'] - $exchange_amount,
                    'position_margin' => $buy_position['position_margin'] - $buy_margin,
                ]);
                $sell_position->update([
                    'hold_position' => $sell_position['hold_position'] - $exchange_amount,
                    'freeze_position' => $sell_position['freeze_position'] - $exchange_amount,
                    'position_margin' => $sell_position['position_margin'] - $sell_margin,
                ]);

                // 解冻合约账户保证金 & 扣除平仓手续费
                $log_type = 'close_position';
                $log_type2 = 'close_position_fee';
                if(!blank($buy_user = User::query()->find($buy['user_id']))){
                    $buy_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',$buy_margin,UserWallet::sustainable_account,$log_type,'',$pair['id']);
                    $buy_user->update_wallet_and_log($pair['margin_coin_id'],'used_balance',-$buy_margin,UserWallet::sustainable_account,$log_type,'',$pair['id']);
                    $buy_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',-$buy_fee,UserWallet::sustainable_account,$log_type2,'',$pair['id']);
                }
                if(!blank($sell_user = User::query()->find($entrust['user_id']))){
                    $sell_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',$sell_margin,UserWallet::sustainable_account,$log_type,'',$pair['id']);
                    $sell_user->update_wallet_and_log($pair['margin_coin_id'],'used_balance',-$sell_margin,UserWallet::sustainable_account,$log_type,'',$pair['id']);
                    $sell_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',-$sell_fee,UserWallet::sustainable_account,$log_type2,'',$pair['id']);
                }

                // TODO 合约账户盈亏结算
                /*
                 * 未实现盈亏计算
                 * 未实现盈亏，是用户当前持有的仓位的盈亏，未实现盈亏会随着最新成交价格变动而变化。
                 * 多仓盈亏 =（平仓均价-开仓均价）* 多仓合约张数 * 合约面值/开仓均价
                 * 空仓盈亏 =（开仓均价-平仓均价）* 空仓合约张数 * 合约面值/开仓均价
                 * 例：如用户持有1000张BTC永续合约多仓仓位（合约面值为1USD），持仓均价为5000USD/BTC. 若当前最新价格为8000USD/BTC，则现有的未实现盈亏= ( 8000-5000 ) * 1000*1 /5000 = 600USDT。
                 * */
                $log_type3 = 'close_long_position';
                $log_type4 = 'close_short_position';
                $buy_position_profit = ContractTool::unRealProfit($buy_position,$pair,$unit_price,$exchange_amount);
                $sell_position_profit = ContractTool::unRealProfit($sell_position,$pair,$unit_price,$exchange_amount);
                $buy_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',$buy_position_profit,UserWallet::sustainable_account,$log_type3,'',$pair['id']);
                $sell_user->update_wallet_and_log($pair['margin_coin_id'],'usable_balance',$sell_position_profit,UserWallet::sustainable_account,$log_type4,'',$pair['id']);

                // 平仓 更新委托盈亏记录
                $buy_profit = blank($buy['profit']) ? $buy_position_profit : $buy['profit'] + $buy_position_profit;
                $sell_profit = blank($entrust['profit']) ? $sell_position_profit : $entrust['profit'] + $sell_position_profit;

            }

            // 更新买单
            $buy_traded_amount = $buy['traded_amount'] + $exchange_amount;
            $avg_price = blank($buy['avg_price']) ? $unit_price : PriceCalculate(($buy['traded_amount'] * $buy['avg_price'] + $exchange_amount * $unit_price) ,'/', $buy_traded_amount);
            if( $buy_traded_amount == $buy['amount'] ){
                //买单全部成交
                $buy->update(['traded_amount' => $buy_traded_amount,'avg_price'=>$avg_price, 'profit'=>$buy_profit,'status' => ContractEntrust::status_completed]);
            }else{
                //买单部分成交
                $buy->update(['traded_amount' => $buy_traded_amount,'avg_price'=>$avg_price, 'profit'=>$buy_profit,'status' => ContractEntrust::status_trading]);
            }

            //卖单委托交易完成 退出循环
            $entrust_avg_price = blank($entrust['avg_price']) ? $unit_price : PriceCalculate(($entrust['traded_amount'] * $entrust['avg_price'] + $exchange_amount * $unit_price) ,'/', $entrust_traded_amount);
            if($entrust_traded_amount == $entrust['amount']) {
                $entrust->update(['traded_amount' => $entrust_traded_amount,'avg_price'=>$entrust_avg_price, 'profit'=>$sell_profit,'status' => ContractEntrust::status_completed]);
                break;
            }else{
                $entrust->update(['traded_amount' => $entrust_traded_amount,'avg_price'=>$entrust_avg_price, 'profit'=>$sell_profit,'status' => ContractEntrust::status_trading]);
            }

        }

    }

}
