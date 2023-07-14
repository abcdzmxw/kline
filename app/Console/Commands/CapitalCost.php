<?php

namespace App\Console\Commands;

use App\Jobs\HandleFlatPosition;
use App\Models\ContractPosition;
use App\Models\SustainableAccount;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Console\Command;

class CapitalCost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capitalCost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '资金费 收取持仓用户所持仓位金额的百分比';

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
        // 资金费率
        $costRate = get_setting_value('cost_rate','contract',0.0001);

        $positions = ContractPosition::query()->where('hold_position','>',0)->cursor();
        foreach ($positions as $position){
            $this->handleCostRate($position,$costRate);
        }
    }

    private function handleCostRate($position,$costRate)
    {
        // 持仓金额
        $position_amount = $position['hold_position'] * $position['unit_amount'];
        $cost = PriceCalculate($position_amount ,'*', $costRate,4); // 资金费

        $balance = SustainableAccount::query()->where('user_id',$position['user_id'])->value('usable_balance');
        if($balance <= $cost) {
            // 合约账户可用保证金 不足以支付资金费时 执行强平
            HandleFlatPosition::dispatch([$position])->onQueue('HandleFlatPosition');
        }else{
            $user = User::query()->find($position['user_id']);
            if(!blank($user)){
                $user->update_wallet_and_log($position['margin_coin_id'],'usable_balance',-$cost,UserWallet::sustainable_account,'position_capital_cost','',$position['contract_id']);
            }
        }
    }

}
