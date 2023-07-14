<?php

namespace App\Console\Commands;

use App\Models\DataTkb;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

class FakeKline2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fakeKline2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定时每日伪造币种Kline数据';

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
        // 最后一条1dayK线
        $last = DataTkb::query()->where('is_day',1)->orderByDesc('Date')->first();
        if(blank($last)){
            //Y-m-d H：i：s  开始结束时间
//            $start_date = Carbon::now()->floorDay();
//            $end_date = Carbon::now()->floorDay()->addDays(2);
//            return false;
            $last['datetime'] = Carbon::now()->subDay(1)->toDateTimeString();
            $last['Close'] = 1;
        }
        $last_date = $last['datetime'];
        //Y-m-d H：i：s  开始结束时间
        $start_date = Carbon::parse($last_date)->addDays(1)->toDateTimeString();
        $end_date = Carbon::parse($last_date)->addDays(2)->toDateTimeString();

        $decimal = 100000;
        $min_rate = get_setting_value('min_rate','coin2',0.04);
        $max_rate = get_setting_value('max_rate','coin2',0.08);
        if($min_rate < 0) $min_rate = 0;
        if($max_rate >= 1) $max_rate = 0.99;
        $up_or_down = get_setting_value('up_or_down','coin2',1);
        $rate = mt_rand($min_rate * $decimal,$max_rate * $decimal) / $decimal;
        $change_rate = $up_or_down == 1 ? 1 + $rate : 1 - $rate;
        $base_price = $last['Close'];
        $target_price = PriceCalculate($last['Close'] ,'*', $change_rate,5);

