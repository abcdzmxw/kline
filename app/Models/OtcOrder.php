<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OtcOrder extends Model
{
    //

    protected $table = 'otc_order';
    protected $primaryKey = 'id';
    protected $guarded = [];

    const status_cancel = 0;
    const status_wait_pay = 1;
    const status_wait_confirm = 2;
    const status_completed = 3;
    const status_appealing = 4;
    public static $statusMap = [
        self::status_cancel => '已撤销',
        self::status_wait_pay => '待支付',
        self::status_wait_confirm => '待确认',
        self::status_completed => '已完成',
        self::status_appealing => '申诉中',
    ];

    const appeal_status_wait = 1;
    const appeal_status_processing = 2;
    const appeal_status_processed = 3;
    public static $appealStatusMap = [
        self::appeal_status_wait => '待处理',
        self::appeal_status_processing => '处理中',
        self::appeal_status_processed => '已处理',
    ];

    protected $appends = ['cur_user_role','confirm_button','status_text','seller_payments','overed_time'];

    public function getPaidImgAttribute($v)
    {
        return blank($v) ? '' : getFullPath($v);
    }

    public function getOveredTimeAttribute()
    {
        return ($lottery_time = strtotime($this->overed_at) - time()) > 0 ? $lottery_time : null;
    }

    public function getCurUserRoleAttribute()
    {
        $user = currenctUser();
        if(!$user) return null;

        $role = null;

        if ($this->user_id == $user->user_id) {
            if ($this->trans_type == 1) {
                $role = 'buyer';
            } else{
                $role = 'seller';
            }
        } elseif ($this->other_uid == $user->user_id) {
            if ($this->trans_type == 2) {
                $role = 'buyer';
            } else {
                $role = 'seller';
            }
        }

        return $role;
    }

    public function getConfirmButtonAttribute()
    {
        if (! in_array($this->status, [OtcOrder::status_wait_pay, OtcOrder::status_wait_confirm])) {
            return null;
        }

        $user = currenctUser();
        if(!$user) return null;

        if ($this->entrust->user_id == $this->user_id) {
            return null;
        }

        if ($this->user_id == $user->user_id) {
            if ($this->status == OtcOrder::status_wait_pay && $this->trans_type == 1) {
                return 'buyer';
            } elseif ($this->status == OtcOrder::status_wait_confirm && $this->trans_type == 2) {
                return 'seller';
            }
        } elseif ($this->other_uid == $user->user_id) {
            if ($this->status == OtcOrder::status_wait_pay && $this->trans_type == 2) {
                return 'buyer';
            } elseif ($this->status == OtcOrder::status_wait_confirm && $this->trans_type == 1) {
                return 'seller';
            }
        }

        return null;
    }

    public function getStatusTextAttribute()
    {
        return self::$statusMap[$this->status];
    }

//    public function getAppealStatusTextAttribute()
//    {
//        return blank($this->appeal_status) ? '' : self::$appealStatusMap[$this->appeal_status];
//    }

    public function getSellerPaymentsAttribute()
    {
        $seller = $this->getSeller();

        if (empty($seller) OR empty($this->entrust)) {
            return [];
        }

        $payType = $this->pay_type;

        $payment = UserPayment::query()
            ->where('user_id', $seller->user_id)
            ->where('pay_type', $payType)
            ->first();

        return blank($payment) ? [] : $payment->toArray();
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function other_user()
    {
        return $this->belongsTo(User::class,'other_uid','user_id');
    }

    public function getBuyer()
    {
        return $this->trans_type == 1 ? $this->user : $this->other_user;
    }

    public function getSeller()
    {
        return $this->trans_type == 2 ? $this->user : $this->other_user;
    }

    public function entrust()
    {
        return $this->belongsTo(OtcEntrust::class,'entrust_id','id');
    }

    // 可撤销
    public function canCancel()
    {
        if ($this->status == self::status_wait_pay) {
            return true;
        }
        return false;
    }

    // 买家可确认支付
    public function canConfirmPaid()
    {
        if ( $this->status != self::status_wait_pay) {
            return '当前订单状态不允许该操作';
        }
        if ( $this->overed_at < Carbon::now()->toDateTimeString() ) {
            return '支付已超时';
        }

        return true;
    }

    // 卖家可确认收到款
    public function canConfirmOrder()
    {
        if ( $this->status != self::status_wait_confirm ) {
            return '当前订单状态不允许该操作';
        }

        return true;
    }

}
