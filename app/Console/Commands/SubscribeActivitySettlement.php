<?php

namespace App\Console\Commands;

use App\Models\Coins;
use App\Models\SubscribeActivity;
use App\Models\User;
use App\Models\UserSubscribeRecord;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SubscribeActivitySettlement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscribe:settlement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '申购活动结算';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $today = Carbon::now()->toDateTimeString();
        $activity = SubscribeActivity::query()->whereDate('end_time','<',$today)->where('status',1)->first();
        if(!blank($activity)){
            $params = $activity['params'];
            $items = UserSubscribeRecord::query()
                ->whereBetween('subscription_time',[strtotime($activity['start_time']),strtotime($activity['end_time'])])
                ->groupBy('user_id')
                ->pluck('user_id');
            foreach ($items as $user_id){
                $amountSum = UserSubscribeRecord::query()
                    ->where('user_id',$user_id)
                    ->whereBetween('subscription_time',[strtotime($activity['start_time']),strtotime($activity['end_time'])])
                    ->sum('subscription_currency_amount');
//                dump($user_id . '--' . $amountSum);
                $param = array_last($params,function($value,$key)use($amountSum){
                   return $value['amount'] < $amountSum;
                });

                if(!empty($param)){
                    $log_type = 'subscribe_activity';
                    // 防止重复结算
                    $is_exist = UserWalletLog::query()->where(['user_id'=>$user_id,'log_type'=>$log_type,'logable_type'=>SubscribeActivity::class,'logable_id'=>$activity['id']])->exists();
                    if(!$is_exist){
                        $award = PriceCalculate($amountSum,'*',$param['rate'],8);
                        $user = User::query()->find($user_id);
                        $coin_id = Coins::query()->where('coin_name',config('coin.coin_symbol'))->value('coin_id') ?? 26;
                        if(!empty($user)) $user->update_wallet_and_log($coin_id,'usable_balance',$award,UserWallet::asset_account,$log_type,'','',$activity['id'],SubscribeActivity::class);
                    }
                }
            }

            $activity->update(['status' => 0]);
        }
    }
}
