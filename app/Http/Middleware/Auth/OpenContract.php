<?php

namespace App\Http\Middleware\Auth;

use App\Models\UserAgreementLog;
use Closure;

class OpenContract
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

            if($user){
                $open = UserAgreementLog::query()->where(['type'=>'contract','user_id'=>$user['user_id']])->first();
                if(blank($open)) return api_response()->error(1051,'永续合约未开通');
            }

        }catch (\Exception $exception){
            return api_response()->error(0,'网络繁忙');
        }

        return $next($request);
    }
}
