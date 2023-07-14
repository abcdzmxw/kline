<?php namespace App\Models;

/*
 * 计划任务
 * */


use Carbon\Carbon;

class LinuxTask
{
    //Y-m-d H：i：s  开始结束时间
    static $home_date = '2020-07-19 00:00:00';
    static $end_date = '2020-10-19 00:00:00';

    //Y-m-d H：i：s  开始结束时间
    static $home_ymd_date = '2020-07-19';
    static $end_ymd_date = '2020-10-19';

    static $time_1min_pointer = 60;         //1分钟时间戳指针
    static $time_5min_pointer = 300;        //5分钟时间戳指针
    static $time_15min_pointer = 900;       //15分钟时间戳指针
    static $time_30min_pointer = 1800;      //30分钟时间戳指针
    static $time_1h_pointer = 3600;         //1小时时间戳指针
    static $time_1day_pointer = 86400;      //1天时间戳指针
    static $time_1week_pointer = 604800;    //1周分钟时间戳指针
    static $time_1month_pointer = 2592000;  //1月分钟时间戳指针

    static $base_price = 2001;              //基础价格

    static $ups_downs = 1;                  //1涨,2跌
    static $ups_downs_sum = 1;              //涨,跌次数

    static $ups_downs_high = 70;            //高
    static $ups_downs_value = 4;            //值
    static $ups_downs_low = 1;              //低

    public function __construct()
    {

        $rate = 0.04;

    }

    static function add_kline_data()
    {

        $home_date = strtotime(static::$home_date);
        $end_date = strtotime(static::$end_date);

        while (true) {

            if ($home_date > $end_date) break;

            $datetime = date('Y-m-d H:i:s', $home_date);
            $Volume = mt_rand(5012, 59870) . '.' . mt_rand(10524, 99870);
            $Symbol = 'stai';
            $Name = 'STAI';
            $kline_data = new KlineData();

            $kline_data->pid = 0;                   //                not null,
            $kline_data->Symbol = $Symbol;          //                not null comment '产品代码',
            $kline_data->Date = $home_date;         //default 0       not null comment '时间戳',
            $kline_data->datetime = $datetime;      //                null,
            $kline_data->Name = $Name;              //default ''      not null comment '产品名称',
            $kline_data->Volume = $Volume;          //default 0.00000 null comment '成交量',
            $kline_data->Amount = 0;                //default 0.00000 null comment '成交额',
            $kline_data->is_1min = 0;               //default 0       null,
            $kline_data->is_5min = 0;               //default 0       null comment '是否是5分钟线',
            $kline_data->is_15min = 0;              //default 0       null,
            $kline_data->is_30min = 0;              //default 0       null,
            $kline_data->is_1h = 0;                 //default 0       null,
            $kline_data->is_day = 0;                //default 0       null,
            $kline_data->is_week = 0;               //default 0       null
            $kline_data->is_month = 1;              //default 0       null
            $kline_data->save();

            echo date('Y-m-d H:i:s', $home_date) . "\n";
            $home_date += static::$time_1month_pointer;
        }
    }

    static function kline_1min()
    {

        $home_ymd_date = strtotime(static::$home_ymd_date);
        $base_price = static::$base_price;

        $ups_downs = static::$ups_downs;                  //1涨,2跌
        $ups_downs_sum = static::$ups_downs_sum;          //涨,跌次数

        while (true) {

            $data = KlineData::where('datetime', "like", date("Y-m-d", $home_ymd_date) . "%")
                ->orderBy('datetime')
                ->where('is_1min', 1)
                ->get();

            if (empty($data->count())) break;

            foreach ($data as $value) {

                if ($ups_downs_sum == 0) {
                    $ups_downs_sum = mt_rand(1, 10);
                    if ($ups_downs == 1) {
                        $ups_downs = 1;
                    } else {
                        $ups_downs = 1;
                    }
                }

                $price = $base_price;

                if ($ups_downs == 1) {

                    $value->Open = $price;
                    $value->High = mt_rand($price, $price + mt_rand(static::$ups_downs_low, static::$ups_downs_high));
                    $value->Low = mt_rand($price - mt_rand(static::$ups_downs_low, static::$ups_downs_high - static::$ups_downs_value), $price);
                    $value->Close = mt_rand($value->Low, $value->High);
                    $value->LastClose = $value->Close;

                } else {

                    $value->Open = $price;
                    $value->High = mt_rand($price, $price + mt_rand(static::$ups_downs_low, static::$ups_downs_high));
                    $value->Low = mt_rand($price - mt_rand(static::$ups_downs_low, static::$ups_downs_high), $price);
                    $value->Close = mt_rand($value->Low, $value->High);
                    $value->LastClose = $value->Close;
                }

                $base_price = $value->Close;

                $value->Open = $value->Open / 100000;
                $value->High = $value->High / 100000;
                $value->Low = $value->Low / 100000;
                $value->Close = $value->Close / 100000;
                $value->LastClose = $value->LastClose / 100000;
                $value->Amount = sprintf("%.5f", $value->Close * $value->Volume);
                $value->save();

                $ups_downs_sum -= 1;
                echo $value->datetime . "," . $value->Open . "," . $value->Close . "," . $value->id . "\n";
            }

            $home_ymd_date += static::$time_1day_pointer;

        }

    }

