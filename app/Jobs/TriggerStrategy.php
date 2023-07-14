<?php

namespace App\Jobs;

use App\Exceptions\ApiException;
use App\Models\ContractEntrust;
use App\Models\ContractPair;
use App\Models\ContractPosition;
use App\Models\ContractStrategy;
use App\Services\ContractService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class TriggerStrategy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务可以尝试的最大次数。
     *
     * @var int
     */
    public $tries = 3;

    private $data;

    /**
     * Create a new job instance.
     * @param array $data [$symbol,$realtime_price]
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws ApiException
     */
    public function handle()
    {
        $symbol = $this->data['symbol'];
        $realtime_price = $this->data['realtime_price'];

        $strategys = ContractStrategy::query()
            ->where('symbol',$symbol)
            ->where('status',1)
            ->where(function ($q) use ($realtime_price){
                $q->where([['position_side','=',1],['tp_trigger_price','<>',''],['tp_trigger_price','<=',$realtime_price]])
                ->orWhere([['position_side','=',1],['sl_trigger_price','<>',''],['sl_trigger_price','>=',$realtime_price]])
                ->orWhere([['position_side','=',2],['tp_trigger_price','<>',''],['tp_trigger_price','>=',$realtime_price]])
                ->orWhere([['position_side','=',2],['sl_trigger_price','<>',''],['sl_trigger_price','<=',$realtime_price]]);
            })->cursor();

        foreach ($strategys as $strategy){

            $position = ContractPosition::getPosition(['user_id'=>$strategy['user_id'],'contract_id'=>$strategy['contract_id'],'side'=>$strategy['position_side']]);
            if(blank($position)) continue;

            DB::beginTransaction();
            try{

                $strategy->update(['status'=>0]);

                $avail_position = $position->avail_position; // 可平数量
                if($avail_position > 0){
                    $pair = ContractPair::query()->where('symbol',$position['symbol'])->first();
                    if(blank($pair)) continue;
                    // 记录仓位保证金(平仓时直接抵消掉)
                    $margin = ($position['position_margin'] / $position['hold_position']) * $position['avail_position'];
                    $unit_fee = PriceCalculate($pair['unit_amount'] ,'*', $pair['maker_fee_rate'],5); // 单张合约手续费
                    $fee = PriceCalculate($position['avail_position'] ,'*', $unit_fee,5);

                    $entrust_price = null;
                    if($position['side'] == 1){
                        if($strategy['tp_trigger_price'] != '' && $strategy['tp_trigger_price'] <= $realtime_price){
                            $entrust_price = $strategy['tp_trigger_price'];
                        }elseif($strategy['sl_trigger_price'] != '' && $strategy['sl_trigger_price'] >= $realtime_price){
                            $entrust_price = $strategy['sl_trigger_price'];
                        }
                    }else{
                        if($strategy['tp_trigger_price'] != '' && $strategy['tp_trigger_price'] >= $realtime_price){
                            $entrust_price = $strategy['tp_trigger_price'];
                        }elseif($strategy['sl_trigger_price'] != '' && $strategy['sl_trigger_price'] <= $realtime_price){
                            $entrust_price = $strategy['sl_trigger_price'];
                        }
                    }
                    if(empty($entrust_price)) continue;

                    //创建订单
                    $order_data = [
                        'user_id' => $position['user_id'],
                        'order_no' => get_order_sn('PCB'),
                        'contract_id' => $pair['id'],
                        'contract_coin_id' => $pair['contract_coin_id'],
                        'margin_coin_id' => $pair['margin_coin_id'],
                        'symbol' => $pair['symbol'],
                        'unit_amount' => $position['unit_amount'],
                        'order_type' => 2,
                        'side' => $position['side'] == 1 ? 2 : 1,
                        'type' => 1,
                        'entrust_price' => $entrust_price,
                        'amount' => $avail_position,
                        'lever_rate' => $position['lever_rate'],
                        'margin' => $margin,
                        'fee' => $fee,
                        'hang_status' => 1,
                        'trigger_price' => null,
                        'ts' => time(),
                    ];
                    $entrust = ContractEntrust::query()->create($order_data);

                    // 冻结持仓数量
                    $position->update([
                        'avail_position' => $position->avail_position - $avail_position,
                        'freeze_position' => $position->freeze_position + $avail_position,
                    ]);

                    // 执行成交平仓
                    $service = new ContractService();
                    if ($entrust['order_type'] == 2 && $entrust['side'] == 1){
                        // 买入平空
                        $service->handleFlatBuyOrder($entrust);
                    }elseif ($entrust['order_type'] == 2 && $entrust['side'] == 2){
                        // 卖出平多
                        $service->handleFlatSellOrder($entrust);
                    }
                }

                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                info($e);
                continue;
            }

        }

    }

}
