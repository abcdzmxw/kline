<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/7
 * Time: 16:32
 */

namespace App\Services;
use App\Exceptions\ApiException;
use App\Jobs\HandleEntrust;
use App\Models\ContractOrder;
use App\Models\ContractPosition;
use App\Models\HistoricalCommission;
use App\Models\SustainableAccount;
use App\Models\ContractBuy;
use App\Models\ContractSell;
use App\Models\ContractPair;
use App\Models\Withdraw;
use App\Services\HuobiService\HuobiapiService;
use App\Services\HuobiService\lib\HuobiLibService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
class PerpetualContractService
{
//    public function orderPlacement($user_id,$array)
//    {
//        #下单
//        $order_id=$array['client_order_id'].date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
//        ContractOrder::query()->where(['user_id'=>$user_id])->insert([
//            'user_id'=>$user_id,
//            'type'=>$array['type'],
//            'contract_code'=>$array['contract_code'],
//            'entrust_price'=>$array['entrust_price'],
//            'volume'=>$array['volume'],
//            'direction'=>$array['direction'],
//            'offset'=>$array['offset'],
//            'lever_rate'=>$array['lever_rate'],
//            'client_order_id'=>$order_id,
//            'created_at'=>time(),
//        ]);
////        return api_response()->success("提交成功");
//        #持仓信息
//        SustainableAccount::query()->where(['user_id'=>$user_id,'coin_name'=>$array['coin_name']])->firstOrFail();
//        ContractPosition::query()->insert([
//            'user_id'=>$user_id,
//            'contract_code'=>$array['contract_code'],
//            'client_order_id'=>$order_id,
//            'margin_mode'=>$array['margin_mode'],
//            'liquidation_price'=>$array['liquidation_price'],
//            'position'=>$array['position'],
//            'avail_position'=>$array['avail_position'],
//            'margin'=>$array['margin'],
//            'avg_cost'=>$array['avg_cost'],
//            'settlement_price'=>$array['settlement_price'],
//            'instrument_id'=>$array['instrument_id'],
//            'leverage'=>$array['leverage'],
//            'realized_pnl'=>$array['realized_pnl'],
//            'side'=>$array['side'],
//            'timestamp'=>$array['timestamp'],
//            'maintenance_margin'=>$array['maintenance_margin'],
//            'settled_pnl'=>$array['settled_pnl'],
//            'last'=>$array['last'],
//            'unrealized_pnl'=>$array['unrealized_pnl'],
//        ]);
//        #历史委托
//
//        HistoricalCommission::query()->insert([
//            'client_order_id'=>$array['client_order_id'],
//            'symbol'=>$array['symbol'],
//            'contract_code'=>$array['contract_code'],
//            'lever_rate'=>$array['lever_rate'],
//            'direction'=>$array['direction'],
//            'offset'=>$array['offset'],
//            'volume'=>$array['volume'],
//            'price'=>$array['price'],
//            'profit'=>$array['profit'],
//            'trade_volume'=>$array['offset'],
//            'fee'=>$array['fee'],
//            'trade_avg_price'=>$array['trade_avg_price'],
//            'order_type'=>$array['order_type'],
//            'status'=>$array['status'],
//            'liquidation_type'=>$array['liquidation_type'],
//            'create_date'=>$array['create_date'],
//
//        ]);
//        return dd($array,$user_id);
//
//    }
    #买入做多
    public function buyLong($user,$array)
    {
        DB::beginTransaction();
        try{
        if($array['type']==2)
        {
            $coin_name=$array['coin_name'];
            $order_id=$array['client_order_id'].date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            #合约里面的余额
            $money=SustainableAccount::query()->where(['user_id'=>$user['user_id'],'coin_name'=>$coin_name])->firstOrFail();
            #开仓数量
            $opening_quantity=$money['usable_balance'];
            $exchange_coin_id=$money['coin_id'];
//            $currency = strtolower($array['coin_name']."UsdT");
//            $entrust_price = Cache::store('redis')->get('market:' . "$currency" . '_detail')['close'];
            $number=($array['entrust_price']*$array['volume'])/$array['lever_rate'];
            $fee=$number*0.002;
            $number_fee=$number+$fee;
            if($opening_quantity<$number_fee)
            {
                return api_response()->error(200,"开仓数大于可开仓数");
            }
            $usable_balance=$opening_quantity-$number_fee;
            $result1=ContractBuy::query()->insert([
                'user_id'=>$user['user_id'],
                'type'=>$array['type'],
                'contract_code'=>$array['contract_code'],
                'entrust_price'=>$array['entrust_price'],
                'exchange_coin_id'=>$exchange_coin_id,
                'volume'=>$array['volume'],
                'direction'=>$array['direction'],
                'offset'=>$array['offset'],
                'lever_rate'=>$array['lever_rate'],
                'client_order_id'=>$order_id,
                'money'=>$number,
            ]);

            $result2=SustainableAccount::query()->where(['user_id'=>$user['user_id'],'coin_name'=>$coin_name])->update([
                'usable_balance'=>$usable_balance,
                'freeze_balance'=>$number,
            ]);
            $res=ContractPosition::query()->where(['user_id'=>$user['user_id'],'side'=>$array['direction'],'status'=>1])->first();
            if (blank($res)) {
                $position=0;
                $avail_position=0;
                $avg_cost=0;
                $margin=0;
                $fee_number=0;
            }else
            {
                $position=$res['position'];
                $avail_position=$res['avail_position'];
                $avg_cost=$res['avg_cost'];
                $margin=$res['margin'];
                $fee_number=$res['fee'];
                ContractPosition::query()->where(['user_id'=>$user['user_id'],'side'=>$array['direction']])->update([
                    'status'=>3
                ]);
            }
            $positions=$position+$array['volume'];
            $avail_positions=$avail_position+$array['volume'];
            $avg_costs=($avg_cost*$array['volume']+$array['entrust_price']*$array['volume'])/($position+$array['volume']);
            $margins=$number+$margin;
            $fees=$fee_number+$fee;
            $result3=ContractPosition::query()->insert([
                'user_id'=>$user['user_id'],
                'contract_code'=>$array['contract_code'],
                'client_order_id'=>$user['client_order_id'],
                'margin_mode'=>'fixed',
                'position'=>$positions,
                'avail_position'=>$avail_positions,
                'margin'=>$margins,
                'avg_cost'=>$avg_costs,
                'leverage'=>$array['lever_rate'],
                'side'=>$array['direction'],
                'fee'=>$fees,
                'status'=>1,
            ]);
        }else{
        $coin_name=$array['coin_name'];
        $order_id=$array['client_order_id'].date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        #合约里面的余额
        $money=SustainableAccount::query()->where(['user_id'=>$user['user_id'],'coin_name'=>$coin_name])->firstOrFail();
        #开仓数量
        $opening_quantity=$money['usable_balance'];
        $exchange_coin_id=$money['coin_id'];
        $number=($array['entrust_price']*$array['volume'])/$array['lever_rate'];
        if($opening_quantity<$number)
        {
            return api_response()->error(200,"开仓数大于可开仓数");
        }
        $usable_balance=$opening_quantity-$number;
        $result1=ContractBuy::query()->insert([
            'user_id'=>$user['user_id'],
            'type'=>$array['type'],
            'contract_code'=>$array['contract_code'],
            'entrust_price'=>$array['entrust_price'],
            'exchange_coin_id'=>$exchange_coin_id,
            'volume'=>$array['volume'],
            'direction'=>$array['direction'],
            'offset'=>$array['offset'],
            'lever_rate'=>$array['lever_rate'],
            'client_order_id'=>$order_id,
            'money'=>$number,
        ]);

        $result2=SustainableAccount::query()->where(['user_id'=>$user['user_id'],'coin_name'=>$coin_name])->update([
            'usable_balance'=>$usable_balance,
            'freeze_balance'=>$number,
        ]);
        }
        if($result1&&$result2&&$result3)
        {
            DB::commit();
            return api_response()->success("下单成功");
        }
        }catch (\Exception $e){
            DB::rollBack();
            return api_response()->error(200,"下单失败");
//            return $this->error(0,$e->getMessage(),$e);
        }

    }
    #卖出做空
    public function sellShort($user,$array)
    {
        DB::beginTransaction();
        try{
            if($array['type']==2)
            {
                $coin_name=$array['coin_name'];
                $order_id=$array['client_order_id'].date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                #合约里面的余额
                $money=SustainableAccount::query()->where(['user_id'=>$user['user_id'],'coin_name'=>$coin_name])->firstOrFail();
                #开仓数量
                $opening_quantity=$money['usable_balance'];
                $exchange_coin_id=$money['coin_id'];
//            $currency = strtolower($array['coin_name']."UsdT");
//            $entrust_price = Cache::store('redis')->get('market:' . "$currency" . '_detail')['close'];
                $number=($array['entrust_price']*$array['volume'])/$array['lever_rate'];
                $fee=$number*0.002;
                $number_fee=$number+$fee;
                if($opening_quantity<$number_fee)
                {
                    return api_response()->error(200,"开仓数大于可开仓数");
                }
                $usable_balance=$opening_quantity-$number_fee;
                $result1=ContractBuy::query()->insert([
                    'user_id'=>$user['user_id'],
                    'type'=>$array['type'],
                    'contract_code'=>$array['contract_code'],
                    'entrust_price'=>$array['entrust_price'],
                    'exchange_coin_id'=>$exchange_coin_id,
                    'volume'=>$array['volume'],
                    'direction'=>$array['direction'],
                    'offset'=>$array['offset'],
                    'lever_rate'=>$array['lever_rate'],
                    'client_order_id'=>$order_id,
                    'money'=>$number,
                ]);

                $result2=SustainableAccount::query()->where(['user_id'=>$user['user_id'],'coin_name'=>$coin_name])->update([
                    'usable_balance'=>$usable_balance,
                    'freeze_balance'=>$number,
                ]);
                $res=ContractPosition::query()->where(['user_id'=>$user['user_id'],'side'=>$array['direction'],'status'=>1])->first();
                if (blank($res)) {
                    $position=0;
                    $avail_position=0;
                    $avg_cost=0;
                    $margin=0;
                    $fee_number=0;
                }else
                {
                    $position=$res['position'];
                    $avail_position=$res['avail_position'];
                    $avg_cost=$res['avg_cost'];
                    $margin=$res['margin'];
                    $fee_number=$res['fee'];
                    ContractPosition::query()->where(['user_id'=>$user['user_id'],'side'=>$array['direction']])->update([
                        'status'=>3
                    ]);
                }
                $positions=$position+$array['volume'];
                $avail_positions=$avail_position+$array['volume'];
                $avg_costs=($avg_cost*$array['volume']+$array['entrust_price']*$array['volume'])/($position+$array['volume']);
                $margins=$number+$margin;
                $fees=$fee_number+$fee;
                $result3=ContractPosition::query()->insert([
                    'user_id'=>$user['user_id'],
                    'contract_code'=>$array['contract_code'],
                    'client_order_id'=>$user['client_order_id'],
                    'margin_mode'=>'fixed',
                    'position'=>$positions,
                    'avail_position'=>$avail_positions,
                    'margin'=>$margins,
                    'avg_cost'=>$avg_costs,
                    'leverage'=>$array['lever_rate'],
                    'side'=>$array['direction'],
                    'fee'=>$fees,
                    'status'=>1,
                ]);
            }else {
                $coin_name = $array['coin_name'];
                $order_id = $array['client_order_id'] . date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                #合约里面的余额
                $money = SustainableAccount::query()->where(['user_id' => $user['user_id'], 'coin_name' => $coin_name])->firstOrFail();
                #开仓数量
                $opening_quantity = $money['usable_balance'];
                $exchange_coin_id = $money['coin_id'];
                #开仓的价值
                $number = ($array['entrust_price'] * $array['volume']) / $array['lever_rate'];
                if ($opening_quantity < $number) {
                    return api_response()->error(200, "开仓数大于可开仓数");
                }
                $usable_balance = $opening_quantity - $number;
                $result1 = ContractSell::query()->insert([
                    'user_id' => $user['user_id'],
                    'type' => $array['type'],
                    'contract_code' => $array['contract_code'],
                    'entrust_price' => $array['entrust_price'],
                    'exchange_coin_id' => $exchange_coin_id,
                    'volume' => $array['volume'],
                    'direction' => $array['direction'],
                    'offset' => $array['offset'],
                    'lever_rate' => $array['lever_rate'],
                    'client_order_id' => $order_id,
                    'money' => $number,
                ]);
                $result2 = SustainableAccount::query()->where(['user_id' => $user['user_id'], 'coin_name' => $coin_name])->update([
                    'usable_balance' => $usable_balance,
                    'freeze_balance' => $number,
                ]);
            }
            if($result1&&$result2&&$result3)
            {
                DB::commit();
            return api_response()->success("下单成功");
            }
        }catch (\Exception $e){
            DB::rollBack();
            return api_response()->error(200,"下单失败");
        }

    }

