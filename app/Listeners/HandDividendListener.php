<?php

namespace App\Listeners;

use App\Events\HandDividendEvent;
use App\Exceptions\ApiException;
use App\Models\BonusLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class HandDividendListener
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
     * @param  HandDividendEvent  $event
     * @return void
     */
    public function handle(HandDividendEvent $event)
    {
        $order = $event->order;
        $hand = $event->hand;
        if(blank($order)) return ;

        DB::beginTransaction();
        try{

            $bonus_logs = $order->bonus;
            foreach ($bonus_logs as $bonus_log){
                if(!isset($bonus_log['user_id']) || blank($bonus_log['user_id'])) continue;
                if($bonus_log['status'] != BonusLog::status_wait) continue;

                if($hand == 1){
                    $user = User::query()->find($bonus_log['user_id']);
                    $user->update_wallet_and_log($bonus_log['coin_id'],$bonus_log['rich_type'],$bonus_log['amount'],$bonus_log['account_type'],$bonus_log['log_type']);

                    $bonus_log->update(['status'=>BonusLog::status_hand,'hand_time'=>Carbon::now()->toDateTimeString()]);
                }else{
                    $bonus_log->update(['status'=>BonusLog::status_cancel,'hand_time'=>Carbon::now()->toDateTimeString()]);
                }
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            info($e);
        }
    }
}
