<?php


namespace App\Http\Middleware\Wallet;

use App\Models\OtcAccount;
use App\Models\OtcCoinlist;
use Closure;
use Illuminate\Support\Facades\DB;

class CheckOtcAccount
{
    /**
     * Handle an incoming request.
     * 检查用户合约账户 不存在则创建
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth('api')->user();
        if($user){
            $otcMarket = OtcCoinlist::query()->where('status',1)->get();
            DB::beginTransaction();
            try{
                foreach ($otcMarket as $coin){
                    // 创建法币账户
                    $result = OtcAccount::query()->where(['user_id'=>$user['user_id'],'coin_id'=>$coin['coin_id']])->exists();
                    if(!$result){
                        OtcAccount::query()->create([
                            'user_id'=>$user['user_id'],
                            'coin_id'=>$coin['coin_id'],
                            'coin_name'=>$coin['coin_name'],
                        ]);
                    }
                }
                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
            }
        }
        return $next($request);
    }
}
