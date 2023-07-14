<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/1
 * Time: 10:30
 */

namespace App\Http\Controllers\Api\V1;
use App\Models\Coins;
use App\Models\Payment;
use App\Models\RechargeManual;
use App\Models\TransferRecord;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use App\Models\UserWalletAddress;
use App\Services\UserService;
use App\Services\FlashexchangeService;
use App\Services\UdunWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Admin\AdminSetting;

class FlashexchangeController extends ApiController
{
    //闪兑功能

    protected $FlashexchangeService;

    public function __construct(FlashexchangeService $FlashexchangeService)
    {
        $this->FlashexchangeService = $FlashexchangeService;
    }

    // 获取闪兑币种
    public function currency_list(Request $request)
    {
        $user = $this->current_user();
        $params = $request->only(['account']);

        $data = $this->FlashexchangeService->currency_list($params);
        return $this->successWithData($data);
    }

    // 获取币种余额
    public function getBalance(Request $request)
    {
        $user = $this->current_user();
        $params = $request->all();

        $data = $this->FlashexchangeService->getBalance($user,$params);
        return $this->successWithData($data);
    }

    // 获取汇率
    public function exchange_rate(Request $request)
    {
        $params = $request->all();

        $data = $this->FlashexchangeService->exchange_rate($params);
        return $this->successWithData($data);
    }

    // 开始闪兑
    public function flicker(Request $request)
    {
        $user = $this->current_user();
        $params = $request->all();

        $data = $this->FlashexchangeService->flicker($user,$params);
        return $data;
    }

    // 获取闪兑列表
    public function flicker_list(Request $request)
    {
        $user = $this->current_user();
        $params = $request->all();
        return $this->FlashexchangeService->flicker_list($user,$params);
    }
}
