<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OptionScene extends Model
{
    //期权场景

    protected $table = 'option_scene';
    protected $primaryKey = 'scene_id';
    protected $guarded = [];

    public $appends = ['begin_time_text','end_time_text','status_text','lottery_time','pair_name','coin_icon'];

    protected $casts = [
        'up_odds' => 'array',
        'down_odds' => 'array',
        'draw_odds' => 'array',
        'begin_price' => 'real',
        'end_price' => 'real',
        'delivery_range' => 'real',
    ];

    //状态
    const status_wait = 1;
    const status_purchase = 2;
    const status_delivery = 3;
    const status_delivered = 4;
    const status_cancel = 5;

    public static $statusMap = [
        self::status_wait => '待购买',
        self::status_purchase => '购买中',
        self::status_delivery => '即将交割',
        self::status_delivered => '已交割',
        self::status_cancel => '流局',
    ];

    public function getCoinIconAttribute()
    {
        $coin_icon = Coins::query()->where('coin_name',str_before($this->pair_time_name,'/'))->value('coin_icon');
        return getFullPath($coin_icon);
    }

    public function getLotteryTimeAttribute()
    {
        return ($lottery_time = $this->end_time - time()) > 0 ? $lottery_time : null;
    }

    public function getStatusTextAttribute()
    {
        if($this->status == self::status_wait){
            $lottery_time = $this->end_time - time();
            $seconds = $this->seconds ?? 90;
            if( $lottery_time > 0 && $lottery_time <= $seconds ){
                return __(self::$statusMap[self::status_delivery]);
            }
            if($lottery_time > $seconds && $lottery_time <= $seconds * 2){
                return __(self::$statusMap[self::status_purchase]);
            }
        }
        return __(self::$statusMap[$this->status]);
    }

    public function getPairNameAttribute()
    {
        return OptionPair::query()->where('pair_id',$this->pair_id)->value('pair_name');
    }

    public function getBeginTimeTextAttribute()
    {
        return Carbon::createFromTimestamp($this->begin_time)->toDateTimeString();
    }

    public function getEndTimeTextAttribute()
    {
        return Carbon::createFromTimestamp($this->end_time)->toDateTimeString();
    }

    public function option_pair()
    {
        return $this->belongsTo(OptionPair::class,'pair_id','pair_id');
    }

    public function option_time()
    {
        return $this->belongsTo(OptionTime::class,'time_id','time_id');
    }

    public function can_bet()
    {
        if($this->option_pair->trade_status == 0){
            return '交易暂时关闭';
        }

        $seconds = 10;
        $scene = $this;
        if( time() > ($scene['begin_time'] - $seconds) ){
            return '已超过可买入时间';
        }
        return true;
    }

    // 期权场景异常 该场次所有订单流局 end_price
    public function cancel_scene()
    {
       // $new_price = Cache::store('redis')->get('market:' . $symbol . '_newPrice')['price'] ?? null;
        try {
            DB::beginTransaction();

            $this->update([
                'status' => OptionScene::status_cancel,
              //   'end_price' => $new_price,
            ]);

            OptionSceneOrder::query()
                ->where('scene_id',$this->scene_id)
                ->where('status',OptionSceneOrder::status_wait)
                ->chunk(100,function ($orders){
                    foreach ($orders as $order){
                        $order->option_order_cancel();
                    }
                });

            DB::commit();
        } catch (\Exception $e) {
            info($e);
            DB::rollback();
        }
    }

    public function scene_orders()
    {
        return $this->hasMany(OptionSceneOrder::class,'scene_id','scene_id');
    }

}