    #当前合约委托
    public function currentCommission($user,$params)
    {

        $buyBuilder = ContractBuy::query()
            ->where('user_id',$user['user_id'])
            ->whereIn('status',[ContractBuy::status_wait,ContractBuy::status_trading]);
        $sellBuilder = ContractSell::query()
            ->where('user_id',$user['user_id'])
            ->whereIn('status',[ContractSell::status_wait,ContractSell::status_trading]);

        if(isset($params['contract_code'])){
            $buyBuilder->where('contract_code',$params['contract_code']);
            $sellBuilder->where('contract_code',$params['contract_code']);
        }
        if(isset($params['type'])){
            $buyBuilder->where('type',$params['type']);
            $sellBuilder->where('type',$params['type']);
        }

        if(isset($params['direction'])){
            if($params['direction'] == 'buy'){
                return $buyBuilder->orderByDesc('created_at')->paginate();
            }else{
                return $sellBuilder->orderByDesc('created_at')->paginate();
            }
        }

        return $sellBuilder->union($buyBuilder)->orderByDesc('created_at')->paginate();

    }
    public function getCurrentBalance($user_id,$coin_name)
    {
        $data=SustainableAccount::query()->where(['user_id' => $user_id ,'coin_name' => $coin_name])->first();
        $return_data=[];
        $return_data['coin_name']=$data['coin_name'];
        $return_data['margin_name']=$data['margin_name'];
        $return_data['usable_balance']=$data['usable_balance'];
        $return_data['freeze_balance']=$data['freeze_balance'];
        return api_response()->success('SUCCESS',$return_data);
    }

