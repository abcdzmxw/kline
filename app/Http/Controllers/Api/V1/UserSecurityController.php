<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSecurityController extends ApiController
{
    // 用户账号安全

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    //账号安全信息
    public function home()
    {
        $user = $this->current_user();

        $user = User::query()->find($user['user_id']);
        $user = $user->makeVisible(['google_token'])->toArray();

        $data = array_only($user,['user_id','country_code','phone','phone_status','email','email_status','google_token','google_status']);
        return $this->successWithData($data);
    }

    //交易密码开关
    public function switchTradeVerify()
    {
        $user = $this->current_user();

        $trade_verify = $user->trade_verify;

        $user->trade_verify = $trade_verify == 1 ? 0 : 1;
        $user->save();
        return $this->successWithData(['trade_verify' => $user['trade_verify']]);
    }

    //用户登录状态 发送code
    public function getCode(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'type' => 'integer|in:1,2', //
        ])) return $vr;

        $user = $this->current_user();
        $type = $request->input('type',1);

        if($type == 1){
            //手机
            if(blank($user['phone'])) return $this->error(0,'手机未绑定');
            $sendResult = sendCodeSMS($user->phone,'',$user->country_code);
        }else{
            //邮箱
            if(blank($user['email'])) return $this->error(0,'邮箱未绑定');
            $sendResult = sendEmailCode($user->email);
        }

        if ($sendResult === true){
            return $this->success('发送成功');
        }
        return $this->error(0,$sendResult);
    }

    //设置或重置交易密码
    public function setOrResetPaypwd(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'sms_code' => '',
            'email_code' => '',
            'google_code' => '',
            'payword' => 'required|digits:6|confirmed:payword_confirmation',
            'payword_confirmation' => 'required|digits:6',
        ])) return $res;

        $user = $this->current_user();

        //验证code
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        $user->payword = $user->passwordHash($request->payword);
        $user->save();

        return $this->success();
    }

    //修改登录密码
    public function updatePassword(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'sms_code' => '',
            'email_code' => '',
            'google_code' => '',
            'password' => 'required|confirmed:password_confirmation',
            'password_confirmation' => 'required',
        ])) return $res;

        $user = $this->current_user();

        //验证code
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        $user->password = $user->passwordHash($request->password);
        $user->save();

        return $this->success();
    }

    //绑定手机
    public function bindPhone(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'phone' => 'required',
            'country_code' => 'required',
            'sms_code' => 'required',
            'email_code' => '',
            'google_code' => '',
        ])) return $res;

        $user = $this->current_user();
        $phone = $request->input('phone');

        //验证code
        $codes = $request->only(['sms_code','email_code','google_code']);
      //  $checkResult = $this->userService->verifySecurityCode($user,$codes);
      //  if ($checkResult !== true) return $this->error(4001,$checkResult);

        //验证绑定账号
        if( !blank(User::query()->where(['phone'=>$phone,'country_code'=>$request->country_code])->first()) ) return $this->error(4001,'账号已存在');

        $checkResult2 = checkSMSCode($phone,$request->sms_code,'bind_phone',$request->country_code);
        if ($checkResult2 !== true) return $this->error(4001,$checkResult2);

        $user->country_id = $request->input('country_id');
        $user->country_code = $request->country_code;
        $user->phone = $phone;
        $user->phone_status = 1;
        $user->save();
        return $this->success();
    }

    //换绑手机
    public function changePhone(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'country_code' => 'required',
            'new_phone' => 'required',
            'new_phone_code' => 'required',
            'sms_code' => '',
            'email_code' => '',
            'google_code' => '',
        ])) return $res;

        $user = $this->current_user();
        $user_data = collect($user)->toArray();
        $phone = $request->input('phone');
        $user_data['country_code'] = $request->input('country_code');
        $user_data['phone'] = $phone;

        //验证code
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user_data,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        //验证绑定账号
        if( !blank(User::query()->where(['phone'=>$phone,'country_code'=>$request->country_code])->first()) ) return $this->error(4001,'账号已存在');

        $checkResult2 = checkSMSCode($phone,$request->new_phone_code,'bind_phone',$request->country_code);
        if ($checkResult2 !== true) return $this->error(4001,$checkResult2);

        $user->country_id = $request->input('country_id');
        $user->country_code = $request->country_code;
        $user->phone = $phone;
        $user->phone_status = 1;
        $user->save();
        return $this->success();
    }

    //发送绑定手机短信验证码
    public function sendBindSmsCode(Request $request,User $user)
    {
        if ($vr = $this->verifyField($request->all(),[
            'country_code' => 'required|string', //国家代码
            'phone' => 'required|string',
            'type' => 'integer|in:2',
        ])) return $vr;

        $account = $request->input('phone');

        $type = $request->input('type',2);
        if($type == 2){
            //绑定验证码
            if ($user->getUserByPhone($account)) return $this->error(0,'账号已被占用');
        }

        $sendResult = sendCodeSMS($account,'bind_phone',$request->country_code);
        if ($sendResult === true){
            return $this->success();
        }
        return $this->error(4001,$sendResult);
    }

    //发送绑定邮箱验证码
    public function sendBindEmailCode(Request $request,User $user)
    {
        if ($vr = $this->verifyField($request->all(),[
            'email' => 'required|string',
            'type' => 'integer|in:2',
        ])) return $vr;

        $account = $request->input('email');

        $type = $request->input('type',2);
        if($type == 2){
            //绑定验证码
            if ($user->getUserByEmail($account)) return $this->error(0,'账号已被占用');
        }

        $sendResult = sendEmailCode($account,'bind_email');
        if ($sendResult === true){
            return $this->success();
        }
        return $this->error(4001,$sendResult);
    }

    //绑定邮箱
    public function bindEmail(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'email' => 'required',
            'email_code' => 'required',
            'sms_code' => '',
            'google_code' => '',
        ])) return $res;

        $user = $this->current_user();
        $email = $request->input('email');

        //验证code
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        //验证绑定账号
        if( !blank(User::query()->where('email',$email)->first()) ) return $this->error(4001,'账号已存在');

        $checkResult2 = checkEmailCode($email,$request->email_code,'bind_email');
        if ($checkResult2 !== true) return $this->error(4001,$checkResult2);

        $user->email = $email;
        $user->email_status = 1;
        $user->save();
        return $this->success();
    }

    //换绑邮箱
    public function changeEmail(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'new_email' => 'required',
            'new_email_code' => 'required',
            'email_code' => '',
            'sms_code' => '',
            'google_code' => '',
        ])) return $res;

        $user = $this->current_user();
        $new_email = $request->input('new_email');

        //验证code
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        //验证换绑账号
        if( !blank(User::query()->where('email',$new_email)->first()) ) return $this->error(4001,'账号已存在');

        $checkResult2 = checkEmailCode($new_email,$request->new_email_code,'bind_email');
        if ($checkResult2 !== true) return $this->error(4001,$checkResult2);

        $user->email = $new_email;
        $user->email_status = 1;
        $user->save();
        return $this->success();
    }

    //解绑邮箱
    public function unbindEmail(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'sms_code' => '',
            'email_code' => '',
            'google_code' => '',
        ])) return $res;

        $user = $this->current_user();
        if(blank($user['email'])) return $this->error();
        if($user['account_type'] == 2) return $this->error(0,'主账号不能解绑');

        //验证code
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        $user->email = '';
        $user->email_status = 0;
        $user->save();
        return $this->success();
    }

    //解绑手机
    public function unbindPhone(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'sms_code' => '',
            'email_code' => '',
            'google_code' => '',
        ])) return $res;

        $user = $this->current_user();
        if(blank($user['phone'])) return $this->error();
        if($user['account_type'] == 1) return $this->error(0,'主账号不能解绑');

        //验证code
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        $user->phone = '';
        $user->phone_status = 0;
        $user->save();
        return $this->success();
    }

    //发送忘记密码短信验证码
    public function sendSmsCodeForgetPassword(Request $request,User $user)
    {
        if ($vr = $this->verifyField($request->all(),[
            'country_code' => 'required|string', //国家代码
            'phone' => 'required|string',
        ])) return $vr;

        $account = $request->input('phone');

        $sendResult = sendCodeSMS($account,'',$request->country_code);
        if ($sendResult === true){
            return $this->success();
        }
        return $this->error(4001,$sendResult);
    }

    //发送忘记密码邮箱验证码
    public function sendEmailCodeForgetPassword(Request $request,User $user)
    {
        if ($vr = $this->verifyField($request->all(),[
            'email' => 'required|string',
        ])) return $vr;

        $account = $request->input('email');

        $sendResult = sendEmailCode($account);
        if ($sendResult === true){
            return $this->success();
        }
        return $this->error(4001,$sendResult);
    }

    //忘记登录密码尝试
    public function forgetPasswordAttempt(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'account' => 'required',
        ])) return $res;

        $account = $request->input('account');
        $user = User::query()->where('phone',$account)->orWhere('email',$account)->first();

        if(blank($user)) return $this->error(0,'用户不存在');

        $user = $user->toArray();
        $verify_data = array_only($user,['country_code','phone','phone_status','email','email_status','google_status']);

        return $this->successWithData($verify_data);
    }

    //忘记登录密码
    public function forgetPassword(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'account' => 'required',
            'sms_code' => '',
            'email_code' => '',
            'google_code' => '',
            'password' => 'required|confirmed:password_confirmation',
            'password_confirmation' => 'required',
        ])) return $res;

        $account = $request->input('account');
        $user = User::query()->where('phone',$account)->orWhere('email',$account)->first();
        if(blank($user)) return $this->error(0,'用户不存在');
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        $user->password = $user->passwordHash($request->password);
        $user->save();

        return $this->success();
    }

    public function disableSmsEmailGoogle(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'type' => 'required|integer|in:1,2,3', //1手机 2邮箱 3谷歌
            'sms_code' => '',
            'email_code' => '',
            'google_code' => '',
        ])) return $vr;

        $user = $this->current_user();
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        DB::beginTransaction();

        $type = $request->input('type');
        if($type == 1){
            $user->phone_status = 0;
        }elseif($type == 2){
            $user->email_status = 0;
        }else{
            $user->google_status = 0;
        }

        $user->save();
        if($user['phone_status'] == 0 && $user['email_status'] == 0 && $user['google_status'] == 0){
            DB::rollBack();
            return $this->error(0,'至少开启一种验证');
        }else{
            DB::commit();
            return $this->success();
        }
    }

    public function enableSmsEmailGoogle(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'type' => 'required|integer|in:1,2,3',
            'sms_code' => '',
            'email_code' => '',
            'google_code' => '',
        ])) return $vr;

        $user = $this->current_user();
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $this->userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        $type = $request->input('type');
        if($type == 1){
            if(blank($user['phone'])) return $this->error(0,'手机未绑定');
            $user->phone_status = 1;
        }elseif($type == 2){
            if(blank($user['email'])) return $this->error(0,'邮箱未绑定');
            $user->email_status = 1;
        }else{
            if(blank($user['google_token'])) return $this->error(0,'谷歌验证未绑定');
            $user->google_status = 1;
        }

        $user->save();
        return $this->success();
    }

    public function changePurchaseCode(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'purchase_code' => 'required|numeric',
        ])) return $vr;
        
        $current_user = $this->current_user();
        $purchase_code = $request->purchase_code;
        DB::beginTransaction();
        
        # 获取用户，锁行
        $user = DB::table('users')
            ->where('user_id', $current_user['user_id'])
            ->lockForUpdate()
            ->first();
        
        # 已填写申购码，不允许再次填写
        if(!empty($user->purchase_code)){
            
            DB::rollBack();
            # 您已设置申购码，不需要再次设置
            return $this->error(4001,'You have set the subscription code, do not need to set it again');
        }
        
        # 申购码不能是自己的
        if($user->invite_code === $purchase_code){
            
            # 申购码不正确
            return $this->error(4001,'Incorrect purchase code');
        }
        
        # 申购码对应用户不存在
        $purchaseCodeUser = DB::table('users')
            ->where('invite_code', $purchase_code)
            ->where('is_agency', 1)
            ->lockForUpdate()
            ->first();
        
        if($purchaseCodeUser == null){
            
            DB::rollBack();
            # 申购码不正确
            return $this->error(4001,'Incorrect purchase code');
        }
        
        # 更新
        DB::table('users')
            ->where('user_id', $current_user['user_id'])
            ->update([
                'purchase_code' => $purchase_code,
                'pid' => $purchaseCodeUser->user_id,
                'referrer' => $purchaseCodeUser->user_id
            ]);
        
        DB::commit();
        return $this->success('modify successfully');
    }
}














