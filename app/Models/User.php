<?php

namespace App\Models;
//use App\Models\UserPledgePromotionGrade;
use App\Exceptions\ApiException;
use App\Notifications\WalletChanged;
use App\Services\UserWalletService;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $primaryKey = 'user_id';
    protected $table = 'users';
    protected $guarded = [];

    protected $appends = ['is_set_payword','status_text','user_auth_level_text','user_grade_name','user_identity_text'];

    protected $hidden = [
        'password', 'payword','login_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $attributes = [
        'user_grade' => 1,
        'user_identity' => 1,
        'is_agency' => 0,
        'user_auth_level' => 0,
        'status' => 1,
    ];

    //用户状态
    const user_status_freeze = 0;//冻结
    const user_status_normal = 1;//正常
    public static $userStatusMap = [
        self::user_status_freeze => '冻结',
        self::user_status_normal => '正常',
    ];

    //用户认证
    const user_auth_level_wait = 0;
    const user_auth_level_primary = 1;
    const user_auth_level_top = 2;
    public static $userAuthMap = [
        self::user_auth_level_wait => '未认证',
        self::user_auth_level_primary => '初级认证',
        self::user_auth_level_top => '高级认证',
    ];

    //用户身份
    const user_identity_common = 1;
    public static $userIdentityMap = [
        self::user_identity_common => '普通用户',
    ];

    public function getUserGradeNameAttribute()
    {
        $app_locale = App::getLocale();
        if($app_locale == 'en'){
            return UserGrade::query()->where('grade_id',$this->user_grade)->value('grade_name_en');
        }elseif($app_locale == 'zh-TW'){
            return UserGrade::query()->where('grade_id',$this->user_grade)->value('grade_name_tw');
        }elseif($app_locale == 'zh-CN'){
            return UserGrade::query()->where('grade_id',$this->user_grade)->value('grade_name');
        }else{
            return UserGrade::query()->where('grade_id',$this->user_grade)->value('grade_name_en');
        }
    }

    public function getStatusTextAttribute()
    {
        return self::$userStatusMap[$this->status];
    }

    public function getUserAuthLevelTextAttribute()
    {
        return __(self::$userAuthMap[$this->user_auth_level]);
    }

    public function getUserIdentityTextAttribute()
    {
        return self::$userIdentityMap[$this->user_identity];
    }

    public function scopeNotFreeze($query)
    {
        return $query->where('status', '!=', self::user_status_freeze);
    }

    public function getIsSetPaywordAttribute()
    {
        $user = $this;

        $isset = 0;
        if(!blank($user->payword)) $isset = 1;

        return $isset;
    }

    public function isOnline()
    {
        return Cache::has('user-is-online-' . $this->user_id);
    }

    public static function getOneSystemUser()
    {
        // return self::query()->where('is_system',1)->where('contract_deal',1)->inRandomOrder()->first();
        return ['user_id' => 0];
    }

    /**
     * Get avatar attribute.
     *
     * @return mixed|string
     */
    public function getAvatar()
    {
        $avatar = $this->avatar;

        if ($avatar) {
            if (! URL::isValidUrl($avatar)) {
                $avatar = Storage::disk(config('admin.upload.disk'))->url($avatar);
            }

            return $avatar;
        }

        return admin_asset(config('admin.default_avatar') ?: '@admin/images/default-avatar.jpg');
    }

    public function getUserByPhone($phone)
    {
        return $this->newQuery()->where(['phone'=>$phone])->first();
    }

    public function getUserByEmail($email)
    {
        return $this->newQuery()->where(['email'=>$email])->first();
    }

    public function user_wallet()
    {
        return $this->hasMany(UserWallet::class,'user_id','user_id');
    }

    public function user_wallet_log()
    {
        return $this->hasMany(UserWalletLog::class,'user_id','user_id');
    }

    public function user_payments(){
        return $this->hasMany('App\Models\UserPayment','user_id','user_id');
    }

    // 上级代理
    public function parent_agent()
    {
        return $this->belongsTo('App\Models\User', 'referrer','id');
    }

    public function parent_user()
    {
        return $this->belongsTo('App\Models\User', 'pid','user_id');
    }

    public function children()
    {
        return $this->hasMany('App\Models\User', 'pid','user_id');
    }

    public function allChildren()
    {
        return $this->hasMany('App\Models\User', 'pid','user_id')->with('allChildren');
    }

    public function direct_user_count()
    {
        return $this->children()->count();
    }

   public  function all_user_count()
    {
        return $this->allChildren()->count();
    }

    /**
     * 更新用户钱包 并记录日志
     *
     * @param integer $coin_id 币种ID
     * @param string $rich_type 资产类型
     * @param float $amount 金额
     * @param integer $account_type 钱包账号类型
     * @param string $log_type 流水类型
     * @param string $log_note 流水描述
     * @param int $sub_account 子账户
     * @param int $logable_id
     * @param string $logable_type
     * @return int|void
     * @throws ApiException
     */
    public function update_wallet_and_log_copy($coin_id,$rich_type,$amount,$account_type,$log_type,$log_note='',$sub_account=null,$logable_id=0,$logable_type='')
    {
        //如果$amount为零，则不记录;
        if ($amount == 0) {
            return;
        }

        $account_class = array_first(UserWallet::$accountMap,function ($value, $key) use ($account_type) {
            return $value['id'] == $account_type;
        });
        if( blank($account_class) ){
            throw new ApiException('账户类型错误');
        }
        $account = new $account_class['model']();

        $exists = $account->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->exists();
        if( $exists ){
            $wallet = $account->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->first();
        }else{
            // TODO 钱包账户不存在 更新创建该钱包账户
            (new UserWalletService())->updateWallet($this);
            $wallet = $account->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->first();
        }
        if(blank($wallet)) throw new ApiException('钱包类型错误');
        $balance = $wallet->$rich_type;

//        if ($amount < 0 && $balance < abs($amount)) {
        if ( $amount < 0 && bccomp($balance,abs($amount)) < 0 ) {
//            dd($balance,$amount);
            // throw new ApiException('资产不足' . '--uid:' . $this->user_id . '--balance:' . $balance . '--amount:' . $amount);
            throw new ApiException('资产不足');
        }

        if($account_type == UserWallet::sustainable_account){
            $currency_id = 1;
            $currency_name = $wallet['margin_name'] ?? '';
        }else{
            $currency_id = $coin_id;
            $currency_name = $wallet['coin_name'] ?? '';
            $sub_account = null;
        }

        if ($amount > 0) {
            $res = $wallet->increment($rich_type, abs($amount));

            if($rich_type == 'usable_balance'){
                //用户钱包资产变动 发送信息通知 在增加的情况下
                $params = [
                    'rich_type' => $rich_type,
                    'coin_name' => $wallet['coin_name'],
                    'change_type' => '增加',
                    'amount' => $amount,
                    'log_type' => $log_type,
                ];
                $this->notify(new WalletChanged($params));
            }
        } else {
            $res = $wallet->decrement($rich_type, abs($amount));

//            if(in_array($log_type,['bet_option']) && $rich_type == 'usable_balance'){
//                // 发放交易分红（给上级用户分红） 并记录
//                $dividend_params = [
//                    'coin_id' => $currency_id,
//                    'coin_name' => $currency_name,
//                    'rich_type' => $rich_type,
//                    'amount' => abs($amount),
//                    'account_type' => $account_type,
//                    'bonusable_id' => $logable_id,
//                    'bonusable_type' => $logable_type,
//                ];
//                $this->dividend($this,$dividend_params);
//            }
        }

        $this->user_wallet_log()->create([
            'account_type' => $account_type,
            'sub_account' => $sub_account,
            'coin_id' => $currency_id,
            'coin_name' => $currency_name,
            'rich_type' => $rich_type,
            'amount' => $amount,
            'before_balance' => $balance,
            'after_balance' => $wallet->$rich_type,
            'log_type' => $log_type,
            'log_note' => $log_note,
            'logable_id' => $logable_id,
            'logable_type' => $logable_type,
            'ts' => time(),
        ]);

        return $res;
    }
    
    
    
    
    public function update_wallet_and_log(
        $coin_id,
        $rich_type,
        $amount,
        $account_type,
        $log_type,
        $log_note='',
        $sub_account=null,
        $logable_id=0,
        $logable_type=''
    ) {

        if ($amount == 0) {
            return;
        }

        $account_class = array_first(UserWallet::$accountMap,function ($value, $key) use ($account_type) {
            return $value['id'] == $account_type;
        });
        
        if( blank($account_class) ){
            throw new ApiException('账户类型错误');
        }
        
        $account = new $account_class['model']();

        $exists = $account->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->exists();
        
        if( $exists ){
            
            $wallet = $account->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->first();
        }else{
            
            (new UserWalletService())->updateWallet($this);
            $wallet = $account->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->first();
        }
        
        if(blank($wallet)) throw new ApiException('钱包类型错误');
        
        $balance = $wallet->$rich_type;

        if ( $amount < 0 && bccomp($balance,abs($amount)) < 0 ) {
            
            throw new ApiException('资产不足');
        }


        if($account_type == UserWallet::sustainable_account){
            
            $currency_id = 1;
            $currency_name = $wallet['margin_name'] ?? '';
        }else{
            
            $currency_id = $coin_id;
            $currency_name = $wallet['coin_name'] ?? '';
            $sub_account = null;
        }

        if ($amount > 0) {
            $res = $wallet->increment($rich_type, abs($amount));

            if($rich_type == 'usable_balance'){
                
                $params = [
                    'rich_type' => $rich_type,
                    'coin_name' => $wallet['coin_name'],
                    'change_type' => '增加',
                    'amount' => $amount,
                    'log_type' => $log_type,
                ];
                $this->notify(new WalletChanged($params));
            }
        } else {
            
            $res = $wallet->decrement($rich_type, abs($amount));
        }

        $this->user_wallet_log()->create([
            'account_type' => $account_type,
            'sub_account' => $sub_account,
            'coin_id' => $currency_id,
            'coin_name' => $currency_name,
            'rich_type' => $rich_type,
            'amount' => $amount,
            'before_balance' => $balance,
            'after_balance' => $wallet->$rich_type,
            'log_type' => $log_type,
            'log_note' => $log_note,
            'logable_id' => $logable_id,
            'logable_type' => $logable_type,
            'ts' => time(),
        ]);

        return $res;
    }
    
    

    // 发放交易分红
    private function dividend($user,$dividend_params)
    {
        if(blank($user)) return ;

        $floor = 1;//层级初始值
        $max_floor = 5;

        $inviter = $user;
        while ($inviter = $inviter->parent_user)
        {
            if($floor > $max_floor) {
                break;
            }
            if($inviter['user_grade'] <= 1) {
                continue;
            }

            $grade_info = UserGrade::get_grade_info($inviter['user_grade']);
            if(blank($grade_info['bonus'])) continue;
            $bonus_rate_arr = explode('|',$grade_info['bonus']);

            //佣金
            if(isset($bonus_rate_arr[$floor-1]))
            {
                $bonus_rate = $bonus_rate_arr[$floor-1];
                if(blank($bonus_rate)) continue;
                $get_bonus = $dividend_params['amount'] * $bonus_rate;
//                $inviter->update_wallet_and_log($dividend_params['coin_id'],$dividend_params['rich_type'],$get_bonus,$dividend_params['account_type'],'dividend');
                // 记录
                BonusLog::query()->create([
                    'user_id' => $inviter['user_id'],
                    'coin_id' => $dividend_params['coin_id'],
                    'coin_name' => $dividend_params['coin_name'],
                    'account_type' => $dividend_params['account_type'],
                    'rich_type' => $dividend_params['rich_type'],
                    'amount' => $get_bonus,
                    'log_type' => 'dividend',
                    'bonusable_id' => $dividend_params['bonusable_id'],
                    'bonusable_type' => $dividend_params['bonusable_type'],
                ]);
            }
            $floor++;
        }
    }
    
    
    // 发放挖矿佣金
    public function dividendPledgePromotion($user,$dividend_params)
    {
       if(blank($user)) return ;

        $floor = 1;//层级初始值
        $max_floor = 2;

        $inviter = $user;
        while ($inviter = $inviter->parent_user)
        {
            info('inviter'.$inviter);
            if($floor > $max_floor) {
                break;
            }
            if($inviter['user_pledge_promotion_grade'] <= 1) {
               continue;
           }

            $grade_info = UserPledgePromotionGrade::get_promotion_grade_info($inviter['user_pledge_promotion_grade']);
            info('grade_info'.$grade_info);
          //  $bonus_rate_arr = explode('|',$grade_info['bonus']);
          //   info('bonus_rate_arr_t'.$bonus_rate_arr);
            if(blank($grade_info['bonus'])) continue;
            $bonus_rate_arr = explode('|',$grade_info['bonus']);
            // info('bonus_rate_arr_t'.$bonus_rate_arr);
            //佣金
            if(isset($bonus_rate_arr[$floor-1]))
            {
                $bonus_rate = $bonus_rate_arr[$floor-1];
               if(blank($bonus_rate)) continue;
                $get_bonus = $dividend_params['amount'] * $bonus_rate;
                
              //$inviter->update_wallet_and_log($dividend_params['coin_id'],$dividend_params['rich_type'],$get_bonus,$dividend_params['account_type'],'dividend');
              
               $inviter->update_wallet_and_log('1','usable_balance',$get_bonus,'1','dividendPledgePromotion');
                // 记录
                BonusLog::query()->create([
                    'user_id' => $inviter['user_id'],
                    'coin_id' => '1',
                    'coin_name' => 'USDT',
                    'account_type' => '1',
                    'rich_type' => 'usable_balance',
                    'amount' => $get_bonus,
                    'log_type' => 'dividendPledgePromotion',
                    'bonusable_id' => '1',
                    'bonusable_type' => '1',
                ]);
            }
            $floor++;
        }
    }
    
    
    

    //根据用户取出无限级子用户
    public static function getSubChildren($user_id,$subIds = [])
    {
        $users = User::query()->where('pid',$user_id)->select(['user_id','pid'])->get();
        foreach ($users as $key=>$value){
            $subIds[] = $value['user_id'];
            $user = User::query()->where('pid',$value['user_id'])->select(['user_id','pid'])->get();
            if($user){
                $subIds = self::getSubChildren($value['user_id'],$subIds);
            }
        }
        return $subIds;
    }

    public function passwordHash($password)
    {
        return password_hash($password,PASSWORD_DEFAULT);
    }

    public function verifyPassword($password,$pHash)
    {
//        throw_if(blank($this->payword) , new ApiException('交易密码未设置',1034));

        return password_verify($password,$pHash);
    }

    public static function gen_invite_code($length = 8)
    {
        $pattern = '0123456789';
        $code = self::gen_comm($pattern, $length);
        $users = User::query()->where('invite_code', $code)->first();
        if ($users) {
            return self::gen_invite_code($length);
        } else {
            return $code;
        }
    }

    public static function gen_login_code($length = 10)
    {
        $pattern = '01234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return self::gen_comm($pattern, $length);
    }

    public static function gen_username($length = 8)
    {
        $pattern = '01234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $username = self::gen_comm($pattern, $length);
        $users = User::query()->where('username', $username)->first();
        if ($users) {
            return self::gen_username($length);
        } else {
            return $username;
        }
    }

    private static function gen_comm($content, $length)
    {
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $content{mt_rand(0, strlen($content) - 1)};    //生成php随机数
        }

        return $key;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getAvatarAttribute($value)
    {
        return getFullPath($value);
    }

}
