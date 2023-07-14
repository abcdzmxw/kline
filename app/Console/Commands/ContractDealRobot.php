<?php

namespace App\Console\Commands;

use App\Exceptions\ApiException;
use App\Handlers\ContractTool;
use App\Jobs\HandleContractEntrust;
use App\Models\ContractEntrust;
use App\Models\ContractDealRobot as ContractRobot;
use App\Models\ContractOrder;
use App\Models\ContractPair;
use App\Models\ContractPosition;
use App\Models\SustainableAccount;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\ContractService;
use App\Traits\RedisTool;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ContractDealRobot extends Command
{
    use RedisTool;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contractDealRobot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '合约交易成交机器人';

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
    public function handle(ContractService $service)
    {
        $deal_time = 1;

        while (true){
            $entrusts = ContractEntrust::query()
                ->whereIn('status',[ContractEntrust::status_wait,ContractEntrust::status_trading])
                ->where('created_at','<', Carbon::now()->subSeconds($deal_time)->toDateTimeString())
                ->cursor();
            foreach ($entrusts as $entrust) {
                try{
                        // 是否满足自动成交条件
                        $flag = false;
                        if($entrust['type'] == 1 || $entrust['type'] == 3){
                            $realtime_price = Cache::store('redis')->get('swap:' . 'trade_detail_' . $entrust['symbol'])['price'];
                            if($entrust['side'] == 1){
                                if($realtime_price <= $entrust['entrust_price']) $flag = true;
                            }else{
                                if($entrust['entrust_price'] <= $realtime_price) $flag = true;
                            }
                        }else{
                            $flag = true;
                        }

                        if($flag){
                            echo $entrust['id'] . "\n";
                            //订单锁
                            $orderLockKey = 'contractDealRobot:' . $entrust['id'];
                            if (!$this->setKeyLock($orderLockKey,3)) continue;

                            if ($entrust['order_type'] == 1 && $entrust['side'] == 1){
                                // 买入开多
                                $service->handleOpenBuyOrder($entrust);
                            }elseif ($entrust['order_type'] == 1 && $entrust['side'] == 2){
                                // 卖出开空
                                $service->handleOpenSellOrder($entrust);
                            }elseif ($entrust['order_type'] == 2 && $entrust['side'] == 1){
                                // 买入平空
                                $service->handleFlatBuyOrder($entrust);
                            }elseif ($entrust['order_type'] == 2 && $entrust['side'] == 2){
                                // 卖出平多
                                $service->handleFlatSellOrder($entrust);
                            }
                        }
                }catch(\Exception $e){
                    info($e);
                    continue;
                }
            }

            sleep(10);
        }
    }

}
