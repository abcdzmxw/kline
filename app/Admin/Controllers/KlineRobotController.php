<?php

namespace App\Admin\Controllers;

use App\Handlers\Kline;
use App\Models\CoinConfig;
use App\Models\CoinData;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class KlineRobotController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->title('行情控制')
            ->body(view('admin.klineRobot'));
//        return $content->full()->body(view('admin.klineRobot'));
//        return view('admin.klineRobot');
    }

    public function kline()
    {
        return view('admin.kline');
    }

    public function getKlineConfig(Request $request)
    {
        $datetime = $request->input('datetime');
        $coin = $request->input('coin',1);
        $symbols = config('coin.exchange_symbols');
        $coins = [];
        $kk = 1;
        foreach ($symbols as $symbol => $model){
            $coins[$kk] = ['symbol' => $symbol, 'model' => $model];
            $kk++;
        }
        $model = $coins[$coin]['model'];
        $symbol = $coins[$coin]['symbol'];

        $period = $request->input('period',30);

        if(empty($datetime)) return [];
//        dd($datetime,Carbon::parse($datetime)->addDay()->toDateTimeString());
        $config = CoinConfig::query()->where('datetime',$datetime)->where('symbol',$symbol)->first();
        if($period == 1){
            $klineData = $model::query()
                ->where('datetime','>=',$datetime)
                ->where('datetime','<',Carbon::parse($datetime)->addDay()->toDateTimeString())
                ->where('is_1min',1)
                ->select(['Date as timestamp','datetime','Open as open','Close as close','High as high','Low as low','Volume as volume','Amount as amount'])
                ->get();
        }else{
            $klineData = $model::query()
                ->where('datetime','>=',$datetime)
                ->where('datetime','<',Carbon::parse($datetime)->addDay()->toDateTimeString())
                ->where('is_30min',1)
                ->select(['Date as timestamp','datetime','Open as open','Close as close','High as high','Low as low','Volume as volume','Amount as amount'])
                ->get();
        }
//        dd($klineData);
        $response = [
            'config' => $config,
            'lists' => [],
            'max' => 0,
            'min' => 1000000,
            'dateKline' => [],
            'klineData' => [],
            'klineFormData' => $klineData,
        ];
        foreach ($klineData as $item){
            $response['lists'][] = [
                ($item['timestamp'] * 1000)- 43200000,$item['close']
            ];
            $response['dateKline'][] = substr($item['datetime'], 11, 5);
            $response['klineData'][] = [
                $item['open'],$item['close'],$item['high'],$item['low']
            ];
            if($item['close'] > $response['max']){
                $response['max'] = $item['close'];
            }
            if($item['close'] < $response['min']){
                $response['min'] = $item['close'];
            }
        }
//        dd($response);
        return $response;
    }

    // 生成K线
    public function generateKline(Request $request)
    {
        $config = $request->all();

        if(empty($config)) return [];
        $klineData = (new Kline())->generateKline2($config);
//        dd($klineData);
        $response = [
            'lists' => [],
            'max' => 0,
            'min' => 1000000,
            'dateKline' => [],
            'klineData' => [],
            'klineFormData' => $klineData,
        ];
        foreach ($klineData as $item){
            $response['lists'][] = [
                $item['timestamp'] * 1000,$item['close']
            ];
            $response['dateKline'][] = substr($item['datetime'], 11, 5);
            $response['klineData'][] = [
                $item['open'],$item['close'],$item['high'],$item['low']
            ];
            if($item['close'] > $response['max']){
                $response['max'] = $item['close'];
            }
            if($item['close'] < $response['min']){
                $response['min'] = $item['close'];
            }
        }
        return $response;
    }

    // 保存K线
    public function kline_save(Request $request)
    {
        $post = $request->all();

        $coin = $request->input('coin',1);
        $symbols = config('coin.exchange_symbols');
        $coins = [];
        $kk = 1;
        foreach ($symbols as $symbol => $model){
            $coins[$kk] = ['symbol' => $symbol, 'model' => $model];
            $kk++;
        }
        $model = $coins[$coin]['model'];
        $symbol = $coins[$coin]['symbol'];

        $config = array_only($post,['datetime','open','close','high','low','min_amount','max_amount']);
        if(empty($config['min_amount'])) $config['min_amount'] = 1000;
        if(empty($config['max_amount'])) $config['max_amount'] = 10000;

        $klineList = $post['klineFormData'];
//dd($config,$klineList);

        DB::beginTransaction();
        try {

            // 保存K线配置
            CoinConfig::query()->updateOrCreate(['datetime'=>$config['datetime'],'symbol'=>$symbol],$config);

            // 保存K线
            foreach ($klineList as $kline){
                if( $kline['timestamp'] < time() ){
                    // continue;
                }
//                $model::query()->updateOrCreate(
//                    ['is_30min' => 1,'Date' => $kline['timestamp']],
//                    [
//                        'Open'      => $kline['open'],
//                        'High'      => $kline['high'],
//                        'Low'       => $kline['low'],
//                        'Close'     => $kline['close'],
//                        'Volume'    => $kline['volume'],
//                        'Amount'    => $kline['amount'],
//                        'datetime'  => $kline['datetime'],
//                    ]
//                );

                $datetime = $kline['datetime'];
                //Y-m-d H：i：s  开始结束时间
                $seconds = 1800;
                $start_date = Carbon::parse($datetime)->toDateTimeString();
                $end_date = Carbon::parse($datetime)->addSeconds($seconds)->toDateTimeString();
                $this->fake_1min_kline(array_merge($kline,['min_amount'=>$config['min_amount'],'max_amount'=>$config['max_amount']]),$start_date,$end_date,$model);
            }

            // 更新一天的K线
            $day_start_date = Carbon::parse($config['datetime'])->toDateTimeString();
            $day_end_date = Carbon::parse($config['datetime'])->addSeconds(86400)->toDateTimeString();
            $this->fake_period_kline('5min',$day_start_date,$day_end_date,$model);
            $this->fake_period_kline('15min',$day_start_date,$day_end_date,$model);
            $this->fake_period_kline('30min',$day_start_date,$day_end_date,$model);
            $this->fake_period_kline('60min',$day_start_date,$day_end_date,$model);
            $this->fake_period_kline('1day',$day_start_date,$day_end_date,$model);
            // 更新周线和月线
            $this->update_kline('1week',$day_start_date,$model);
            $this->update_kline('1mon',$day_start_date,$model);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    // 生成1minK线
    private function fake_1min_kline($config,$start_date,$end_date,$model)
    {
        $decimal = 100000;
        $seconds = 60;
        $flag = 'is_1min';

        $open_price = $config['open'];
        $close_price = $config['close'];
        $high_price = $config['high'];
        $low_price = $config['low'];
        $min_amount = $config['min_amount'];
        $max_amount = $config['max_amount'];
        $start = strtotime($start_date);
        $end = strtotime($end_date);

        $period_seconds = 60;
        $periodCount = 1800 / $period_seconds;
        $unit = custom_number_format(bcMath(($high_price - $low_price) ,$periodCount - 1,'/',8),8);

        $points = linspace($open_price,$close_price,$periodCount);
//dd($open_price,$close_price,$points,$unit);
        // 24小时 周期价格上涨下跌趋势随机 1涨2跌
        $period2_seconds = 60;
        $periodCount2 = 1800 / $period2_seconds;
        $periodsTrend = [];
        for ($i=0;$i<$periodCount2;$i++){
            if($close_price > $open_price){
                $thresholdValue = 60;
            }else{
                $thresholdValue = 40;
            }
            $periodsTrend[$start+($i*$period2_seconds)] = mt_rand(1,100) <= $thresholdValue ? 1 : 2;
        }

        $ups_downs_high     = abs(floor( $unit * $decimal * 10));            //高
        $ups_downs_value    = abs(floor( $unit * 2 *$decimal));            //值
        $ups_downs_low      = 0;              //低

//        dd($open_price,$close_price,$unit,$points,$ups_downs_high,$ups_downs_value);

        $current = $start;
        $kkk = 0;
        while ( $current < $end ){
            echo $current . '--' . $kkk . "\r\n";
            // 获取上一条
            $prev = $model::query()->where($flag,1)->where('Date',$current - $seconds)->first(); // 上一条K线
            if(blank($prev)){
                $price = $open_price * $decimal;
            }else{
                $price = $prev['Close'] * $decimal;
            }

            $close_point = $points[$kkk] * $decimal;

            $amount = mt_rand($min_amount * $decimal,$max_amount * $decimal) / $decimal;
            $volume = bcMath($amount,$price / $decimal,'/');
            $open = $price;
            $up_or_down = mt_rand(1,100);

            $first = array_first($periodsTrend,function($v,$k)use($current,$period2_seconds){
                return $current >= $k && $current < ($k + $period2_seconds);
            });
//            dump($current . '--' . $first);
            if($first == 1){
                $value = 60;
            }else{
                $value = 40;
            }

            // dump($ups_downs_low,$ups_downs_value,$ups_downs_high);
            if ($up_or_down <= $value) {
                // 涨
                $close = mt_rand($close_point,$close_point + mt_rand($ups_downs_low, $ups_downs_high));
                $high = mt_rand($close, $close + mt_rand($ups_downs_low,$ups_downs_value));
                $low = mt_rand($close - mt_rand($ups_downs_low, $ups_downs_value), $close);
            } else {
                // 跌
                $close = mt_rand($close_point - mt_rand($ups_downs_low, $ups_downs_high), $close_point);
                $high = mt_rand($close, $close + mt_rand($ups_downs_value, $ups_downs_high));
                $low = mt_rand($close - mt_rand($ups_downs_low, $ups_downs_value), $close);
            }

            if($current == $start){
                $open = $open_price * $decimal;
            }elseif ($current + $seconds == $end){
                $close = $close_price * $decimal;
            }

            $high = max($open,$close,$high,$low);
            $low = min($open,$close,$high,$low);

//            dd($open,$close,$high,$low,$close_point);

            $open = $open / $decimal;
            $close = $close / $decimal;
            $high = $high / $decimal;
            $low = $low / $decimal;

//            dd($open,$close,$high,$low,$prev->toArray());
            $model::query()->updateOrCreate(
                [   'Date' => $current ,$flag => 1 ],
                [
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
                ]
            );

            $current += $seconds;
            $kkk++;
        }

        return true;
    }

    // 根据1minK线生成其他周期K线
    public function fake_period_kline($period,$start_date,$end_date,$model)
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

        $kkk = 1;
        while ( $current < $end ){
            echo $current . '--' . $kkk . "\r\n";
            $where_start = $current;
            $where_end = $current + $seconds - 60;

            $open = $model::query()->where('is_1min',1)->where('Date',$where_start)->value('Open');
            $close = $model::query()->where('is_1min',1)->where('Date',$where_end)->value('Close');
            $high = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->max('High');
            $low = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->min('Low');
            $volume = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->sum('Volume');
            $amount = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->sum('Amount');

            $model::query()->updateOrCreate(
                [   'Date' => $current ,$flag => 1 ],
                [
                'datetime'=>date('Y-m-d H:i:s',$current),
                'Open'=>$open,
                'Close'=>$close,
                'High'=>$high,
                'Low'=>$low,
                'LastClose'=>$close,
                'Volume'=>$volume,
                'Amount'=>$amount,
            ]);

            $current += $seconds;
            $kkk++;
        }
    }

    // 更新周线和月线
    public function update_kline($period,$start_date,$model)
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
            $open = $model::query()->where('is_1min',1)->where('Date','>=',$where_start)->orderBy('Date','asc')->first()['Open'] ?? null;
            $close = $model::query()->where('is_1min',1)->where('Date','<=',$where_end)->orderBy('Date','desc')->first()['Close'] ?? null;
            $high = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->max('High');
            $low = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->min('Low');
            $volume = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->sum('Volume');
            $amount = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->sum('Amount');

            $model::query()->updateOrCreate(
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
            $open = $model::query()->where('is_1min',1)->where('Date','>=',$where_start)->orderBy('Date','asc')->first()['Open'] ?? null;
            $close = $model::query()->where('is_1min',1)->where('Date','<=',$where_end)->orderBy('Date','desc')->first()['Close'] ?? null;
            $high = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->max('High');
            $low = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->min('Low');
            $volume = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->sum('Volume');
            $amount = $model::query()->where('is_1min',1)->whereBetween('Date',[$where_start,$where_end])->sum('Amount');

            $model::query()->updateOrCreate(
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

    protected function checkTimeInterval($datetime, $price){
        $min5 = CoinData::query()->where('datetime','<=', $datetime)->where('is_5min',1)->orderByDesc('datetime')->first();
        if($min5){
            $this->updateTimeIntervalKline($min5, $price, $datetime ,'5');
        }

        $min15 = CoinData::query()->where('datetime','<=', $datetime)->where('is_15min',1)->orderByDesc('datetime')->first();
        if($min15){
            $this->updateTimeIntervalKline($min15, $price, $datetime ,'15');
        }

        $min30 = CoinData::query()->where('datetime','<=', $datetime)->where('is_30min',1)->orderByDesc('datetime')->first();
        if($min30){
            $this->updateTimeIntervalKline($min30, $price, $datetime ,'30');
        }

        $min60 = CoinData::query()->where('datetime','<=', $datetime)->where('is_1h',1)->orderByDesc('datetime')->first();
        if($min60){
            $this->updateTimeIntervalKline($min60, $price, $datetime ,'60');
        }

        $day = CoinData::query()->where('datetime','<', $datetime)->where('is_day',1)->orderByDesc('datetime')->first();
        if($day){
            $this->updateTimeIntervalKline($day, $price, $datetime ,'day');
        }

        $w = date('w', strtotime($datetime));
        $time = strtotime($datetime);
        if($w > 0){
            $time+=(7-$w)*86400;
        }
        $date = date('Y-m-d 00:00:00', $time);
        $week = CoinData::query()->where('datetime', $date)->where('is_week',1)->orderByDesc('datetime')->first();
        if($week){
            $this->updateTimeIntervalKline($week, $price, $datetime ,'week');
        }

        $month = CoinData::query()->where('datetime','<', $datetime)->where('is_month',1)->orderByDesc('datetime')->first();
        if($month){
            $this->updateTimeIntervalKline($month, $price, $datetime ,'month');
        }
    }

    protected function updateTimeIntervalKline($dataStai, $price, $datetime, $timeInterval)
    {
        if($price>$dataStai->high){
            $dataStai->high = $price+(mt_rand(1,10)/10000);
        }
        if($price<$dataStai->low){
            $dataStai->low = $price-(mt_rand(1,10)/10000);
        }
        $dataStai->save();
    }

}
