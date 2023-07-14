<?php

namespace App\Console\Commands;

use App\Models\ContractEntrust;
use App\Models\Recharge;
use App\Models\SustainableAccount;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use Illuminate\Console\Command;

class CheckAnomaly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CheckAnomaly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $items = SustainableAccount::query()->cursor();
        foreach ($items as $item){
            dump($item['user_id']);
            // 合约转入
            $contract_in = UserWalletLog::query()
                ->where('user_id',$item['user_id'])
                ->where('rich_type','usable_balance')
                ->where('account_type',UserWallet::sustainable_account)
                ->where('log_type','fund_transfer')
                ->where('amount','>',0)
                ->sum('amount');
            // 合约转出
            $contract_out = UserWalletLog::query()
                ->where('user_id',$item['user_id'])
                ->where('rich_type','usable_balance')
                ->where('account_type',UserWallet::sustainable_account)
                ->where('log_type','fund_transfer')
                ->where('amount','<',0)
                ->sum('amount');
            // 手续费
            $fee = UserWalletLog::query()
                ->where('user_id',$item['user_id'])
                ->where('rich_type','usable_balance')
                ->where('account_type',UserWallet::sustainable_account)
                ->whereIn('log_type',['open_position_fee','close_position_fee','system_close_position_fee','cancel_open_position_fee'])
                ->sum('amount');
            // 资金费
            $cost = UserWalletLog::query()
                ->where('user_id',$item['user_id'])
                ->where('rich_type','usable_balance')
                ->where('account_type',UserWallet::sustainable_account)
                ->where('log_type','position_capital_cost')
                ->sum('amount');
            // 盈亏
            $profit = ContractEntrust::query()
                ->where('user_id',$item['user_id'])
                ->where('status',ContractEntrust::status_completed)
                ->sum('profit');
            // 合约扣款
            $charge = Recharge::query()
                ->where('user_id',$item['user_id'])
                ->where('type',2)
                ->where('account_type',UserWallet::sustainable_account)
                ->sum('amount');

            // 理论余额
            $theory_balance = PriceCalculate(($contract_in + $contract_out - abs($fee) - abs($cost) + $profit - $item['used_balance'] - $item['freeze_balance']) ,'+', $charge,8);
            // 异常资金
            $anomaly_balance = PriceCalculate($theory_balance,'-',$item['usable_balance'],8);
            if($anomaly_balance != 0){
                $user = User::query()->find($item['user_id']);
                $user->update(['contract_anomaly'=>1]);
            }
        }
    }
}
