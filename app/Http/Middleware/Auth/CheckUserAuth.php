<?php

namespace App\Http\Middleware\Auth;

use App\Models\User;
use App\Models\UserAuth;
use Closure;

class CheckUserAuth
{
    /**
     * Handle an incoming request.
     * 实名认证未通过
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try{
            $user = auth('api')->user();

            if (!$user || ($user['user_auth_level'] == User::user_auth_level_wait)){
                $userAuth = UserAuth::query()->where(['user_id'=>$user['user_id']])->first();
                if ($userAuth && $userAuth->status == UserAuth::STATUS_WAIT)
                    return api_response()->error(1033,'认证审核中');
                return api_response()->error(1033,'请完成实名认证');
            }
        }catch (\Exception $exception){
            return api_response()->error(0,'网络繁忙');
        }

        return $next($request);
    }
}
