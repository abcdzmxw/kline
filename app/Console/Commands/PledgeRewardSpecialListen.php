<?php

namespace App\Console\Commands;

use App\Jobs\OptionOrderDelivery;
use App\Models\OptionPair;
use App\Models\OptionScene;
use App\Models\OptionSceneOrder;
use App\Models\PledgeOrder;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\HuobiService\HuobiapiService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PledgeRewardSpecialListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PledgeRewardSpecial';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '监听特惠项目返回利息';

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
       // $cachedb = config('database.redis.cache.database', 1);
        $cachedb = config('database.redis.default.database', 0);
        $pattern = '__keyevent@' . $cachedb . '__:expired';
//        Redis::connection('publisher')->subscribe([$pattern],function ($channel){    // 订阅键过期事件
        /*
        Redis::psubscribe([$pattern],function ($channel){    // 订阅键过期事件
            $key = str_after($channel,':');
            $key_type = str_before($key,':');
            $PledgeRewardSpecial_id = str_after($key,':');    // 取出场景 ID
//            echo $key_type . "\n";
//            echo $scene_id . "\n";
        */
        Redis::psubscribe([$pattern], function ($message, $channel) {
            //$message 就是我们从获取到的过期key的名称
            $key = str_after($channel,':');
            $key_type = str_before($key,':');
            info('key_type:'.$key_type);
            
            $explode_arr = explode('_', $message);
            $prefix=$explode_arr[0];
            if($prefix=='pledgeReward'){
            $order_id = $explode_arr[2];
        //    info('pledgeReward7:'.$order_id);
            //   switch ($key_type) {
            //       case 'pledgeReward':
            $nowDay = Carbon::now()->toDateString();
            // 查询到期付息的质押订单

            $orderList = PledgeOrder::query()->where('id', $order_id)->get();
            foreach ($orderList as &$k) {
                $day = Carbon::parse($k->created_at)->addDays($k->cycle + 1)->toDateString();
                //echo $k->id . '---' . $nowDay . '>=' . $day . PHP_EOL;
                //   if ($nowDay >= $day) {
                // 解锁
                DB::beginTransaction();
                try {
                    //echo 'begin' . PHP_EOL;
                    //   PledgeOrder::query()->where('id', $k->id)->update(['status' => 0]);
                    //扣除用户可用资产 冻结
                    $user = User::query()->find($k['user_id']);
                    //dd($user);
                    //质押挖矿利息日返
                    $user->update_wallet_and_log($k['coin_id'], 'usable_balance', $k['reward'] / $k->cycle,
                        UserWallet::asset_account, 'pledge_mining_interest_daily_return');
                    info("挖矿订单 {$order_id} 自动返息");
                    DB::commit();
                } catch (\Exception $e) {
                    echo $e;
                    DB::rollBack();
                }
                //   }
                //        }
                //      break;
                //  case 'get_begin_price':
//           //         info('订阅键过期事件：get_begin_price' . $scene_id);
                //       $this->get_begin_price($scene_id);
                //       break;
                //   default:
                //      break;
            }
            }
        });

 }
}
