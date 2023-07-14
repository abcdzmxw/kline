<?php

namespace App\Listeners;

use App\Events\UserUpgradeEvent;
use App\Models\User;
use App\Models\UserGrade;
use App\Models\UserUpgradeLog;
use App\Models\UserWalletLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class UserUpgradeListener implements ShouldQueue
{
    /**
     * Create the event listener.
     * 检测用户升级
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserUpgradeEvent  $event
     * @return void
     */
    public function handle(UserUpgradeEvent $event)
    {
        $user = $event->user;
        $user = User::query()->find($user['user_id']);
        if(blank($user)) return ;

        $old_grade_id = $user['user_grade'];
        //获取可以升的上级会员级别信息 判断是否符合升级条件
        $new_grade_id = $old_grade_id + 1;
        $up_grade = UserGrade::get_grade_info($new_grade_id);
        if(blank($up_grade)) return ;

        //判断会员是否达到升级条件

        //检测用户直接推荐指定级别的下级数量
        if($up_grade['ug_recommend_grade'] && $up_grade['ug_recommend_num']){
            $direct_user_num = User::query()
                ->where('pid',$user['user_id'])
                ->where('user_auth_level',2)
                ->where('user_grade','>=',$up_grade['ug_recommend_grade'])
                ->count();

            if($direct_user_num < $up_grade['ug_recommend_num']) return ; // 推荐指定级别人数未达条件

            if( ($up_grade['ug_direct_vol'] && $up_grade['ug_direct_vol_num']) || ($up_grade['ug_direct_recharge'] && $up_grade['ug_direct_recharge_num']) || $up_grade['ug_total_vol']){
                $direct_users = User::query()
                    ->where('pid',$user['user_id'])
                    ->where('user_auth_level',2)
                    ->where('user_grade','>=',$up_grade['ug_recommend_grade'])
                    ->get();

                if( $up_grade['ug_direct_vol'] && $up_grade['ug_direct_vol_num'] ){
                    // ②直推≥2个账户交易量≥5000usdt
                    $direct_vol_num = 0;
                    foreach ($direct_users as $user){
                        $log_types = ['bet_option'];
                        $vol = $this->countAmount($user,$log_types);
                        if($vol >= $up_grade['ug_direct_vol']){
                            $direct_vol_num++;
                        }
                    }
                    if($direct_vol_num < $up_grade['ug_direct_vol_num']) return ;
                }elseif($up_grade['ug_direct_recharge'] && $up_grade['ug_direct_recharge_num']){
                    // ③≥3个高级矿工累计充值≥5万usdt
                    $direct_recharge_num = 0;
                    foreach ($direct_users as $user){
                        $log_types = ['recharge'];
                        $recharge = $this->countAmount($user,$log_types);
                        if($recharge >= $up_grade['ug_direct_recharge']){
                            $direct_recharge_num++;
                        }
                    }
                    if($direct_recharge_num < $up_grade['ug_direct_recharge_num']) return ;
                }else{
                    // 直推总交易量≥6万usdt
                    $direct_total_vol = 0;
                    foreach ($direct_users as $user){
                        $log_types = ['bet_option'];
                        $direct_total_vol += $this->countAmount($user,$log_types);
                    }
                    if($direct_total_vol < $up_grade['ug_total_vol']) return ;
                }
            }

        }

        //检测用户自身交易量
        if($up_grade['ug_self_vol']){

            $log_types = ['bet_option'];
            $self_vol = $this->countAmount($user,$log_types);

            if($self_vol < $up_grade['ug_self_vol']) return ;
        }

        // 达成升级条件
        $user->update(['user_grade'=>$up_grade['grade_id']]);
        // 记录升级日志
        UserUpgradeLog::query()->create([
            'user_id' => $user['user_id'],
            'user_old_grade' => $old_grade_id,
            'user_new_grade' => $up_grade['grade_id'],
        ]);
    }

    public function countAmount($user,$log_types)
    {
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

        return $amt;
    }

}
