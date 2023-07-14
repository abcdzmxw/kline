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
use App\Services\UserService;
use App\Services\UserWalletService;
use App\Services\UdunWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use App\Models\Admin\AdminSetting;

class UserWalletController extends ApiController
{
    //钱包

    protected $UserWalletService;

    public function __construct(UserWalletService $UserWalletService)
    {
        $this->UserWalletService = $UserWalletService;
    }

    // 账户列表
    public function accounts()
    {
        $data = UserWallet::$accountMap;
        $data = array_map(function ($v){
            $v['name'] = __($v['name']);
            return $v;
        },$data);
        return $this->successWithData($data);
    }

    // 账户下面的子账户类别
    public function accountPairList(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'account' => 'required',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['account']);

        $data = $this->UserWalletService->accountPairList($user,$params);
        return $this->successWithData($data);
    }

    // 可转账币种列表
    public function coinList(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'from_account' => 'required',
            'to_account' => 'required',
//            'pair_id' => '', // 哪个账户 合约账户标识是contract_id
        ])) return $vr;

        $params = $request->all();
        $data = $this->UserWalletService->coinList($params);
        return $this->successWithData($data);
    }

    public function getBalance(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'account' => 'required',
//            'pair_id' => 'required_if:account,2',
            'coin_name' => 'required',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $data = $this->UserWalletService->getBalance($user,$params);
        return $this->successWithData($data);
    }

    // 划转
    public function transfer(Request $request)
    {
        if(empty($request->input('amount'))){
            return $this->error(0,'数量不能为空');
        }
        if ($vr = $this->verifyField($request->all(),[
            'from_account' => 'required|in:1,2,3',
            'to_account' => 'required|in:1,2,3',
//            'pair_id' => '', // 哪个账户 合约账户标识是contract_id
            'amount' => 'required', // 金额
            'coin_name' => 'required', // 转账币种
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        return $this->UserWalletService->transfer($user,$params);
    }

    // 划转记录
    public function transferRecords(Request $request)
    {
        $user = $this->current_user();
        $params = $request->all();
        return $this->UserWalletService->transferRecords($user,$params);
    }



    //获取钱包流水
    public function getWalletLogs(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'account_type' => '',
            'coin_name' => '',
            'rich_type' => '',
            'log_type' => '',
            'start_time' => 'date',
            'end_time' => 'date',
        ])) return $vr;

        $user = $this->current_user();

        $builder = UserWalletLog::query()->where('user_id',$user['user_id']);

        if($account_type = $request->input('account_type',1)){
            $builder->where('account_type',$account_type);
        }

        if($rich_type = $request->input('rich_type','usable_balance')){
            $builder->where('rich_type',$rich_type);
        }

        if(!blank($coin_name = $request->input('coin_name'))){
            $coin_id = Coins::query()->where('coin_name',$coin_name)->value('coin_id');
            $builder->where('coin_id',$coin_id);
        }

        if($log_type = $request->input('log_type')){
            $builder->where('log_type',$log_type);
        }

        if(
            ($start_time = $request->input('start_time'))
            && ($end_time = $request->input('end_time'))
        ){
            if($start_time == $end_time){
                $builder->whereDate('created_at',$start_time);
            }else{
                $builder->whereDate('created_at', '>',$start_time)->whereDate('created_at', '<=',$end_time);
            }
        }

        $data = $builder->latest()->paginate();

        return $this->successWithData($data);
    }

    #钱包充值地址
    public function wallet_image(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_id' => 'required',
            'address_type' => '',
        ])) return $vr;

        $coin_id=$request->input('coin_id');
        $address_type=$request->input('address_type');
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $res = $this->UserWalletService->walletImage($coin_id,$user_id,$address_type);
        return $this->checkAddressByUdun($res,$coin_id,$address_type,$user_id);
    }
    
    
    protected function checkAddressByUdun($res,$coin_id,$address_type,$user_id){
        $dataarr = json_decode(json_encode($res),true);
        $data = $dataarr['original'];
        if($data['result_code'] == 'SUCCESS' && $data['message'] == 'SUCCESS'){
            $UdunWalletService = new UdunWalletService();
            $supportInfo = $UdunWalletService->supportCoins();
            if($supportInfo['code'] == 200 ){
                if( $coin_id == 1){
                    if($address_type == 2 ){
                        $name = 'USDT-ERC20';
                    }elseif($address_type == 1){
                        $name = 'BTC';
                    }elseif($address_type == 3){
                        $name = 'USDT-TRC20';
                    }
                }else{
                    $coinInfo  = UserWallet::query()->where('coin_id',$coin_id)->first();
                    $name = $coinInfo['coin_name'];
                }
                $mainCoinType = '';
                foreach($supportInfo['data'] as $k => $v){
                    if($v['name'] == $name){
                        $mainCoinType = $v['mainCoinType'];
                    }
                }

//                $checkAddressRes = $UdunWalletService->checkAddress($mainCoinType,$data['data']['address']);
                $checkAddressRes = $UdunWalletService->checkExistAddress($mainCoinType,$data['data']['address']);
//                if($user_id == 5){
//                    return array($checkAddressRes,$data['data']['address']);
//                }
                if($checkAddressRes['code'] == 200 && $checkAddressRes['message'] == 'SUCCESS' ){
                    return $res;
                }
            }
        }
        $res = [];
        $result['code'] = 200;
        $result['message'] = 'FAILED';
        $result['result_code'] = 'FAILED';
        $result['data']['address'] = '';

        $alarmEmail = env('ALARM_EMAIL');
        if($alarmEmail == ''){
            return false;
        }
        $alarmEmail = explode(',', $alarmEmail);
        foreach ($alarmEmail as $key){
            sendEmailError($key, $user_id);//填接收异常邮件的邮箱地址,需要多个就复制多一行这行代码
        }
        return $result;
    }
    
    
    #钱包划转
    public function funds_transfer(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_id' => 'required',
            'coin_name' => 'required',
            'first_account' => 'required',
            'last_account' => 'required',
            'amount' => 'required',
        ])) return $vr;

        $user = $this->current_user();
        $user_id=$user['user_id'];
        $coin_name=$request->input('coin_name');
        $coin_id=$request->input('coin_id');
        $amount=$request->input('amount');
        $first_account=$request->input('first_account');
        $last_account=$request->input('last_account');
        return $this->UserWalletService->fundsTransfer($user_id,$coin_name,$coin_id,$amount,$first_account,$last_account);
    }
    #划转记录
    public function transfer_record(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->transferRecord($user_id);
    }

    #申购记录
    public function subscribe_records(Request $request){
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->subscribeRecords($user_id);
    }

    #充币
    public function recharge(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $coin_id=$request->input('coin_id');
        $address=$request->input('address');
        $amount=$request->input('amount');
        return $this->UserWalletService->recharge($user_id,$coin_id,$address,$amount);
    }
    #充币处理
    public function recharge_dispose(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $coin_id=$request->input('coin_id');
        $status=$request->input('status');
        return $this->UserWalletService->rechargeDispose($user_id,$status,$coin_id);
    }

    #充币记录
    public function deposit_history(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->depositHistory($user_id);
    }
    #提币
    public function withdraw(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_id' => 'required|integer',
            'address_type' => '', // 当提币USDT时 需选择地址类型是1-omni还是2-erc20
            'address' => 'required',
            'amount' => 'required',
            'exchange_rate' => '',
           
            'address_note' => '',
            'code' => '',
            'code_type' => 'integer|in:1,2,3', //二次验证Code类型 1手机 2邮箱 3谷歌验证器
        ])) return $vr;

        $user = $this->current_user();

        // 提币二次验证开关
        $withdraw_switch = get_setting_value('withdraw_switch','common',0);
        if($withdraw_switch == 1){
            // 二次验证
            $code = $request->input('code');
            if(empty($code)) return $this->error(4001,'Missing Parameters');
            $code_type = $request->input('code_type',1);
            $userService = new UserService();
            $checkResult = $userService->verifyCode($user,$code_type,$code);
            if ($checkResult !== true) return $this->error(4001,$checkResult);
        }

        // 小白
        // 用户权限判断  如果没有高级认证不可参与交易
        if($user->user_auth_level != 2){
            return $this->error(0,'请完成高级认证');  // 使用公共语言
        }

        $user_id=$user['user_id'];
        $coin_id=$request->input('coin_id');
        $address=$request->input('address');
        $address_type=$request->input('address_type');
        if($coin_id == 1 && empty($address_type)) $address_type = $request->input('addressType');
        $amount=$request->input('amount');
         $currency=$request->input('currency');
        $exchange_rate=$request->input('exchange_rate');
        $address_note=$request->input('address_note','');
        return $this->UserWalletService->withdraw($user_id,$coin_id,$address,$amount,$exchange_rate,$currency,$address_note,$address_type);
    }

    // 撤销提币申请
    public function cancelWithdraw(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'withdraw_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $res = $this->UserWalletService->cancelWithdraw($user,$params);
        if(!$res){
            return $this->error();
        }
        return $this->success('success');
    }

    #提币处理
    public function withdraw_dispose(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $coin_id=$request->input('coin_id');
        $status=$request->input('status');
        return $this->UserWalletService->withdrawDispose($user_id,$status,$coin_id);
    }
    #提币记录
    public function withdrawal_record(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->withdrawalRecord($user_id);
    }
    
   #2023-3-7
   #提现
    public function cashwithdraw(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_id' => 'required|integer',
            'address_type' => '', // 当提币USDT时 需选择地址类型是1-omni还是2-erc20
            'address' => 'required',
            'amount' => 'required',
            'address_note' => '',
            'code' => '',
            'code_type' => 'integer|in:1,2,3', //二次验证Code类型 1手机 2邮箱 3谷歌验证器
        ])) return $vr;

        $user = $this->current_user();

        // 提币二次验证开关
        $withdraw_switch = get_setting_value('withdraw_switch','common',0);
        if($withdraw_switch == 1){
            // 二次验证
            $code = $request->input('code');
            if(empty($code)) return $this->error(4001,'Missing Parameters');
            $code_type = $request->input('code_type',1);
            $userService = new UserService();
            $checkResult = $userService->verifyCode($user,$code_type,$code);
            if ($checkResult !== true) return $this->error(4001,$checkResult);
        }

        // 小白
        // 用户权限判断  如果没有高级认证不可参与交易
        if($user->user_auth_level != 2){
            return $this->error(0,'请完成高级认证');  // 使用公共语言
        }

        $user_id=$user['user_id'];
        $coin_id=$request->input('coin_id');
        $address=$request->input('address');
        $address_type=$request->input('address_type');
        if($coin_id == 1 && empty($address_type)) $address_type = $request->input('addressType');
        $amount=$request->input('amount');
        $address_note=$request->input('address_note','');
        return $this->UserWalletService->cashWithdraw($user_id,$coin_id,$address,$amount,$address_note,$address_type);
    }

    // 撤销提现申请
    public function cancelCashWithdraw(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'withdraw_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();
        $res = $this->UserWalletService->cancelCashWithdraw($user,$params);
        if(!$res){
            return $this->error();
        }
        return $this->success('success');
    }

    #提现处理
    public function cashWithdraw_dispose(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $coin_id=$request->input('coin_id');
        $status=$request->input('status');
        return $this->UserWalletService->cashWithdrawDispose($user_id,$status,$coin_id);
    }
    #提现记录
    public function cashWithdrawal_record(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->cashWithdrawalRecord($user_id);
    }
    
    #end 2023-3-7
    
    
    
    
    
    #永续账户
    public function sustainable_account(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->sustainableAccount($user_id);
    }
    #资金账户
    public function fund_account(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->fundAccount($user_id);
    }

    // 合约账户资产
    public function contractAccount(Request $request)
    {
        $user = $this->current_user();
        return $this->UserWalletService->contractAccount($user['user_id']);
    }

    // 法币账户资产
    public function otcAccount(Request $request)
    {
        $user = $this->current_user();
        return $this->UserWalletService->otcAccount($user['user_id']);
    }

    #资产
    public function personal_assets(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->personalAssets($user_id);
    }
    #划转当前余额信息
    public function token_list(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $first_account=$request->input('first_account');
        return $this->UserWalletService->tokenList($user_id,$first_account);
    }
    #币种资产
    public function withdrawal_balance(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_name' => 'required|string'
        ])) return $vr;

        $user = $this->current_user();
        $user_id=$user['user_id'];
        $coin_name=$request->input('coin_name');
        return $this->UserWalletService->withdrawalBalance($user_id,$coin_name);
    }

    #提币地址管理
    public function withdrawal_address_management(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->withdrawalAddressManagement($user_id);
    }
    #提币地址删除
    public function withdrawal_address_deleted(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $id=$request->input('id');
        return $this->UserWalletService->withdrawalAddressDeleted($user_id,$id);
    }
    #提币地址添加
    public function withdrawal_address_add(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $address=$request->input('address');
        $address_note=$request->input('address_note');
        $coin_name=$request->input('coin_name');
        return $this->UserWalletService->withdrawalAddressAdd($user_id,$address,$coin_name,$address_note);
    }
    #提币地址修改
    public function withdrawal_address_modify(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        $id=$request->input('id');
        $address_note=$request->input('address_note');
        $address=$request->input('address');
        return $this->UserWalletService->withdrawalAddressModify($user_id,$id,$address,$address_note);
    }
    #提币地址选择
    public function withdrawal_select_address(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->withdrawalSelectAddress($user_id);
    }
    #申购展示
    public function subscribe(Request $request)
    {

        $user = $this->current_user();
        $user_id=$user['user_id'];
//        $user_locale = $request->header("lang");

        $res = $this->UserWalletService->subscribe($user_id);

        // $lang = App::getLocale();
        // $res->original = baiduTransAPI($res->original, 'auto', $lang);
        // var_dump($res['']);
        return $res;
    }

    // 申购活动
    public function subscribeActivity()
    {
        $user = $this->current_user();

        $data = $this->UserWalletService->subscribeActivity($user);
        return $this->successWithData($data);
    }

    #申购币种集合
    public function subscribeToken_list(Request $request)
    {
        $user = $this->current_user();

        $user_id=$user['user_id'];
        return $this->UserWalletService->subscribeTokenList($user_id);
    }
    #立即申购
    public function subscribe_now_copy(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'amount' => 'required',
            'coin_name' => 'required',
        ])) return $res;

        $user = $this->current_user();
        $user_id=$user['user_id'];
        $amount=$request->input('amount');
        $coin_name=$request->input('coin_name');
        $invitation_code=$request->input('invitation_code');

        $res = $this->UserWalletService->subscribeNow($user_id,$amount,$coin_name);
        if(!$res){
            return $this->error();
        }
        return $this->success('SUCCESS',true);

    }
    # 检查是否有申购码
    private function isHasPurchaseCode ()
    {
        $user = $this->current_user();
        $purchase_code = $user['purchase_code'];

        return !empty($purchase_code);
    }
    #立即申购
    public function subscribe_now(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'amount' => 'required',
            'coin_name' => 'required',
        ])) return $res;

        $user = $this->current_user();
        $user_id=$user['user_id'];
        $amount=$request->input('amount');
        $coin_name=$request->input('coin_name');
        $invitation_code=$request->input('invitation_code');

        // 检查是否有申购码
        // if(!$this->isHasPurchaseCode()){
        //     return $this->error(4002, 'You have not filled in the subscription code');
        // }

        $res = $this->UserWalletService->subscribeNow($user_id,$amount,$coin_name);
        if(!$res){
            return $this->error();
        }
        return $this->success('SUCCESS',true);

    }
    #申购结果
    public function subscribe_announce_results(Request $request)
    {
        return $this->UserWalletService->subscribeAnnounceResults();
    }

    #上币申请
    public function application_for_listing(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        if ($vr = $this->verifyField($request->all(),[
        'coin_name' => 'required|String', //币种英文名
        'coin_chinese_name' => 'required|String', //币种中文名required|numeric
        'contact_position' => 'required|string', //联系人及职位
        'contact_phone' => 'required|numeric', //联系人电话
        'coin_market_price' => 'required|numeric', //Token市价
        'contact_email' => 'required|string', //联系人邮箱
        'cotes_const' => 'required|string', //项目注册地
        'agency_personnel' => 'required|String', //项目投资机构/个人
        'currency_code' => 'required|String', //币种代码(符号)
        'currency_identification' => 'required|String', //币种标识(22px*22px)
        'placement' => 'required|String', //募资日期
        'official_website' => 'required|String', //官方网站
        'white_paper_link' => 'required|String', //白皮书链接(若无链接上传附件)
        'currency_circulation' => 'required|numeric', //币种总发行量
        'coin_turnover' => 'required|numeric', //币种流通量
        'coin_allocation_proportion' => 'required|String', //币种分配比例
        'cash_people_counting' => 'required|numeric', //持币人数
        'online_bourse' => 'required|string', //已上线交易平台
        'private_cemetery_price' => 'required|String', //私募/公墓价格
        'block_network_type' => 'required|string', //币种区块网络类型(ETH,EOS)
        'currency_issue_date' => 'required|String', //币种发行日期
        'blockchain_browser' => 'required|String', //区块浏览器
        'official_wallet_address' => 'required|String', //官方钱包地址
        'contract_address' => 'required|String', //合约地址
        'twitter_link' => 'required|String', //Twitter链接
        'telegram_link' => 'required|String', //Telegram链接
        'facebook_link' => 'required|String', //Facebook链接
        'listing_fee_budget' => 'required|numeric', //上币费预算(BTC为单位)
        'market_currency_quantity' => 'required|numeric', //上币后市场活动项目代币数量
        'currency_chinese_introduction' => 'required|String', //币种中文介绍
        'currency_english_introduction' => 'required|String', //币种英文介绍
        'remarks' => 'required|String', //备注
        'white_paper' => 'required|String', //上传白皮书
        'referrer_mechanism_code' => 'required|String', //推荐人姓名机构及推荐码(选填)
    ])) return $vr;

        $params=$request->all();
         return $this->UserWalletService->applicationForListing($user_id,$params);

    }
    #添加市场币种交易对
   public function market_token_add(Request $request)
   {
       $user_id=$request->input('user_id');
       return $this->UserWalletService->marketTokenAdd($user_id);
   }
   #交易对信息
   public function trading_pair_currency(Request $request)
   {
       if ($vr = $this->verifyField($request->all(),[
           'symbol' => 'required',
       ])) return $vr;

       $symbol=$request->input('symbol');
       return $this->UserWalletService->tradingPairCurrency($symbol);
   }

    #自动充币到账
    public function charge_eth(Request $request)
    {
        $data = $request->all();
        info('-charge_eth-' . json_encode($data));
//        return $this->UserWalletService->chargeEth($data);
    }
    #创建钱包地址
    public function create_wallet_address()
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->createWalletAddress($user_id);
    }

    #钱包充值地址
    public function wallet_payment_method(Request $request)
    {
        $coin_name=$request->input('coin_name');
        $address_type=$request->input('address_type');
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->walletPaymentMethod($coin_name,$user_id,$address_type);
    }

    public function collection_type(Request $request)
    {
        $user = $this->current_user();
        $user_id=$user['user_id'];
        return $this->UserWalletService->collectionType($user_id);
    }
    // 查询手动充值记录
    public function recharge_manual_log(Request $request)
    {
        // 分页设置
        if ($vr = $this->verifyField($request->all(), [
            'per_page' => 'numeric',
        ])) return $vr;
        // 默认每页数量15
        $limit = $request->input('per_page') ?? 15;
        $user = $this->current_user();
        $user_id = $user['user_id'];
        // 查询充值记录
        $data = RechargeManual::query()
            ->where('user_id', $user_id)
            ->latest()
            ->paginate($limit);

        return $this->successWithData($data);
    }
    public function recharge_manual_post(Request $request)
    {
        $params = $request->all();
        // 获取前台传过来的参数
        // 1、PayPal账号
        // 2、充值金额
        // 3、支付凭证（图片）URL
        if ($vr = $this->verifyField($params, [
            'currency' => 'required|string',
            'amount' => 'required|numeric',
            'image' => 'required|string',
        ])) return $vr;

        // 获取用户id
        $user_id = $this->current_user()['user_id'];

        $payment = Payment::query()->where('currency',$params)->firstOrFail();
        $pay_money = PriceCalculate($params['amount'],'*',$payment['exchange_rate'],4);

        $res = RechargeManual::query()->create([
            'user_id' => $user_id,
            'account' => json_encode($payment->toArray()),
            'amount' => $params['amount'],
            'image' => $params['image'],
            'pay_currency' => $payment['currency'],
            'pay_money' => $pay_money,
            'exchange_rate' => $payment['exchange_rate'],
            'status' => 0,
        ]);
        if (!$res) {
            return $this->error();
        }
        return $this->success();
    }
    public function paypal()
    {
        $arr = array();
        $setting = AdminSetting::query()->where('module', 'paypal')->get()->toArray();
        if (blank($setting)) return $this->successWithData($arr);
        foreach ($setting as $value) {
            if ($value['type'] == 'image') {
                $arr[$value["key"]] = getFullPath($value["value"]);
            } else {
                $arr[$value["key"]] = $value["value"];
            }
        }
        return $this->successWithData($arr);
    }

    public function getPayments()
    {
        $parments = Payment::getRechargePayments();
        return $this->successWithData($parments);
    }

}
