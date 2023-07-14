<?php

namespace App\Http\Middleware\Auth;

use Closure;

class CheckGoogleToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth('api')->user();
        if(blank($user)) return api_response()->error(1003,'请先登录');

        $google_token = $user->google_token;
        if (blank($google_token)){
            return api_response()->error(1035,'请先开启谷歌验证');
        }

        $google2fa = app('pragmarx.google2fa');
        $google_code = $request->input('google_code');
        if( $google2fa->verifyKey($google_token, $google_code) !== true){
            return api_response()->error(4001,'验证失败');
        }

        return $next($request);
    }
}
