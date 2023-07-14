<?php

namespace App\Console\Commands;

use App\Models\InsideTradeSell;
use App\Models\User;
use App\Services\InsideTradeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CancelSellEntrust extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancelSellEntrust';

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
        // 委托超时关闭时间
        $cancel_time = intval(get_setting_value('order_ttl','exchange',120) * 60);

        $orders = InsideTradeSell::query()
            ->whereIn('status',[InsideTradeSell::status_wait,InsideTradeSell::status_trading])
            ->where('created_at','<', Carbon::now()->subSeconds($cancel_time)->toDateTimeString())
            ->cursor();

        foreach ($orders as $order) {
            $user = User::query()->find($order['user_id']);
            if(!blank($user)){
                (new InsideTradeService())->cancelEntrust($user,$order);
            }
        }
    }
}