    static function kline_5min()
    {

        $home_ymd_date = strtotime(static::$home_ymd_date);

        while (true) {

            $data = KlineData::where('datetime', "like", date("Y-m-d", $home_ymd_date) . "%")
                ->orderBy('datetime')
                ->where('is_5min', 1)
                ->get();

            if (empty($data->count())) break;

            foreach ($data as $value) {

                $open = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_5min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Date', 'asc')
                    ->value('Open');
                $close = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_5min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Date', 'desc')
                    ->value('Close');
                $high = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_5min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('High', 'desc')
                    ->value('High');
                $low = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_5min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Low', 'asc')
                    ->value('Low');

                if (empty($open)) {
                    $temp = KlineData::where('datetime', $value->datetime)->where('is_1min', 1)->first();
                    $value->Open = $temp->Open;
                    $value->High = $temp->High;
                    $value->Low = $temp->Low;
                    $value->Close = $temp->Close;
                    $value->LastClose = $temp->Close;
                } else {
                    $value->Open = $open;
                    $value->High = $high;
                    $value->Low = $low;
                    $value->Close = $close;
                    $value->LastClose = $close;
                }

                $value->Amount = sprintf("%.5f", $value->Close * $value->Volume);
                $value->save();

                echo $value->datetime . "," . $value->Open . "," . $value->Close . "," . $value->id . "\n";
            }

            $home_ymd_date += static::$time_1day_pointer;

        }
    }

    static function kline_15min()
    {

        $home_ymd_date = strtotime(static::$home_ymd_date);

        while (true) {

            $data = KlineData::where('datetime', "like", date("Y-m-d", $home_ymd_date) . "%")
                ->orderBy('datetime')
                ->where('is_15min', 1)
                ->get();

            if (empty($data->count())) break;

            foreach ($data as $value) {

                $open = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_15min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Date', 'asc')
                    ->value('Open');
                $close = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_15min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Date', 'desc')
                    ->value('Close');
                $high = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_15min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('High', 'desc')
                    ->value('High');
                $low = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_15min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Low', 'asc')
                    ->value('Low');

                if (empty($open)) {
                    $temp = KlineData::where('datetime', $value->datetime)->where('is_1min', 1)->first();
                    $value->Open = $temp->Open;
                    $value->High = $temp->High;
                    $value->Low = $temp->Low;
                    $value->Close = $temp->Close;
                    $value->LastClose = $temp->Close;
                } else {
                    $value->Open = $open;
                    $value->High = $high;
                    $value->Low = $low;
                    $value->Close = $close;
                    $value->LastClose = $close;
                }

                $value->Amount = sprintf("%.5f", $value->Close * $value->Volume);
                $value->save();

                echo $value->datetime . "," . $value->Open . "," . $value->Close . "," . $value->id . "\n";
            }

            $home_ymd_date += static::$time_1day_pointer;

        }
    }

    static function kline_30min()
    {

        $home_ymd_date = strtotime(static::$home_ymd_date);

        while (true) {

            $data = KlineData::where('datetime', "like", date("Y-m-d", $home_ymd_date) . "%")
                ->orderBy('datetime')
                ->where('is_30min', 1)
                ->get();

            if (empty($data->count())) break;

            foreach ($data as $value) {

                $open = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_30min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Date', 'asc')
                    ->value('Open');
                $close = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_30min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Date', 'desc')
                    ->value('Close');
                $high = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_30min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('High', 'desc')
                    ->value('High');
                $low = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_30min_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Low', 'asc')
                    ->value('Low');

                if (empty($open)) {
                    $temp = KlineData::where('datetime', $value->datetime)->where('is_1min', 1)->first();
                    $value->Open = $temp->Open;
                    $value->High = $temp->High;
                    $value->Low = $temp->Low;
                    $value->Close = $temp->Close;
                    $value->LastClose = $temp->Close;
                } else {
                    $value->Open = $open;
                    $value->High = $high;
                    $value->Low = $low;
                    $value->Close = $close;
                    $value->LastClose = $close;
                }

                $value->Amount = sprintf("%.5f", $value->Close * $value->Volume);
                $value->save();

                echo $value->datetime . "," . $value->Open . "," . $value->Close . "," . $value->id . "\n";
            }

            $home_ymd_date += static::$time_1day_pointer;

        }
    }

