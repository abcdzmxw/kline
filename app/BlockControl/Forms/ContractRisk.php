<?php

namespace App\BlockControl\Forms;

use App\Models\ContractPair;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class ContractRisk extends Form
{
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return Response
     */
    public function handle(array $input)
    {
//         dd($input);

        $symbols = ContractPair::query()->pluck('symbol');
        foreach ($symbols as $symbol){
            if(!empty($input[$symbol])){
                $risk_key = 'fkJson:' . $symbol . '/USDT';
                $data = $input[$symbol];
                Redis::set($risk_key,json_encode($data));
            }
        }

        // return $this->error('Your error message.');

        return $this->success('Processed successfully.');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $symbols = ContractPair::query()->pluck('symbol');

        $index = 1;
        foreach ($symbols as $symbol){
            $func = function () use ($symbol){
                // 获取风控任务
                $risk_key = 'fkJson:' . $symbol . '/USDT';
                $risk = json_decode( Redis::get($risk_key) ,true);
                $this->row(function ($row) use ($symbol,$risk) {
                    $minUnit = $risk['minUnit'] ?? 0;
                    $count = $risk['count'] ?? 0;
                    $enabled = $risk['enabled'] ?? 0;

                    $row->width(3)->text($symbol . '.' . 'minUnit','单位')->default($minUnit);
                    $row->width(3)->text($symbol . '.' . 'count','计数')->default($count);
                    $row->width(3)->switch($symbol . '.' . 'enabled','开关')->default($enabled);
                });
            };
            // 第一个参数是选项卡标题，第二个参数是内容，第三个参数是是否选中
            $title = $symbol . '合约';
            if($index == 1){
                $this->tab($title,$func,true);
            }else{
                $this->tab($title,$func);
            }
            $index++;
        }
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [];
    }
}
