<?php

namespace App\Listeners;

use App\Events\UserRegisterEvent;
use App\Models\Coins;
use App\Models\User;
use App\Models\UserWallet;
use App\Notifications\CommonNotice;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserRegisterListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserRegisterEvent  $event
     * @return void
     */
    public function handle(UserRegisterEvent $event)
    {
        $user = $event->user;

        $user = User::query()->find($user['user_id']);
        if(blank($user)) return ;

        // 注册赠送STAI
        $send_amount = get_setting_value('register_send_stai',100);
        $coin_id = 6;
        $content = 'registered successfully.';
//        if($send_amount > 0){
//            $user->update_wallet_and_log($coin_id,'usable_balance',$send_amount,UserWallet::asset_account,'register_send_stai');
//            $content = '恭喜您注册成功,新用户赠送'.$send_amount.'STAI';
//        }

        // 发送消息通知
        $user->notify(new CommonNotice(['title'=>'registered successfully','content'=>$content]));
    }
}
