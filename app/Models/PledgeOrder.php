<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PledgeOrder extends Model
{
    // 币币交易交易对
//use Notifiable;

    protected $table = 'pledge_order';
    protected $primaryKey = 'id';
    protected $guarded = [];

  protected $events = [
        'created' => \App\Events\PledgeUpgradeEvent::class,
    ];



    //状态
    const status_freeze = 0;
    const status_normal = 1;
    public static $statusMap = [
        self::status_freeze => '结束',
        self::status_normal => '质押中',
    ];
    
    
    public static function boot()  
  {        parent::boot();
   //    static::created(function ($pledgeOrder)
    //   { \event(new PledgeUpgradeEvent($pledgeOrder));
        
//});    
}

//    public function PledgeProduct()
//    {
//        return $this->belongsTo('Models\PledgeProduct','product_id');
//    }
}
