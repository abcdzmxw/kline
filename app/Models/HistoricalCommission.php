<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/8
 * Time: 15:31
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class HistoricalCommission extends Model
{
    #合约历史委托
    protected $primaryKey = 'id';
    protected $table = 'contract_historical_commission';
    public $timestamps = false;
    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }
}
