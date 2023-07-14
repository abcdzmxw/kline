<?php

namespace App\Jobs;

use App\Handlers\Kline;
use App\Models\TestTradeBuy;
use App\Models\TestTradeOrder;
use App\Models\TestTradeSell;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class HandleTestEntrust implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            'quote_coin_id' => $entrust['quote_coin_id'],
            'base_coin_id' => $entrust['base_coin_id'],
            'entrust_price' => $entrust['entrust_price'],
        ];
        if($entrust['entrust_type'] == 1){
            if(!$entrust->can_trade()) return ;
            //限价交易 获取可交易卖单 撮单
            //获取可交易列表
            $sellList = TestTradeSell::getSellTradeList($entrust['type'],$where_data);
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
            }
        }else{
            if(!$entrust->can_trade()) return ;
            //限价交易 获取可交易买单 撮单
            //获取可交易列表
            $buyList = TestTradeBuy::getBuyTradeList($entrust['type'],$where_data);
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
            }
        }
    }

    public function handleBuyTrade($entrust,$sellList)
    {
        $entrust_amount = $entrust['amount'];
        $entrust_money = $entrust['money'];
        $entrust_traded_amount = $entrust['traded_amount']; //交易量 单位：exchange_coin
        $entrust_traded_money = $entrust['traded_money'];  //交易额 单位：base_coin
        foreach ($sellList as $sell){
            if( $tradeSell = TestTradeSell::query()->where('order_no',$sell['order_no'])->first() ){
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
                }

                //更新卖单
                $sell_traded_amount = $tradeSell['traded_amount'] + $exchange_amount;
                $sell_traded_money = $tradeSell['traded_money'] + $exchange_money;
                if($sell_traded_amount == $tradeSell['amount']){
                    //卖单全部成交
                    $tradeSell->update([
                        'traded_amount' => $sell_traded_amount,
                        'traded_money' => $sell_traded_money,
                        'status' => TestTradeSell::status_completed,
                    ]);
                }else{
                    //卖单部分成交
                    $tradeSell->update([
                        'traded_amount' => $sell_traded_amount,
                        'traded_money' => $sell_traded_money,
                        'status' => TestTradeSell::status_trading,
                    ]);
                }

                //增加委托成交匹配记录
                $order = TestTradeOrder::query()->create([
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
                    'ts' => time(),
                ]);

                if($order){
                    $unit_price = $order['unit_price'];
                    $periods = ['1min','5min','15min','30min','60min','1day','1week','1mon'];
                    foreach ($periods as $period){
                        (new Kline())->cacheKline($period,$unit_price);
                    }
                }

                //买单委托交易完成 退出循环 更新买单
                if($entrust['type'] == 1 || $entrust['type'] == 3){
                    if($entrust_amount == 0) {
                        $entrust_update_data = [
                            'traded_amount' => $entrust_traded_amount,
                            'traded_money' => $entrust_traded_money,
                            'status' => TestTradeBuy::status_completed,
                        ];

                        $entrust->update($entrust_update_data);

                        break;
                    }else{
                        $entrust->update([
                            'traded_amount' => $entrust_traded_amount,
                            'traded_money' => $entrust_traded_money,
                            'status' => TestTradeBuy::status_trading,
                        ]);
                    }
                }

            }
        }

    }

    public function handleSellTrade($entrust,$buyList)
    {
        $entrust_amount = $entrust['amount'];
        $entrust_traded_amount = $entrust['traded_amount']; //交易量 单位：exchange_coin
        $entrust_traded_money = $entrust['traded_money'];  //交易额 单位：base_coin
        foreach ($buyList as $buy){
            if( $tradeBuy = TestTradeBuy::query()->where('order_no',$buy['order_no'])->first() ){

                //卖单限价委托 可与买单限价委托和市价委托交易 （卖单委托计量单位都是交易量amount）
                //卖单市价委托 只可与买单限价委托交易
                $entrust_surplus_amount = $entrust['amount'] - $entrust_traded_amount; //剩余交易量 计量单位
                if($tradeBuy['type'] == 1 || $tradeBuy['type'] == 3){
                    if($entrust['type'] == 1 || $entrust['type'] == 3){
//                        $unit_price = max($entrust['entrust_price'],$tradeBuy['entrust_price']);
                        $unit_price = min($entrust['entrust_price'],$tradeBuy['entrust_price']);
                    }else{
                        $unit_price = $tradeBuy['entrust_price'];
                    }
                    $exchange_amount = min($entrust_surplus_amount,$tradeBuy['surplus_amount']);
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
                        'status' => TestTradeBuy::status_completed,
                    ]);
                }else{
                    //买单部分成交
                    $tradeBuy->update([
                        'traded_amount' => $buy_traded_amount,
                        'traded_money' => $buy_traded_money,
                        'status' => TestTradeBuy::status_trading,
                    ]);
                }

                //增加委托成交匹配记录
                $order = TestTradeOrder::query()->create([
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
                    'ts' => time(),
                ]);

                if($order){
                    $unit_price = $order['unit_price'];
                    $periods = ['1min','5min','15min','30min','60min','1day','1week','1mon'];
                    foreach ($periods as $period){
                        (new Kline())->cacheKline($period,$unit_price);
                    }
                }

                //卖单委托交易完成 退出循环
                if($entrust_amount == 0) {
                    $entrust->update([
                        'traded_amount' => $entrust_traded_amount,
                        'traded_money' => $entrust_traded_money,
                        'status' => TestTradeSell::status_completed,
                    ]);
                    break;
                }else{
                    $entrust->update([
                        'traded_amount' => $entrust_traded_amount,
                        'traded_money' => $entrust_traded_money,
                        'status' => TestTradeSell::status_trading,
                    ]);
                }

            }
        }

    }

}
