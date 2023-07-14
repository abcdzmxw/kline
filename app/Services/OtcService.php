<?php


namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Coins;
use App\Models\OtcAccount;
use App\Models\OtcCoinlist;
use App\Models\OtcEntrust;
use App\Models\OtcOrder;
use App\Models\User;
use App\Models\UserPayment;
use App\Models\UserWallet;
use App\Services\ExchangeRateService\ExchangeRateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OtcService
{
    public function otcTicker()
    {
        return OtcCoinlist::query()->where('status',1)->get();
    }

    public function tradingEntrusts($user,$params)
    {
        $builder = OtcEntrust::query()->with('user')->where(['coin_name'=>$params['virtual_coin'],'side'=>$params['side']])->where('status',1);
       //  $builder = OtcEntrust::query()->with('user')->where(['coin_name'=>'USDT','side'=>'2'])->where('status',1);

       if(!empty($user)){
          $builder->where('user_id','!=',$user['user_id']);
       }

     //   if($params['pay_type'] != 'all'){
     //       $builder->whereRaw('FIND_IN_SET(?,pay_type)',[$params['pay_type']]);
    //    }

        return $builder->paginate();
    }

    public function storeBuyEntrust($user,$params)
    {
        $pair = OtcCoinlist::query()->where('coin_name',$params['virtual_coin'])->first();
        if(empty($pair)) throw new ApiException();
        if( ($can_store = $pair->can_store()) !== true ) throw new ApiException($can_store);
 
        // 检测收款方式
        $payments = json_decode($params['pay_type'],true);
        if(empty($payments)) throw new ApiException('收款方式不能为空');
        foreach ($payments as $payment){
            $is_exist = UserPayment::query()
                ->where('user_id', $user['user_id'])
                ->where('pay_type', $payment)
                ->exists();
            if(!$is_exist) throw new ApiException('未绑定该收款方式');
        }

        $amount = $params['amount'];

        $overtime = $pair['max_register_time'];
        if($overtime == 0){
            $overed_time = 0;
        }else{
            $overed_time = Carbon::now()->addHours(intval($overtime))->toDateTimeString();
        }

        DB::beginTransaction();
        try{

            $entrust = OtcEntrust::query()->create([
                'user_id' => $user['user_id'],
                'side' => $params['side'],
                'order_sn' => get_order_sn('otc'),
                'coin_id' => $pair['coin_id'],
                'coin_name' => $pair['coin_name'],
                'min_num' => $params['min_num'] ?? null,
                'max_num' => $params['max_num'] ?? null,
                'pay_type' => implode(',', $payments),
                'note' => $params['note'] ?? null,
                'publish_time' => time(),
                'price' => $params['price'],
                'amount' => $amount,
                'cur_amount' => $amount,
                'overed_at' => $overed_time,
            ]);

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }

        return $entrust;
    }

    public function storeSellEntrust($user,$params)
    {
        
        $pair = OtcCoinlist::query()->where('coin_name',$params['virtual_coin'])->first();
        if(empty($pair)) throw new ApiException();
        if( ($can_store = $pair->can_store()) !== true ) throw new ApiException($can_store);
       
        // 检测收款方式
        $payments = json_decode($params['pay_type'],true);
        if(empty($payments)) throw new ApiException('收款方式不能为空');
        foreach ($payments as $payment){
            $is_exist = UserPayment::query()
                ->where('user_id', $user['user_id'])
                ->where('pay_type', $payment)
                ->exists();
            if(!$is_exist) throw new ApiException('未绑定该收款方式');
        }

        //法币账户
        $account = OtcAccount::query()->where(['user_id' => $user['user_id'],'coin_name' => $pair['coin_name']])->first();
       
         if(empty($account)) throw new ApiException('账户类型错误-cash');
        
        $balance = $account->usable_balance;
        $amount = $params['amount'];
        if($balance < $amount) throw new ApiException('余额不足');

         $amount = $params['amount'];
        $overtime = $pair['max_register_time'];
        if($overtime == 0){
            $overed_time = 0;
        }else{
            $overed_time = Carbon::now()->addHours(intval($overtime))->toDateTimeString();
        }
      
        DB::beginTransaction();
        try{

            $entrust = OtcEntrust::query()->create([
                'user_id' => $user['user_id'],
                'side' => $params['side'],
                'order_sn' => get_order_sn('otc'),
                'coin_id' => $pair['coin_id'],
                'coin_name' => $pair['coin_name'],
                'min_num' => $params['min_num'] ?? null,
                'max_num' => $params['max_num'] ?? null,
                'pay_type' => implode(',', json_decode($params['pay_type'], true)),
                'note' => $params['note'] ?? null,
                'publish_time' => time(),
                'price' => $params['price'],
                'amount' => $amount,
                'cur_amount' => $amount,
                'overed_at' => $overed_time,
            ]);

            //扣除用户可用资产 冻结
          $user->update_wallet_and_log($account['coin_id'],'usable_balance',-$amount,UserWallet::otc_account,'store_otc_sell_entrust');
          $user->update_wallet_and_log($account['coin_id'],'freeze_balance',$amount,UserWallet::otc_account,'store_otc_sell_entrust');

            DB::commit();
           

        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }
        return $entrust;
    }

    public function storeOrder($user,$params)
    {
        $entrust = OtcEntrust::query()->find($params['entrust_id']);
        if(empty($entrust)) throw new ApiException('委托不存在');
        if($user['user_id'] == $entrust['user_id']) throw new ApiException('不能和自己进行交易');
//        if($entrust['side'] == $params['trans_type']) throw new ApiException('参数错误');

        $trans_type = $entrust['side'] == 1 ? 2 : 1;
        // 判断买卖双方的交易方式
        $pay_type_arr = is_array($entrust['pay_type']) ? $entrust['pay_type'] : explode(',', $entrust['pay_type']);
        if (!in_array($params['pay_type'],$pay_type_arr)) {
            throw new ApiException('支付方式不匹配');
        }

        // 检测收款方式
        $is_exist = UserPayment::query()
            ->where('user_id', $user['user_id'])
            ->where('pay_type', $params['pay_type'])
            ->exists();
        if(!$is_exist) throw new ApiException('未绑定该收款方式');


        $amount = $params['amount'];
        if($entrust['cur_amount'] < $amount) throw new ApiException('下单数量不得大于剩余数量');
        if(!empty($entrust['min_num']) && $entrust['min_num'] != 0 && $entrust['min_num'] > $amount) throw new ApiException('下单数量不能小于最小限量');
        if(!empty($entrust['max_num']) && $entrust['max_num'] != 0 && $entrust['max_num'] < $amount) throw new ApiException('下单数量不能大于最大限量');

        $overtime = get_setting_value('otc_order_overed','otc',15);
        if($overtime == 0){
            $overed_time = 0;
        }else{
            $overed_time = Carbon::now()->addMinutes(intval($overtime))->toDateTimeString();
        }

        DB::beginTransaction();
        try{
            $order_data = [
                'trans_type' => $trans_type,
                'order_sn' => get_order_sn('od'),
                'user_id' => $user['user_id'],
                'other_uid' => $entrust['user_id'],
                'entrust_id' => $entrust['id'],
                'coin_id' => $entrust['coin_id'],
                'coin_name' => $entrust['coin_name'],
                'amount' => $amount,
                'pay_type' => $params['pay_type'],
                'price' => $entrust['price'],
                'money' => $params['amount'] * $entrust['price'],
                'order_time' => time(),
                'status' => OtcOrder::status_wait_pay,
                'overed_at' => $overed_time,
            ];
            $order = OtcOrder::query()->create($order_data);
            info('数量'.$amount);
            if($trans_type == 2){
                $user->update_wallet_and_log($order['coin_id'],'usable_balance',-$amount,UserWallet::otc_account,'store_otc_order');
                $user->update_wallet_and_log($order['coin_id'],'freeze_balance',$amount,UserWallet::otc_account,'store_otc_order');
            }

            $entrust->update([
                'cur_amount' => PriceCalculate($entrust['cur_amount'] ,'-', $amount,6),
                'lock_amount' => PriceCalculate($entrust['lock_amount'] ,'+', $amount,6),
            ]);

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }

        return $order;
    }

    public function myEntrusts($user_id,$params)
    {
        $builder = OtcEntrust::query()->where('user_id',$user_id);

        if(!empty($params['side'])){
            $builder->where('side',$params['side']);
        }
        if(!empty($params['status'])){
            $builder->where('status',$params['status']);
        }

        return $builder->orderByDesc('id')->paginate();
    }

    public function myOrders($user_id,$params)
    {
        $builder = OtcOrder::query();

        if(!empty($params['type'])){
            $type = $params['type'];
            if($type == 2){
                $builder->where(['trans_type' => 1,'user_id' => $user_id]);
            }elseif($type == 1){
                $builder->where(['trans_type' => 2,'user_id' => $user_id]);
            }elseif($type == 3){
                $builder->where(['trans_type' => 2,'other_uid' => $user_id]);
            }else{
                $builder->where(['trans_type' => 1,'other_uid' => $user_id]);
            }
        }
        if(!empty($params['status'])){
            if($params['status'] != 99){
                $builder->where('status',$params['status']);
            }
        }

        return $builder->orderByDesc('id')->paginate();
    }

    public function orderDetail($user_id,$params)
    {
        return OtcOrder::query()->where('id',$params['order_id'])->where(function($q)use($user_id){
            $q->where('user_id',$user_id)->orWhere('other_uid',$user_id);
        })->firstOrFail();
    }

    public function cancelEntrust($user_id,$params)
    {
        $entrust = OtcEntrust::query()->where(['user_id'=>$user_id,'id'=>$params['entrust_id']])->firstOrFail();
        if (! $entrust->canCancel()) {
            throw new ApiException('当前委托不可撤销');
        }

        DB::beginTransaction();
        try {

            $entrust->update(['status' => OtcEntrust::status_canceled]);

            if($entrust['side'] == 2){
                // 退回剩余资金
                $user = User::query()->findOrFail($user_id);
                $user->update_wallet_and_log($entrust['coin_id'],'usable_balance',$entrust['cur_amount'],UserWallet::otc_account,'cancelOtcEntrust');
                $user->update_wallet_and_log($entrust['coin_id'],'freeze_balance',-$entrust['cur_amount'],UserWallet::otc_account,'cancelOtcEntrust');
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $entrust;
    }

    public function cancelOrder($user_id,$params)
    {
        $order = OtcOrder::query()->where(['id'=>$params['order_id']])->firstOrFail();
        if (! $order->canCancel()) {
            throw new ApiException('当前订单不可撤销');
        }
        if($order['user_id'] != $user_id && $order['other_uid'] != $user_id){
            throw new ApiException('非法操作');
        }

        DB::beginTransaction();
        try {

            $order->update(['status' => OtcEntrust::status_canceled]);

            $amount = $order['amount'];
            $entrust = $order->entrust;
            $entrust->update([
                'cur_amount' => PriceCalculate($entrust['cur_amount'] ,'+', $amount,6),
                'lock_amount' => PriceCalculate($entrust['lock_amount'] ,'-', $amount,6),
            ]);

            if($order['trans_type'] == 2){
                $seller = $order->getSeller();
                $seller->update_wallet_and_log($order['coin_id'],'usable_balance',$amount,UserWallet::otc_account,'cancel_otc_order');
                $seller->update_wallet_and_log($order['coin_id'],'freeze_balance',-$amount,UserWallet::otc_account,'cancel_otc_order');
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $order;
    }

    public function confirmPaidOrder($user_id,$params)
    {
        $order = OtcOrder::query()->where('id',$params['order_id'])->firstOrFail();
        if (($checkRes = $order->canConfirmPaid()) !== true) throw new ApiException($checkRes);

        if($order['trans_type'] == 1){
            if($user_id != $order['user_id']) throw new ApiException('非法操作');
        }else{
            if($user_id != $order['other_uid']) throw new ApiException('非法操作');
        }

        DB::beginTransaction();
        try {

            $order->update(['status' => OtcOrder::status_wait_confirm,'paid_img'=>$params['paid_img'],'pay_time' => time()]);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $order;
    }

    public function confirmOrder($user_id,$params)
    {
        $order = OtcOrder::query()->where('id',$params['order_id'])->firstOrFail();
        if (($checkRes = $order->canConfirmOrder()) !== true) throw new ApiException($checkRes);

        if($order['trans_type'] == 1){
            if($user_id != $order['other_uid']) throw new ApiException('非法操作');
        }else{
            if($user_id != $order['user_id']) throw new ApiException('非法操作');
        }

        DB::beginTransaction();
        try {

            // 更新订单
            $order->update(['status' => OtcOrder::status_completed,'deal_time' => time()]);

            // 更新委托
            $entrust = $order->entrust;
            $entrust->update(['lock_amount' => $entrust['lock_amount'] - $order['amount']]);

            //买家入账
            $buyer = $order->getBuyer();
            $buyer->update_wallet_and_log($order['coin_id'],'usable_balance',$order['amount'],UserWallet::otc_account,'confirmOtcOrder');

            // 卖家冻结金额减少
            $seller = $order->getSeller();
            $seller->update_wallet_and_log($order['coin_id'],'freeze_balance',-$order['amount'],UserWallet::otc_account,'confirmOtcOrder');

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $order;
    }
    
     public function confirmCashOrder($user_id,$params)
    {
        $order = OtcOrder::query()->where('id',$params['order_id'])->firstOrFail();
        if (($checkRes = $order->canConfirmOrder()) !== true) throw new ApiException($checkRes);

        if($order['trans_type'] == 1){
            if($user_id != $order['other_uid']) throw new ApiException('非法操作');
        }else{
            if($user_id != $order['user_id']) throw new ApiException('非法操作');
        }

        DB::beginTransaction();
        try {

            // 更新订单
            $order->update(['status' => OtcOrder::status_completed,'deal_time' => time()]);

            // 更新委托
            $entrust = $order->entrust;
            $entrust->update(['lock_amount' => $entrust['lock_amount'] - $order['amount']]);

            //买家入账
            $buyer = $order->getBuyer();
            $buyer->update_wallet_and_log($order['coin_id'],'usable_balance',$order['amount'],UserWallet::otc_account,'confirmOtcOrder');

            // 卖家冻结金额减少
            $seller = $order->getSeller();
            $seller->update_wallet_and_log($order['coin_id'],'freeze_balance',-$order['amount'],UserWallet::otc_account,'confirmOtcOrder');

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $order;
    }
    
    
    

    public function notConfirmOrder($user_id,$params)
    {
        $order = OtcOrder::query()->where('id',$params['order_id'])->firstOrFail();
        if (($checkRes = $order->canConfirmOrder()) !== true) throw new ApiException($checkRes);

        if($order['trans_type'] == 1){
            if($user_id != $order['other_uid']) throw new ApiException('非法操作');
        }else{
            if($user_id != $order['user_id']) throw new ApiException('非法操作');
        }

        DB::beginTransaction();
        try {

            // 更新订单
            $order->update(['status' => OtcOrder::status_appealing,'appeal_status'=>OtcOrder::appeal_status_wait,'appeal_time'=>time()]);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $order;
    }

}
