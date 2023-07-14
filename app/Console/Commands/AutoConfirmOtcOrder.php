<?php

namespace App\Console\Commands;

use App\Models\OtcOrder;
use App\Services\OtcService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoConfirmOtcOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-confirm-otc-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $confirm_overtime = intval(get_setting_value('otc_order_confirm','otc',10) * 3600);

        $orders = OtcOrder::query()->where('status', OtcOrder::status_wait_confirm)->where(function ($query) use ($confirm_overtime) {
            $query->where('pay_time', '<', Carbon::now()->subSeconds($confirm_overtime)->timestamp);
        })->cursor();

        foreach ($orders as $order){
            try{
                $user_id = $order['trans_type'] == 1 ? $order['other_uid'] : $order['user_id'];
                $params = ['order_id'=>$order['id']];
                (new OtcService())->confirmOrder($user_id,$params);
            }catch (\Exception $exception){
                info($exception);
                continue;
            }
        }
    }
}
