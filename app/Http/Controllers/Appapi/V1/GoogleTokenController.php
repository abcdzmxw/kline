<?php

namespace App\Http\Controllers\Appapi\V1;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;

class GoogleTokenController extends ApiController
{
    // 谷歌验证器

    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = app('pragmarx.google2fa');
    }

    //获取google_token
    public function getGoogleToken()
    {
        $user = $this->current_user();
        if(!blank($user['google_token'])){
            return $this->error(4001,'已开启谷歌验证');
        }

        $secret = $this->google2fa->generateSecretKey();
        $company = ''; // 公司名称
        $holder = $user['account']; // 用户标示，可以是邮箱或者名称等
        $data['qrcode_url'] = $this->google2fa->getQRCodeUrl($company,$holder,$secret);
        $data['secret'] = $secret;

        return $this->successWithData($data);
    }

    //绑定
    public function bindGoogleToken(Request $request,UserService $userService)
    {
        if ($vr = $this->verifyField($request->all(),[
            'google_token' => 'required|string',
            'google_code' => 'required|string',
            'sms_code' => '',
            'email_code' => '',
        ])) return $vr;

        $user = $this->current_user();
        //验证登录用户code 开启几种验证方式就验证几种
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        //验证谷歌code
        $google_token = $request->input('google_token');
        if( $this->google2fa->verifyKey($google_token, $request->google_code) === true){
            $user->update(['google_token'=>$google_token,'google_status'=>1]);
            return $this->success();
        }
        return $this->error(4001,'验证失败');
    }

    //解绑
    public function unbindGoogleToken(Request $request,UserService $userService)
    {
        if ($vr = $this->verifyField($request->all(),[
            'google_code' => '',
            'sms_code' => '',
            'email_code' => '',
        ])) return $vr;

        $user = $this->current_user();
        //验证登录用户code 开启几种验证方式就验证几种
        $codes = $request->only(['sms_code','email_code','google_code']);
        $checkResult = $userService->verifySecurityCode($user,$codes);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        $user->update(['google_token' => null,'google_status'=>0]);
        return $this->success();
    }

}
