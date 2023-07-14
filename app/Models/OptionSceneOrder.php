<?php

namespace App\Models;

use App\Events\HandDividendEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class OptionSceneOrder extends Model
{
    //期权订单

    protected $table = 'option_scene_order';
    protected $primaryKey = 'order_id';
    protected $guarded = [];

    protected $casts = [
        'fee' => 'real',
        'bet_amount' => 'real',
        'odds' => 'real',
        'range' => 'real',
        'delivery_amount' => 'real',
        'end_price' => 'real',
    ];

    protected $attributes = [
        'status' => 1,
    ];

    public $appends = ['status_text','delivery_time_text','lottery_time'];

    const status_wait = 1;
    const status_delivered = 2;
    const status_cancel = 3;

    public static $statusMap = [
        self::status_wait => '待交割',
        self::status_delivered => '已交割',
        self::status_cancel => '流局',
    ];

    public function getStatusTextAttribute()
    {
        return __(self::$statusMap[$this->status]);
    }

    public function getDeliveryTimeTextAttribute()
    {
        return blank($this->delivery_time) ? '--' : Carbon::createFromTimestamp($this->delivery_time)->toDateTimeString();
    }

    public function getLotteryTimeAttribute()
    {
        return ($lottery_time = $this->end_time - time()) > 0 ? $lottery_time : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function scene()
    {
        return $this->belongsTo(OptionScene::class,'scene_id','scene_id');
    }

    public function bonus()
    {
        return $this->morphMany('App\Models\BonusLog', 'bonusable');
    }

    public function option_order_cancel()
    {
        if ( blank($this) || $this->status !== OptionSceneOrder::status_wait || !blank($this->delivery_time) ) {
            return;
        }

        try {
            DB::beginTransaction();

            //更新订单
            $this->update([
                'status' => OptionSceneOrder::status_cancel,
            ]);

//            event(new HandDividendEvent($this,0));

            $user = $this->user;
            $user->update_wallet_and_log($this->bet_coin_id,'usable_balance',$this->bet_amount,UserWallet::asset_account,'option_order_cancel');

            DB::commit();
        } catch (\Exception $e) {
            info($e);
            DB::rollback();
        }

    }

    public function option_order_delivery($delivery_result, $begin_price = 0)
    {
        if ( blank($this) || $this->status !== OptionSceneOrder::status_wait || !blank($this->delivery_time) ) {
            return;
        }

        try {
            DB::beginTransaction();
          //  $new_price = $this->end_price;
//            echo '================================'.PHP_EOL;
//            echo $delivery_result['delivery_up_down'].PHP_EOL;
//            echo $this->end_price.PHP_EOL;
            // 是否作弊
            if(!blank($this->end_price)) {
//                echo '进入作弊'.PHP_EOL;
                $new_price = $this->end_price;
    
                if ($new_price > $begin_price) {
                    $delivery_result['delivery_up_down'] = 1;
                } elseif ($new_price == $begin_price) {
                    $delivery_result['delivery_up_down'] = 3;
                } else {
                    $delivery_result['delivery_up_down'] = 2;
                }
                info($this->order_id.'delivery_result:' . $delivery_result['delivery_up_down']);
            }
            
//            $fee_rate = 0.002; //期权手续费比率

           if(!blank($this->end_price)) {
                    $new_price = $this->end_price;
                    $result = PriceCalculate(($new_price - $begin_price) ,'/', $begin_price,8) * 100;
                    $delivery_range = abs($result);
                } else {
                    $cachedb = config('database.redis.cache.database',1);
                    $symbol = strtolower(str_before($this->pair_name,'/') . str_after($this->pair_name,'/'));
                    $new_price = Cache::store('redis')->get('market:' . $symbol . '_newPrice')['price'] ?? null;
                    $result = PriceCalculate(($new_price - $begin_price) ,'/', $begin_price,8) * 100;
                    $delivery_range = abs($result);
                }
             
             
            $delivery_amount = -$this->bet_amount;
//            $fee = 0;
//            if($this->up_down == $delivery_result['delivery_up_down'] && $this->range <= $delivery_result['delivery_range']){
//            echo $delivery_result['delivery_up_down'].PHP_EOL;
//            echo '================================'.PHP_EOL;
            if($this->up_down == $delivery_result['delivery_up_down']){
                info('option_order_delivery:' . $this->order_id);
                $user = User::query()->find($this->user_id);

                $fee_rate = OptionTime::query()->where('time_id',$this->time_id)->value('fee_rate'); //期权手续费比率

                $complete_amount = PriceCalculate($this->bet_amount,'*',$this->odds,6);
               $fee = PriceCalculate($complete_amount ,'*', $fee_rate,8);
//                $delivery_amount = $complete_amount - $fee;
                // $delivery_amount = $complete_amount;
                $delivery_amount = $complete_amount + $this->bet_amount;
                info('奖励金额：'.$delivery_amount.'盈利金额'.$complete_amount.'本金'.$this->bet_amount);
                $user->update_wallet_and_log($this->bet_coin_id,'usable_balance',$delivery_amount,UserWallet::asset_account,'option_order_delivery');
            }
            
            //更新订单
            $this->update([
                'status' => OptionSceneOrder::status_delivered,
                'delivery_time' => time(),
            //     'end_price' => $new_price+1,
         //       'range' => $delivery_range,
                'delivery_amount' => $delivery_amount,
            ]);

//            event(new HandDividendEvent($this,1));

            DB::commit();
        } catch (\Exception $e) {
            info($e);
            DB::rollback();
//            throw $e;
        }
    }

}
