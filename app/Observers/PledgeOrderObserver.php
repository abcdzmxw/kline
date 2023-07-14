<?php

namespace App\Observers;

use App\Models\PledgeOrder;
use App\Models\User;
use App\Models\UserGrade;
use App\Models\UserPledgePromotionGrade;
use App\Models\UserUpgradeLog;
use App\Models\UserWalletLog;
use Illuminate\Support\Facades\DB;

class PledgeOrderObserver
{
    /**
     * Handle the pledge order "created" event.
     *
     * @param  \App\Models\PledgeOrder  $pledgeOrder
     * @return void
     */
    public $user;

    public function __construct(
        User $user

    )
    {
        $this->user = $user;
    }

    public function created(PledgeOrder $pledgeOrder)
    {
        $user_id=$pledgeOrder['user_id'];
        //  $user = $this->user;
        $user = User::query()->find($user_id);
        // throw new \Exception('交易id:' . $user_id.'发放奖励失败');
        if(blank($user)) return ;
        
        if($user->is_system==1 || $user->pid==0 || $user->is_agency==1) return ;

        $is_agency_p= User::where('pid', $user->pid)->first()->parent_user->is_agency;
        $is_system_p= User::where('pid', $user->pid)->first()->parent_user->is_system;
        info('is_agency_p:'.$is_agency_p.',is_system_p:'.$is_system_p);
        if($is_agency_p==1 || $is_system_p==1) return ;

        $old_grade_id = User::where('pid', $user->pid)->first()->parent_user->user_pledge_promotion_grade;
        //获取可以升的上级会员级别信息 判断是否符合升级条件
        $new_grade_id = $old_grade_id + 1;
        $up_grade = UserPledgePromotionGrade::get_promotion_grade_info($new_grade_id);
        if(blank($up_grade)) return ;

       // info('user_id:'.$user_id.',grade_id'.$old_grade_id);


        //判断会员是否达到升级条件

        //检测用户直接推荐指定级别的下级数量
        if($up_grade['ug_recommend_grade'] && $up_grade['ug_recommend_num']){
            $direct_user_num = User::query()
                ->where('pid',$user->pid)
                ->where('user_auth_level','>=',1)
                ->where('user_pledge_promotion_grade','>=',$up_grade['ug_recommend_grade'])
                ->count();
          //  info('pid:'.$user->pid.','.'direct_user_num:'.$direct_user_num);
            if($direct_user_num < $up_grade['ug_recommend_num']) return ; // 推荐指定级别人数未达条件
        }
        //升级所需直推用户参与挖矿次数
        if($up_grade['ug_direct_pledge_num'] || $up_grade['ug_direct_pledge_total_num']){

            $direct_users = User::query()
                ->where(['pid'=>$user->pid])
                ->where('user_auth_level',1)
                ->get();

            if( $up_grade['ug_direct_pledge_num']){
                // ②直推每个质押挖矿次数>=2

                $direct_pledge_num = 0;
                foreach ($direct_users as $directuser){
                    //  $log_types = ['bet_option'];
                    $direct_pledge_num1 = DB::table('pledge_order')->where(['user_id' => $directuser->user_id])->count();
                    if($direct_pledge_num1 >= $up_grade['direct_pledge_num']){
                        $direct_pledge_num++;
                 //       info('direct_pledge_num'.$direct_pledge_num);
                    }
                }
                if($direct_pledge_num < $up_grade['ug_direct_pledge_num']) return ;

            }

            elseif( $up_grade['ug_direct_pledge_total_num']) {
                // 直推质押挖矿总次数>=2
                $ug_direct_pledge_total_num = 0;
                foreach ($direct_users as $directuser) {

                    $ug_direct_pledge_total_num += $this->DB::table('pledge_order')->where(['user_id' => $directuser->user_id])->count();
                }
            //    info('ug_direct_pledge_total_num'.$ug_direct_pledge_total_num);
                if ($ug_direct_pledge_total_num < $up_grade['ug_direct_pledge_total_num']) return;
            }
        }

// 达成升级条件
        //  if($direct_user_num>0 && $direct_user_num >= $up_grade['ug_recommend_num']
        //  && $direct_pledge_num >= $up_grade['ug_direct_pledge_num'])
        //  {
        // $user->update(['user_pledge_promotion_grade'=>$up_grade['grade_id']]);
        $user->where('user_id', $user->pid)->update(['user_pledge_promotion_grade'=>$up_grade['grade_id']]);
        //  User::where('status', 1)
        //  ->where('user_id', $user->user_id)
        //   ->update(['user_pledge_promotion_grade'=>$up_grade['grade_id']]);
        // 记录升级日志

        UserUpgradeLog::query()->create([
            'user_id' => $user['pid'],
            'user_old_grade' => $old_grade_id,
            'user_new_grade' => $up_grade['grade_id'],
        ]);
        //   }

    }

    /**
     * Handle the pledge order "updated" event.
     *
     * @param  \App\Models\PledgeOrder  $pledgeOrder
     * @return void
     */
    public function updated(PledgeOrder $pledgeOrder)
    {
        //
    }

    /**
     * Handle the pledge order "deleted" event.
     *
     * @param  \App\Models\PledgeOrder  $pledgeOrder
     * @return void
     */
    public function deleted(PledgeOrder $pledgeOrder)
    {
        //
    }

    /**
     * Handle the pledge order "restored" event.
     *
     * @param  \App\Models\PledgeOrder  $pledgeOrder
     * @return void
     */
    public function restored(PledgeOrder $pledgeOrder)
    {
        //
    }

    /**
     * Handle the pledge order "force deleted" event.
     *
     * @param  \App\Models\PledgeOrder  $pledgeOrder
     * @return void
     */
    public function forceDeleted(PledgeOrder $pledgeOrder)
    {
        //
    }
}
