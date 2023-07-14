<?php

namespace App\Console\Commands;

use App\Models\OtcOrder;
use App\Services\OtcService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckOtcOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-otc-order';

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
        $orders = OtcOrder::query()->where('status', OtcOrder::status_wait_pay)->where(function ($query) {
            $query->where('overed_at', '<', Carbon::now()->toDateTimeString());
        })->cursor();

        foreach ($orders as $order){
            try{
                $user_id = $order['user_id'];
                $params = ['order_id'=>$order['id']];
                (new OtcService())->cancelOrder($user_id,$params);
            }catch (\Exception $exception){
                info($exception);
                continue;
            }
        }
    }
}