    static function kline_1h()
    {

        $home_ymd_date = strtotime(static::$home_ymd_date);

        while (true) {

            $data = KlineData::where('datetime', "like", date("Y-m-d", $home_ymd_date) . "%")
                ->orderBy('datetime')
                ->where('is_1h', 1)
                ->get();

            if (empty($data->count())) break;

            foreach ($data as $value) {

                $open = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_1h_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Date', 'asc')
                    ->value('Open');
                $close = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_1h_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Date', 'desc')
                    ->value('Close');
                $high = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_1h_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('High', 'desc')
                    ->value('High');
                $low = KlineData::where('Date', '<=', $value->Date)
                    ->where('Date', '>', $value->Date - static::$time_1h_pointer)
                    ->where('is_1min', 1)
                    ->orderBy('Low', 'asc')
                    ->value('Low');

                if (empty($open)) {
                    $temp = KlineData::where('datetime', $value->datetime)->where('is_1min', 1)->first();
                    $value->Open = $temp->Open;
                    $value->High = $temp->High;
                    $value->Low = $temp->Low;
                    $value->Close = $temp->Close;
                    $value->LastClose = $temp->Close;
                } else {
                    $value->Open = $open;
                    $value->High = $high;
                    $value->Low = $low;
                    $value->Close = $close;
                    $value->LastClose = $close;
                }

                $value->Amount = sprintf("%.5f", $value->Close * $value->Volume);
                $value->save();

                echo $value->datetime . "," . $value->Open . "," . $value->Close . "," . $value->id . "\n";
            }

            $home_ymd_date += static::$time_1day_pointer;

        }
    }

    static function kline_1day(){

        $data = KlineData::orderBy('datetime')->where('is_day', 1)->get();
        foreach ($data as $value){

            $open = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1day_pointer)
                ->where('is_1min', 1)
                ->orderBy('Date', 'asc')
                ->value('Open');
            $close = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1day_pointer)
                ->where('is_1min', 1)
                ->orderBy('Date', 'desc')
                ->value('Close');
            $high = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1day_pointer)
                ->where('is_1min', 1)
                ->orderBy('High', 'desc')
                ->value('High');
            $low = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1day_pointer)
                ->where('is_1min', 1)
                ->orderBy('Low', 'asc')
                ->value('Low');

            if (empty($open)) {
                $temp = KlineData::where('datetime', $value->datetime)->where('is_1min', 1)->first();
                $value->Open = $temp->Open;
                $value->High = $temp->High;
                $value->Low = $temp->Low;
                $value->Close = $temp->Close;
                $value->LastClose = $temp->Close;
            } else {
                $value->Open = $open;
                $value->High = $high;
                $value->Low = $low;
                $value->Close = $close;
                $value->LastClose = $close;
            }

            $value->Amount = sprintf("%.5f", $value->Close * $value->Volume);
            $value->save();

            echo $value->datetime . "," . $value->Open . "," . $value->Close . "," . $value->id . "\n";
        }
    }

    static function kline_1week()
    {
        $data = KlineData::orderBy('datetime')->where('is_week', 1)->get();
        foreach ($data as $value){

            $open = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1week_pointer)
                ->where('is_1min', 1)
                ->orderBy('Date', 'asc')
                ->value('Open');
            $close = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1week_pointer)
                ->where('is_1min', 1)
                ->orderBy('Date', 'desc')
                ->value('Close');
            $high = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1week_pointer)
                ->where('is_1min', 1)
                ->orderBy('High', 'desc')
                ->value('High');
            $low = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1week_pointer)
                ->where('is_1min', 1)
                ->orderBy('Low', 'asc')
                ->value('Low');

            if (empty($open)) {
                $temp = KlineData::where('datetime', $value->datetime)->where('is_1min', 1)->first();
                $value->Open = $temp->Open;
                $value->High = $temp->High;
                $value->Low = $temp->Low;
                $value->Close = $temp->Close;
                $value->LastClose = $temp->Close;
            } else {
                $value->Open = $open;
                $value->High = $high;
                $value->Low = $low;
                $value->Close = $close;
                $value->LastClose = $close;
            }

            $value->Amount = sprintf("%.5f", $value->Close * $value->Volume);
            $value->save();

            echo $value->datetime . "," . $value->Open . "," . $value->Close . "," . $value->id . "\n";
        }
    }

    static function kline_1month()
    {
        $data = KlineData::orderBy('datetime')->where('is_month', 1)->get();
        foreach ($data as $value){

            $open = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1month_pointer)
                ->where('is_1min', 1)
                ->orderBy('Date', 'asc')
                ->value('Open');
            $close = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1month_pointer)
                ->where('is_1min', 1)
                ->orderBy('Date', 'desc')
                ->value('Close');
            $high = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1month_pointer)
                ->where('is_1min', 1)
                ->orderBy('High', 'desc')
                ->value('High');
            $low = KlineData::where('Date', '<=', $value->Date)
                ->where('Date', '>', $value->Date - static::$time_1month_pointer)
                ->where('is_1min', 1)
                ->orderBy('Low', 'asc')
                ->value('Low');

            if (empty($open)) {
                $temp = KlineData::where('datetime', $value->datetime)->where('is_1min', 1)->first();
                $value->Open = $temp->Open;
                $value->High = $temp->High;
                $value->Low = $temp->Low;
                $value->Close = $temp->Close;
                $value->LastClose = $temp->Close;
            } else {
                $value->Open = $open;
                $value->High = $high;
                $value->Low = $low;
                $value->Close = $close;
                $value->LastClose = $close;
            }

            $value->Amount = sprintf("%.5f", $value->Close * $value->Volume);
            $value->save();

            echo $value->datetime . "," . $value->Open . "," . $value->Close . "," . $value->id . "\n";
        }
    }
}
