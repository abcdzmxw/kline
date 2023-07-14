<?php


namespace App\Services;


use App\Exceptions\ApiException;
use App\Jobs\HandleEntrust;
use App\Models\InsideTradeBuy;
use App\Models\InsideTradeOrder;
use App\Models\InsideTradePair;
use App\Models\InsideTradeSell;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\UserRestrictedTrading;  // 用户限制交易


class InsideTradeService
{
    public function getCurrentEntrust($user,$params)
    {
        $buyBuilder = InsideTradeBuy::query()
            ->where('user_id',$user['user_id'])
            ->whereIn('status',[InsideTradeBuy::status_wait,InsideTradeBuy::status_trading]);

        $sellBuilder = InsideTradeSell::query()
            ->where('user_id',$user['user_id'])
            ->whereIn('status',[InsideTradeSell::status_wait,InsideTradeSell::status_trading]);

        if(isset($params['symbol'])){
            $buyBuilder->where('symbol',$params['symbol']);
            $sellBuilder->where('symbol',$params['symbol']);
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

    public function getHistoryEntrust($user,$params)
    {
        $buyBuilder = InsideTradeBuy::query()->with('order_details')
            ->where('user_id',$user['user_id'])
            ->whereIn('status',[InsideTradeBuy::status_cancel,InsideTradeBuy::status_completed]);

        $sellBuilder = InsideTradeSell::query()->with('order_details')
            ->where('user_id',$user['user_id'])
            ->whereIn('status',[InsideTradeSell::status_cancel,InsideTradeSell::status_completed]);

        if(isset($params['symbol'])){
            $buyBuilder->where('symbol',$params['symbol']);
            $sellBuilder->where('symbol',$params['symbol']);
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

        $buyList = $buyBuilder->get();
        $sellList = $sellBuilder->get();
        $data = $buyList->merge($sellList)->sortByDesc('created_at');
        //当前页数 默认1
        $page = request()->page ?: 1;
        //每页的条数
        $per_page = request()->per_page ?: 10;
        //计算每页分页的初始位置
        $offset = ($page * $per_page) - $per_page;
        //实例化LengthAwarePaginator类，并传入对应的参数
        $data = new LengthAwarePaginator($data->slice($offset,$per_page)->values(), count($data), $per_page, $page);
        return $data;
    }

    public function getEntrustTradeRecord($user,$params)
    {
        $builder = InsideTradeOrder::query();
        if($params['entrust_type'] == 1){
            $builder->where('buy_id',$params['entrust_id'])->where('buy_user_id',$user['user_id']);
        }else{
            $builder->where('sell_id',$params['entrust_id'])->where('sell_user_id',$user['user_id']);
        }

        return $builder->orderByDesc('created_at')->get();
    }

    public function cancelEntrust($user,$entrust)
    {
        if(!$entrust->can_cancel()) throw new ApiException('当前委托不可撤销');
        DB::beginTransaction();
        try{
            //更新委托
            $res = $entrust->update([
                'status' => 0,
                'cancel_time' => time(),
            ]);

            // 小白
            $symbol_bi = $entrust['entrust_type'] == 1 ? str_after($entrust['symbol'],'/'):str_before($entrust['symbol'],'/');
            // 获取钱包
            $wallet = UserWallet::query()->where(['user_id' => $user->user_id,'coin_name' => $symbol_bi])->first();


            //删除交易列表中的记录
            if($entrust['entrust_type'] == 1){
                //更新用户资产
                $return_money = $entrust['money'] - $entrust['traded_money'];
                

                if($return_money > $wallet['freeze_balance']){
                    $return_money = $wallet['freeze_balance'];
                }
                $user->update_wallet_and_log($entrust['quote_coin_id'],'usable_balance',$return_money,UserWallet::asset_account,'cancel_entrust');
                $user->update_wallet_and_log($entrust['quote_coin_id'],'freeze_balance',-$return_money,UserWallet::asset_account,'cancel_entrust');
            }else{
                //更新用户资产
                $return_money = $entrust['surplus_amount'];
                if($return_money > $wallet['freeze_balance']){
                    $return_money = $wallet['freeze_balance'];
                }
                
                $user->update_wallet_and_log($entrust['base_coin_id'],'usable_balance',$return_money,UserWallet::asset_account,'cancel_entrust');
                $user->update_wallet_and_log($entrust['base_coin_id'],'freeze_balance',-$return_money,UserWallet::asset_account,'cancel_entrust');
            
               
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }
        
        return $res;
    }

    public function batchCancelEntrust($user,$params)
    {
        $buyBuilder = InsideTradeBuy::query()
            ->where('user_id',$user['user_id'])
            ->whereIn('status',[InsideTradeBuy::status_wait,InsideTradeBuy::status_trading]);

        $sellBuilder = InsideTradeSell::query()
            ->where('user_id',$user['user_id'])
            ->whereIn('status',[InsideTradeSell::status_wait,InsideTradeSell::status_trading]);

        if(isset($params['symbol'])){
            $buyBuilder->where('symbol',$params['symbol']);
            $sellBuilder->where('symbol',$params['symbol']);
        }

        $entrusts =  $sellBuilder->union($buyBuilder)->get();
        if(blank($entrusts)) throw new ApiException('暂无委托');

        DB::beginTransaction();
        try{

            foreach ($entrusts as $entrust) {
                $this->cancelEntrust($user,$entrust);
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }

        return api_response()->success('撤单成功');
    }

    public function storeBuyEntrust_copy($user,$params)
    {
        $pair = InsideTradePair::query()->where('pair_name',$params['symbol'])->first();
        if(blank($pair)) throw new ApiException('交易对不存在');
        if( ($can_store = $pair->can_store()) !== true ) throw new ApiException($can_store);

        //基础货币账户
        $wallet = UserWallet::query()->where(['user_id' => $user->user_id,'coin_id' => $pair['quote_coin_id']])->first();
        if(blank($wallet)) throw new ApiException('钱包类型错误');
        $balance = $wallet->usable_balance;

        //1限价交易 买入卖出数量单位都是exchange_coin
        //2市价交易 买入数量单位是交易额base_coin 卖出数量单位是exchange_coin
        //3止盈止损
        if($params['type'] == 1){
            $entrust_price = $params['entrust_price'];
            if($entrust_price <= 0) throw new ApiException('请输入价格');
            $trigger_price = null;
            $amount = $params['amount'];
            $money = PriceCalculate($params['entrust_price'],'*',$params['amount'],6);
            $hang_status = 1;
        }elseif($params['type'] == 2){
            $entrust_price = null;
            $trigger_price = null;
            $amount = null;
            $money = $params['total'];
            $hang_status = 1;
        }else{
            $entrust_price = $params['entrust_price'];
            if($entrust_price <= 0) throw new ApiException('请输入价格');
            $trigger_price = $params['trigger_price'];
            $amount = $params['amount'];
            $money = PriceCalculate($params['entrust_price'],'*',$params['amount'],6);
            $hang_status = 0;
        }
        if($balance < $money) throw new ApiException('余额不足');

        DB::beginTransaction();
        try{

            //创建订单
            $order_data = [
                'user_id' => $user['user_id'],
                'order_no' => get_order_sn('EB'),
                'symbol' => $pair['pair_name'],
                'type' => $params['type'],
                'entrust_price' => $entrust_price,
                'quote_coin_id' => $pair['quote_coin_id'],
                'base_coin_id' => $pair['base_coin_id'],
                'amount' => $amount,
                'money' => $money,
                'hang_status' => $hang_status,
                'trigger_price' => $trigger_price,
            ];
            $entrust = InsideTradeBuy::query()->create($order_data);

            //扣除用户可用资产 冻结
            $user->update_wallet_and_log($wallet['coin_id'],'usable_balance',-$money,UserWallet::asset_account,'store_buy_entrust');
            $user->update_wallet_and_log($wallet['coin_id'],'freeze_balance',$money,UserWallet::asset_account,'store_buy_entrust');

            if($entrust['hang_status'] == 1){
                //添加待处理委托Job
                HandleEntrust::dispatch($entrust)->onQueue('handleEntrust');
            }

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }

        return $entrust;
    }

    // 币币 - 买单
    public function storeBuyEntrust($user,$params)
    {
        $pair = InsideTradePair::query()->where('pair_name',$params['symbol'])->first();
        if(blank($pair)) throw new ApiException('交易对不存在');
        if( ($can_store = $pair->can_store()) !== true ) throw new ApiException($can_store);

        if($params['amount'] <= 0){
            throw new ApiException('交易失败');
        }

        //基础货币账户
        $wallet = UserWallet::query()->where(['user_id' => $user->user_id,'coin_id' => $pair['quote_coin_id']])->first();
        if(blank($wallet)) throw new ApiException('钱包类型错误');
        $balance = $wallet->usable_balance;


        // 判断价格
        // 小白-获取行情
        if($params['type'] == 1){
            $cacheKey = 'market:' . strtolower(str_before($params['symbol'],'/') . str_after($params['symbol'],'/')) . '_newPrice';
            $cacheData = Cache::store('redis')->get($cacheKey);
            if(empty($cacheData['price']) || $cacheData['price'] <=0){
                throw new ApiException('行情通讯失败');
            }
            $hang_price = $cacheData['price'];

            if($hang_price < $params['entrust_price']){
                throw new ApiException('不能高于行情价格');
            }
        }
        
        // 获取买卖出限制 小白
        if($pair['buy_restricted'] == 1){
            throw new ApiException('发行结束，已无申购额度');
        }
        $buy_restricted = UserRestrictedTrading::where(['user_id'=>$user['user_id'],'coin_id'=>$pair['base_coin_id'],'type'=>'1','direction'=>'1','status'=>'1'])->first();
        if($buy_restricted){
            throw new ApiException('发行结束，已无申购额度');
        }


        //1限价交易 买入卖出数量单位都是exchange_coin
        //2市价交易 买入数量单位是交易额base_coin 卖出数量单位是exchange_coin
        //3止盈止损
        if($params['type'] == 1){
            $entrust_price = $params['entrust_price'];
            if($entrust_price <= 0) throw new ApiException('请输入价格');
            $trigger_price = null;
            $amount = $params['amount'];
            $money = PriceCalculate($params['entrust_price'],'*',$params['amount'],6);
            $hang_status = 1;
        }elseif($params['type'] == 2){
            $entrust_price = null;
            $trigger_price = null;
            $amount = null;
            $money = $params['total'];
            $hang_status = 1;
        }else{
            $entrust_price = $params['entrust_price'];
            if($entrust_price <= 0) throw new ApiException('请输入价格');
            $trigger_price = $params['trigger_price'];
            $amount = $params['amount'];
            $money = PriceCalculate($params['entrust_price'],'*',$params['amount'],6);
            $hang_status = 0;
        }
        // 禁止买入AETC/USDT
        if (strtoupper($params['symbol']) == 'AETC/USDT') throw new ApiException('现阶段公开发行尚未完成，个人交易尚未开始');
        if($balance < $money) throw new ApiException('余额不足');

        DB::beginTransaction();
        try{

            //创建订单
            $order_data = [
                'user_id' => $user['user_id'],
                'order_no' => get_order_sn('EB'),
                'symbol' => $pair['pair_name'],
                'type' => $params['type'],
                'entrust_price' => $entrust_price,
                'quote_coin_id' => $pair['quote_coin_id'],
                'base_coin_id' => $pair['base_coin_id'],
                'amount' => $amount,
                'money' => $money,
                'hang_status' => $hang_status,
                'trigger_price' => $trigger_price,
            ];
            $entrust = InsideTradeBuy::query()->create($order_data);

            //扣除用户可用资产 冻结
            $user->update_wallet_and_log($wallet['coin_id'],'usable_balance',-$money,UserWallet::asset_account,'store_buy_entrust');
            $user->update_wallet_and_log($wallet['coin_id'],'freeze_balance',$money,UserWallet::asset_account,'store_buy_entrust');

            if($entrust['hang_status'] == 1){
                //添加待处理委托Job
                HandleEntrust::dispatch($entrust)->onQueue('handleEntrust');
            }

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }

        return $entrust;
    }

    // 币币 - 卖单
    public function storeSellEntrust($user,$params)
    {
        $pair = InsideTradePair::query()->where('pair_name',$params['symbol'])->first();
        if(blank($pair)) throw new ApiException('交易对不存在');
        if( ($can_store = $pair->can_store()) !== true ) throw new ApiException($can_store);

        if($params['amount'] <= 0){
            throw new ApiException('交易失败');
        }

        //卖出货币账户：要交换的货币
        $wallet = UserWallet::query()->where(['user_id' => $user->user_id,'coin_id' => $pair['base_coin_id']])->first();
        if(blank($wallet)) throw new ApiException('钱包类型错误');
        $balance = $wallet->usable_balance;

        // 判断价格
        // 小白-获取行情
        if($params['type'] == 1){
            $cacheKey = 'market:' . strtolower(str_before($params['symbol'],'/') . str_after($params['symbol'],'/')) . '_newPrice';
            $cacheData = Cache::store('redis')->get($cacheKey);
            if(empty($cacheData['price']) || $cacheData['price'] <=0){
                throw new ApiException('行情通讯失败');
            }
            $hang_price = $cacheData['price'];

            if($hang_price > $params['entrust_price']){
                throw new ApiException('不能低于行情价格');
            }
        }

        // 小白
        if($pair['sell_restricted'] == 1){
            throw new ApiException('已进入锁仓期，请等待硬币上市');
        }

        // 获取买卖出限制 小白
        $sell_restricted = UserRestrictedTrading::where(['user_id'=>$user['user_id'],'coin_id'=>$pair['base_coin_id'],'type'=>'1','direction'=>'2','status'=>'1'])->first();
        if($sell_restricted){
            throw new ApiException('已进入锁仓期，请等待硬币上市');
        }

        //1限价交易 买入卖出数量单位都是exchange_coin
        //2市价交易 买入数量单位是交易额base_coin 卖出数量单位是exchange_coin
        if($params['type'] == 1){
            $entrust_price = $params['entrust_price'];
            if($entrust_price <= 0) throw new ApiException('请输入价格');
            $amount = $params['amount'];
            $money = PriceCalculate($params['entrust_price'],'*',$params['amount'],6);
            $trigger_price = null;
            $hang_status = 1;
        }elseif($params['type'] == 2){
            $entrust_price = null;
            $amount = $params['amount'];
            $money = 0;
            $trigger_price = null;
            $hang_status = 1;
        }else{
            $entrust_price = $params['entrust_price'];
            if($entrust_price <= 0) throw new ApiException('请输入价格');
            $amount = $params['amount'];
            $money = PriceCalculate($params['entrust_price'],'*',$params['amount'],6);
            $trigger_price = $params['trigger_price'];
            $hang_status = 0;
        }
        // 禁止卖出AETC/USDT
         if (strtoupper($params['symbol']) == 'AETC/USDT') throw new ApiException('现阶段公开发行尚未完成，个人交易尚未开始');
        if($balance < $amount) throw new ApiException('余额不足');

        DB::beginTransaction();
        try{

            //创建订单
            $order_data = [
                'user_id' => $user['user_id'],
                'order_no' => get_order_sn('ES'),
                'symbol' => $pair['pair_name'],
                'type' => $params['type'],
                'entrust_price' => $entrust_price,
                'quote_coin_id' => $pair['quote_coin_id'],
                'base_coin_id' => $pair['base_coin_id'],
                'amount' => $amount,
                'money' => $money,
                'hang_status' => $hang_status,
                'trigger_price' => $trigger_price,
            ];
            $entrust = InsideTradeSell::query()->create($order_data);

            //扣除用户可用资产 冻结
            $user->update_wallet_and_log($wallet['coin_id'],'usable_balance',-$amount,UserWallet::asset_account,'store_sell_entrust');
            $user->update_wallet_and_log($wallet['coin_id'],'freeze_balance',$amount,UserWallet::asset_account,'store_sell_entrust');

            if($entrust['hang_status'] == 1){
                //添加待处理委托Job
                HandleEntrust::dispatch($entrust)->onQueue('handleEntrust');
            }

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }

        return $entrust;
    }

    public function handleBuyOrder($tradeBuy)
    {
        // 系统账户下单 然后跟当前委托成交
        if(blank($tradeBuy)) return;
        if($tradeBuy['type'] == 1 || $tradeBuy['type'] == 3){
            // 限价单 成交价格取买单委托价
            $entrust_price = $tradeBuy['entrust_price'];
            $cacheKey = 'market:' . strtolower(str_before($tradeBuy['symbol'],'/') . str_after($tradeBuy['symbol'],'/')) . '_newPrice';
            $cacheData = Cache::store('redis')->get($cacheKey);
            if(!blank($cacheData) && $entrust_price > $cacheData['price']){
                $entrust_price = $cacheData['price'];
            }
            $surplus_amount = $tradeBuy['amount'] - $tradeBuy['traded_amount'];
            $surplus_money = $tradeBuy['money'] - $tradeBuy['traded_money'];
        }else{
            // TODO 市价单 成交价格取当前市场实时成交价 或者卖一价
            $cacheKey = 'market:' . strtolower(str_before($tradeBuy['symbol'],'/') . str_after($tradeBuy['symbol'],'/')) . '_newPrice';
            $cacheData = Cache::store('redis')->get($cacheKey);
            if(blank($cacheData)) return ;
            $entrust_price = $cacheData['price'];
            $surplus_money = $tradeBuy['money'] - $tradeBuy['traded_money'];
            $surplus_amount = $surplus_money / $entrust_price;
        }
        if($surplus_amount <= 0) return;

        $user = User::getOneSystemUser(); // 获取随机一个系统账户
        if(blank($user)) return;

        DB::beginTransaction();
        try{
            //创建订单
            $order_data = [
                'user_id' => $user['user_id'],
                'order_no' => get_order_sn('ES'),
                'symbol' => $tradeBuy['symbol'],
                'type' => 1,
                'entrust_price' => $entrust_price,
                'quote_coin_id' => $tradeBuy['quote_coin_id'],
                'base_coin_id' => $tradeBuy['base_coin_id'],
                'amount' => $surplus_amount,
                'money' => $surplus_money,
            ];
            $entrust = InsideTradeSell::query()->create($order_data);

            $this->deal($tradeBuy,$entrust,'buy');

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    public function handleSellOrder($tradeSell)
    {
        // 系统账户下单 然后跟当前委托成交
        if(blank($tradeSell)) return;
        if($tradeSell['type'] == 1 || $tradeSell['type'] == 3){
            $entrust_price = $tradeSell['entrust_price'];
            $cacheKey = 'market:' . strtolower(str_before($tradeSell['symbol'],'/') . str_after($tradeSell['symbol'],'/')) . '_newPrice';
            $cacheData = Cache::store('redis')->get($cacheKey);
            if(!blank($cacheData) && $entrust_price < $cacheData['price']){
                $entrust_price = $cacheData['price'];
            }
            $surplus_amount = $tradeSell['amount'] - $tradeSell['traded_amount'];
            $surplus_money = $tradeSell['money'] - $tradeSell['traded_money'];
        }else{
            $cacheKey = 'market:' . strtolower(str_before($tradeSell['symbol'],'/') . str_after($tradeSell['symbol'],'/')) . '_newPrice';
            $cacheData = Cache::store('redis')->get($cacheKey);
            if(blank($cacheData)) return ;
            $entrust_price = $cacheData['price'];
            $surplus_amount = $tradeSell['amount'] - $tradeSell['traded_amount'];
            $surplus_money = $surplus_amount * $entrust_price;
        }
        if($surplus_amount <= 0) return;

        $user = User::getOneSystemUser(); // 获取随机一个系统账户
        if(blank($user)) return;

        DB::beginTransaction();
        try{
            //创建订单
            $order_data = [
                'user_id' => $user['user_id'],
                'order_no' => get_order_sn('ES'),
                'symbol' => $tradeSell['symbol'],
                'type' => 1,
                'entrust_price' => $entrust_price,
                'quote_coin_id' => $tradeSell['quote_coin_id'],
                'base_coin_id' => $tradeSell['base_coin_id'],
                'amount' => $surplus_amount,
                'money' => $surplus_money,
            ];
            $entrust = InsideTradeBuy::query()->create($order_data);

            $this->deal($entrust,$tradeSell,'sell');

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    public function deal($buy,$sell,$side = 'buy',$deal_amount = null)
    {
        $maker_fee_rate = get_setting_value('maker_fee_rate','exchange',0.002); //挂单手续费比率
        $taker_fee_rate = get_setting_value('taker_fee_rate','exchange',0.002); //吃单手续费比率

        if($side == 'buy'){
            $exchange_amount = $sell['amount'];
            $unit_price = $sell['entrust_price'];
            $exchange_money = PriceCalculate($unit_price,'*',$sell['amount'],6);
            $symbol = $sell['symbol'];

            $buy_fee = PriceCalculate($exchange_amount ,'*', $maker_fee_rate,6);
            $sell_fee = 0;
            $freeze_balance = empty($buy['entrust_price']) ? $exchange_money : PriceCalculate($buy['entrust_price'], '*', $exchange_amount, 6); //冻结金额
            //$sell_fee = PriceCalculate($exchange_money ,'*', $taker_fee_rate,8);

            //买单全部成交
            $buy->update([
                'traded_amount' => $buy['traded_amount'] + $sell['amount'],
                'traded_money' => $buy['traded_money'] + $sell['money'],
                'status' => empty($deal_amount) ? InsideTradeBuy::status_completed : InsideTradeBuy::status_trading,
            ]);
            //卖单全部成交
            $sell->update([
                'traded_amount' => $sell['amount'],
                'traded_money' => $sell['money'],
                'status' => InsideTradeSell::status_completed,
            ]);
        }else{
            $exchange_amount = $buy['amount'];
            $unit_price = $buy['entrust_price'];
            $exchange_money = PriceCalculate($unit_price,'*',$buy['amount'],6);
            $symbol = $buy['symbol'];

            //$buy_fee = PriceCalculate($exchange_amount ,'*', $taker_fee_rate,8);
            $buy_fee = 0;
            $sell_fee = PriceCalculate($exchange_money ,'*', $maker_fee_rate,6);
            $freeze_balance = empty($sell['entrust_price']) ? $exchange_money : PriceCalculate($sell['entrust_price'], '*', $exchange_amount, 6); //冻结金额

            //买单全部成交
            $buy->update([
                'traded_amount' => $buy['amount'],
                'traded_money' => $buy['money'],
                'status' => InsideTradeBuy::status_completed,
            ]);
            //卖单全部成交
            $sell->update([
                'traded_amount' => $sell['traded_amount'] + $buy['amount'],
                'traded_money' => $sell['traded_money'] + $buy['money'],
                'status' => empty($deal_amount) ? InsideTradeBuy::status_completed : InsideTradeBuy::status_trading,
            ]);
        }

        //增加委托成交匹配记录
        InsideTradeOrder::query()->create([
            'buy_order_no' => $buy['order_no'],
            'sell_order_no' => $sell['order_no'],
            'buy_id' => $buy['id'],
            'sell_id' => $sell['id'],
            'buy_user_id' => $buy['user_id'],
            'sell_user_id' => $sell['user_id'],
            'unit_price' => $unit_price,
            'symbol' => $symbol,
            'quote_coin_id' => $buy['quote_coin_id'],
            'base_coin_id' => $buy['base_coin_id'],
            'trade_amount' => $exchange_amount,
            'trade_money' => $exchange_money,
            'trade_buy_fee' => $buy_fee,
            'trade_sell_fee' => $sell_fee,
        ]);

        //更新用户钱包
        $buy_user = User::query()->find($buy['user_id']);
        $sell_user = User::query()->find($sell['user_id']);
        if($side == 'buy' && $buy_user){
            $buy_user->update_wallet_and_log($buy['quote_coin_id'], 'freeze_balance', -$freeze_balance, UserWallet::asset_account, 'entrust_exchange');
            // 返还多余金额     这里不用减(减去手续费:得到什么就扣除什么手续费) 这里是USDT是作为资金
            $buy_user->update_wallet_and_log($buy['quote_coin_id'], 'usable_balance', ($freeze_balance - $exchange_money), UserWallet::asset_account, 'entrust_exchange');
            // $buy_user->update_wallet_and_log($buy['quote_coin_id'],'freeze_balance',-$exchange_money,UserWallet::asset_account,'entrust_exchange');
            $buy_user->update_wallet_and_log($buy['base_coin_id'],'usable_balance',$exchange_amount - $buy_fee,UserWallet::asset_account,'entrust_exchange');
        }elseif($side == 'sell' && $sell_user){
            $sell_user->update_wallet_and_log($sell['quote_coin_id'],'usable_balance',$exchange_money - $sell_fee,UserWallet::asset_account,'entrust_exchange');
            $sell_user->update_wallet_and_log($sell['base_coin_id'],'freeze_balance',-$exchange_amount,UserWallet::asset_account,'entrust_exchange');
        }
    }

}
