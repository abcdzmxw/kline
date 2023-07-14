<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataTkb extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'data_tkb';
    protected $guarded = [];
    public $timestamps = false;

    /*public $attributes = [
        'pid' => 0,
        'Symbol' => 'LVO',
        'Name' => 'LVO',
        'Price2' => 0,
        'Price3' => 0,
        'Open_Int' => 0,
        'is_1min' => 0,
        'is_5min' => 0,
        'is_15min' => 0,
        'is_30min' => 0,
        'is_1h' => 0,
        'is_2h' => 0,
        'is_4h' => 0,
        'is_6h' => 0,
        'is_12h' => 0,
        'is_day' => 0,
        'is_week' => 0,
        'is_month' => 0,
    ];*/

    public static function getKlineData($symbol,$period,$size)
    {
        $builder = self::query();

        $wheres = [
            '1min' => 'is_1min',
            '5min' => 'is_5min',
            '15min' => 'is_15min',
            '30min' => 'is_30min',
            '60min' => 'is_1h',
            '1day' => 'is_day',
            '1week' => 'is_week',
            '1mon' => 'is_month',
        ];
        $where = $wheres[$period] ?? 'is_1min';
        $builder->where($where,1);

        $data = $builder->where('Date','<',time())->limit($size)->orderByDesc('Date')->get();
        if(blank($data)) return [];
        $data = $data->sortBy('Date')->values()->map(function ($kline){
            $item = [
                "id"=> $kline['Date'],
                "amount"=> $kline['Amount'],
                "count"=> $kline['Amount'],
                "open"=> $kline['Open'],
                "close"=> $kline['Close'],
                "low"=> $kline['Low'],
                "high"=> $kline['High'],
                "vol"=> $kline['Volume']
            ];
            $item['price'] = $item['close'];
            return $item;
        })->toArray();
        return $data;
    }
}
