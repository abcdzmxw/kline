<?php

namespace App\Http\Controllers\Appapi\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserWalletLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GeneralizeController extends ApiController
{
    // 推广

    //获取推广信息
    public function getGeneralizeInfo()
    {
        $user = $this->current_user();

        $data = [];

        $log_types = ['dividend'];
        $logs = UserWalletLog::query()->where('user_id',$user['user_id'])
            ->where('rich_type','usable_balance')
            ->whereIn('log_type',$log_types)
            ->get()->groupBy('coin_name');
        $amt = 0;
        foreach ($logs as $coin_name => $items){
            if($coin_name == 'USDT'){
                $price = 1;
            }else{
                $ticker = Cache::store('redis')->get('market:' . strtolower($coin_name) . 'usdt' . '_detail');
                $price = $ticker['close'] ?? 1;
            }
            $amount = abs($items->sum('amount'));
            $amt += PriceCalculate($amount,'*',$price,4);
        }

        $data['invite_user_num'] = User::query()->where('pid',$user['user_id'])->count();
        $data['invite_dividend'] = $amt;
        $data['invite_code'] = $user['invite_code'];
        $data['invite_url'] = config('app.h5_url') . "/#/pages/reg/index?invite_code=" . $user['invite_code'];

        return $this->successWithData($data);
    }

    //推广邀请记录
    public function generalizeList(Request $request)
    {
        $user = $this->current_user();

        $per_page = $request->input('per_page',10);

//        $data = User::query()->where('referrer',$user['user_id'])->paginate();
        $data = User::query()->where('pid',$user['user_id'])->paginate($per_page);
        return $this->successWithData($data);
    }

    //推广返佣记录
    public function generalizeRewardLogs(Request $request)
    {
        $user = $this->current_user();

        $log_types = ['dividend'];

        $logs = UserWalletLog::query()->where('user_id',$user['user_id'])
            ->where('rich_type','usable_balance')
            ->whereIn('log_type',$log_types)
            ->paginate();

        return $this->successWithData($logs);
    }

    //申请代理
    public function applyAgency(Request $request)
    {

    }

}
