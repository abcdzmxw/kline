<?php

namespace App\Console\Commands;

use App\Models\InsideTradeBuy;
use App\Models\InsideTradeDealRobot;
use App\Models\InsideTradeOrder;
use App\Models\InsideTradeSell;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\InsideTradeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DealRobot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dealRobot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '成交机器人 用户币币交易下委托单一定时间没有成交时 系统自动下单成交';

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
     * @throws \Exception
     */
    public function handle()
    {
        // 系统自动成交时间
//        $deal_time = intval(get_setting_value('deal_time','exchange',10) * 60);
        $deal_time = 10;

        $buy_orders = InsideTradeBuy::query()
            ->whereIn('status',[InsideTradeBuy::status_wait,InsideTradeBuy::status_trading])
            ->where('created_at','<', Carbon::now()->subSeconds($deal_time)->toDateTimeString())
            ->cursor();
        foreach ($buy_orders as $order) {
            $deal_robot = InsideTradeDealRobot::query()->where('status',1)->where('symbol',$order['symbol'])->first();
            if(!blank($deal_robot)){
                $flag = false;
                if( $order['type'] == 1 || $order['type'] == 3 ){
                    // 获取最新一条成交记录 即实时最新价格
                    $key = 'market:' . strtolower(str_before($deal_robot['symbol'],'/') . str_after($deal_robot['symbol'],'/')) . '_newPrice';
                    $realtime_price = Cache::store('redis')->get($key)['price'];
                    $min = $realtime_price - ($deal_robot->bid_minus_unit * $deal_robot->bid_minus_count);
                    $max = $realtime_price + ($deal_robot->bid_plus_unit * $deal_robot->bid_plus_count);
                    if($min <= $order['entrust_price'] && $order['entrust_price'] <= $max) $flag = true;
                }else{
                    $flag = true;
                }

                try {
                    if($flag){
                        (new InsideTradeService())->handleBuyOrder($order);
                    }
                }catch (\Exception $e){
                    info($e);
                    continue;
                }
            }
        }

        $sell_orders = InsideTradeSell::query()
            ->whereIn('status',[InsideTradeSell::status_wait,InsideTradeSell::status_trading])
            ->where('created_at','<', Carbon::now()->subSeconds($deal_time)->toDateTimeString())
            ->cursor();
        foreach ($sell_orders as $order) {
            $deal_robot = InsideTradeDealRobot::query()->where('status',1)->where('symbol',$order['symbol'])->first();
            if(!blank($deal_robot)){
                $flag = false;
                if( $order['type'] == 1 || $order['type'] == 3 ){
                    // 获取最新一条成交记录 即实时最新价格
                    $key = 'market:' . strtolower(str_before($deal_robot['symbol'],'/') . str_after($deal_robot['symbol'],'/')) . '_newPrice';
                    $realtime_price = Cache::store('redis')->get($key)['price'];
                    $min = $realtime_price - ($deal_robot->ask_minus_unit * $deal_robot->ask_minus_count);
                    $max = $realtime_price + ($deal_robot->ask_plus_unit * $deal_robot->ask_plus_count);
                    if($min <= $order['entrust_price'] && $order['entrust_price'] <= $max) $flag = true;
                }else{
                    $flag = true;
                }

                try {
                    if($flag){
                        (new InsideTradeService())->handleSellOrder($order);
                    }
                }catch (\Exception $e){
                    info($e);
                    continue;
                }

            }
        }
    }

}
