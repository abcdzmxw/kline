<?php

namespace App\Http\Controllers\Appapi\V1;

use App\Services\PledgeProductService;
use Illuminate\Http\Request;

class PledgeProductController extends ApiController
{
    protected $service;

    public function __construct(PledgeProductService $pledgeProductService)
    {
        $this->service = $pledgeProductService;
    }

    public function getProductList(Request $request)
    {
        $data = $this->service->getProductList();
        return $this->successWithData($data);
    }

    public function getProduct(Request $request)
    {
        if ($res = $this->verifyField($request->all(), [
            'id' => 'required|integer',
        ])) {
            return $res;
        }

        $data = $this->service->getProduct($request->id);
        return $this->successWithData($data);
    }

    public function buyProduct(Request $request)
    {
        if ($vr = $this->verifyField($request->all(), [
            'id'  => 'required|integer',
            'num' => 'required', //数量
        ])) {
            return $vr;
        }

        $user   = $this->current_user();
        $params = $request->only(['id', 'num']);

        $res = $this->service->buyProduct($user, $params);
        if (!$res) {
            return $this->error();
        }
        return $this->success();
       //  event(new PledgeUpgradeEvent($user));
      //  event(new PledgeUpgradeEvent(PledgeOrder));
    }

    public function getOrderList(Request $request)
    {
        $user = $this->current_user();
        $data = $this->service->getOrderList($user);
        return $this->successWithData($data);
    }

    public function getOrder(Request $request)
    {
        $user = $this->current_user();
        if ($res = $this->verifyField($request->all(), [
            'id' => 'required|integer',
        ])) {
            return $res;
        }

        $data = $this->service->getOrder($user, $request->id);
        return $this->successWithData($data);
    }
}
