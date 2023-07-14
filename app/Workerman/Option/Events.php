<?php


namespace App\Workerman\Option;

use App\Models\CoinData;
use App\Models\Coins;
use App\Models\InsideTradePair;
use App\Models\OptionPair;
use App\Models\OptionScene;
use App\Models\OptionTime;
use App\Models\TestTradeBuy;
use App\Models\TestTradeOrder;
use App\Models\TestTradeSell;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use \Workerman\Lib\Timer;
use GatewayWorker\Lib\Gateway;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Http\Client;

class Events
{
    const MAX_PACKAGE = 256;

    public static function onWorkerStart($businessWorker)
    {
        // 拿到当前进程的id编号.
        $workid = $businessWorker->id;
        echo 'workid--' . $workid . "\r\n";
        $chs = ['marketList','indexMarketList','exchangeMarketList','newPrice'];
        if ($workid == 0) {
            $options = OptionTime::query()->where('option_time.status',1)
                ->where('option_pair.status',1)
                ->select(['option_pair.pair_id','option_pair.pair_name','option_pair.symbol','option_time.time_id','option_time.time_name','option_time.seconds'])
                ->crossJoin('option_pair')->get()->toArray();
            foreach ($options as $option){
                Timer::add(1, function ($option) {
                    $group_id = 'sceneListNewPrice';
                    $cache_data = Cache::store('redis')->get('market:' . $option['symbol'] . '_newPrice');
                    if(Gateway::getClientIdCountByGroup($group_id) > 0){
                        $scene = OptionScene::query()->where([
                            ['time_id','=',$option['time_id']],
                            ['pair_id','=',$option['pair_id']],
                            ['end_time','>=',time()],
                            ['end_time','<',time() + $option['seconds']],
                        ])->first();
                        if(blank($scene)){
                            $data = [];
                        }else{
                            $data = [
                                'time_id' => $option['time_id'],
                                'pair_id' => $option['pair_id'],
                                'time_name' => $option['time_name'],
                                'pair_name' => $option['pair_name'],
                                'symbol' => $option['symbol'],
                                'pair_time_name' => $scene['pair_time_name'],
                                'upodds' => $scene['up_odds'][0]['odds'],
                                'downodds' => $scene['down_odds'][0]['odds'],
                                'increase' => $cache_data['increase'],
                                'increaseStr' => $cache_data['increaseStr'],
                                'trend_up' => mt_rand(10,99) / 100,
                            ];
                            $data['trend_down'] = round(1 - $data['trend_up'],2);
                        }
                        $message = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id]);
                        Gateway::sendToGroup($group_id,$message);
                    }
                },[$option]);
            }

