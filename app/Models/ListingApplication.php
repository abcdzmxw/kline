<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class ListingApplication extends Model
{
	
    protected $table = 'listing_Application';
    public $timestamps = false;
    const status_wait = 0;//待审核
    const status_pass = 1;//审核通过
    const status_reject = 2;//审核拒绝
    public static $statusMap = [
        self::status_wait => '待审核',
        self::status_pass => '审核通过',
        self::status_reject => '审核拒绝',
    ];
}
