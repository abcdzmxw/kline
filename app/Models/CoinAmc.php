<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinAmc extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'data_amc';
    protected $guarded = [];
    public $timestamps = false;
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
