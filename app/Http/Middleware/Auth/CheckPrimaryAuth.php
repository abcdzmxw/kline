<?php

namespace App\Http\Middleware\Auth;

use App\Models\User;
use App\Models\UserAuth;
use Closure;

class CheckPrimaryAuth
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
        try{
            $user = auth('api')->user();

            if (!$user || ($user['user_auth_level'] < User::user_auth_level_primary)){
                return api_response()->error(1033,'请先完成初级认证');
            }
        }catch (\Exception $exception){
            return api_response()->error(0,'网络繁忙');
        }

        return $next($request);
    }
}
