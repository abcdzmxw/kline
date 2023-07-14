<?php

namespace App\Console\Commands;

use App\Models\InsideTradeBuy;
use App\Models\InsideTradeDealRobot;
use App\Models\InsideTradeSell;
use App\Models\PledgeOrder;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\InsideTradeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PledgeUnlock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PledgeUnlock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '质押解锁';

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
        $nowDay = Carbon::now()->toDateString();
        // 查询到期的质押订单
        $orderList = PledgeOrder::query()->where('status', 1)->get();
        foreach ($orderList as &$k) {
            $day = Carbon::parse($k->created_at)->addDays($k->cycle + 1)->toDateString();
            //echo $k->id . '---' . $nowDay . '>=' . $day . PHP_EOL;
            if ($nowDay >= $day) {
                // 解锁
                DB::beginTransaction();
                try {
                    //echo 'begin' . PHP_EOL;
                    PledgeOrder::query()->where('id', $k->id)->update(['status' => 0]);
                    //扣除用户可用资产 冻结
                    $user = User::query()->find($k['user_id']);
                    //dd($user);
                    if ($k->special = 0) {
                    $user->update_wallet_and_log($k['coin_id'], 'usable_balance', $k['total'],
                        UserWallet::asset_account, 'sell_pledge_product');
                    }
                    else{
                         $user->update_wallet_and_log($k['coin_id'], 'usable_balance', $k['num'] + $k['reward']/$k->cycle,
                            UserWallet::asset_account, 'sell_pledge_product_special');
                    }
                    $user->update_wallet_and_log($k['coin_id'], 'freeze_balance', -$k['num'],
                        UserWallet::asset_account, 'sell_pledge_product');

                    DB::commit();
                } catch (\Exception $e) {
                    echo $e;
                    DB::rollBack();
                }
            }
        }
    }
}
