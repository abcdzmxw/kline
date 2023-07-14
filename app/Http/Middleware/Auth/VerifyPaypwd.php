<?php

namespace App\Http\Middleware\Auth;

use Closure;

class VerifyPaypwd
{
    /**
     * Handle an incoming request.
     * 验证交易密码
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth('api')->user();

        if($user['trade_verify'] == 1){
            $payword = $request->input('payword');
            if(blank($payword)) return api_response()->error(0,'请输入交易密码');
            $check_res = $user->verifyPassword($payword,$user['payword']);
            if(!$check_res) {
                return api_response()->error(0,'密码错误');
            }
        }

        return $next($request);
    }
}
