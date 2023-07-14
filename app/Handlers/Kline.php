<?php


namespace App\Handlers;

use App\Models\TestTradeOrder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Kline
{
    /**** START ****/

    // 后台生成一天的30分钟周期线 然后根据30分钟线随机生成1min线 机器人根据1minK线跑盘

    private $klineData = [];

    public function generateKline2($config)
    {
        if(empty($config)) return false;

        $datetime = $config['datetime'];
        //Y-m-d H：i：s  开始结束时间
        $start_date = Carbon::parse($datetime)->toDateTimeString();
        $end_date = Carbon::parse($datetime)->addDays(1)->toDateTimeString();

        // 生成K线
        $this->fake_period_kline($config,$start_date,$end_date);

        foreach ($this->klineData as &$kline){
            if($kline['low'] < $config['low']){
                $kline['low'] = $config['low'];
            }
            if($kline['high'] > $config['high']){
                $kline['high'] = $config['high'];
            }
        }

        return $this->klineData;
    }

    // 生成30minK线
    private function fake_period_kline($config,$start_date,$end_date)
    {
        $decimal = 100000;
        $seconds = 1800;
        $period = 'is_30min';

        $open_price = $config['open'];
        $close_price = $config['close'];
        $high_price = $config['high'];
        $low_price = $config['low'];
        $min_amount = $config['min_amount'];
        $max_amount = $config['max_amount'];
        $start = strtotime($start_date);
        $end = strtotime($end_date);

//        dd($open_price,$close_price,$high_price,$low_price);

        $period_seconds = 1800;
        $periodCount = 86400 / $period_seconds;
        $unit = custom_number_format(bcMath(($high_price - $low_price) ,$periodCount - 1,'/',8),8);

        // 24小时 周期价格上涨下跌趋势随机 1涨2跌
        $period2_seconds = 1800;
        $periodCount2 = 86400 / $period2_seconds;
        $periodsTrend = [];
        for ($i=0;$i<$periodCount2;$i++){
            if($close_price > $open_price){
                $thresholdValue = 60;
            }else{
                $thresholdValue = 40;
            }
            $periodsTrend[$start+($i*$period2_seconds)] = mt_rand(1,100) <= $thresholdValue ? 1 : 2;
        }

        $ups_downs_high     = abs(floor($unit * $decimal * 8));            //高
        $ups_downs_value    = abs(floor($unit * 3 *$decimal));            //值
        $ups_downs_low      = 0;              //低

//        dd($unit,$ups_downs_high,$ups_downs_value,$ups_downs_low);

        $current = $start;
        $kkk = 1;
        while ( $current < $end ){
//            echo $current . '--' . $kkk . "\r\n";
            // 获取上一条
            $prev_time = $current - $seconds;
            $prev = array_first($this->klineData ?? [],function($v,$k)use($prev_time){
                return $v['timestamp'] == $prev_time;
            });
            if(blank($prev)){
                $price = $open_price * $decimal;
            }else{
                $price = $prev['close'] * $decimal;
            }
            $amount = mt_rand($min_amount * $decimal,$max_amount * $decimal) / $decimal;
            $volume = bcMath($amount,$price,'/');
            $open = $price;
            $up_or_down = mt_rand(1,100);

            $flag = array_first($periodsTrend,function($v,$k)use($current,$period2_seconds){
                return $current >= $k && $current < ($k + $period2_seconds);
            });
//            dump($current . '--' . $flag);
            if($flag == 1){
                $value = 60;
            }else{
                $value = 40;
            }

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

            if($current == $start){
                $open = $open_price * $decimal;
            }elseif ($current + $seconds == $end){
                $close = $close_price * $decimal;
            }

            $high = max($open,$close,$high,$low);
            $low = min($open,$close,$high,$low);

            $open = $open / $decimal;
            $close = $close / $decimal;
            $high = $high / $decimal;
            $low = $low / $decimal;

//            dd($open,$close,$high,$low,$prev->toArray());
            $klineItem = [
                'timestamp'=>$current,
                'datetime'=>date('Y-m-d H:i:s',$current),
                'open'=>$open,
                'close'=>$close,
                'high'=>$high,
                'low'=>$low,
                'volume'=>$volume,
                'amount'=>$amount,
                $period => 1,
            ];
            array_push($this->klineData,$klineItem);

            $current += $seconds;
            $kkk++;
        }

        return true;
    }

    /**** END ****/

    //创建表
    public function createTable($table)
    {
        Schema::create($table, function (Blueprint $table) {
            $table->integer('id')->index()->default(0)->comment('时间周期');
            $table->float('open',20,6)->default(0)->comment('开盘价');
            $table->float('close',20,6)->default(0)->comment('收盘价');
            $table->float('high',20,6)->default(0)->comment('最高价');
            $table->float('low',20,6)->default(0)->comment('最低价');
            $table->float('amount',20,6)->default(0)->comment('交易量(quote)');
            $table->float('vol',20,6)->default(0)->comment('成交量(base)');
            $table->float('price', 20, 6)->default(0)->comment('价格');
            $table->integer('count')->default(0)->comment('成交单数');
            $table->integer('time')->default(0)->comment('时间点记录');
        });
    }

    public function generateKline($period)
    {
        if ($period == '1min'){
            $seconds = 60;
            $open_carbon = Carbon::now()->floorMinute()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorMinute();
        }elseif ($period == '5min'){
            $seconds = 300;
            $open_carbon = Carbon::now()->floorMinute()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorMinute();
        }elseif ($period == '15min'){
            $seconds = 900;
            $open_carbon = Carbon::now()->floorMinute()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorMinute();
        }elseif ($period == '30min'){
            $seconds = 1800;
            $open_carbon = Carbon::now()->floorMinute()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorMinute();
        }elseif ($period == '60min'){
            $seconds = 3600;
            $open_carbon = Carbon::now()->floorHour()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorHour();
        }elseif ($period == '4hour'){
            $seconds = 14400;
            $open_carbon = Carbon::now()->floorHour()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorHour();
        }elseif ($period == '1day'){
            $seconds = 86400;
            $open_carbon = Carbon::now()->floorDay()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorDay();
        }elseif ($period == '1week'){
            $seconds = 604800;
            $open_carbon = Carbon::now()->floorDay()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorDay();
        }elseif ($period == '1mon'){
            $seconds = 2592000;
            $open_carbon = Carbon::now()->floorMonth()->subSeconds($seconds);
            $close_carbon = Carbon::now()->floorMonth();
        }else{
            return;
        }
        $id = $close_carbon->timestamp;
        $open_time = $open_carbon->timestamp;
        $close_time = $close_carbon->timestamp;

        $base_coin = 'lvo';
        $quote_coin = 'usdt';
        $table = strtolower("test_kline_" . $period . "_" . $base_coin . "_" . $quote_coin);
        if (!Schema::hasTable($table)) {
            $this->createTable($table);
        }

        $first = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->orderBy('order_id','asc')->first();
//        if(blank($first)) return;
        if(blank($first)){
            // 时间周期内没有数据
            $prev = DB::table($table)->where('id',$open_time)->first(); // 上一条K线
            if(blank($prev)) return;
            $prev = get_object_vars($prev);
            $data = [
                "id" => $id,
                "open" =>  $prev['open'],
                "close" => (real)$prev['close'],
                "high" => (real)$prev['high'],
                "low" => (real)$prev['low'],
                "price" => (real)$prev['price'],
                "amount" => $prev['amount'],
                "vol" => $prev['vol'] ,
                "count" => $prev['count'],
                "time" => time()
            ];
        }else{
            $last = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->orderBy('order_id','desc')->first();
            $high = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->max('unit_price');
            $low = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->min('unit_price');
            $amount = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->sum('trade_amount');
            $count = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->count();
            $vol = $amount * TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->avg('unit_price');

            $prev = DB::table($table)->where('id',$open_time)->first(); // 上一条K线
            if(blank($prev)){
                $open = (real)$first['unit_price'];
            }else{
                $prev = get_object_vars($prev);
                $open = $prev['close'];
            }

            $data = [
                "id" => $id,
                "open" =>  $open,
                "close" => (real)$last['unit_price'],
                "high" => (real)$high,
                "low" => (real)$low,
                "price" => (real)$last['unit_price'],
                "amount" => $amount,
                "vol" => $vol ,
                "count" => $count,
                "time" => time()
            ];
        }

        // 缓存
        Cache::store('redis')->put('market:' . $base_coin . $quote_coin . '_kline_' . $period,$data);

        // 查询当前周期K线 防止重复插入
        $kline = DB::table($table)->where('id',$id)->first();
        if(blank($kline)){
            DB::table($table)->insert($data);
        }else{
            $kline->update(array_except($data,['id']));
        }

    }

    public function cacheKline($period,$unit_price)
    {
        if ($period == '1min'){
            $seconds = 60;
            $open_carbon = Carbon::now()->floorMinute();
            $close_carbon = Carbon::now()->floorMinute()->addSeconds($seconds);
        }elseif ($period == '5min'){
            $seconds = 300;
            $open_carbon = Carbon::now()->floorMinute();
            $close_carbon = Carbon::now()->floorMinute()->addSeconds($seconds);
        }elseif ($period == '15min'){
            $seconds = 900;
            $open_carbon = Carbon::now()->floorMinute();
            $close_carbon = Carbon::now()->floorMinute()->addSeconds($seconds);
        }elseif ($period == '30min'){
            $seconds = 1800;
            $open_carbon = Carbon::now()->floorMinute();
            $close_carbon = Carbon::now()->floorMinute()->addSeconds($seconds);
        }elseif ($period == '60min'){
            $seconds = 3600;
            $open_carbon = Carbon::now()->floorHour();
            $close_carbon = Carbon::now()->floorHour()->addSeconds($seconds);
        }elseif ($period == '4hour'){
            $seconds = 14400;
            $open_carbon = Carbon::now()->floorHour();
            $close_carbon = Carbon::now()->floorHour()->addSeconds($seconds);
        }elseif ($period == '1day'){
            $seconds = 86400;
            $open_carbon = Carbon::now()->floorDay();
            $close_carbon = Carbon::now()->floorDay()->addSeconds($seconds);
        }elseif ($period == '1week'){
            $seconds = 604800;
            $open_carbon = Carbon::now()->floorDay();
            $close_carbon = Carbon::now()->floorDay()->addSeconds($seconds);
        }elseif ($period == '1mon'){
            $seconds = 2592000;
            $open_carbon = Carbon::now()->floorMonth();
            $close_carbon = Carbon::now()->floorMonth()->addSeconds($seconds);
        }else{
            return;
        }
        $id = $open_carbon->timestamp;
        $open_time = $open_carbon->timestamp;
        $close_time = $close_carbon->timestamp;

        $base_coin = 'lvo';
        $quote_coin = 'usdt';
        $table = strtolower("test_kline_" . $period . "_" . $base_coin . "_" . $quote_coin);
        if (!Schema::hasTable($table)) {
            $this->createTable($table);
        }

        $first = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->orderBy('order_id','asc')->first();
//        if(blank($first)) return;
        if(blank($first)){
            // 时间周期内没有数据
            $prev = DB::table($table)->where('id',$open_time)->first(); // 上一条K线
            if(blank($prev)) return;
            $prev = get_object_vars($prev);
            $data = [
                "id" => $id,
                "open" =>  $prev['open'],
                "close" => (real)$unit_price,
                "high" => (real)$prev['high'],
                "low" => (real)$prev['low'],
                "price" => (real)$prev['price'],
                "amount" => $prev['amount'],
                "vol" => $prev['vol'] ,
                "count" => $prev['count'],
                "time" => time()
            ];
        }else{
            $high = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->max('unit_price');
            $low = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->min('unit_price');
            $amount = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->sum('trade_amount');
            $count = TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->count();
            $vol = $amount * TestTradeOrder::query()->whereBetween('ts',[$open_time,$close_time])->avg('unit_price');

            $prev = DB::table($table)->where('id',$open_time)->first(); // 上一条K线
            if(blank($prev)){
                $open = (real)$first['unit_price'];
            }else{
                $prev = get_object_vars($prev);
                $open = $prev['close'];
            }

            $data = [
                "id" => $id,
                "open" =>  $open,
                "close" => (real)$unit_price,
                "high" => (real)$high,
                "low" => (real)$low,
                "price" => (real)$unit_price,
                "amount" => $amount,
                "vol" => $vol ,
                "count" => $count,
                "time" => time()
            ];
        }

        // 更新缓存
        Cache::store('redis')->put('market:' . $base_coin . $quote_coin . '_kline_' . $period,$data);
    }

}
