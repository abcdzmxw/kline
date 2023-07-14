<?php

namespace App\Console\Commands;

use App\Models\OptionPair;
use App\Models\OptionScene;
use App\Models\OptionTime;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOptionScene extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createOptionScene';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建期权场景';

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
        $items = OptionTime::query()->where('option_time.status',1)
            ->where('option_pair.status',1)
            ->select(['option_time.time_id','option_time.time_name','option_time.seconds','option_time.odds_up_range','option_time.odds_down_range','option_time.odds_draw_range','option_pair.pair_id','option_pair.pair_name'])
            ->crossJoin('option_pair')->get()->toArray();

        try {
            foreach ($items as $item){
                $pair_id = $item['pair_id'];
                $time_id = $item['time_id'];
                $start = Carbon::now();
                $end = Carbon::now()->addSeconds($item['seconds']);
                $range = date_range($start,$end,$item['seconds']);
                $pair_name = $item['pair_name'];
                $symbol = strtolower(str_before($pair_name,'/') . str_after($pair_name,'/'));
                $new_date = Arr::first($range,function ($value, $key) use ($start) {
                    return $value >= $start;
                });
//                dd($range,$start->toDateTimeString(),$end->toDateTimeString(),$new_date);
                if(!$new_date){
                    continue;
                }
                $carbon_obj = Carbon::parse($new_date);
                $begin_time = $carbon_obj->timestamp;
                $end_time = $carbon_obj->addSeconds($item['seconds'])->timestamp;
                $where = [
                    'pair_id' => $pair_id,
                    'time_id' => $time_id,
                    'begin_time' => $begin_time,
                ];
                $up_odds_data = [];
                $up_range = $item['odds_up_range'];
                if(!is_array($up_range)) $up_range = json_decode($up_range,true);
                foreach ($up_range as $k1 => $v1){
                    $odds = $v1['odds'];
                    $up_odds_data[$k1] = ['uuid'=>Str::uuid(),'range'=>$v1['range'],'odds'=>$odds,'up_down'=>1];
                }
                $down_odds_data = [];
                $down_range = $item['odds_down_range'];
                if(!is_array($down_range)) $down_range = json_decode($down_range,true);
                foreach ($down_range as $k2 => $v2){
                    $odds = $v2['odds'];
                    $down_odds_data[$k2] = ['uuid'=>Str::uuid(),'range'=>$v2['range'],'odds'=>$odds,'up_down'=>2];
                }
                $draw_odds_data = [];
                $draw_range = $item['odds_draw_range'];
                if(!is_array($draw_range)) $draw_range = json_decode($draw_range,true);
                foreach ($draw_range as $k3 => $v3){
                    $odds = $v3['odds'];
                    $draw_odds_data[$k3] = ['uuid'=>Str::uuid(),'range'=>$v3['range'],'odds'=>$odds,'up_down'=>3];
                }

                
                
                $create_data = [
                    'scene_sn' => get_order_sn('scene'),
                    'time_id' => $time_id,
                    'seconds' => $item['seconds'],
                    'pair_id' => $pair_id,
                    'pair_time_name' => $item['pair_name'] . '-' . $item['time_name'],
                    'up_odds' => $up_odds_data,
                    'down_odds' => $down_odds_data,
                    'draw_odds' => $draw_odds_data,
                    'begin_time' => $begin_time,
                    'end_time' => $end_time,
                    'begin_price' => Cache::store('redis')->get('market:' . $symbol . '_newPrice')['price'] ?? null,
                ];

                $carbon_obj1 = Carbon::parse($new_date)->subSeconds($item['seconds']);
                $begin_time1 = $carbon_obj1->timestamp;
                $end_time1 = $carbon_obj1->addSeconds($item['seconds'])->timestamp;
                $where1 = [
                    'pair_id' => $pair_id,
                    'time_id' => $time_id,
                    'begin_time' => $begin_time1,
                ];
                $create_data1 = [
                    'scene_sn' => get_order_sn('scene'),
                    'time_id' => $time_id,
                    'seconds' => $item['seconds'],
                    'pair_id' => $pair_id,
                    'pair_time_name' => $item['pair_name'] . '-' . $item['time_name'],
                    'up_odds' => $up_odds_data,
                    'down_odds' => $down_odds_data,
                    'draw_odds' => $draw_odds_data,
                    'begin_time' => $begin_time1,
                    'end_time' => $end_time1,
                    'begin_price' => Cache::store('redis')->get('market:' . $symbol . '_newPrice')['price'] ?? null,
                    
                ];

                $carbon_obj2 = Carbon::parse($new_date)->addSeconds($item['seconds']);
                $begin_time2 = $carbon_obj2->timestamp;
                $end_time2 = $carbon_obj2->addSeconds($item['seconds'])->timestamp;
                $where2 = [
                    'pair_id' => $pair_id,
                    'time_id' => $time_id,
                    'begin_time' => $begin_time2,
                ];
                $create_data2 = [
                    'scene_sn' => get_order_sn('scene'),
                    'time_id' => $time_id,
                    'seconds' => $item['seconds'],
                    'pair_id' => $pair_id,
                    'pair_time_name' => $item['pair_name'] . '-' . $item['time_name'],
                    'up_odds' => $up_odds_data,
                    'down_odds' => $down_odds_data,
                    'draw_odds' => $draw_odds_data,
                    'begin_time' => $begin_time2,
                    'end_time' => $end_time2,
                    'begin_price' => Cache::store('redis')->get('market:' . $symbol . '_newPrice')['price'] ?? null,
                ];

                $scene1 = OptionScene::query()->firstOrCreate($where1,$create_data1);
                $scene = OptionScene::query()->firstOrCreate($where,$create_data);
                $scene2 = OptionScene::query()->firstOrCreate($where2,$create_data2);
                if(!isset($scene['status'])){
                    //创建期权场景成功
                    Cache::store('redis')->put('get_begin_price:'.$scene->scene_id,$scene->scene_id,PriceCalculate($begin_time,'-',time())); // 获取期权周期开始价格
                    Cache::store('redis')->put('option_delivery:'.$scene->scene_id,$scene->scene_id,PriceCalculate($end_time,'-',time())); // 指定周期时间后--执行期权交割
                }
                if(!isset($scene1['status'])){
                    //创建期权场景成功
                    Cache::store('redis')->put('get_begin_price:'.$scene1->scene_id,$scene1->scene_id,PriceCalculate($begin_time1,'-',time())); // 获取期权周期开始价格
                    Cache::store('redis')->put('option_delivery:'.$scene1->scene_id,$scene1->scene_id,PriceCalculate($end_time1,'-',time())); // 指定周期时间后--执行期权交割
                }
                if(!isset($scene2['status'])){
                    //创建期权场景成功
                    Cache::store('redis')->put('get_begin_price:'.$scene2->scene_id,$scene2->scene_id,PriceCalculate($begin_time2,'-',time())); // 获取期权周期开始价格
                    Cache::store('redis')->put('option_delivery:'.$scene2->scene_id,$scene2->scene_id,PriceCalculate($end_time2,'-',time())); // 指定周期时间后--执行期权交割
                }
            }
        } catch (\Exception $e) {
            info($e);
        }

    }
}