            Timer::add(1, function () {
                $data = Events::getMarketList();
                $group_id2 = 'indexMarketList';
                $group_id3 = 'exchangeMarketList';
                $message2 = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id2]);
                $message3 = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id3]);
                if(Gateway::getClientIdCountByGroup($group_id2) > 0) Gateway::sendToGroup($group_id2,$message2);
                if(Gateway::getClientIdCountByGroup($group_id3) > 0) Gateway::sendToGroup($group_id3,$message3);
            });

            // COIN_SYMBOL -- START
            $coins = config('coin.exchange_symbols');
            $seconds = 3;
            foreach ($coins as $coin1 => $class){
                $coin1 = strtolower($coin1);
                Timer::add($seconds, function ($coin1,$class){
                    $symbol = $coin1 . 'usdt';
                    $group_id = 'buyList_' . $symbol;
                    if(Gateway::getClientIdCountByGroup($group_id) > 0){
                        $data = Events::getCoinBuyList($symbol,$class);
                        $message = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id]);
                        Gateway::sendToGroup($group_id,$message);
                    }
                },[$coin1,$class]);
                // if($coin1 != strtolower(config('coin.coin_symbol'))){
                    Timer::add($seconds, function ($coin1,$class){
                        $symbol = $coin1 . 'usdt';
                        $group_id = 'sellList_' . $symbol;
                        if(Gateway::getClientIdCountByGroup($group_id) > 0){
                            $data = Events::getCoinBuyList($symbol,$class);
                            $message = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id]);
                            Gateway::sendToGroup($group_id,$message);
                        }
                    },[$coin1,$class]);
                // }
                Timer::add($seconds, function ($coin1,$class){
                    $symbol = $coin1 . 'usdt';
                    $group_id = 'tradeList_' . $symbol;
                    if(Gateway::getClientIdCountByGroup($group_id) > 0){
                        $data = Events::getCoinTradeItem($symbol,$class);
                        $message = json_encode(['code'=>0,'msg'=>'success','type'=>'dynamic','data'=>$data,'sub'=>$group_id]);
                        Gateway::sendToGroup($group_id,$message);
                    }
                },[$coin1,$class]);

                // 小白
                $periods = ['1min','5min','15min','30min','60min','1day','1week','1mon'];
                Timer::add($seconds, function ($periods,$coin1,$class) {
                    $symbol = $coin1 . 'usdt';
                    foreach ($periods as $period){
                        $data = Events::getCoinKline($symbol,$period,$class);
                        Cache::store('redis')->put('market:' . $symbol . '_kline_' . $period,$data);

                        $group_id = 'Kline_' . $symbol . '_' . $period;
                        if(Gateway::getClientIdCountByGroup($group_id) > 0){
                            $message = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id,'type'=>'dynamic']);
                            Gateway::sendToGroup($group_id,$message);
                        }
                    }
                },[$periods,$coin1,$class]);
       //Cache::store('redis')->put('market:macusdt_newPrice1',config('database'));
                Timer::add(2, function($coin1,$class)
                {
                    $coin1_symbol = $coin1 . 'usdt';
                    $kline = $class::query()->where('Date','<',time())->where('is_1min',1)->orderByDesc('Date')->first();
                    $day_kline = $class::query()->where('Date',Carbon::yesterday()->getTimestamp())->where('is_day',1)->orderByDesc('Date')->first();
                    if(blank($kline)){
                        $cache_data = [];
                    }else{
                        $decimal = 100000;
                        $ups_downs_high = 20;            //高
                        $ups_downs_low = 1;              //低
                        $up_or_down = mt_rand(1,5);
                        $flag2 = mt_rand(1,2);
                        $cache_data = [
                            "id"=> $kline['Date'],
                            "count"=> $day_kline['Amount'],
                            "open"=> $kline['Open'],
                            "low"=> $kline['Low'],
                            "high"=> $kline['High'],
                            "vol"=> $day_kline['Volume'],
                            "version"=> $kline['Date'],
                            'ts'=> \Carbon\Carbon::now()->getPreciseTimestamp(3),
                        ];
                        $cache_data['amount'] = $flag2 == 1 ? round($day_kline['Amount'] + (mt_rand(10,40) / 100000),5) : round($day_kline['Amount'] - (mt_rand(10,40) / 100000),5);
                        $decimal_price = $kline['Close'] * $decimal;
                        if ($up_or_down <= 3) {
                            $cache_data['close'] = mt_rand($decimal_price, $decimal_price + mt_rand($ups_downs_low, $ups_downs_high)) / $decimal;
                        }else{
                            $cache_data['close'] = mt_rand($decimal_price - mt_rand($ups_downs_low, $ups_downs_high) , $decimal_price) / $decimal;
                        }
                        $cache_data['price'] = $cache_data['close'];
                        if(isset($cache_data['open']) && $cache_data['open'] != 0){
                            if(blank($day_kline)){
                                if(($cache_data['close'] - $cache_data['open']) == 0){
                                    $increase = 0;
                                }else{
                                    $increase = round(($cache_data['close'] - $cache_data['open']) / $cache_data['open'],4);
                                }
                            }else{
                                if(($cache_data['close'] - $day_kline['Close']) == 0){
                                    $increase = 0;
                                }else{
                                    $increase = round(($cache_data['close'] - $day_kline['Close']) / $day_kline['Close'],4);
                                }
                            }
                        }else{
                            $increase = 0;
                        }
                        $cache_data['increase'] = $increase;
                        $flag = $increase >= 0 ? '+' : '';
                        $cache_data['increaseStr'] = $increase == 0 ? '+0.00%' : $flag . $increase * 100 . '%';
                    }
                    $cache_data2 = [
                        "id"=> Str::uuid()->toString(),
                        "ts"=> $cache_data['ts'],
                        "tradeId"=> Str::uuid()->toString(),
                        "amount"=> $cache_data['amount'],
                        "price"=> $cache_data['price'],
                        // "direction"=> "buy",  (a ? b : c) ? d : e
                       // 'direction'=> $coin1 == strtolower(config('coin.coin_symbol')) ? 'buy' :(mt_rand(0,1) == 0 ? 'buy' : 'sell'),
                           'direction'=> $coin1 == strtolower(config('coin.coin_symbol')) ? 'buy' : mt_rand(0,1) == 0 ? 'buy' : 'sell',
                        "increase"=> $cache_data['increase'],
                        "increaseStr"=> $cache_data['increaseStr']
                    ];

                    // 历史价格数据book
                    $new_price_book_key = 'market:' . $coin1_symbol . '_newPriceBook';
                    $new_price_book = Cache::store('redis')->get($new_price_book_key);
                    if(blank($new_price_book)){
                        $prices = [];
                    }else{
                        $size = count($new_price_book) >= 10 ? 10 : count($new_price_book);
                        $prices = array_random($new_price_book,$size);
                        $prices = array_values(Arr::sort($prices, function ($value) {
                            return $value['ts'];
                        }));
                        $prices = Arr::pluck($prices, 'price');
                    }
                    $cache_data['prices'] = $prices;

                    Cache::store('redis')->put('market:' . $coin1_symbol . '_detail',$cache_data);
                    if(!blank($cache_data2)){
                        //$cache_data2['__db'] = config('database');
                        Cache::store('redis')->put('market:' . $coin1_symbol . '_newPrice',$cache_data2);
                     
                        
                        //缓存历史价格数据book
                        if(blank($new_price_book)){
                            Cache::store('redis')->put($new_price_book_key,[$cache_data2]);
                        }else{
                            array_push($new_price_book,$cache_data2);
                            if(count($new_price_book) > 200){
                                array_shift($new_price_book);
                            }
                            Cache::store('redis')->put($new_price_book_key,$new_price_book);
                        }
                    }
                },[$coin1,$class]);
            }
            // COIN_SYMBOL -- END

            $pairs = OptionPair::query()->where('status',1)->get()->toArray();
            Timer::add(1, function ($pairs) {
                foreach ($pairs as $pair){
                    $group_id = 'newPrice_' . $pair['symbol'];
                    if(Gateway::getClientIdCountByGroup($group_id) > 0){
                        $data = Events::getNewPrice($pair['symbol']);
                        $message = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id]);
                        Gateway::sendToGroup($group_id,$message);
                    }
                }
            },[$pairs]);

        }
    }

    public static function onWorkerStop($businessWorker)
    {
        // 拿到当前进程的id编号.
        $workid = $businessWorker->id;
        if ($workid == 0) {
            Timer::delAll();
        }
    }

    public static function onConnect($client_id)
    {
    }

    public static function getSceneListNewPrice($type = 'sceneListNewPrice')
    {
        $pairs = OptionPair::query()->where('status',1)->get();
        $times = OptionTime::query()->where('status',1)->get();
        $data = [];
        foreach ($pairs as $key => $pair){
            $cache_data = Cache::store('redis')->get('market:' . $pair['symbol'] . '_newPrice');
            $pair_id = $pair['pair_id'];

            $data[$key]['guessPairsName'] = $pair['pair_name'];
            foreach ($times as $time){
                $time_id = $time['time_id'];
                $scene = OptionScene::query()->where([
                    ['time_id','=',$time_id],
                    ['pair_id','=',$pair_id],
                    ['end_time','>=',time()],
                    ['end_time','<',time() + $time['seconds']],
                ])->first();
                $scene['increase'] = $cache_data['increase'];
                $scene['increaseStr'] = $cache_data['increaseStr'];

                $data[$key]['scenePairList'][] = $scene;
            }
        }
        return $data;
    }

    public static function getMarketList($type = 'marketList')
    {
        $market = [];
//        $data = InsideTradePair::query()->where('status',1)->get()->groupBy('quote_coin_name')->toArray();
        $data = InsideTradePair::getCachedPairs();
        $kk = 0;
        foreach ($data as $k => $items){
            $coin = array_first(Coins::getCachedCoins(),function ($value,$key) use ($k){
                return $value['coin_name'] == $k;
            });
            if(blank($coin)) continue;
            $market[$kk]['coin_name'] = $coin['coin_name'];
            $market[$kk]['full_name'] = $coin['full_name'];
            $market[$kk]['coin_icon'] = getFullPath($coin['coin_icon']);
            $market[$kk]['coin_content'] = $coin['coin_content'];
            $market[$kk]['qty_decimals'] = $coin['qty_decimals'];
            $market[$kk]['price_decimals'] = $coin['price_decimals'];
            foreach ($items as $key2 => $item){
                $cd = Cache::store('redis')->get('market:' . $item['symbol'] . '_detail');
                $coin_name = $item['base_coin_name'];
                $coin2 = array_first(Coins::getCachedCoins(),function ($value,$key) use ($coin_name){
                    return $value['coin_name'] == $coin_name;
                });
                $cd['price'] = $cd['close'];
                $cd['qty_decimals'] = $item['qty_decimals'];
                $cd['price_decimals'] = $item['price_decimals'];
                $cd['min_qty'] = $item['min_qty'];
                $cd['min_total'] = $item['min_total'];
                $cd['coin_name'] = $item['base_coin_name'];
                $cd['coin_icon'] = getFullPath($coin2['coin_icon']);
                $cd['pair_id'] = $item['pair_id'];
                $cd['pair_name'] = $item['pair_name'];
                $cd['symbol'] = $item['symbol'];
                $market[$kk]['marketInfoList'][$key2] = $cd;
            }
            $kk++;
        }
        return $market;
    }

    public static function onWebSocketConnect($client_id, $data)
    {
        echo "onWebSocketConnect\r\n";
    }

    // 获取K线
    public static function getCoinKline($symbol,$period,$class)
    {
        $periods = [
            '1min' => 60,
            '5min' => 300,
            '15min' => 900,
            '30min' => 1800,
            '60min' => 3600,
            '1day' => 86400,
            '1week' => 604800,
            '1mon' => 2592000,
        ];
        $wheres = [
            '1min' => 'is_1min',
            '5min' => 'is_5min',
            '15min' => 'is_15min',
            '30min' => 'is_30min',
            '60min' => 'is_1h',
            '1day' => 'is_day',
            '1week' => 'is_week',
            '1mon' => 'is_month',
        ];
        $seconds = $periods[$period] ?? 60;
        $where = $wheres[$period] ?? 'is_1min';
        $kline = $class::query()->where($where,1)->where('Date','>',(time() - $seconds))->where('Date','<=',time())->first();
        $kline_cache_data = Cache::store('redis')->get('market:' . $symbol . '_detail');
        if($kline['Date'] == time()){
            $cache_data = [
                "id"=> $kline['Date'],
                "amount"=> $kline['Amount'],
                "count"=> mt_rand(10,55),
                "open"=> $kline['Open'],
                "close"=> $kline['Close'],
                "low"=> $kline['Low'],
                "high"=> $kline['High'],
                "vol"=> $kline['Volume']
            ];
            $cache_data['price'] = $cache_data['close'];
        }else{
            $cache_data = [
                "id"=> $kline['Date'],
                "amount"=> round($kline['Amount'] + (mt_rand(10,99) / 10000),5),
                "count"=> mt_rand(10,55),
                "open"=> $kline['Open'],
                "close"=> $kline_cache_data['close'],
                "low"=> $kline['Low'],
                "high"=> $kline['High'],
                "vol"=> $kline['Volume']
            ];
            $cache_data['price'] = $cache_data['close'];
        }

        return $cache_data;
    }

    public static function getCoinBuyList($symbol,$class)
    {
        $kline = $class::query()->where('is_1min',1)->where('Date','<',time())->orderByDesc('Date')->first();
        if(blank($kline)) return [];
        $kline_cache_data = Cache::store('redis')->get('market:' . $symbol . '_detail');
        $buyList = [];

        for ($i = 0; $i <= 19; $i++) {
            if($i == 0){
                $buyList[$i] = [
                    'id'=> Str::uuid(),
                    "amount"=> round((mt_rand(10000,3000000) / 1000),4),
                    'price'=> $kline_cache_data['close'],
                ];
            }else{
                $open = $kline['Open'];
                $close = $kline['Close'];
                $min = min($open,$close) * 100000;
                $max = max($open,$close) * 100000;
                $price = round(mt_rand($min,$max) / 100000,5);

                $buyList[$i] = [
                    'id'=> Str::uuid()->toString(),
                    "amount"=> round((mt_rand(10000,3000000) / 1000),4),
                    'price'=> $price,
                ];
            }
        }
        return $buyList;
    }

    public static function getCoinTradeList($symbol,$class)
    {
        $kline = $class::query()->where('is_1min',1)->where('Date','<',time())->orderByDesc('Date')->first();
        if(blank($kline)) return [];
        $kline_cache_data = Cache::store('redis')->get('market:' . $symbol . '_detail');
        $tradeList = [];

        for ($i = 0; $i <= 30; $i++) {
            if($i == 0){
                $tradeList[$i] = [
                    'id'=> Str::uuid(),
                    "amount"=> round((mt_rand(10000,3000000) / 1000),4),
                    'price'=> $kline_cache_data['close'],
                    'tradeId'=> Str::uuid()->toString(),
                    'ts'=> Carbon::now(config('app.url',''))->getPreciseTimestamp(3),
                    'increase'=> -0.1626,
                    'increaseStr'=> "-16.26%",
                    'direction'=> mt_rand(0,1) == 0 ? 'buy' : 'sell',
                ];
            }else{
                $open = $kline['Open'];
                $close = $kline['Close'];
                $min = min($open,$close) * 100000;
                $max = max($open,$close) * 100000;
                $price = round(mt_rand($min,$max) / 100000,5);

                $tradeList[$i] = [
                    'id'=> Str::uuid()->toString(),
                    "amount"=> round((mt_rand(10000,3000000) / 1000),4),
                    'price'=> $price,
                    'tradeId'=> Str::uuid()->toString(),
                    'ts'=> Carbon::now(config('app.url',''))->getPreciseTimestamp(3),
                    'increase'=> -0.1626,
                    'increaseStr'=> "-16.26%",
                    'direction'=> mt_rand(0,1) == 0 ? 'buy' : 'sell',
                ];
            }
        }
        return $tradeList;
    }

    public static function getNewPrice($symbol)
    {
        $key = 'market:' . $symbol . '_newPrice';
        $data = Cache::store('redis')->get($key);
        $data['ts'] = Carbon::now()->getPreciseTimestamp(3);
        return $data;
    }

    public static function getCoinTradeItem($symbol,$class = null)
    {
        $kline_cache_data = Cache::store('redis')->get('market:' . $symbol . '_detail');
        $tradeItem = [
            'id'=> Str::uuid()->toString(),
            "amount"=> round((mt_rand(10000,3000000) / 1000),4),
            'price'=> $kline_cache_data['close'],
            'tradeId'=> Str::uuid()->toString(),
            'ts'=> Carbon::now()->getPreciseTimestamp(3),
            'increase'=> 0,
            'increaseStr'=> "--",
            'direction'=> mt_rand(0,1) == 0 ? 'buy' : 'sell',
        ];

        return $tradeItem;
    }

    public static function onMessage($client_id, $message)
    {
//        if (strlen($message) > Events::MAX_PACKAGE) Gateway::closeClient($client_id);
        echo $message .':'. $client_id . "--onMessage\r\n";
        $message = json_decode($message);

        if(isset($message->cmd)){
            switch ($message->cmd){
                case 'pong' :
                    Gateway::sendToClient($client_id, json_encode(['code'=>0,'msg'=>'success']));
                    break;
                case 'sub' :
                    $sub = $message->msg;

                    $_SESSION['subs'][$sub] = $sub;
                    Gateway::joinGroup($client_id, $sub);

                    break;
                case 'unsub' :
                    $sub = $message->msg;

                    if(array_get($_SESSION['subs'], $sub)){
                        array_forget($_SESSION['subs'], $sub);
                        Gateway::leaveGroup($client_id, $sub);
                    }

                    break;
                case 'req' :
                    $ch = $message->msg;
                    $type = str_before($ch,'_');
                    if($type == 'tradeList'){
                        $params = str_after($ch,'_');
                        $symbol = str_before($params,'_');

                        // 火币最新成交明细缓存
                        $new_price_book_key = 'market:' . $symbol . '_newPriceBook';
                        $new_price_book = Cache::store('redis')->get($new_price_book_key);
                        if(blank($new_price_book)) $new_price_book = [];
                        if(!blank($new_price_book)) $new_price_book = array_reverse($new_price_book);
                        $new_price_book = array_slice($new_price_book,-30,30);
                        Gateway::sendToClient($client_id, json_encode(['code'=>0,'msg'=>'success','data'=>$new_price_book,'sub'=>$ch,'type'=>'history','client_id'=>$client_id]));
                    }elseif($type == 'Kline'){
                        $params = str_after($ch,'_');
                        $symbol = str_before($params,'_');
                        $period = str_after($params,'_');
                        if(blank($symbol) || blank($period)){
                            Gateway::sendToClient($client_id, json_encode(['code'=>-1,'msg'=>'params error','client_id'=>$client_id]));
                            break;
                        }

                        $self_coin = config('coin.coin_symbol');
                        if(strpos($symbol,strtolower($self_coin)) !== false){
                            $kline_book = CoinData::getKlineData($symbol,$period,200);
                            if(blank($kline_book)) $kline_book = [];
                            Gateway::sendToClient($client_id, json_encode(['code'=>0,'msg'=>'success','data'=>$kline_book,'sub'=>$ch,'type'=>'history','client_id'=>$client_id]));
                        }else{
                            $kline_book_key = 'market:' . $symbol . '_kline_book_' . $period;
                            $kline_book = Cache::store('redis')->get($kline_book_key);
                            if(blank($kline_book)) $kline_book = [];
                            Gateway::sendToClient($client_id, json_encode(['code'=>0,'msg'=>'success','data'=>$kline_book,'sub'=>$ch,'type'=>'history','client_id'=>$client_id]));
                        }
                    }

                    break;
            }
        }
        return true;
    }

    public static function onClose($client_id)
    {
        if(isset($_SESSION['time_id'])){
            Timer::del($_SESSION['time_id']);
        }
    }
}