//        dd($start_date,$end_date);

        // 生成一天的所有1minK线 其他周期K线根据1minK线生成
        if($this->fake_1min_kline($change_rate,$base_price,$target_price,$start_date,$end_date)){
            $this->fake_period_kline('5min',$start_date,$end_date);
            $this->fake_period_kline('15min',$start_date,$end_date);
            $this->fake_period_kline('30min',$start_date,$end_date);
            $this->fake_period_kline('60min',$start_date,$end_date);
            $this->fake_period_kline('1day',$start_date,$end_date);

            // $dt = 0 ~ 6 每周日生成1weekK线
            if(Carbon::parse($start_date)->dayOfWeek == 0){
                $this->fake_period_kline('1week',$start_date,$end_date);
            }
            // 每月1号 生成1monK线
            if(Carbon::parse($start_date)->day == 1){
                $this->fake_period_kline('1mon',$start_date,$end_date);
            }

            // 每天更新周线和月线
            $this->update_kline('1week',$start_date);
            $this->update_kline('1mon',$start_date);
        }

    }

    // 生成1minK线
    private function fake_1min_kline($change_rate,$base_price,$target_price,$start_date,$end_date)
    {
        $decimal = 100000;
        $seconds = 60;
        $flag = 'is_1min';

        $is_sharp = get_setting_value('is_sharp','coin2',0);
        if($is_sharp == 1){
            $first_target_price = $base_price = PriceCalculate($base_price ,'+', PriceCalculate(($target_price - $base_price), '*',0.8,8),5);
        }

        $start = strtotime($start_date);
        $end = strtotime($end_date);

        $period_seconds = 60;
        $periodCount = 86400 / $period_seconds;
        $unit = custom_number_format(PriceCalculate(($target_price - $base_price) ,'/', $periodCount,8),8);

        // 24小时 周期价格上涨下跌趋势随机 1涨2跌
        $period2_seconds = 900;
        $periodCount2 = 86400 / $period2_seconds;
        $periodsTrend = [];
        for ($i=0;$i<$periodCount2;$i++){
            if($change_rate > 1){
                $thresholdValue = 60;
            }else{
                $thresholdValue = 40;
            }
            $periodsTrend[$start+($i*$period2_seconds)] = mt_rand(1,100) <= $thresholdValue ? 1 : 2;
//            $periodsTrend[$i] = mt_rand(1,100) <= $thresholdValue ? 1 : 2;
        }
//        dd($periodsTrend);

//        $mu = (abs($change_rate - 1) / 0.05) / 1.5;
//        if($mu < 1) $mu = 1;
//        dd($change_rate,$base_price,$target_price,$mu);
        $ups_downs_high = abs(floor($unit * $decimal * 80));            //高
        $ups_downs_value = abs(floor($unit * 30 *$decimal));            //值
        $ups_downs_low = floor(1);              //低

//        dd($change_rate,$base_price,$target_price,$unit,$ups_downs_high,$ups_downs_value,$ups_downs_low);

        $current = $start;
        $kkk = 1;
//        dd($current,$start,$end);
        while ( $current < $end ){
            echo $current . '--' . $kkk . "\r\n";
            $max = $base_price * ($change_rate + 0.01);
            // 获取上一条
            $prev = DataTkb::query()->where($flag,1)->where('Date',$current - $seconds)->first(); // 上一条K线
            if(blank($prev)){
                $prev = DataTkb::query()->where('is_day',1)->orderByDesc('Date')->first();
            }
            $price = $prev['Close'] * $decimal;
            $volume = ceil(mt_rand(535012, 2009870) / 1440) . '.' . mt_rand(10524, 99870);
            $amount = ceil(mt_rand(535012, 2009870) / 1440) . '.' . mt_rand(10524, 99870);
            $open = $price;
//            if($prev['Close'] >= $max){
//                $up_or_down = 100;
//            }else{
//                $up_or_down = mt_rand(1,100);
//            }
            $up_or_down = mt_rand(1,100);

            $m = intval(date('i',$current));
            $first = array_first($periodsTrend,function($v,$k)use($current,$period2_seconds){
//                dump($current .'=='. $k);
                return $current >= $k && $current < ($k + $period2_seconds);
            });
//            dump($current . '--' . $first);
            if($first == 1){
                $value = 60;
            }else{
                $value = 40;
            }

            // 第二根线也得是跌
            if($is_sharp == 1 && $current == $start + $seconds) $up_or_down = 100;

            // dump($ups_downs_low,$ups_downs_value,$ups_downs_high);
            if ($up_or_down <= $value) {
                // 涨
                $close = mt_rand($price,$price + mt_rand($ups_downs_low, $ups_downs_high));
                $high = mt_rand($close, $close + mt_rand($ups_downs_low,$ups_downs_value));
                $low = mt_rand($close - mt_rand($ups_downs_low, $ups_downs_value), $close);
            } else {
                // 跌
                $close = mt_rand($price - mt_rand($ups_downs_low, $ups_downs_high), $price);
                $high = mt_rand($close, $close + mt_rand($ups_downs_value, $ups_downs_high));
                $low = mt_rand($close - mt_rand($ups_downs_low, $ups_downs_value), $close);
            }

            // 大幅拉升开关
            if($is_sharp == 1 && $current == $start){
                $amend = $first_target_price * $decimal - $close;
                $close = $first_target_price * $decimal;
                $high = $high + $amend;
                $low = $low + $amend;
            }
            if($is_sharp == 1){
                if($change_rate > 1){
                    $min = $first_target_price * $decimal;
                    $max = $target_price * $decimal;

                    if($close < $min){
                        $amend = $min - $close;
                        $close = $min;
                        $high = $high + $amend;
                        $low = $low + $amend;
                    }
                }else{
                    $min = $target_price * $decimal;
                    $max = $first_target_price * $decimal;

                    if($close > $max){
                        $amend = $close - $max;
                        $close = $max;
                        $high = $high - $amend;
                        $low = $low - $amend;
                    }
                    if($close <= 0){
                        $amend = $min - $close;
                        $close = $min;
                        $high = $high + $amend;
                        $low = $low + $amend;
                    }
                }
            }

//            if($current + $seconds == $end){
//                if($close > $target_price * $decimal){
//                    $close = $target_price * $decimal;
//                    $amend = $close - $target_price * $decimal;
//                    $high = $high - $amend;
//                    $low = $low - $amend;
//                }else{
//                    $close = $target_price * $decimal;
//                    $amend = $target_price * $decimal - $close;
//                    $high = $high + $amend;
//                    $low = $low + $amend;
//                }
//            }else{
//                if($close > $max * $decimal){
//                    $amend = $close - $max * $decimal;
//                    $close = $max * $decimal;
//                    $high = $high - $amend;
//                    $low = $low - $amend;
//                }
//            }

//            if($close > $max * $decimal){
//                $amend = $close - $max * $decimal;
//                $close = $max * $decimal;
//                $high = $high - $amend;
//                $low = $low - $amend;
//            }

            $high = max($open,$close,$high,$low);
            $low = min($open,$close,$high,$low);

            $open = PriceCalculate($open ,'/', $decimal,5);
            $close = PriceCalculate($close ,'/', $decimal,5);
            $high = PriceCalculate($high ,'/', $decimal,5);
            $low = PriceCalculate($low ,'/', $decimal,5);

//            dd($open,$close,$high,$low,$prev->toArray());
            DataTkb::query()->create([
                'Date'=>$current,
                'datetime'=>date('Y-m-d H:i:s',$current),
                'Open'=>$open,
                'Close'=>$close,
                'High'=>$high,
                'Low'=>$low,
                'LastClose'=>$close,
                'Volume'=>$volume,
                'Amount'=>$amount,
                $flag => 1,
            ]);

            $current += $seconds;
            $kkk++;
        }

        return true;
    }

    // 根据1minK线生成其他周期K线
    public function fake_period_kline($period,$start_date,$end_date)
    {
        $periods = [
            '1min' => [60,'is_1min'],
            '5min' => [300,'is_5min'],
            '15min' => [900,'is_15min'],
            '30min' => [1800,'is_30min'],
            '60min' => [3600,'is_1h'],
            '1day' => [86400,'is_day'],
            '1week' => [604800,'is_week'],
            '1mon' => [2592000,'is_month'],
        ];
        $seconds = $periods[$period][0] ?? 60;
        $flag = $periods[$period][1] ?? 'is_1min';

        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $current = $start;

        if($period == '1week'){
            $where_start = Carbon::parse($start_date)->startOfWeek(Carbon::SUNDAY)->getTimestamp();
            $where_end = Carbon::parse($start_date)->endOfWeek(Carbon::SUNDAY)->getTimestamp();
            $open = DataTkb::query()->where('is_1min',1)->where('Date','>=',$where_start)->orderBy('Date','asc')->first()['Open'] ?? null;
            $close = DataTkb::query()->where('is_1min',1)->where('Date','<=',$where_end)->orderBy('Date','desc')->first()['Close'] ?? null;
            $high = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->max('High');
            $low = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->min('Low');
            $volume = mt_rand(535012, 2009870) . '.' . mt_rand(10524, 99870);
            $amount = mt_rand(535012, 2009870) . '.' . mt_rand(10524, 99870);

            DataTkb::query()->updateOrCreate(
                [
                    'Date'=>$where_start,
                    $flag => 1,
                ],
                [
                    'datetime'=>date('Y-m-d H:i:s',$where_start),
                    'Open'=>$open,
                    'Close'=>$close,
                    'High'=>$high,
                    'Low'=>$low,
                    'LastClose'=>$close,
                    'Volume'=>$volume,
                    'Amount'=>$amount,
                ]
            );
        }elseif($period == '1mon'){
            $where_start = Carbon::parse($start_date)->startOfMonth()->getTimestamp();
            $where_end = Carbon::parse($start_date)->endOfMonth()->getTimestamp();
            $open = DataTkb::query()->where('is_1min',1)->where('Date','>=',$where_start)->orderBy('Date','asc')->first()['Open'] ?? null;
            $close = DataTkb::query()->where('is_1min',1)->where('Date','<=',$where_end)->orderBy('Date','desc')->first()['Close'] ?? null;
            $high = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->max('High');
            $low = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->min('Low');
            $volume = mt_rand(535012, 2009870) . '.' . mt_rand(10524, 99870);
            $amount = mt_rand(535012, 2009870) . '.' . mt_rand(10524, 99870);

            DataTkb::query()->updateOrCreate(
                [
                    'Date'=>$where_start,
                    $flag => 1,
                ],
                [
                    'datetime'=>date('Y-m-d H:i:s',$where_start),
                    'Open'=>$open,
                    'Close'=>$close,
                    'High'=>$high,
                    'Low'=>$low,
                    'LastClose'=>$close,
                    'Volume'=>$volume,
                    'Amount'=>$amount,
                ]
            );
        }else{
            $kkk = 1;
            while ( $current < $end ){
                echo $current . '--' . $kkk . "\r\n";
                $where_start = $current;
                $where_end = $current + $seconds - 60;

                $open = DataTkb::query()->where('is_1min',1)->where('Date',$where_start)->value('Open');
                $close = DataTkb::query()->where('is_1min',1)->where('Date',$where_end)->value('Close');
                $high = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->max('High');
                $low = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->min('Low');
                $volume = ceil(mt_rand(535012, 2009870) / (86400/$seconds)) . '.' . mt_rand(10524, 99870);
                $amount = ceil(mt_rand(535012, 2009870) / (86400/$seconds)) . '.' . mt_rand(10524, 99870);

                DataTkb::query()->create([
                    'Date'=>$current,
                    'datetime'=>date('Y-m-d H:i:s',$current),
                    'Open'=>$open,
                    'Close'=>$close,
                    'High'=>$high,
                    'Low'=>$low,
                    'LastClose'=>$close,
                    'Volume'=>$volume,
                    'Amount'=>$amount,
                    $flag => 1,
                ]);

                $current += $seconds;
                $kkk++;
            }
        }
    }

    // 更新周线和月线
    public function update_kline($period,$start_date)
    {
        $periods = [
            '1min' => [60,'is_1min'],
            '5min' => [300,'is_5min'],
            '15min' => [900,'is_15min'],
            '30min' => [1800,'is_30min'],
            '60min' => [3600,'is_1h'],
            '1day' => [86400,'is_day'],
            '1week' => [604800,'is_week'],
            '1mon' => [2592000,'is_month'],
        ];
        $seconds = $periods[$period][0] ?? 60;
        $flag = $periods[$period][1] ?? 'is_1min';

        if($period == '1week'){
            $where_start = Carbon::parse($start_date)->startOfWeek(Carbon::SUNDAY)->getTimestamp();
            $where_end = Carbon::parse($start_date)->endOfWeek(Carbon::SUNDAY)->getTimestamp();
            $open = DataTkb::query()->where('is_1min',1)->where('Date','>=',$where_start)->orderBy('Date','asc')->first()['Open'] ?? null;
            $close = DataTkb::query()->where('is_1min',1)->where('Date','<=',$where_end)->orderBy('Date','desc')->first()['Close'] ?? null;
            $high = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->max('High');
            $low = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->min('Low');
            $volume = mt_rand(535012, 2009870) . '.' . mt_rand(10524, 99870);
            $amount = mt_rand(535012, 2009870) . '.' . mt_rand(10524, 99870);

            DataTkb::query()->updateOrCreate(
                [
                    'Date'=>$where_start,
                    $flag => 1,
                ],
                [
                    'datetime'=>date('Y-m-d H:i:s',$where_start),
                    'Open'=>$open,
                    'Close'=>$close,
                    'High'=>$high,
                    'Low'=>$low,
                    'LastClose'=>$close,
                    'Volume'=>$volume,
                    'Amount'=>$amount,
                ]
            );
        }elseif ($period == '1mon'){
            $where_start = Carbon::parse($start_date)->startOfMonth()->getTimestamp();
            $where_end = Carbon::parse($start_date)->endOfMonth()->getTimestamp();
            $open = DataTkb::query()->where('is_1min',1)->where('Date','>=',$where_start)->orderBy('Date','asc')->first()['Open'] ?? null;
            $close = DataTkb::query()->where('is_1min',1)->where('Date','<=',$where_end)->orderBy('Date','desc')->first()['Close'] ?? null;
            $high = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->max('High');
            $low = DataTkb::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->min('Low');
            $volume = mt_rand(535012, 2009870) . '.' . mt_rand(10524, 99870);
            $amount = mt_rand(535012, 2009870) . '.' . mt_rand(10524, 99870);

            DataTkb::query()->updateOrCreate(
                [
                    'Date'=>$where_start,
                    $flag => 1,
                ],
                [
                    'datetime'=>date('Y-m-d H:i:s',$where_start),
                    'Open'=>$open,
                    'Close'=>$close,
                    'High'=>$high,
                    'Low'=>$low,
                    'LastClose'=>$close,
                    'Volume'=>$volume,
                    'Amount'=>$amount,
                ]
            );
        }
    }

}
