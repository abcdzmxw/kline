<?php

namespace App\Services;

use App\Models\User;

use App\Exceptions\ApiException;
use App\Models\PledgeOrder;
use App\Models\PledgeProduct;
use App\Models\UserWallet;

use App\Models\UserPledgePromotionGrade;
use App\Models\UserUpgradeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Redis;




class PledgeProductService
{
    public function getProduct($id)
    {
        $user     = auth('api')->user();
        $is_login = false;
        $data     = PledgeProduct::query()->where(['id' => $id, 'status' => 1])->first();
        if ($data) {
            $data['cover']      = getFullPath($data['cover']);
            $data['spread_img'] = getFullPath($data['spread_img']);
            $data['can_buy'] = false;
            if (isset($user->user_id)) {
                $is_login   = true;
                $userWallet = (new UserWalletService())->withdrawalBalance($user->user_id,
                    ['coin_name' => $data['coin_name']]);
                $count = PledgeOrder::query()->where(['user_id' => $user->user_id, 'product_id' => $data['id']])->count();
                if($count < $data['can_buy_num']){
                    $data['can_buy'] = true;
                }
            }
            $data['is_login'] = $is_login;
            $data['coin_num'] = $userWallet->original['data']['usable_balance'] ?? 0;
        } else {
            $data = [];
        }
        return $data;
    }

    public function getProductList()
    {
        $user = auth('api')->user();
        $data = PledgeProduct::query()->where(['status' => 1])->get();
        $data = $data->map(function ($item, $key) use ($user) {
            $item['cover']      = getFullPath($item['cover']);
            $item['spread_img'] = getFullPath($item['spread_img']);
            $item['can_buy'] = false;
            if (isset($user->user_id)) {
                $count = PledgeOrder::query()->where(['user_id' => $user->user_id, 'product_id' => $item['id']])->count();
                if($count < $item['can_buy_num']){
                    $item['can_buy'] = true;
                }
            }
            return $item;
        })->toArray();
        return $data;
    }

    public function getOrder($user, $id)
    {
        $data = PledgeOrder::query()->where(['id' => $id, 'user_id' => $user['user_id']])->first();
        if ($data) {
            //$data['status'] = PledgeOrder::$statusMap[$data['status']];
            $data['end_time'] = Carbon::parse($data['created_at'])->addDays($data['cycle']+1)->toDateString().' 00:00:00';
            $data['product_name'] = PledgeProduct::query()->where('id',$data['product_id'])->value('name');
        } else {
            $data = [];
        }
        return $data;
    }

    public function getOrderList($user)
    {
        $limit = request()->input('limit') ?? 15;
        $data  = PledgeOrder::query()->where(['user_id' => $user['user_id']])->orderByDesc('id')->simplePaginate($limit);
        $data  = $data->map(function ($item, $key) {
            //$item['status'] = PledgeOrder::$statusMap[$item['status']];
            $item['end_time'] = Carbon::parse($item['created_at'])->addDays($item['cycle']+1)->toDateString().' 00:00:00';
            $item['product_name'] = PledgeProduct::query()->where('id',$item['product_id'])->value('name');
            return $item;
        })->toArray();
        return $data;
    }

