<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService\ExchangeRateService;
use App\Services\OtcService;
use Illuminate\Http\Request;

class OtcController extends ApiController
{
    // OTC

    protected $service;

    public function __construct(OtcService $service)
    {
        $this->service = $service;
    }

    public function test()
    {

    }
    
    


    // 报价
    public function otcTicker()
    {
        return $this->successWithData($this->service->otcTicker());
    }

    // 获取交易中委托
    public function tradingEntrusts(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'virtual_coin' => 'required',
            'side' => 'required|in:1,2', // 1买 2卖
            'pay_type' => 'required|in:all,bank_card,alipay,wechat',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $data = $this->service->tradingEntrusts($user,$params);
        return $this->successWithData($data);
    }

    /**
     * 发布委托
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ApiException
     */
    public function storeEntrust(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'virtual_coin' => 'required',
            'side' => 'required|in:1,2', // 1买 2卖
            'price' => 'required', //委托价格
            'amount' => 'required|numeric', //委托数量
            'pay_type' => 'required|json', //支付方式
            'note' => '', //备注
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();

        $orderLockKey = 'otc_entrust_lock:' . $user['user_id'];
        if (!$this->setKeyLock($orderLockKey,2)){ //订单锁
            return $this->error();
        }

        if($request->side == 1){
            $res = $this->service->storeBuyEntrust($user,$params);
        }else{
            $res = $this->service->storeSellEntrust($user,$params);
        }
        if(!$res){
            return $this->error(0,'委托失败');
        }
        return $this->success('委托成功');
    }

    // 下单
    public function storeOrder(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'entrust_id' => 'required',
            'trans_type' => 'in:1,2', // 1买 2卖
            'amount' => 'required|numeric', //数量
            'pay_type' => 'required|string|in:bank_card,alipay,wechat', //支付方式
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();

        $orderLockKey = 'otc_order_lock:' . $user['user_id'];
        if (!$this->setKeyLock($orderLockKey,3)){ //订单锁
            return $this->error();
        }

        $res = $this->service->storeOrder($user,$params);
        if(!$res){
            return $this->error(0,'下单失败');
        }
        return $this->success('下单成功',$res);
    }

    // 我发布的广告委托
    public function myEntrusts(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'status' => '',
            'side' => '', // 委托方向 1买2卖
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $data = $this->service->myEntrusts($user['user_id'],$params);
        return $this->successWithData($data);
    }

    // 撤销广告委托
    public function cancelEntrust(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'entrust_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $res = $this->service->cancelEntrust($user['user_id'],$params);
        if(!$res){
            return $this->error();
        }
        return $this->success();
    }

    // 我的订单
    public function myOrders(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'type' => 'required|in:1,2,3,4', // 订单类型 2购买订单 1出售订单 3广告购买订单 4广告出售订单
            'status' => '',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $data = $this->service->myOrders($user['user_id'],$params);
        return $this->successWithData($data);
    }

    // 订单详情
    public function orderDetail(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'order_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $data = $this->service->orderDetail($user['user_id'],$params);
        return $this->successWithData($data);
    }

    // 撤销订单
    public function cancelOrder(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'order_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $res = $this->service->cancelOrder($user['user_id'],$params);
        if(!$res){
            return $this->error();
        }
        return $this->success();
    }

    // 买家确认付款
    public function confirmPaidOrder(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'order_id' => 'required|integer',
            'paid_img' => 'required|string',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $res = $this->service->confirmPaidOrder($user['user_id'],$params);
        if(!$res){
            return $this->error();
        }
        return $this->success();
    }

    // 卖家确认收款，放币
    public function confirmOrder(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'order_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $res = $this->service->confirmOrder($user['user_id'],$params);
        if(!$res){
            return $this->error();
        }
        return $this->success();
    }
    
     // 卖家确认收款，放币
    public function confirmCashOrder(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'order_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $res = $this->service->confirmOrder($user['user_id'],$params);
       // if(!$res){
      //      return $this->error();
     //   }
        return $this->success();
    }

    // 卖家确认未收到款, 状态变更为后台仲裁状态
    public function notConfirmOrder(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'order_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $res = $this->service->notConfirmOrder($user['user_id'],$params);
        if(!$res){
            return $this->error();
        }
        return $this->success();
    }

}
