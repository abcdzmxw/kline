<?php

namespace App\Http\Middleware\Wallet;

use App\Models\ContractPair;
use App\Models\SustainableAccount;
use Closure;

class CheckContractAccount
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
            // 创建合约账户
            #永续合约账户
            $result = SustainableAccount::query()->where(['user_id'=>$user['user_id']])->exists();
            if(!$result){
                SustainableAccount::query()->create([
                    'user_id' => $user['user_id'],
                    'coin_id' => 1,
                    'coin_name' => 'USDT',
                    'margin_name' => 'USDT',
                ]);
            }
        }
        return $next($request);

//        $user = auth('api')->user();
//        if($user){
//            // 更新或者创建合约账户
//            $contracts = ContractPair::query()->where('status',1)->get();
//            foreach ($contracts as $contract){
//                #永续合约账户
//                $result = SustainableAccount::query()->where(['user_id'=>$user['user_id'],'coin_id'=>$contract['contract_coin_id']])->first();
//                if(blank($result)){
//                    SustainableAccount::query()->create([
//                        'user_id' => $user['user_id'],
//                        'contract_id' => $contract['id'],
//                        'coin_id' => $contract['contract_coin_id'],
//                        'coin_name' => $contract['contract_coin_name'],
//                        'margin_name' => $contract['type'],
//                    ]);
//                }
//            }
//        }
//        return $next($request);
    }
}
