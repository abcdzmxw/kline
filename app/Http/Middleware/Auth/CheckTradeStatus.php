<?php

namespace App\Http\Middleware\Auth;

use Closure;

class CheckTradeStatus
{
    /**
     * Handle an incoming request.
     * 交易状态检测
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth('api')->user();
        if(blank($user)) return api_response()->error();

        if ($user->trade_status == 0){
            return api_response()->error(0,'账号交易锁定，请联系客服');
        }

        return $next($request);
    }
}
