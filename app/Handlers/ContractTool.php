<?php


namespace App\Handlers;


use App\Models\ContractPair;
use App\Models\ContractPosition;
use Illuminate\Support\Facades\Cache;

class ContractTool
{
    // 合约工具类

    /**
     * 计算持仓未实现盈亏
     * 多仓未实现盈亏 =（1/持仓均价-1/最新成交价）* 多仓合约张数 * 合约面值
     * 空仓未实现盈亏 =（1/最新成交价-1/持仓均价）* 空仓合约张数 * 合约面值
     * @param $position     //  仓位
     * @param $contract     //  合约
     * @param $flat_price   //  平仓价格(最新价)
     * @param $amount       //  平仓数量
     * @return float|int
     */
    public static function unRealProfit($position,$contract,$flat_price,$amount=null)
    {
        if(blank($flat_price)) return 0;

        $avg_price = $position['avg_price']; // 开仓均价

        if(blank($amount)) $amount = $position['hold_position']; //持仓数量
        if($position['side'] == 1){ //多
            //盈亏=(最新价-开仓平均价)*持仓
            $profit = $amount == 0 ? 0 : ($flat_price-$avg_price)*$amount;
            //盈亏=(最新价-开仓均价)*持仓数量*(永续合约交易对的合约单张面值)/开仓均价
            //$profit = $amount == 0 ? 0 : ($flat_price - $avg_price) * $amount * ($contract['unit_amount'] / $avg_price);
        }else{ //空
            //盈亏=(开仓平均价-最新价)*持仓
            $profit = $amount == 0 ? 0 : ($avg_price-$flat_price)*$amount;
            //盈亏=(最新价-开仓均价)*持仓数量*(永续合约交易对的合约单张面值)/开仓均价
            //$profit = $amount == 0 ? 0 : ($avg_price - $flat_price) * $amount * ($contract['unit_amount'] / $avg_price);
        }
        return custom_number_format($profit,5);
    }

    public static function unRealProfit2($position,$contract,$flat_price,$amount=null)
    {
        if(blank($flat_price)) return 0;

        if($position['side'] == 1){
            $spread = $contract['buy_spread'] ?? 0;
            $avg_price = PriceCalculate($position['avg_price'] ,'*', (1 + $spread),8);
        }else{
            $spread = $contract['sell_spread'] ?? 0;
            $avg_price = PriceCalculate($position['avg_price'] ,'*', (1 - $spread),8);
        }
        $settle_spread = $contract['settle_spread'] ?? 0;
//        $avg_price = $position['side'] == 1 ? PriceCalculate($position['avg_price'] ,'+', $spread,8) : PriceCalculate($position['avg_price'] ,'-', $spread,8); // 开仓均价

        if(blank($amount)) $amount = $position['hold_position'];
        if($position['side'] == 1){
            if($flat_price > $avg_price){
                // 盈利 结算滑点
                $flat_price = max($avg_price,PriceCalculate($flat_price,'*',(1-$settle_spread),8));
            }
            $profit = $amount == 0 ? 0 : ($flat_price - $avg_price) * $amount;
            //$profit = $amount == 0 ? 0 : ($flat_price - $avg_price) * $amount * ($contract['unit_amount'] / $avg_price);
        }else{
            if($flat_price < $avg_price){
                // 盈利 结算滑点
                $flat_price = min($avg_price,PriceCalculate($flat_price,'*',(1+$settle_spread),8));
            }
            $profit = $amount == 0 ? 0 : ($avg_price - $flat_price) * $amount;
            //$profit = $amount == 0 ? 0 : ($avg_price - $flat_price) * $amount * ($contract['unit_amount'] / $avg_price);
        }
        return custom_number_format($profit,5);
    }

    // 风险率(爆仓率)
    public static function riskRate($account)
    {
        /**
         * 爆仓率 // (账户可用余额 + 持仓保证金 + 委托冻结保证金 + 未实现盈亏) / (持仓保证金 + 委托冻结保证金)
         */
        return PriceCalculate(($account['usable_balance'] + $account['used_balance'] + $account['freeze_balance'] + $account['totalUnrealProfit']) ,'/', ($account['used_balance'] + $account['freeze_balance']),4);
    }
    // public static function riskRate($account)
    // {
    //     /**
    //      * 爆仓率 // 账户可用余额 + 持仓保证金 + 未实现盈亏 / 持仓保证金
    //      */
    //     return PriceCalculate(($account['usable_balance'] + $account['used_balance'] + $account['totalUnrealProfit']) ,'/', $account['used_balance'],4);
    // }