    public function buyProduct($user, $params)
    {
        $params['num'] = PriceCalculate($params['num'], '*', 1, 4);

        $product = PledgeProduct::query()->where(['id' => $params['id'], 'status' => 1])->first();
        if (blank($product)) {
            throw new ApiException('产品错误');
        }

        if ($product->min_amount > $params['num'] || $product->max_amount < $params['num']) {
            throw new ApiException('可买数量超出限制');
        }

        $wallet = UserWallet::query()->where(['user_id' => $user->user_id, 'coin_id' => $product->coin_id])->first();
        if (blank($wallet)) {
            throw new ApiException('钱包类型错误');
        }

        $count = PledgeOrder::query()->where(['user_id' => $user->user_id, 'product_id' => $params['id']])->count();
        if($count >= $product->can_buy_num){
            throw new ApiException('超出可购买次数');
        }

        $balance = $wallet->usable_balance;
        if ($balance < $params['num']) {
            throw new ApiException('余额不足');
        }

        DB::beginTransaction();
        try {
            $reward = PriceCalculate($params['num'], '*', ($product->rate / 100), 4);
            //创建订单
            $order_data = [
                'user_id'    => $user['user_id'],
                'order_no'   => get_order_sn('ZY'),
                'product_id' => $product->id,
                'coin_id'    => $product->coin_id,
                'coin_name'  => $product->coin_name,
                'cycle'      => $product->cycle,
                'rate'       => $product->rate,
                'num'        => $params['num'],
                'reward'     => $reward,
                'total'      => $params['num'] + $reward,
                'special'       => $product->special,
                'status'     => 1,
            ];

            $order = PledgeOrder::query()->create($order_data);

            //扣除用户可用资产 冻结
            $user->update_wallet_and_log($wallet['coin_id'], 'usable_balance', -$params['num'],
                UserWallet::asset_account, 'buy_pledge_product');
            $user->update_wallet_and_log($wallet['coin_id'], 'freeze_balance', $params['num'],
                UserWallet::asset_account, 'buy_pledge_product');

            // for ($i = 1; $i<$product->cycle; $i++) {
            // Cache::store('redis')->put('pledgeReward:'.[$i],Carbon::now()->addDays($i));
            //   Cache::store('redis')->put('ORDER_CONFIRM:'.$order->id,$order->id,1); // 1分钟后过期。这里为了测试方便，暂设置为1分钟。

            //  }


           
            DB::commit();
            

          







            $parent_user = User::where('pid', $user->pid)->first()->parent_user->user_grade;
            // $parent_user=$user->parent_user();
         //   INFO('parent'.$parent_user);

            $dividend_params = [
                's_user_id'=> $user->user_id,
                'amount' => $order->num,

            ];


         //   $user->dividendPledgePromotion($user,$dividend_params);


 if($order->special=='1'){
                for ($i = 1; $i<$order->cycle; $i++) {
                    Redis::setex('pledgeReward_'.$i.'_'.$order->id,86400*$i,$i.'_'.$order->id);
                info('pledgeReward_'.$i.'_'.$order->id.',time:'.$order->created_at->addHours($i*24).',uid:'.$order->user_id);
                //    Redis::setex('pledgeReward:'.$order->id.'_'.$i,60*$i,$order->id);
                    // $date_approval = Carbon::parse($order->created_at)->addDays($i);
                    //   Cache::store('redis')->put('pledgeReward_all:'.[$i],$date_approval,$order->id);
                }
            }


          //  info(Carbon::parse($order->created_at)->addDays(1));
           // info('pledgeReward9:'.$order->id);

            //    Cache::store('redis')->put('ORDER_CONFIRM:'.$order->id,$order->id,1); // 1分钟后过期。这里为了测试方便，暂设置为1分钟。
            //  for ($i = 1; $i<$order->cycle; $i++) {
            // Cache::store('redis')->put('pledgeReward:'.[$i],Carbon::now()->addDays($i));
            //  Cache::store('redis')->put('pledgeReward:'.$order->id.$i,$order->id,1); // 1分钟后过期。这里为了测试方便，暂设置为1分钟。
            // info('pledgeReward:'.$order->id.$i);
            //  console.log($order->id);
            //  Redis::setex('pledgeReward:'.$order->id.'_'.$i,60*60*60*24*$i,$order->id);
            //     Redis::setex('pledgeReward_'.$order->id.'_'.$i,60*$i,$order->id);
            //Redis::setex('pledgeReward:'.$order->id.$i,$order->created_at->addDays(1),$order->id);
            //        echo "设置过期key=".$order->id.$i."成功";
            //  Redis::Expireat('pledgeReward:'.$order->id.$i,$order->created_at->addDays(1)) ;

            //   }
            //Expireat KEY_NAME TIME_IN_UNIX_TIMESTAMP
            //   console.log(this.$order->id,this.$order->cycle);
            //      $order_id = 2019;
            //因为一个项目中可能会有很多使用到setex的地方，所以给订单id加个前缀
            //  $order_prefix_id = 'order_'.$order_id;
            //将订单ID存入redis缓存中，并且设置过期时间为5秒
            //  $key_name = $order_prefix_id; //我们在订阅中只能接收到$key_name的值
            //   $expire_second = 5; //设置过期时间，单位为秒
            //   $value = $order_id;
            //  Redis::setex('ORDER_CONFIRM:'.$order->id,5,$order->id);
            //   echo "设置过期key=".$order_prefix_id."成功";


            //更新用户奖励级别
            //用户等级规则数组
            $rule = [
                ['amount' => 5000, 'frequency' => 5,'level' => 1],
                ['amount' =>10000,'frequency' =>10, 'level' => 2],
                ['amount' =>20000, 'frequency' =>20,'level' => 3],
                ['amount' =>30000, 'frequency' =>30,'level' => 4],
            ];
            $orderCacal = DB::table('pledge_order')
                ->select(DB::raw('count(*) as count_order,SUM(num) as total_amount'))
                ->where(['user_id' => $user->user_id])->get();

            $totalAmount = DB::table('pledge_order')->where(['user_id' => $user->user_id])->sum("num");
            $totalOrderNum = DB::table('pledge_order')->where(['user_id' => $user->user_id])->count();
            $all_user_count = $user->all_user_count(); // 获取随机一个系统账户
            $user_count = $user->direct_user_count();
           // info('info'.$totalAmount.'-'.$totalOrderNum.'-'.$all_user_count.$user_count);
            //根据等级$level降序二维数组$rule
            $levels = array_column($rule, 'level');
            array_multisort($levels, SORT_DESC, $rule);

            //循环处理用户数据，根据等级规则计算用户等级
            //foreach ($userData as &$user) {
            $userLevel = 0; //用户等级默认为0
            foreach ($rule as $level) { //循环用户等级规则
                //如果用户质押金额大于等于等级规则的金额，则设置用户的等级为对应等级规则的等级，跳出该循环
                if ( $totalAmount >= $level['amount'] && $totalOrderNum>=$level['frequency']) {  //注意：规则必须是降序的，如果是升序的，该判断会导致结果出问题
                    $userLevel = $level['level'];
                 //  info('userLevel:'.$userLevel);
                    break;
                }
            }

           // if ($userLevel >= 1) {
          // if ($user->reward_level_status == 1 && $user->reward_level<$userLevel) {
              // if ($user->reward_level_status == 1 && $user->reward_level<$userLevel) {
              if ($user->reward_level<$userLevel) {
                User::where('status', 1)
                   ->where('user_id', $user->user_id)
                  // ->where(['user_id' => $user->user_id,'reward_level_status' => '1'])
                    ->update(['reward_level' => $userLevel,'reward_level_status' => '0']);
              }
              $rewardLevelStatus = DB::table('users')->where(['user_id' => $user->user_id])->value('reward_level_status');
              if ($rewardLevelStatus == 0) {
                $rewardUsdt = DB::table('reward_level')->where(['level' => $userLevel])->value('content');
    
                    $user->update_wallet_and_log($wallet['coin_id'], 'usable_balance', $rewardUsdt,
                        UserWallet::asset_account, 'buy_pledge_product_reward');
                        //把reward_level_status更新为1，表示已经发放达标奖
                        User::where('status', 1)
                    ->where('user_id', $user->user_id)
                    ->update(['reward_level_status' => '1']);
                   throw new ApiException('TransactionAmount');
                }
                else
                {
                   // throw new ApiException('ReachedTargetAmount:'.$totalAmount.',奖励等级是L'.$userLevel);
                    throw new ApiException('ReachedTargetAmount');
                    
                }


         //  }
            //else{
                //  throw new ApiException('不达标');
        //    }

          


        } catch (\Exception $e) {
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }
       
        return $order;
         
    }
}
