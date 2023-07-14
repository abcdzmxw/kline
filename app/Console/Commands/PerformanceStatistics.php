<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\ContractEntrust;
use App\Models\OptionSceneOrder;
use App\Models\Performance;
use App\Models\UserSubscribeRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PerformanceStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '代理业绩统计';

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
        $startDate = Carbon::now();
//        $startDate = '2021-06-18 00:00:00';
        $agents = Agent::query()->where("is_agency",1)->cursor();
        foreach ($agents as $agent){
            echo $agent['id'] . "\r\n";

            $aids = Agent::getBaseAgentIds($agent['id']);
            $start_time = Carbon::parse($startDate)->startOfWeek(Carbon::MONDAY)->toDateTimeString();
            $end_time = Carbon::parse($startDate)->endOfWeek(Carbon::SUNDAY)->toDateTimeString();

            $subscribe_performance = $this->get_subscribe_performance($aids,$start_time,$end_time);
            $contract_performance = $this->get_contract_performance($aids,$start_time,$end_time);
            $option_performance = $this->get_option_performance($aids,$start_time,$end_time);
            $subscribe_rebate_rate = $agent['subscribe_rebate_rate'] ?? 0.85;
            $contract_rebate_rate = $agent['contract_rebate_rate'] ?? 0.85;
            $option_rebate_rate = $agent['option_rebate_rate'] ?? 0.85;
            $subscribe_rebate = PriceCalculate($subscribe_performance,'*',$subscribe_rebate_rate,8);
            $contract_rebate = PriceCalculate($contract_performance,'*',$contract_rebate_rate,8);
            $option_rebate = PriceCalculate($option_performance,'*',$option_rebate_rate,8);

            Performance::query()->updateOrCreate(
                ['aid' => $agent['id'],'start_time' => $start_time,'end_time'=>$end_time],
                [
                    'subscribe_performance' => $subscribe_performance,
                    'contract_performance' => $contract_performance,
                    'option_performance' => $option_performance,
                    'subscribe_rebate_rate' => $subscribe_rebate_rate,
                    'contract_rebate_rate' => $contract_rebate_rate,
                    'option_rebate_rate' => $option_rebate_rate,
                    'subscribe_rebate' => $subscribe_rebate,
                    'contract_rebate' => $contract_rebate,
                    'option_rebate' => $option_rebate,
                ]
            );
        }
    }

    // 申购业绩
    private function get_subscribe_performance($aids,$start_time,$end_time)
    {
        $start = strtotime($start_time);
        $end = strtotime($end_time);

        return UserSubscribeRecord::query()->whereHas('user',function ($q)use($aids){
                    $q->where('is_system',0)->whereIn('referrer',$aids);
                })
                ->whereBetween('subscription_time',[$start,$end])
                ->where('payment_currency','USDT')
                ->sum('payment_amount');
    }

    // 合约业绩
    private function get_contract_performance($aids,$start_time,$end_time)
    {
        $profit = ContractEntrust::query()
            ->where('order_type',2)
            ->whereHas('user',function ($q)use($aids){
                $q->where('is_system',0)->whereIn('referrer',$aids);
            })
            ->whereDate('created_at','>=',$start_time)->whereDate('created_at','<=',$end_time)
            ->where('status',ContractEntrust::status_completed)
            ->sum('profit');

        if($profit < 0){
            return abs($profit);
        }else{
            return 0;
        }
    }

    // 期权业绩
    private function get_option_performance($aids,$start_time,$end_time)
    {
        // 这里分成2部分 用户亏的直接累加  用户赚的需要减去赌注=平台亏损

        $builder = OptionSceneOrder::query()->whereHas('user',function ($q)use($aids){
                    $q->where('is_system',0)->whereIn('referrer',$aids);
                })
                ->whereDate('created_at','>=',$start_time)->whereDate('created_at','<=',$end_time)
                ->where('bet_coin_name','USDT')->where('status',2);

        $sum1 = (clone $builder)->where('delivery_amount','<',0)->sum('delivery_amount');   // 用户亏损
        $sum2 = (clone $builder)->where('delivery_amount','>',0)->sum('delivery_amount');   // 用户盈利
        $sum3 = (clone $builder)->where('delivery_amount','>',0)->sum('bet_amount');        // 用户押注

        return abs($sum1 + ($sum2 - $sum3));
    }

}
