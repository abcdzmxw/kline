<?php

namespace App\Http\Controllers\Appapi\V1;

use App\Models\UserPayment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserPaymentController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = $this->current_user();

        $data[UserPayment::PAY_TYPE_BANK] = UserPayment::query()->where(['user_id'=>$user['user_id'],'pay_type'=>UserPayment::PAY_TYPE_BANK])->first();
        $data[UserPayment::PAY_TYPE_ALIPAY] = UserPayment::query()->where(['user_id'=>$user['user_id'],'pay_type'=>UserPayment::PAY_TYPE_ALIPAY])->first();
        $data[UserPayment::PAY_TYPE_WECHAT] = UserPayment::query()->where(['user_id'=>$user['user_id'],'pay_type'=>UserPayment::PAY_TYPE_WECHAT])->first();

        return $this->successWithData($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'pay_type' => 'required|string|in:bank_card,alipay,wechat',
            'real_name' => 'required|string',
            'card_no' => 'required|string',
            'code_img' => 'string|required_if:pay_type,alipay|required_if:pay_type,wechat',
            'bank_name' => 'string|required_if:pay_type,bank_card',
            'open_bank' => 'string|required_if:pay_type,bank_card',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['pay_type','real_name','card_no','code_img','bank_name','open_bank']);
        $params['user_id'] = $user['user_id'];

        $res = UserPayment::query()->firstOrCreate(['user_id'=>$user['user_id'],'pay_type'=>$params['pay_type']],$params);
        if(!$res){
            return $this->error();
        }
        return $this->successWithData($res);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = $this->current_user();

        $detail = UserPayment::query()->where(['user_id'=>$user['user_id'],'id'=>$id])->firstOrFail();
        return $this->successWithData($detail);
    }

    public function setStatus($id)
    {
        $user = $this->current_user();

        $payment = UserPayment::query()->where(['user_id'=>$user['user_id'],'id'=>$id])->firstOrFail();
        $payment_status = $payment['status'];

        $payment->status = $payment_status === 1 ? 0 : 1;
        $payment->save();

        return $this->successWithData($payment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if ($vr = $this->verifyField($request->all(),[
            'pay_type' => 'required|string|in:bank_card,alipay,wechat',
            'real_name' => 'required|string',
            'card_no' => 'required|string',
            'code_img' => 'string|required_if:pay_type,alipay|required_if:pay_type,wechat',
            'bank_name' => 'string|required_if:pay_type,bank_card',
            'open_bank' => 'string|required_if:pay_type,bank_card',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['pay_type','real_name','card_no','code_img','bank_name','open_bank']);

        $payment = UserPayment::query()->where(['user_id'=>$user['user_id'],'id'=>$id])->firstOrFail();
        $res = $payment->update($params);
        if(!$res){
            return $this->error();
        }
        return $this->successWithData($res);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        //
    }
}
