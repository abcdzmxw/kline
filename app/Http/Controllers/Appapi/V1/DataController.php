<?php

namespace App\Http\Controllers\Appapi\V1;

use App\Http\Controllers\Controller;
use App\Models\Coins;
use App\Models\InsideTradePair;
use App\Models\OptionPair;
use App\Models\OptionScene;
use App\Models\OptionTime;
use App\Services\HuobiService\HuobiapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DataController extends ApiController
{
    //

    public function sceneListNewPrice()
    {
        \Channel\Client::connect('127.0.0.1', 2307);
        $pairs = OptionPair::query()->where('status',1)->get();
        $times = OptionTime::query()->where('status',1)->get();
        $data = [];
        foreach ($pairs as $key => $pair){
            if(blank($pair)) continue;
            $cache_key = 'market:' . $pair['symbol'] . '_newPrice';
            $cache_data = Cache::store('redis')->get($cache_key);

            $pair_id = $pair['pair_id'];
            $data[$key]['guessPairsName'] = $pair['pair_name'];
            foreach ($times as $time){
                $time_id = $time['time_id'];
                $start = Carbon::now();
                $end = Carbon::now()->addSeconds($time['seconds']);
                $range = date_range($start,$end,$time['seconds']);
                $new_date = Arr::first($range,function ($value, $key) use ($start) {
                    return $value >= $start;
                });
                if($new_date){
                    $carbon_obj = Carbon::parse($new_date);
                    $begin_time = $carbon_obj->timestamp;
                    $where = [
                        'pair_id' => $pair_id,
                        'time_id' => $time_id,
                        'begin_time' => $begin_time,
                    ];
                    $scene = OptionScene::query()->where($where)->first();
                    $scene['increase'] = $cache_data['increase'];
                    $scene['increaseStr'] = $cache_data['increaseStr'];

                    $data[$key]['scenePairList'][] = $scene;
                }else{
                    $data[$key]['scenePairList'][] = [];
                }
            }
        }
        $group_id = 'sceneListNewPrice'; //
        \Channel\Client::publish('send_to_group', array(
            'group_id'=>$group_id,
            'message'=>$data
        ));
    }

    public function market()
    {
        \Channel\Client::connect('127.0.0.1', 2307);

        $group_id2 = 'indexMarketList'; //
        $group_id3 = 'exchangeMarketList'; //

        $market = [];
        $data = InsideTradePair::query()->where('status',1)->orderBy('sort','asc')->get()->groupBy('quote_coin_name')->toArray();
        $kk = 0;
        foreach ($data as $k => $items){
            $coin = Coins::query()->where('coin_name',$k)->first();
            $market[$kk]['coin_name'] = $coin['coin_name'];
            $market[$kk]['full_name'] = $coin['full_name'];
            $market[$kk]['coin_icon'] = getFullPath($coin['coin_icon']);
            $market[$kk]['coin_content'] = $coin['coin_content'];
            $market[$kk]['qty_decimals'] = $coin['qty_decimals'];
            $market[$kk]['price_decimals'] = $coin['price_decimals'];
            $quote_coin_name = strtolower($k);
            foreach ($items as $key2 => $item){
                $cd = Cache::store('redis')->get('market:' . $item['symbol'] . '_detail');
//                $key = 'market:' . $item['symbol'] . '_newPrice';
//                $cd = Cache::store('redis')->get($key);
                $cd['price'] = $cd['close'];
                $cd['qty_decimals'] = $item['qty_decimals'];
                $cd['price_decimals'] = $item['price_decimals'];
                $cd['min_qty'] = $item['min_qty'];
                $cd['min_total'] = $item['min_total'];
                $cd['coin_name'] = $item['base_coin_name'];
                $cd['pair_id'] = $item['pair_id'];
                $cd['pair_name'] = $item['pair_name'];
                $cd['symbol'] = $item['symbol'];
                $market[$kk]['marketInfoList'][$key2] = $cd;
            }
            $kk++;
        }

        // Channel\Client给所有服务器的所有进程广播分组发送消息事件
        \Channel\Client::publish('send_to_group', array(
            'group_id'=>$group_id2,
            'message'=>$market
        ));
        \Channel\Client::publish('send_to_group', array(
            'group_id'=>$group_id3,
            'message'=>$market
        ));
    }

}
