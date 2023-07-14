<?php

namespace App\Http\Middleware\Wallet;

use App\Models\Coins;
use App\Models\UserWallet;
use Closure;
use Illuminate\Support\Facades\DB;

class CheckUserWallet
{
    /**
     * Handle an incoming request.
     * 检查用户资金账户 不存在则创建
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth('api')->user();

        if($user){
            $coins = Coins::query()->where(['status'=>1])->get();

            DB::beginTransaction();
            try{
                foreach($coins as $coin)
                {
                    $result = UserWallet::query()->where(['user_id'=>$user['user_id'],'coin_id'=>$coin['coin_id']])->exists();
                    if(!$result){
                        UserWallet::query()->create([
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
