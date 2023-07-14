<?php

namespace App\Console\Commands;

use App\Exceptions\ApiException;
use App\Handlers\ContractTool;
use App\Jobs\HandleContractEntrust;
use App\Jobs\HandleFlatPosition;
use App\Models\ContractEntrust;
use App\Models\ContractOrder;
use App\Models\ContractPair;
use App\Models\ContractPosition;
use App\Models\ContractWearPositionRecord;
use App\Models\SustainableAccount;
use App\Models\User;
use App\Models\UserAgreementLog;
use App\Models\UserWallet;
use App\Services\ContractService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FlatPosition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flatPosition';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '永续合约系统强制平仓';

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
        while (true) {
            // 强制平仓风险率
            $flatRiskRate = get_setting_value('flat_risk_rate','contract',0.7);

            SustainableAccount::query()->chunkById(1000,function ($wallets)use($flatRiskRate){
                foreach ($wallets as $wallet){
                    $user_id = $wallet['user_id'];
                    if(blank($wallet)) continue;

                    $account = [];
                    $totalUnrealProfit = 0;
                    $positions = ContractPosition::query()->where('user_id',$user_id)->where('hold_position','>',0)->get();
                    foreach ($positions as $position){
                        $contract = ContractPair::query()->find($position['contract_id']);
                        // 获取最新一条成交记录 即实时最新价格
                        $realtime_price = Cache::store('redis')->get('swap:' . 'trade_detail_' . $position['symbol'])['price'] ?? null;
                        $unRealProfit = ContractTool::unRealProfit($position,$contract,$realtime_price);
                        $totalUnrealProfit += $unRealProfit;
                    }

                    $account['usable_balance'] = $wallet['usable_balance'];
                    $account['used_balance'] = $wallet['used_balance'];
                    $account['freeze_balance'] = $wallet['freeze_balance'];
                    $account['totalUnrealProfit'] = $totalUnrealProfit;
                    $account['account_equity'] = custom_number_format($account['usable_balance'] + $account['used_balance'] + $account['freeze_balance'] + $account['totalUnrealProfit'],4); // 永续账户权益 = 账户可用余额 + 持仓保证金 + 委托冻结保证金 + 未实现盈亏
                    // 风险率 用以衡量当前合约账户风险程度的指标。风险率越低，账户风险越高，当风险率=10.0%时，将会被强制平仓。风险率=账户权益/（持仓保证金+委托冻结）*100%
                    $riskRate = ContractTool::riskRate($account);
                    // 风险率是衡量用户资产风险的指标，当风险率 ≤ 10%时，您的仓位将会被系统强制平仓
                    if($riskRate != 0 && $riskRate <= $flatRiskRate){
                        echo $user_id . '--' . $riskRate . '--' . json_encode($account) . "\r\n";
                        // TODO 强制平仓
                        HandleFlatPosition::dispatch($positions,1)->onQueue('HandleFlatPosition');
                    }
                }
            });

            sleep(5);
        }
    }

}
