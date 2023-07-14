<?php

namespace App\Jobs;

use App\Models\OptionSceneOrder;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class OptionOrderDelivery implements ShouldQueue
{
    //期权订单结算

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scene_order;
    protected $delivery_result;

    /**
     * Create a new job instance.
     * @param $scene_order
     * @param $delivery_result
     * @return void
     */
    public function __construct($scene_order,$delivery_result)
    {
        $this->scene_order = $scene_order;
        $this->delivery_result = $delivery_result;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (blank($this->scene_order) || $this->scene_order->status !== OptionSceneOrder::status_wait || !blank($this->scene_order->delivery_time)) {
            return;
        }

        try {
            DB::beginTransaction();

            //更新订单
            $this->scene_order->update([
                'status' => OptionSceneOrder::status_delivered,
                'delivery_time' => time(),
            ]);

            if($this->scene_order->up_down == $this->delivery_result['delivery_up_down'] && $this->scene_order->range <= $this->delivery_result['delivery_range']){
                info('option_order_delivery:' . $this->scene_order->order_id);
                $user = User::query()->find($this->scene_order->user_id);

                $amount = PriceCalculate($this->scene_order->bet_amount,'*',$this->scene_order->odds,8);
                // $amount = $this->scene_order->bet_amount + $amount;
                $user->update_wallet_and_log($this->scene_order->bet_coin_id,'usable_balance',$amount,UserWallet::asset_account,'option_order_delivery');
            }

            DB::commit();
        } catch (\Exception $e) {
            info($e);
            DB::rollback();
//            throw $e;
        }

    }
}
