<?php
/*
 * @Author: your name
 * @Date: 2021-06-03 11:56:59
 * @LastEditTime: 2021-06-04 11:20:48
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \server\app\Models\RechargeManual.php
 */

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ApiException;

use function PHPSTORM_META\map;

class RechargeManual extends Model
{
    use HasDateTimeFormatter;
    use SoftDeletes;

    protected $table = 'recharge_manual';
    protected $primaryKey = 'id';
    protected $guarded = [];

    /**
     * @description: 模型属性的默认值
     * @param {*}
     * @return {*}
     */
    protected $attributes = [];
    
protected $casts = [
        'pay_money' => 'real',
        'amount' => 'real',
        'exchange_rate' => 'real',
    ];
    /**
     * @description: 同意充值
     * @process
     * 1、查询判断订单是否存在（是否为0）
     * 2、判断订单状态是否正常（未未处理）
     * 3、查询用户钱包是否存在（USDT）
     * 4、①将用户金额充值到USDT ②写入充值日志 ③更改订单状态未审核通过（1）
     * 5、返回True/false
     * @param {*}
     * @return {*}
     */
    public function agree($id)
    {
        // 查询订单是否存在
        $res =  $this->find($id);
        // 判断订单状态
        if ($res['status'] !== 0) {
            return false;
        }
        $uid = $res['user_id'];
       // $num = $res['pay_money'];
        $num = $res['amount'];
        // 查询用户是否存在
        $userinfo = DB::table('users')
            ->where('user_id', $uid)
            ->select('username')
            ->first();
        $username = $userinfo->username;
        // 查询用户钱包是否存在
        $userwallet = DB::table('user_wallet')
            ->where([
                ['user_id', '=', $uid],
                ['coin_name', '=', 'USDT']
            ])
            ->first();
        if (!$userwallet) {
            return false;
        }
        // 开启事务
        DB::beginTransaction();
        try {
            $q1 = DB::table('user_wallet')
                ->where([['user_id', '=', $uid], ['coin_name', '=', 'USDT']])
                ->increment('usable_balance', $num);
            $q2 = DB::table('user_wallet_logs')
                ->insert([
                    'user_id' => $uid,
                    'account_type' => 1,
                    'coin_id' => 1,
                    'coin_name' => 'USDT',
                    'rich_type' => 'usable_balance',
                    'amount' => $num,
                    'log_type' => 'recharge'
                ]);
            $q3 = DB::table('user_wallet_recharge')
                ->insert([
                    'user_id' => $uid,
                    'username' => $username,
                    'coin_id' => 1,
                    'coin_name' => 'USDT',
                    'datetime' => time(),
                    'amount' => $num,
                    'status' => 1,
                    'type' => 1,
                    'account_type' => 1,
                    'note' => '手动上分'
                ]);
            $q4 = DB::table('recharge_manual')
                ->where('id', $id)
                ->update([
                    'id' => $id,
                    'status' => 1
                ]);
            if ($q1 && $q2 && $q3 && $q4) {
                DB::commit();
            } else {
                return false;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e);
            throw new ApiException($e->getMessage());
            return false;
        }
        return true;
    }
    /**
     * @description: 拒绝充值
     * @param {*}
     * @return {*}
     */
    public function reject($id)
    {
        // 更改手动充值状态
        $q1 = DB::table('recharge_manual')
            ->where('id', $id)
            ->update(['status' => 2]);
        if (!$q1) {
            return false;
        }
        return true;
    }
    // 关联表
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