    /**
     * 计算预估强平价
     * 预估强平价 合约账户风险率=10.0%时的预估价格。此价格仅供参考，实际强平价以发生强平事件时成交的价格为准
     * @param $account       // 合约账户
     * @param $buy_position  // 用户多仓
     * @param $sell_position // 用户空仓
     * @param $contract      // 合约
     * @return string $flatPrice
     */
    public static function flatPrice($account,$buy_position,$sell_position,$contract)
    {
        $flat_risk_rate = get_setting_value('flat_risk_rate','contract',0.1);
        // 平仓时的永续账户权益 = 账户可用余额 + 持仓保证金 + 委托冻结保证金 + 未实现盈亏
        $flat_account_equity = $flat_risk_rate * ($account['used_balance'] + $account['freeze_balance']);
        // 平仓时的账户未实现盈亏
        $unRealProfit = $flat_account_equity - $account['usable_balance'] - $account['used_balance'] - $account['freeze_balance'];
        // 预估强平价 $flat_price =
        // 预估强平价的计算：风险率为10%时，账户权益=500*10%=50USDT,根据未实现盈亏公式算得：预估强平均价=10000-950/（5000/10000）=8100 USDT
        // 求 $flat_price ？
        if($buy_position['hold_position'] == 0 && $sell_position['hold_position'] == 0){
            $flatPrice = '--';
        }elseif ($buy_position['hold_position'] == 0 && $sell_position['hold_position'] > 0){
            $flatPrice = (($sell_position['avg_price'] * $sell_position['hold_position'] * ($contract['unit_amount'] / $sell_position['avg_price'])) - $unRealProfit)
                        / ($sell_position['hold_position'] * ($contract['unit_amount'] / $sell_position['avg_price']));
            $flatPrice = $flatPrice <= 0 ? '--' : custom_number_format($flatPrice,4);
        }elseif ($buy_position['hold_position'] > 0 && $sell_position['hold_position'] == 0){
            $flatPrice = (($buy_position['avg_price'] * $buy_position['hold_position'] * ($contract['unit_amount'] / $buy_position['avg_price'])) + $unRealProfit)
                / ($buy_position['hold_position'] * ($contract['unit_amount'] / $buy_position['avg_price']));
            $flatPrice = $flatPrice <= 0 ? '--' : custom_number_format($flatPrice,4);
        }else{
            $a = $buy_position['avg_price'] * $buy_position['hold_position'] * ($contract['unit_amount'] / $buy_position['avg_price']);
            $b = $sell_position['avg_price'] * $sell_position['hold_position'] * ($contract['unit_amount'] / $sell_position['avg_price']);
            $c = $buy_position['hold_position'] * ($contract['unit_amount'] / $buy_position['avg_price']);
            $d = $sell_position['hold_position'] * ($contract['unit_amount'] / $sell_position['avg_price']);
//            dd($unRealProfit,$a,$b,$c,$d);
            $flatPrice = ($unRealProfit - $b + $a) / ($c - $d);
            $flatPrice = $flatPrice <= 0 ? '--' : custom_number_format($flatPrice,4);
        }
        return $flatPrice;
    }

