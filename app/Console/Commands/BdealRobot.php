<?php

namespace App\Console\Commands;

use App\Models\InsideTradeBuy;
use App\Models\InsideTradeDealRobot;
use App\Models\InsideTradeSell;
use App\Services\InsideTradeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class BdealRobot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BdealRobot';

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
        $deal_time = 1;

        while (true) {
            $buy_orders = InsideTradeBuy::query()
                ->whereIn('status',[InsideTradeBuy::status_wait,InsideTradeBuy::status_trading])
                ->where('created_at','<', Carbon::now()->subSeconds($deal_time)->toDateTimeString())
                ->cursor();
            foreach ($buy_orders as $order) {
                
                
                $flag = false;
                if( $order['type'] == 1 || $order['type'] == 3 ){
                    // 获取最新一条成交记录 即实时最新价格
                    $key = 'market:' . strtolower(str_before($order['symbol'],'/') . str_after($order['symbol'],'/')) . '_newPrice';
                    $realtime_price = Cache::store('redis')->get($key)['price'];
                    if($order['entrust_price'] >= $realtime_price) $flag = true;
                }else{
                    $flag = true;
                }

                try {
                    if($flag) (new InsideTradeService())->handleBuyOrder($order);
                }catch (\Exception $e){
                    info($e);
                    continue;
                }
                
            }

            sleep(10);
        }

    }
}