    #持仓信息
    public function contractPosition($user_id,$params)
    {
//        $copo=ContractPosition::query()->first();
        DB::beginTransaction();
        try {
        $many=0;
        $air=0;
        $return_data=[];
        $currency = strtolower($params['coin_name']."UsdT");
        $newest_price = Cache::store('redis')->get('market:' . "$currency" . '_detail')['close'];
//       $userCoPo=UserContractPosition::query()->where(['user_id'=>$user_id,'contract_code'=>$params['contract_code']])->get();
//        foreach ($userCoPo as $userCo)
//        {
//              $may = $userCo['client_order_id']
//        }
//        #空头持仓数量
//        $position=$copo['position'];
//        #结算均价
//        $settlement_price=$newest_price;
//        #开仓均价
//        $avg_cost=$copo['avg_cost'];
//        #杠杆倍数
//        $leverage=$copo['leverage'];
        #开仓手数等于
        #需要展示的信息   1.开仓均价，2.收益，3.预估强平价4.收益率 6.保证金7.持仓量8.保证金率9.可平量10.维持保证金率11.调整杠杆12.平仓
//        #多头持仓数量
//        $position=1000;
//        #结算均价
//        $settlement_price=40;
//        #开仓均价
//        $avg_cost=10;
//        #杠杆倍数
//        $leverage=10;
        $data=ContractPosition::query()->where(['user_id'=>$user_id,'contract_code'=>$params['contract_code'],'side'=>$params['direction'],'status'=>1])->firstOrFail();
        #开仓均价
        $avg_cost=$data['avg_cost'];
        #持仓信息
        $contract_code=$data['contract_code'];
        #持仓量 可平数量
        $position=$data['position'];
        #保证金
        $margin=$data['margin']-$data['fee'];
        #收益美金 $settlement_price代表的是当前价格  代表当前BTC价格 $btc_price
        $income=$position*($newest_price-$avg_cost);
        #调整杠杆
        $leverage=$data['leverage'];
        #收益率
        $yield=$income/($position*$avg_cost);
        $yield_rate=$yield*$leverage;

        $yield_rates=round($yield_rate*100,2).'%';
//        dd($yield_rates);
        #仓位模式  '仓位模式：全仓crossed   逐仓 fixed'
        $margin_mode=$data['margin_mode'];
        if($margin_mode=='fixed')
        {
            $maintenance_margin=$margin/($avg_cost*$position)*100;
            $margin_rate=round($maintenance_margin,2)."%";
            $maintenance_percentage=round($maintenance_margin-1,2).'%';
            #强平价格
            $liquidation_price=$avg_cost-($avg_cost*$maintenance_margin/100+$avg_cost*1/100);
            $return_data['leverage']=$leverage;
            $return_data['position']=$position;
            $return_data['liquidation_price']=$liquidation_price;
            $return_data['income']=$income;
            $return_data['margin']=$data['margin'];
            $return_data['maintenance_percentage']=$maintenance_percentage;
            $return_data['yield_rate']=$yield_rates;
            $return_data['contract_code']=$data['contract_code'];

//            return api_response()->success("SUCCESS", $return_data);
//            dd($yield_rates."---".$liquidation_price);
//            dd($income."收益--".$liquidation_price."爆仓价--"."保证金率".$margin_rate."----".$maintenance_percentage."资金维持率--".$yield_rates."--当前收益率"."--保证金".$data['margin']);
            #如果当前价格小于或者等于爆仓价，则被强平  做多
            if($newest_price<=$liquidation_price) {
                $freeze = SustainableAccount::query()->where(['user_id' => $user_id, 'coin_name' => $params['coin_name']])->first();
                $freeze_balance = $freeze['freeze_balance'] - $data['margin'];
                $suAc = SustainableAccount::query()->where(['user_id' => $user_id, 'coin_name' => $params['coin_name']])->update([
                    'freeze_balance' => $freeze_balance,
                ]);
                $hico = HistoricalCommission::query()->insert([
                    'user_id' => $user_id,
//                    'client_order_id'=>$data['client_order_id'],
                    'contract_code' => $params['contract_code'],
                    'lever_rate' => $leverage,
                    'turnover_ratio' => $position,
                    'direction' => $params['direction'],
                    'offset' => 'close',
                    'deal_done' => $position,
                    'volume' => $position,
                    'price' => $liquidation_price,
                    'profit' => $income,
                    'fee' => $data['fee'],
                    'trade_avg_price' => $avg_cost,
                    'order_type' => '3',
                    'status' => '1',
                    'liquidation_type' => '3',
                    'liquidation_price' => $liquidation_price,
                    'create_date' => time(),
                ]);
                $copo = ContractPosition::query()->where(['user_id' => $user_id, 'contract_code' => $contract_code, 'side' => $params['direction']])->update([
                    'status' => 2,
                ]);
            }
        }else
        {
            $crossed=SustainableAccount::query()->where(['user_id'=>$user_id,'coin_name'=>$params['coin_name']])->firstOrFail();
            #全仓维持保证金率
            $maintenance_margin=($crossed['usable_balance']+$margin)/($avg_cost*$position)*100;
            $maintenance_percentage=round($maintenance_margin-1,2)."%";
            #强平价格
            $liquidation_price=$avg_cost-($avg_cost*$maintenance_margin/100);
            if($newest_price<=$liquidation_price)
            {
                $suAc=SustainableAccount::query()->where(['user_id'=>$user_id,'coin_name'=>$params['coin_name']])->update([
                    'usable_balance'=>0,
                    'freeze_balance'=>0,
                ]);
                $hico=HistoricalCommission::query()->insert([
                    'user_id'=>$user_id,
                    'client_order_id'=>$data['client_order_id'],
                    'contract_code'=>$params['contract_code'],
                    'lever_rate'=>$leverage,
                    'turnover_ratio'=>$position,
                    'direction'=>$params['direction'],
                    'offset'=>'close',
                    'deal_done'=>$position,
                    'volume'=>$position,
                    'price'=>$liquidation_price,
                    'profit'=>$income,
                    'fee'=>$data['fee'],
                    'trade_avg_price'=>$avg_cost,
                    'order_type'=>'3',
                    'status'=>'1',
                    'liquidation_type'=>'3',
                    'liquidation_price'=>$liquidation_price,
                    'create_date'=>time(),
                ]);
                $copo=ContractPosition::query()->where(['user_id'=>$user_id,'contract_code'=>$contract_code,'side'=>$params['direction']])->update([
                    'status'=>2,
                ]);

            }

        }
            if($suAc&&$hico&&$copo)
            {
                DB::commit();
                return api_response()->success("SUCCESS", true);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return api_response()->success("SUCCESS", $return_data);
//       return ContractPosition::query()->where(['user_id'=>$user_id,'client_order_id'=>$params['client_order_id'],'contract_code'=>$contract_code])->get();
        }
    }

    #持仓信息
    public function contractPositionSell($user_id,$params)
    {
//        $copo=ContractPosition::query()->first();
        DB::beginTransaction();
        try {
            $many=0;
            $air=0;
            $return_data=[];
            $currency = strtolower($params['coin_name']."UsdT");
            $newest_price = Cache::store('redis')->get('market:' . "$currency" . '_detail')['close'];
//       $userCoPo=UserContractPosition::query()->where(['user_id'=>$user_id,'contract_code'=>$params['contract_code']])->get();
//        foreach ($userCoPo as $userCo)
//        {
//              $may = $userCo['client_order_id']
//        }
//        #空头持仓数量
//        $position=$copo['position'];
//        #结算均价
//        $settlement_price=$newest_price;
//        #开仓均价
//        $avg_cost=$copo['avg_cost'];
//        #杠杆倍数
//        $leverage=$copo['leverage'];
            #开仓手数等于
            #需要展示的信息   1.开仓均价，2.收益，3.预估强平价4.收益率 6.保证金7.持仓量8.保证金率9.可平量10.维持保证金率11.调整杠杆12.平仓
//        #多头持仓数量
//        $position=1000;
//        #结算均价
//        $settlement_price=40;
//        #开仓均价
//        $avg_cost=10;
//        #杠杆倍数
//        $leverage=10;
            $data=ContractPosition::query()->where(['user_id'=>$user_id,'contract_code'=>$params['contract_code'],'side'=>$params['direction'],'status'=>1])->firstOrFail();
            #开仓均价
            $avg_cost=$data['avg_cost'];
            #持仓信息
            $contract_code=$data['contract_code'];
            #持仓量 可平数量
            $position=$data['position'];
            #保证金
            $margin=$data['margin']-$data['fee'];
            #收益美金 $settlement_price代表的是当前价格  代表当前BTC价格 $btc_price
            $income=$position*($newest_price-$avg_cost);
            #调整杠杆
            $leverage=$data['leverage'];
            #收益率
            $yield=$income/($position*$avg_cost);
            $yield_rate=$yield*$leverage;

            $yield_rates=round($yield_rate*100,2).'%';
//        dd($yield_rates);
            #仓位模式  '仓位模式：全仓crossed   逐仓 fixed'
            $margin_mode=$data['margin_mode'];
            if($margin_mode=='fixed')
            {
                $maintenance_margin=$margin/($avg_cost*$position)*100;
                $margin_rate=round($maintenance_margin,2)."%";
                $maintenance_percentage=round($maintenance_margin-1,2).'%';
                #强平价格
                $liquidation_price=$avg_cost-($avg_cost*$maintenance_margin/100+$avg_cost*1/100);
                $return_data['leverage']=$leverage;
                $return_data['position']=$position;
                $return_data['liquidation_price']=$liquidation_price;
                $return_data['income']=$income;
                $return_data['margin']=$data['margin'];
                $return_data['maintenance_percentage']=$maintenance_percentage;
                $return_data['yield_rate']=$yield_rates;
                $return_data['contract_code']=$data['contract_code'];

//            return api_response()->success("SUCCESS", $return_data);
            dd($yield_rates."---".$liquidation_price);
//            dd($income."收益--".$liquidation_price."爆仓价--"."保证金率".$margin_rate."----".$maintenance_percentage."资金维持率--".$yield_rates."--当前收益率"."--保证金".$data['margin']);
                #如果当前价格小于或者等于爆仓价，则被强平  做多
                if($newest_price<=$liquidation_price) {
                    $freeze = SustainableAccount::query()->where(['user_id' => $user_id, 'coin_name' => $params['coin_name']])->first();
                    $freeze_balance = $freeze['freeze_balance'] - $data['margin'];
                    $suAc = SustainableAccount::query()->where(['user_id' => $user_id, 'coin_name' => $params['coin_name']])->update([
                        'freeze_balance' => $freeze_balance,
                    ]);
                    $hico = HistoricalCommission::query()->insert([
                        'user_id' => $user_id,
//                    'client_order_id'=>$data['client_order_id'],
                        'contract_code' => $params['contract_code'],
                        'lever_rate' => $leverage,
                        'turnover_ratio' => $position,
                        'direction' => $params['direction'],
                        'offset' => 'close',
                        'deal_done' => $position,
                        'volume' => $position,
                        'price' => $liquidation_price,
                        'profit' => $income,
                        'fee' => $data['fee'],
                        'trade_avg_price' => $avg_cost,
                        'order_type' => '3',
                        'status' => '1',
                        'liquidation_type' => '3',
                        'liquidation_price' => $liquidation_price,
                        'create_date' => time(),
                    ]);
                    $copo = ContractPosition::query()->where(['user_id' => $user_id, 'contract_code' => $contract_code, 'side' => $params['direction']])->update([
                        'status' => 2,
                    ]);
                }
            }else
            {
                $crossed=SustainableAccount::query()->where(['user_id'=>$user_id,'coin_name'=>$params['coin_name']])->firstOrFail();
                #全仓维持保证金率
                $maintenance_margin=($crossed['usable_balance']+$margin)/($avg_cost*$position)*100;
                $maintenance_percentage=round($maintenance_margin-1,2)."%";
                #强平价格
                $liquidation_price=$avg_cost-($avg_cost*$maintenance_margin/100);
                if($newest_price<=$liquidation_price)
                {
                    $suAc=SustainableAccount::query()->where(['user_id'=>$user_id,'coin_name'=>$params['coin_name']])->update([
                        'usable_balance'=>0,
                        'freeze_balance'=>0,
                    ]);
                    $hico=HistoricalCommission::query()->insert([
                        'user_id'=>$user_id,
                        'client_order_id'=>$data['client_order_id'],
                        'contract_code'=>$params['contract_code'],
                        'lever_rate'=>$leverage,
                        'turnover_ratio'=>$position,
                        'direction'=>$params['direction'],
                        'offset'=>'close',
                        'deal_done'=>$position,
                        'volume'=>$position,
                        'price'=>$liquidation_price,
                        'profit'=>$income,
                        'fee'=>$data['fee'],
                        'trade_avg_price'=>$avg_cost,
                        'order_type'=>'3',
                        'status'=>'1',
                        'liquidation_type'=>'3',
                        'liquidation_price'=>$liquidation_price,
                        'create_date'=>time(),
                    ]);
                    $copo=ContractPosition::query()->where(['user_id'=>$user_id,'contract_code'=>$contract_code,'side'=>$params['direction']])->update([
                        'status'=>2,
                    ]);

                }

            }
            if($suAc&&$hico&&$copo)
            {
                DB::commit();
                return api_response()->success("SUCCESS", true);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return api_response()->success("SUCCESS", $return_data);
//       return ContractPosition::query()->where(['user_id'=>$user_id,'client_order_id'=>$params['client_order_id'],'contract_code'=>$contract_code])->get();
        }
    }


    #平仓
    public function closeOut()
    {

    }

    public function cancelOrder($user_id,$client_order_id,$direction,$status)
    {
//        DB::beginTransaction();
//        try{
//            if($direction=="buy"&&$status==1)
//            {
//                $res= ContractBuy::query()->where(['client_order_id'=>$client_order_id])->first();
//                $money = $res['money'];
//                $coin_name= $res['coin_name'];
//                $result = ContractBuy::query()->where(['client_order_id'=>$client_order_id])->delete();
//            }else if($direction=="sell"&&$status==1)
//            {
//
//                $res= ContractSell::query()->where(['client_order_id'=>$client_order_id])->first();
//                $money = $res['money'];
//                $coin_name= $res['coin_name'];
//                $result = ContractBuy::query()->where(['client_order_id'=>$client_order_id])->delete();
//            }
//            if($result)
//            {
//                SustainableAccount::query()->where(['user_id'=>$user_id,'coin_name'=>$coin_name])->first();
//                $result2=SustainableAccount::query()->where(['user_id'=>$user_id])->update([
//
//                ]);
//            }
//
//            if($result2)
//            {
//                DB::commit();
//                return api_response()->success("撤单成功");
//            }
//        }catch (\Exception $e){
//            DB::rollBack();
//            return api_response()->error(200,"撤单失败");
////            return $this->error(0,$e->getMessage(),$e);
//        }

    }
    #历史委托
    public function historicalCommission($user_id,$contract_code)
    {

        $result=HistoricalCommission::query()->where(['user_id'=>$user_id,'contract_code'=>$contract_code])->paginate();
        return api_response()->success('SUCCESS',$result);

    }
}