    // 预估强平价
    public static function getFlatPrice($account,$contract)
    {
        $flat_risk_rate = get_setting_value('flat_risk_rate','contract',0.7);  // 获取配置信息  强平风险率 表里配置0.2

        // 全部其它合约的未实现盈亏（除合约$symbol外）
        $totalUnrealProfit = 0;
        $positions = ContractPosition::query()->where('user_id',$account['user_id'])->where('hold_position','>',0)->get();  // 获取持仓信息
        foreach ($positions as $position){
            if($position['symbol'] == $contract['symbol']) continue;
            $pair = ContractPair::query()->find($position['contract_id']);  // 获取交易对配置
            // 获取最新一条成交记录 即实时最新价格
            $realtime_price = Cache::store('redis')->get('swap:' . 'trade_detail_' . $position['symbol'])['price'] ?? null;
            $unRealProfit = ContractTool::unRealProfit($position,$pair,$realtime_price); //  所有持仓未实现盈亏
            $totalUnrealProfit += $unRealProfit;
        }

        // 获取行情价格
        $hangqing_price = Cache::store('redis')->get('swap:' . 'trade_detail_' . $contract['symbol'])['price'] ?? null;
        if(empty($hangqing_price)){
            $hangqing_price = $contract['unit_amount'];
        }
        // dd($hangqing_price);
        /**
         * abs($unRealProfit + $totalUnrealProfit) = 浮亏  // (浮亏的数量 > 可用保证金 + 持仓保证金 * (1 - $flat_risk_rate)) 时爆仓
         */
        // （可用金额+保证金） * （1- 强平风险率） - 全部盈亏
        $unRealProfit = -($account['usable_balance'] + $account['used_balance'] * (1 - $flat_risk_rate)) - $totalUnrealProfit;

        $buy_position = ContractPosition::getPosition(['user_id'=>$account['user_id'],'contract_id'=>$contract['id'],'side'=>1]);
        $sell_position = ContractPosition::getPosition(['user_id'=>$account['user_id'],'contract_id'=>$contract['id'],'side'=>2]);
        //dd($buy_position->toArray(),$sell_position->toArray(),$unRealProfit);
        // 求 $flat_price ？
        if($buy_position['hold_position'] == 0 && $sell_position['hold_position'] == 0){    // 持仓数量 等于0 预估强平价位 --
            $flatPrice = '--';
        }elseif ($buy_position['hold_position'] == 0 && $sell_position['hold_position'] > 0){  // 持有空仓
            // 计算预估强平价  = （（平均价 * 持仓数量 * （行情价 / 平均价))  - 爆仓金额） / (持仓数量 * (行情价 / 平均价))
            $flatPrice = (($sell_position['avg_price'] * $sell_position['hold_position'] * ($hangqing_price / $sell_position['avg_price'])) - $unRealProfit)
                / ($sell_position['hold_position'] * ($hangqing_price / $sell_position['avg_price']));
            /*$flatPrice = (($sell_position['avg_price'] * $sell_position['hold_position'] * ($contract['unit_amount'] / $sell_position['avg_price'])) - $unRealProfit)
                / ($sell_position['hold_position'] * ($contract['unit_amount'] / $sell_position['avg_price']));*/

            $flatPrice = $flatPrice <= 0 ? '--' : custom_number_format($flatPrice,4);
        }elseif ($buy_position['hold_position'] > 0 && $sell_position['hold_position'] == 0){
            $flatPrice = (($buy_position['avg_price'] * $buy_position['hold_position'] * ($hangqing_price / $buy_position['avg_price'])) + $unRealProfit)
                / ($buy_position['hold_position'] * ($hangqing_price / $buy_position['avg_price']));
            /*$flatPrice = (($buy_position['avg_price'] * $buy_position['hold_position'] * ($contract['unit_amount'] / $buy_position['avg_price'])) + $unRealProfit)
                / ($buy_position['hold_position'] * ($contract['unit_amount'] / $buy_position['avg_price']));*/
            $flatPrice = $flatPrice <= 0 ? '--' : custom_number_format($flatPrice,4);
        }else{
            /*$a = $buy_position['avg_price'] * $buy_position['hold_position'] * ($contract['unit_amount'] / $buy_position['avg_price']);
            $b = $sell_position['avg_price'] * $sell_position['hold_position'] * ($contract['unit_amount'] / $sell_position['avg_price']);
            $c = $buy_position['hold_position'] * ($contract['unit_amount'] / $buy_position['avg_price']);
            $d = $sell_position['hold_position'] * ($contract['unit_amount'] / $sell_position['avg_price']);*/


            $a = $buy_position['avg_price'] * $buy_position['hold_position'] * ($hangqing_price / $buy_position['avg_price']);
            $b = $sell_position['avg_price'] * $sell_position['hold_position'] * ($hangqing_price / $sell_position['avg_price']);
            $c = $buy_position['hold_position'] * ($hangqing_price / $buy_position['avg_price']);
            $d = $sell_position['hold_position'] * ($hangqing_price / $sell_position['avg_price']);
            //  dd($unRealProfit,$a,$b,$c,$d);
            // $flatPrice = ($unRealProfit - $b + $a) / ($c - $d);
            // $flatPrice = $flatPrice <= 0 ? '--' : custom_number_format($flatPrice,4);
            if($c == $d){
                // return $c.'--'.$d;
                $flatPrice = '--';
            }else{
                $flatPrice = ($unRealProfit - $b + $a) / ($c - $d);
                // return $flatPrice;
                $flatPrice = $flatPrice <= 0 ? '--' : custom_number_format($flatPrice,4);
            }
        }

        return $flatPrice;
    }

}
