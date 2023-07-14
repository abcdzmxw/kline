<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/7
 * Time: 16:27
 */

namespace App\Http\Controllers\Api\V1;
use App\Models\SustainableAccount;
use App\Models\UserSubscribe;
use App\Services\PerpetualContractService;
use Illuminate\Http\Request;

class PerpetualContractController extends ApiController
{
    //永续合约

    protected $PerpetualContractService;

    public function __construct(PerpetualContractService $PerpetualContractService)
    {
        $this->PerpetualContractService = $PerpetualContractService;
    }
    #下单
    public function order_placement(Request $request)
    {
        if ($data = $this->verifyField($request->all(),[
            'type' => 'required|integer|in:1,2', //委托类型 1限价交易 2市价交易
            'contract_code' => 'required|string', //合约代码取值范围BTC-USD
            'entrust_price' => 'required_if:type,1|numeric', //委托价格
            'direction' => 'required|string|in:buy,sell', //买卖方向    buy":买,"sell":卖
            'offset' => 'required|String|in:open,close', //开仓平仓     open",开,"close"平
            'volume' => 'required|numeric', //委托数量（张）
            'lever_rate' => 'required|numeric', //杠杆倍数
            'client_order_id' => 'required|String', //客户端订单必须保持唯一
            'coin_name' => 'required|String', //  币种名称

        ])) return $data;

        $array=$request->only(['type','contract_code','entrust_price','direction','offset','volume','lever_rate','client_order_id','exchange_coin_id','coin_name']);
        $user = $this->current_user();
        $orderLockKey = 'inside_entrust_lock:' . $user['user_id'];
        if (!$this->setKeyLock($orderLockKey,3)){ //订单锁
            return $this->error();
        }

        if($request->direction == 'buy'){
            $res = $this->PerpetualContractService->buyLong($user,$array);
        }else{
            $res = $this->PerpetualContractService->sellShort($user,$array);
        }
        if(!$res){
            return $this->error(0,'下单失败，请稍后重试');
        }
        return $res;
    }
    #撤单
    public function cancel_order(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $client_order_id = $request->input('client_order_id');
        $direction = $request->input('direction');
        $status = $request->input('status');
        return $this->PerpetualContractService->cancelOrder($user_id,$client_order_id,$direction,$status);
    }
    #批量撤单
    public function bulk_cancellation(Request $request)
    {

    }
    #获取当前余额
    public function get_current_balance(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_name' => 'required|string', //交易对 参数格式：BTC/USDT
        ])) return $vr;

        $user = $this->current_user();
        $user_id=$user['user_id'];
        $coin_name = $request->input('coin_name');

        return $this->PerpetualContractService->getCurrentBalance($user_id,$coin_name);
    }
    #当前委托
    public function current_commission(Request $request)
    {

        if ($vr = $this->verifyField($request->all(),[
            'direction' => 'required|string|in:buy,sell', //买卖方向
            'type' => 'integer|in:1,2', //委托类型 1限价交易 2市价交易
            'contract_code' => 'required|String', //交易对 参数格式：BTC-USDT
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['direction','type','contract_code']);

        $data = $this->PerpetualContractService->currentCommission($user,$params);
        return $this->successWithData($data);
    }

    #持仓信息
    public function  contract_position(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
//        $client_order_id= $request->input("client_order_id");
//        $contract_code = $request->input("contract_code");
//        $contract_code = $request->input("contract_code");
        if ($vr = $this->verifyField($request->all(),[
//            'client_order_id' => 'required|String', //客户端订单ID
            'contract_code' => 'required|String', //合约代码取值范围BTC-USD
            'coin_name' => 'required|String', //币种名称
//            'margin_mode' => 'required|string|in:crossed,fixed', //仓位 模式 全仓 crossed fixed 逐仓
            'direction' => 'required|string|in:buy,sell', //仓位 模式 做多或者做空
        ])) return $vr;
        $params = $request->only(['client_order_id','contract_code','coin_name','direction']);
        return $this->PerpetualContractService->contractPosition($user_id,$params);
    }
    #历史委托
    public function historical_commission(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $contract_code=$request->input('contract_code');
        return $this->PerpetualContractService->historicalCommission($user_id,$contract_code);
    }


}
