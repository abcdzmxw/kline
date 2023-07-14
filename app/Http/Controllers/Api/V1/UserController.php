<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Advice;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ContractEntrust;
use App\Models\InsideTradeOrder;
use App\Models\InsideTradePair;
use App\Models\InsideTradeRisk;
use App\Models\Mongodb\NewPriceBook;
use App\Models\OptionPair;
use App\Models\OptionScene;
use App\Models\OptionSceneOrder;
use App\Models\OptionTime;
use App\Models\UserGrade;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use App\Models\Withdraw;
use App\Models\Coins;
use App\Models\Recharge;
use App\Models\SustainableAccount;
use App\Models\TransferRecord;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Services\CoinService\BitCoinService;
use App\Services\CoinService\GethService;
use App\Services\CoinService\GethTokenService;
use App\Services\CoinService\OmnicoreService;
use App\Services\CoinService\TronService;
use App\Services\UdunWalletService;
use App\Services\UserService;
use App\Exceptions\ApiException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use App\Libs\Ethtool\Callback;
use phpseclib\Math\BigInteger as BigNumber;
use Illuminate\Support\Facades\App;


class UserController extends ApiController
{
    public function test(){
        // $from = '0x518EFf46032e8a43Ae64629C9D2cB5c180D96fD3';
        // $from_pk = '375a89dd4f45619b99e7029a5e169a8b0bbdc659644b50dabe31a4861ff91932';
        // $to = '0x062b624351B92d3aEdAEe8C9C713751CDAdA9eAF';
        // $h = (new GethTokenService('0x11465FDC8D73B927244cD2D5a296b9d22e7382e2',config('coin.erc20_usdt.abi')))->sendRawToken($from,$from_pk,$to,100,'0x11465FDC8D73B927244cD2D5a296b9d22e7382e2');
        // dd($h,1);

        $symbol = 'DASH';
        $period = '5min';
        $seconds = 300;
        $cache_data = ['id'=>1608273600,'open'=>1,'close'=>2,'high'=>35,'low'=>21];
        $kline_book_key = 'swap:' . $symbol . '_kline_book_' . $period;
        $kline_book = Cache::store('redis')->get($kline_book_key);
//        dd($kline_book);
        $last_item1 = array_pop($kline_book);
        $update_last_item1 = $last_item1;
        $periodMap = [
            '5min' => ['period'=>'1min','seconds'=>60],
            '15min' => ['period'=>'5min','seconds'=>300],
            '30min' => ['period'=>'5min','seconds'=>300],
            '60min' => ['period'=>'5min','seconds'=>300],
            '1day' => ['period'=>'60min','seconds'=>3600],
            '1week' => ['period'=>'1day','seconds'=>86400],
            '1mon' => ['period'=>'1day','seconds'=>86400],
        ];
        $map = $periodMap[$period] ?? null;
        $kline_base_book = Cache::store('redis')->get('swap:' . $symbol . '_kline_book_' . $map['period']);
        if(!blank($kline_base_book)){
            $first_item_id = $cache_data['id'];
            $last_item_id = $cache_data['id'] + $seconds - $map['seconds'];
            $items = array_where($kline_base_book,function ($value,$key)use($first_item_id,$last_item_id){
                return $value['id'] >= $first_item_id && $value['id'] <= $last_item_id;
            });
dd($items,array_first($items)['open'],array_last($items)['close'],max(array_pluck($items,'high')),min(array_pluck($items,'low')));
            $update_last_item1['open']     = array_first($items)['open'] ?? $update_last_item1['open'];
            $update_last_item1['close']    = array_last($items)['close'] ?? $update_last_item1['close'];
            $update_last_item1['high']     = max(array_pluck($items,'high')) ?? $update_last_item1['high'];
            $update_last_item1['low']      = min(array_pluck($items,'low')) ?? $update_last_item1['low'];
        }
        dd($update_last_item1);

//        $kline_base_book = [
//            ['id'=>1607010900,'open'=>1,'close'=>2,'high'=>35,'low'=>21],
//            ['id'=>1607010960,'open'=>2,'close'=>22,'high'=>36,'low'=>24],
//            ['id'=>1607011020,'open'=>3,'close'=>31,'high'=>32,'low'=>20],
//            ['id'=>1607011080,'open'=>33,'close'=>25,'high'=>30,'low'=>20.4],
//            ['id'=>1607011140,'open'=>5,'close'=>23,'high'=>33,'low'=>24.1],
//        ];
//        dd(max(array_pluck($kline_base_book,'close')));

        // request
//        $apiKey = 'a0cd2e9a2a50b2682b198b5f6ff9b9b3';
//        $params = [
//            'a1' => 1,
//            'b1' => 2,
//            'c1' => 3,
//            'timestamp' => time(),
//        ];
//        ksort($params);
//        $params_str = json_encode($params);
//        $sign = md5($params_str.$apiKey);
//        $params['sign'] = $sign;
//
//        // api
//        $apiKey2 = 'a0cd2e9a2a50b2682b198b5f6ff9b9b3';
//        $request = [
//            'a1' => 1,
//            'b1' => 2,
//            'c1' => 3,
//            'timestamp' => time(),
//            'sign' => 'f06d271e08b5d997465dd6d69437a2d5',
//        ];
//        $params2 = array_except($request,'sign');
//        ksort($params2);
//        $params2 = json_encode($params2);
//        $sign2 = md5($params2.$apiKey2);
//        dd($sign,$sign2);

//        dd(config('coin.udun_switch'));
//        $res = (new UdunWalletService())->supportCoins(true);
//        $res = (new UdunWalletService())->createAddress(60);
//        dd($res,1);
//        $fullNode = new \IEXBase\TronAPI\Provider\HttpProvider(config('node.TRON_HOST'));
//        $solidityNode = null;
//        $eventServer = null;
//        try {
//            $client = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
//        } catch (\IEXBase\TronAPI\Exception\TronException $e) {
//            throw new ApiException($e->getMessage());
//        }

//        $arr = [
//            ["TJZAVXv9i3SBVZCgoPVZqhqY5TZR23KFy2" => "100000055"],
//            ["TMuGH9ekV2dA2Bev96f6RaabskDbsf5691" => "100000000"],
//            ["TYce95NWdDiDgPSp51o6Y4tipRWTkEb5B1" => "1309198192494147000000000"],
//        ];
//
//        $contractAddress = 'TYce95NWdDiDgPSp51o6Y4tipRWTkEb5B1';
//        $value = array_filter($arr, function($v) use ($contractAddress) {
//            return key($v) == $contractAddress;
//        });
//        $first = array_shift($value);
//        dd($value,$first,$first[$contractAddress]);

//        $adds = ['TNaRAoLUyYEV2uF7GUrzSjRQTU8v5ZJ5VR','TBeZq3VkVM15LPFAGHdqb2osrDYexTrZSW','TD73eXvY2FdxYsx38ZbXBreSDqnbeLoF4f','TG3KE9EH5Qh1RmuzWWkTtxFzgv1d23jrAq','TG2X6sYcNMyPpSMCm82oQdTV8XSCeRrkYN'];
//        $res = (new TronService())->getTokenBalance(array_random($adds));
//        dd($res);
//        $res = $client->getAccount('TNaRAoLUyYEV2uF7GUrzSjRQTU8v5ZJ5VR');
//        $address =
//        $client->setAddress('TNaRAoLUyYEV2uF7GUrzSjRQTU8v5ZJ5VR');
//        $manager = $client->getManager();
//        $res = $manager->request('walletsolidity/getaccount');
//        dd($res);
//        dd((new TronService())->getBalance('TUQBTzXrpHbgm3qUKJqg3YpSY7WV7tpqKM'));
//        $res = (new TronService())->newAccount();
//        dd($res instanceof TronAddress);
//        $res->getAddress();
//        dd($res->getAddress(true));
//        $icon = Coins::icon('BTC');
//        dd($icon);
//        $history_data_key = 'market:' . 'btcusdt' . '_kline_book_' . '1min';
//        $history_cache_data = Cache::store('redis')->get($history_data_key);
//        $data = json_encode($history_cache_data);
//        $data = gzencode($data);
//        $data = mb_convert_encoding($data,'UTF-8','UTF-8');
//        $data = json_decode(gzdecode($data),true);
//        dd($data);

//        return $this->successWithData($data);
//        dd($data);
//        $input = '0xa9059cbb000000000000000000000000ab5c66752a9e8167967685f1450532fb96d5d24f000000000000000000000000000000000000000000000000000000003b4e7ec0';
//        $input_str = substr($input,10);
//        $input_data = str_split($input_str,64);
//        $to = '0x' . ltrim($input_data[0],'0');
//        $value = hexdec(ltrim($input_data[1],'0')) / pow(10,6);
//        dd($to,$value);

//        $trade_detail = Cache::store('redis')->get('swap:' . 'trade_detail_' . 'BTC');
//        dd($trade_detail);
//        $res = (new BitCoinService())->collection('1BKnGXhD9sbccPyHir4Z9VMETT4FizBFX3','13yJugeWNibp16DAwnKtYcjK1AD2yNQfaP','0.03723418');
//        dd($res);
//        dd(ContractEntrust::query()->with('order_details')->find(330));
//        $from_account = 2;
//        $account_class = array_first(UserWallet::$accountMap,function ($value, $key) use ($from_account) {
//            return $value['id'] == $from_account;
//        });
//        dd($account_class['model']::query()->first()->toArray());
//        $user = User::query()->with('allChildren')->where('user_id',6000037)->first();
//        dd($user);
//        $entrust = ContractEntrust::query()->find(3);
//        $where_data = [
//            'contract_id' => $entrust['contract_id'],
//            'entrust_price' => $entrust['entrust_price'],
//            'user_id' => $entrust['user_id'],
//            'order_type' => $entrust['order_type'],
//        ];
//        $res = (new GethService())->collection('0xafC185dCd37Ad401767CA0b6691822d13b6C231f','0x10Dd28BDa75579FdCa32372F3DbC1956583e8891','0.364426385590188339');
//        dd($res);
//        $gasPrice = Utils::toHex(Utils::toWei('80','Gwei'),true);
//        $fee = new BigNumber((hexdec($gasPrice) * hexdec($gas)));
//        $send_value = Utils::toHex($fee,true);
//        $send_eth = (new GethService())->weiToEther($fee);
//        dd($fee,$send_value,$send_eth);
//        dd(Utils::toWei('80','Gwei'));
//        $gasPrice = Utils::toHex(Utils::toWei('80','Gwei'),true);
//        dd(hexdec(Utils::toWei('80','Gwei')));
//        $gasPrice = Utils::fromWei('3b9aca00','Gwei');
//        dd($gasPrice);
//        $r1 = 0.0048 * pow(10,18) / 80000;
//        dd($r1);
//        $v = 5.4E-6;
//        $v = 0.00000546;
//        dd(number_format($v,8));
//        dd((new GethService())->sendFee('0x2cbadf5ed67d5b200856f224ac98864a7396abf0'));
//        $amount = 0.0048;
//        $gasPrice = (new GethService())->interactiveEth('eth_gasPrice',[]);
//        $gas = Utils::toHex(30000,true);
//        $value = Utils::toWei((string)$amount,'ether')->toString();
//        $g = (hexdec($gasPrice) * hexdec($gas));
//        $a = new BigNumber($value);
//        $b = new BigNumber($g);
//        $c = $a->subtract($b);
//dd($value,(string)$g,$c,$a->subtract($c)->toString());
//        dd((Utils::toEther(hexdec($gasPrice) * hexdec($gas))));
//        $value = Utils::toWei((string)$amount,'ether')->toString();
//        dd(Utils::toWei((string)$amount,'ether'));
//        $a = $value - (hexdec($gasPrice) * hexdec($gas));
//        dd(
//            $gasPrice , hexdec($gasPrice) ,
//            $gas,hexdec($gas),
//            (string)(hexdec($gasPrice) * hexdec($gas)),
//            $value,(string)$a
//        );
//////        $send_value = Utils::toHex($value - (hexdec($gasPrice) * hexdec($gas)),true);
//////        dd($value,$send_value);
////        return (new GethService())->getBalance('0xddcea46c98b040e0289fa1f413a1153e7ce57b1c');
//        $amount = '5.199904848';
//        $value = Utils::toWei($amount,'ether')->toString();
//        $gasPrice = (new GethService())->interactiveEth('eth_gasPrice',[]);
//        $value2 = $value - (hexdec($gasPrice) * hexdec($gas));
//        $value3 = (new GethService())->weiToEther($value2);
//        $value4 = Utils::toHex($value2);
//        dd(
//            $gasPrice , hexdec($gasPrice) ,
//            $gas,hexdec($gas),
//            hexdec($gasPrice) * hexdec($gas),
//            $value,$value2,$value3,$value4
//        );
//        $value1 = $value - $gasPrice * $gas;
//        $value1 = "0x" . base_convert(bcmul($value,'1000000000000000000',0),10,16);
//        $value2 = Utils::toWei($value,'ether');
//        dd($amount,$value,$value1);
//        dd((new GethService())->getEthGasPrice());
//        dd(dechex(30000));
//        dd(Utils::toWei('0.0048','ether'));
//        $gasPrice = (new GethService())->interactiveEth('eth_gasPrice',[]);
//        $gas = (new GethService())->interactiveEth('eth_estimateGas',[
//            "from"=>'0xd25057e5a65af95320c37426dd8c46962a220436',
//            "to"=>'0xa417e0a8f6f420b774c66bac0cde468430acfca8',
//            "gas"=>'90000',
//            "gasPrice"=>$gasPrice,
//            "value"=>"0x" . base_convert(bcmul(3,'1000000000000000000',0),10,16),
//            "data"=>"0xd46e8dd67c5d32be8d46e8dd67c5d32be8058bb8eb970870f072445675058bb8eb970870f072445675"
//        ]);
//        dd(Utils::fromWei($gasPrice,'Gwei'));
//        $a = (new GethService())->getBalance('0xd25057e5a65af95320c37426dd8c46962a220436');
//        $value = "0x" . base_convert(bcmul($a,'1000000000000000000',0),10,16);
//        dd($value);
//        $value = hexdec(ltrim(Utils::stripZero('0x0000000000000000000000000000000000000000000000000000000003daf040'),'0')) / pow(10,6);
//        dd(Utils::fromWei('3daf040', 'mwei'));
//        $amount = bcmul(10.3,bcpow(10,18));
//        $amount = "0x" . base_convert(bcmul(10.365,'1000000000000000000',0),10,16);
//        dd($amount,Utils::fromWei($amount, 'ether'));
//        list($bnq, $bnr) = Utils::fromWei('0x29ccc60aaf7a0000', 'ether');
//        dd($bnq,$bnr,$bnr->toString() / pow(10,18),$bnq->toString() + $bnr->toString() / pow(10,18));
//        $value = 0x29ccc60aaf7a0000;
//        dd((string)$value / pow(10,18));
//        $web3 = new Web3(new HttpProvider(new HttpRequestManager(env('GETH_HOST'), 30)));
//        $gasprice = (new GethService())->interactiveEth('eth_gasPrice',[]);
//        dd($gasprice);
//        dd(hexdec($gasprice));
//        list($bnq, $bnr) = Utils::fromWei($gasprice, 'Gwei');
//        dd($bnq->toString());
//        dd((new GethService())->sendTransaction('0xd25057e5a65af95320c37426dd8c46962a220436','0x697dd3317e7dce63ec104051a9eb25e25e0406ec',0.0999));
//        list($bnq, $bnr) = Utils::fromWei('4563918244f40000', 'ether');
//        dd($bnq->toString());
//        $data = (new GethService())->newAccount('12345678');
//        $data = (new GethService())->listAccounts();
//        dd($data);
//        list($bnq, $bnr) = Utils::fromWei('1000', 'kwei');
//        dd($bnq->toString());
//        $web3 = new Web3(new HttpProvider(new HttpRequestManager(env('GETH_HOST'), 30)));
//        $value = ltrim($web3->utils->stripZero('0x00000000000000000000000000000000000000000000000000000015997ce911'),'0');
//        dd(hexdec($value) / pow(10,18));
//        dd($web3->getUtils()::fromWei('0x00000000000000000000000000000000000000000000000000000003e5bfbc90','wei'));
//        dd((new GethService())->interactiveEth('eth_getBlockByHash',['0x1d26049340cd5623b57756c332000afb3af69c99f1f29ecdda59456b958fa8b2',true]));
//        $web3 = new Web3(new HttpProvider(new HttpRequestManager(env('GETH_HOST'), 30)));
//        $cb = new Callback();
//        $web3->eth->newFilter([
//            "fromBlock"=> "latest",
//            "toBlock"=> "latest",
//            'address'=>'0xdac17f958d2ee523a2206206994597c13d831ec7',
//            [
//                "topics"=>["0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef"]
//            ]
//        ],$cb);
//        $fid = $cb->result;
//
//        $web3->eth->getFilterLogs($fid,$cb);
//        return $cb->result;
//        return $fid;
//        $data = (new \App\Services\CoinService\GethService())->interactiveEth('eth_getLogs',[
//            "fromBlock"=> "latest",
//            "toBlock"=> "latest",
//            'address'=>'0xdac17f958d2ee523a2206206994597c13d831ec7',
//            [
//                "topics"=>["0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef"]
//            ]
//        ]);
//        return $data;
//        dd((new GethService())->interactiveEth('eth_getBlockByHash',['0x1d26049340cd5623b57756c332000afb3af69c99f1f29ecdda59456b958fa8b2',true]));
//        return (new GethService())->getBalance('0x59c4cd07debe579910e3bf5cf55000a762d62d17');
//        $min_table = strtolower("test_kline_" . '1min' . "_" . 'lvo' . "_usdt");
//        dd(DB::table($min_table)->where('id','<',time())->orderByDesc('id')->first());
//        $open_carbon = \Illuminate\Support\Carbon::now()->floorMinute();
//        $id = $open_carbon->timestamp;
//        $data = [
//            "id"=> $id,
//            "amount"=> 1,
//            "count"=> rand(10,55),
//            "open"=> 2,
//            "close"=> 3,
//            "low"=> 4,
//            "high"=> 5,
//            "vol"=> 6
//        ];
//        Gateway::$registerAddress = '127.0.0.1:1236';
//        $group_id = 'Kline_' . 'lvousdt' . '_' . '1min';
//        $message = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id,'type'=>'dynamic']);
//        Gateway::sendToGroup($group_id,$message);
//        $day_kline = DB::table(strtolower("test_kline_" . '1day' . "_" . 'lvo' . "_usdt"))->where('id','<',time())->orderByDesc('id')->first();
//        dd($day_kline);
//        $data = DB::table(strtolower("test_kline_" . '1min' . "_" . 'lvo' . "_usdt"))->where('id','<',time())->orderByDesc('id')->first();
//        dd(get_object_vars($data));

//        $data = (new GethService())->listAccounts();
//        $data = (new GethService())->getBalance('0xafc185dcd37ad401767ca0b6691822d13b6c231f');
//        dd($data);
//        dd(User::getOneSystemUser()->toArray());
//        $kline_key = 'market:' . 'stdausdt' . '_kline_1day';
//        $last_cache_data = Cache::store('redis')->get($kline_key);
//        dd($last_cache_data);
//        dd(config('coin.coin_symbol'));
//        $risk = \App\Models\InsideTradeRisk::query()
//            ->where('status',1)
//            ->where('symbol','btcusdt')
//            ->where('start_time','<=',time())
//            ->where(function($q){
//                $q->where('end_time',null)->orWhere('end_time','>',time());
//            })
//            ->first();
//        dd($risk);
//        dd(InsideTradeRisk::query()->where('symbol','btcusdt')->first()->toArray());
//        dd(Carbon::today()->format('Y-m-d'),Carbon::today()->subDays(1)->format('Y-m-d'));
//        $grade_info = UserGrade::get_grade_info(1);
//        $bonus_str = $grade_info['bonus'] ?? '0.005|0.005|0.005';
//        $bonus_rate_arr = explode('|',$bonus_str);
//        dd(blank($bonus_str));
//        dd($scene = OptionScene::query()
//            ->where('end_time','<',time())
//            ->whereNotIn('status',[OptionScene::status_delivered,OptionScene::status_cancel])
//            ->get()->toArray());
//        dd(Cache::store('redis')->get('market:' . 'staiusdt' . '_newPrice'));
//        $coin = array_first(Coins::getCachedCoins(),function ($v,$k){
//            return $v['coin_name'] == 'USDT';
//        });
//        dd($coin);
//        dd(NewPriceBook::all()->toArray());
//        dd(NewPriceBook::query()->create(['id'=>1,'name'=>'qwer']));
//        $logs = UserWalletLog::query()->where('user_id',9)
//            ->where('rich_type','usable_balance')
////            ->whereIn('log_type',['bet_option'])
//            ->get()->groupBy('coin_id');
//        dd($logs->toArray());
//        $pairs = OptionPair::query()->where('status',1)->get();
//        $times = OptionTime::query()->where('option_time.status',1)
//            ->where('option_pair.status',1)
//            ->select(['option_time.time_id','option_time.time_name','option_time.seconds','option_pair.pair_id','option_pair.pair_name'])
//            ->crossJoin('option_pair')->get();
//dd($times->toArray());
//        $pairs = InsideTradePair::query()->where('status',1)->pluck('symbol');
//        $periods = ['1min','5min','15min','30min','60min','4hour','1day','1week','1mon','1year'];
//        dd($pairs->crossJoin($periods)->all());
//        $pairs = OptionPair::query()->where('status',1)->select(['pair_id','pair_name','symbol'])->get()->toArray();
//        $times = OptionTime::query()->where('option_time.status',1)
//            ->where('option_pair.status',1)
//            ->select(['option_pair.pair_id','option_pair.pair_name','option_pair.symbol','option_time.time_id','option_time.time_name','option_time.seconds'])
//            ->crossJoin('option_pair')->get()->toArray();
//        dd($times);
//        dd((new Agent())->device());
//        $new_price_book_key = 'market:' . 'btcusdt' . '_newPriceBook';
//        $new_price_book = array_map(function($v){
//            return json_decode($v,true);
//        },\Illuminate\Support\Facades\Redis::lrange($new_price_book_key,0,-1));
////                        $new_price_book = Cache::store('redis')->get($new_price_book_key);
//        if(blank($new_price_book)) $new_price_book = [];
//        if(!blank($new_price_book)) $new_price_book = array_reverse($new_price_book);
//        $new_price_book = array_slice($new_price_book,-30,30);
////        array_slice($new_price_book,0,30);
//        dd($new_price_book);
//        dd(createWalletAddress(9,'BTC'));
//        dd(0.02001 + (rand(10,80) / 100000));
//        $sub = 'indexMarketList';
//        $type = str_before($sub,'_');
//        $params = str_after($sub,'_');
//        $symbol = str_before($params,'_');
//        dd($sub,$type,$params,$symbol);
//        $symbol = 'btcusdt';
//        $period = '1min';
//        $kline_book_key = 'market:' . $symbol . '_kline_book_' . $period;
////        $kline_book = Cache::store('redis')->get($kline_book_key);\
//        $kline_book = \Illuminate\Support\Facades\Redis::lrange($kline_book_key,0,1);
//        dd($kline_book_key,$kline_book);
//        $option_pairs = OptionPair::query()->where('status',1)->select(['symbol','quote_coin_name','base_coin_name'])->get()->toArray();
//        $exchange_pairs = InsideTradePair::query()->where('status',1)->select(['symbol','quote_coin_name','base_coin_name'])->get()->toArray();
//        $tmp_arr = array_merge($option_pairs,$exchange_pairs);
//        $pairs = collect($tmp_arr)->unique(function ($item){
//            return $item['symbol'];
//        })->toArray();
//        dd($pairs);
//        $data = InsideTradePair::query()->get()->groupBy('quote_coin_name')->toArray();
//        dd($data);
//        $scene = OptionScene::query()->findOrFail(1);
//        $odds_arr = array_collapse([$scene['up_odds'],$scene['down_odds'],$scene['draw_odds']]);
//        $odds = array_first($odds_arr, function ($value, $key) {
//            return $value['uuid'] == '368a9ea9-22a8-4434-ab9b-a54a93729d93';
//        });
//        dd($odds);
//        dd(array_collapse([['q'=>1,'w'=>2],['a'=>'a']]));
//        dd(str_after('btcusdt.trade','.'));
//        $data = InsideTradePair::query()->get();
//        dd($data->groupBy('quote_coin_name')->toArray());
//        dd(Cache::store('redis')->tags('market_asdf')->put('market_asdf3','market_asdf3'));
//        dd(Cache::store('redis')->tags('market_asdf')->get('market_asdf'));
//        $ch = "market.ethbtc.kline.1mon";
//        $ch = "market.btcusdt.mbp.refresh.20";
//        $ch = "market.btcusdt.detail";
//        $pattern_kline = '/^market\.(.*?)\.kline\.([\s\S]*)/';
//        $pattern_depth = '/^market\.(.*?)\.mbp\.refresh\.20$/';
//        $pattern_detail = '/^market\.(.*?)\.detail$/'; //市场概要
//        dd(preg_match($pattern_detail, $ch, $match),$match);
//        dd(json_decode(cache::store('redis')->get('btcusdt_depth'),true));
//        dd(cache::store('file')->get('btcusdt_newPrice'));
//        dd(cache::store('file')->put('btcusdt',[1,2,3,4,5]));
//        dd( (new HuobiapiService())->getAllRecords() );
//        dd(OptionTime::all()->toArray());
//        $sub = ['q','w','e','r'];
//        dd(array_pluck($sub,'w'),$sub);
//        $pair = OptionPair::query()->find(1);
//        $symbol = strtolower($pair['base_coin_name']) . strtolower($pair['quote_coin_name']);
//        $market_trade = (new HuobiapiService())->getMarketTrade($symbol);
//        dd($market_trade);
//        dd(gettype($market_trade),$market_trade);
//        return $this->successWithData($market_trade);
//        dd(request()->allFiles());
//        dd(file_get_contents("https://api.hadax.com/market/trade?symbol=ethusdt"));
//        dd(file_get_contents("https://api.huobi.pro/market/trade?symbol=ethusdt"));
//        $after_encode_data = 'oxOd68uPP/REmEoEnJoS2Rk/ka8gSuWaTnpTEup7aJ/7LW5grjHAiFlMkHSIoCEcvLxRkST/CAX/7hUZ40tjxZFNCiXLUaOZEPDTbFaYcySx4Dslx8AlLeLdOcNKE7DsZlpnRe0MSI9QMNmkePbaYEScyzzczF6+m3UYnn2KhHI=';

//        $decode_result = rsa_decode($after_encode_data);
//        dd($decode_result);
//        $times = OptionTime::query()->where('status',1)->get();
//        dd($times->toArray());
//        $start = Carbon::now();
//        $end = Carbon::now()->addSeconds(300);
//        $range = date_range($start,$end,300);
//        dd($range);

//        dd(request()->all());
//        $phone = '18617004850';
//        dd(sendCodeSMS($phone));
//        $email = '351843463@qq.com';
//        dd(sendEmailCode($email));

//        $user = User::query()->find(3);
//        dd($user->update_wallet_and_log(1,'usable_balance',-10,UserWallet::option_account,'option_order_delivery'));
//        $account_class = array_first(UserWallet::$accountMap,function ($value, $key) {
//            return $value['id'] == 2;
//        });
//        $account = new $account_class['model']();
//        dd($account::$richMap);
//        dd($account);
           // $result=UserCoinName::query()->where([ 'coin_id' => $coin_id])->firstOrFail();


//        dd(Cache::put('testkey','123456',10));
//        $scene_id = 123;
//        dd(Cache::store('redis')->put('testkey:'.$scene_id,$scene_id,8));
//        $market_trade = [
//            "ch"=> "market.btcusdt.trade.detail",
//            "status"=> "ok",
//            "ts"=> 1593242272637,
//            "tick"=> [
//                "id"=> 109242365299,
//                "ts"=> 1593242272481,
//                "data"=> [
//                    [
//                        "id"=> 1.0924236529944560206305051e+25,
//                        "ts"=> 1593242272481,
//                        "trade-id"=> 102152942444,
//                        "amount"=> 0.27,
//                        "price"=> 200,
//                        "direction"=> "sell"
//                    ],
//                    [
//                        "id"=> 1.0924236529944560206305051e+35,
//                        "ts"=> 1593242272482,
//                        "trade-id"=> 102152942445,
//                        "amount"=> 0.29,
//                        "price"=> 100,
//                        "direction"=> "sell"
//                    ]
//                ]
//            ]
//        ];
//        $trade_data = $market_trade['tick']['data'];
//        $price_arr = Arr::pluck($trade_data,'price');
//        $new_price = PriceCalculate(array_sum($price_arr) ,'/', count($price_arr));
//dd($new_price);
//        $url = 'https://api.hadax.pro/market/trade?symbol=btcusdt';
//        $info = @file_get_contents($url);
//        dd($info);
//        return $info;
//        $data = (new HuobiapiService())->getMarketTrade('btcusdt');
//        dd($data);
    }

    //模拟登陆获取token
    public function mockLogin(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'user_id' => 'required|integer'
        ])) return $vr;

        $user = User::query()->find($request->user_id);

        //生成token
        $login_code = User::gen_login_code();
        $user->login_code = $login_code;
        $user->last_login_ip = $request->getClientIp();
        $user->last_login_time = Carbon::now()->toDateTimeString();
        $user->save();

        $token =  auth('api')->claims(['login_code' => $login_code])->fromUser($user);

        return $this->successWithData(['token' => $token,'user_id'=>$user['user_id']]);
    }

    //获取用户信息
    public function getUserInfo()
    {
        $user = $this->current_user();

        return $this->successWithData($user);
    }

    //修改用户信息
    public function updateUserInfo(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'avatar' => '',
            'username' => 'string|min:1|max:30',
            'second_verify' => 'integer|in:0,1',
        ])) return $res;

        $params = $request->all();
        if(blank($params)) return $this->error('缺少参数');

        $user = $this->current_user();

        $res = $user->update($params);
        if(!$res){
            return $this->error();
        }
        return $this->successWithData($user);
    }

    //登陆二次验证开关
    public function switchSecondVerify()
    {
        $user = $this->current_user();

        $second_verify = $user->second_verify;

        $user->second_verify = $second_verify == 1 ? 0 : 1;
        $user->save();
        return $this->successWithData(['second_verify' => $user['second_verify']]);
    }

    public function addAdvice(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'phone' => 'string',
            'email' => 'string|email',
            'realname' => 'string',
            'contents' => 'required|string',
            'imgs' => '',
        ])) return $vr;

        $user = $this->current_user();

        $params = $request->only(['contents','email','phone','realname','imgs']);
        $params['user_id'] = $user['user_id'];

        $res = Advice::query()->create($params);

        if(!$res){
            return $this->error();
        }
        return $this->success();
    }

    public function advices()
    {
        $user = $this->current_user();

        $advices = Advice::query()->where(['user_id'=>$user['user_id']])->latest()->paginate();

        return $this->successWithData($advices);
    }

    public function adviceDetail(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();

        $advice = Advice::query()->where(['user_id'=>$user['user_id'],'id'=>$request->id])->firstOrFail();

        return $this->successWithData($advice);
    }

    //用户消息通知数量统计
    public function myNotifiablesCount(Request $request)
    {
        $user = $this->current_user();

        $count = [];

        $count['total'] = $user->unreadNotifications()->count();

        return $this->successWithData($count);
    }

    public function myNotifiables_copy(Request $request)
    {
        $user = $this->current_user();

        $notifiables = $user->notifications()->latest()->paginate();

        // $notifiables = $notifiables->toArray();

        //获取列表 全部标记已读
//        $user->unreadNotifications->markAsRead();

        // var_dump(json_encode());
        // return $notifiables;
        // return $this->successWithData($notifiables);
        // var_dump());
        // var_dump());

        // $str = $notifiables['data'][0]['data']['title'];
        // $str = str_replace('资产', ' ' . __('资产') . ' ', $str);
        // $str = str_replace('增加', ' ' . __('增加') . ' ', $str);
        // $str = str_replace('减少', ' ' . __('减少') . ' ', $str);
        // $notifiables['data'][0]['data']['title'] = $str;

        // $str2 = $notifiables['data'][0]['data']['content'];
        // $str = str_replace('资产', ' ' . __('资产') . ' ', $str);
        // $str = str_replace('增加', ' ' . __('增加') . ' ', $str);
        // $str = str_replace('减少', ' ' . __('减少') . ' ', $str);


        return $this->successWithData($notifiables);
    }

    private static function trans_myNotifiables ($notifiables) {

        $data = $notifiables->toArray()['data'];

        // 取出title
        $titleArr = [];
        $contentArr = [];
        for ($i = 0, $len = count($data); $i < $len; $i++) {

            array_push($titleArr, [

                'title' => $data[$i]['data']['title']
            ]);

            array_push($contentArr, [

                'content' => $data[$i]['data']['content']
            ]);
        }

        $lang = App::getLocale();

        $transTitleArr = baiduTransAPI(json_encode($titleArr), 'zh', 'en');
        $transContentArr = baiduTransAPI(json_encode($contentArr), 'zh', 'en');

        $transTitleArr = json_decode($transTitleArr, true);
        $transContentArr = json_decode($transContentArr, true);

        // var_dump(json_encode($titleArr));
    }

    public function myNotifiables(Request $request)
    {
        $user = $this->current_user();

        $notifiables = $user->notifications()->latest()->paginate();

        self::trans_myNotifiables($notifiables);

        return $this->successWithData($notifiables);
    }

    public function batchReadNotifiables(Request $request)
    {
        $user = $this->current_user();

        //全部标记已读
        $user->unreadNotifications->markAsRead();

        return $this->success();
    }

    public function readNotifiable(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'id' => 'required',
        ])) return $vr;

        $user = $this->current_user();

        $notifiable = $user->notifications()->where('id',$request->id)->firstOrFail();
        // var_dump(json_encode($notifiable));

        //标记消息为已读
        $notifiable->markAsRead();

        return $this->successWithData($notifiable);
    }

    //获取认证信息
    public function getAuthInfo(Request $request,UserService $userService)
    {
        $user = $this->current_user();

        $auth = $userService->getAuthInfo($user);
        return $this->successWithData($auth);
    }

    //发送实名认证短信验证码
    public function sendSmsCodeAuth(Request $request,User $user)
    {
        if ($vr = $this->verifyField($request->all(),[
           'country_code' => 'required|string', //国家代码
            'phone' => 'required|string',
        ])) return $vr;

        $account = $request->input('phone');

        $sendResult = sendCodeSMS($account,'',$request->country_code);
        if ($sendResult === true){
            return $this->success();
        }
        return $this->error(4001,$sendResult);
    }

    //初级认证
    public function primaryAuth(Request $request,UserService $userService)
    {
        if(empty($request->input('realname'))){
            return $this->error(0,'姓名不能为空');
        }
        if(empty($request->input('id_card'))){
            return $this->error(0,'证件号码不能为空');
        }
        if(empty($request->input('front_img'))){
            return $this->error(0,'Picture upload failed');
        }
        if(empty($request->input('back_img'))){
            return $this->error(0,'Picture upload failed');
        }

        if ($res = $this->verifyField($request->all(),[
            'country_code' => 'string',
            'realname' => 'required|string',
            'id_card' => 'required|string',
            'type' => 'integer|in:1',
        ])) return $res;

        $user = $this->current_user();
        $params['country_id'] = $request->input('country_id',1);
        $params['country_code'] = $request->input('country_code','86');
        $params['realname'] = $request->input('realname');
        $params['id_card'] = $request->input('id_card');
        $params['birthday'] = $request->input('birthday');
        $params['address'] = $request->input('address');
        $params['city'] = $request->input('city');
        $params['postal_code'] = $request->input('postal_code');
        $params['extra'] = $request->input('extra');
        $params['phone'] = $request->input('phone');
        $params['type'] = $request->input('type',1);

        $params['front_img'] = $request->input('front_img');
        $params['back_img'] = $request->input('back_img');

        // 判断图片
        if($params['front_img']){
            if(!strstr($params['front_img'], 'https://')){
                $params['front_img'] = 'https://'.$_SERVER['HTTP_HOST'].'/storage/'.$params['front_img'];
            }
        }
        // 判断图片
        if($params['back_img']){
            if(!strstr($params['back_img'], 'https://')){
                $params['back_img'] = 'https://'.$_SERVER['HTTP_HOST'].'/storage/'.$params['back_img'];
            }
        }


        // 短信验证
        $account = $request->input('account');
        $params['chu_country_code'] = $request->input('country_code','86');
        $params['chu_phone'] = $account;
        $checkResult = checkSMSCode_auth($account,$request->code,'',$request->country_code);
        if ($checkResult !== true) return $this->error(4001,$checkResult);

        //if ( !$this->isIdentityCard($params['id_card']) ) return $this->error(0,'身份证不合法');
        $res = $userService->primaryAuth($user,$params);
        if(!$res){
            return $this->error();
        }
        return $this->success('认证成功');
    }

    //高级认证
    public function topAuth(Request $request,UserService $userService)
    {
     //   if(empty($request->input('hand_img'))){
    //        return $this->error(0,'Picture upload failed');
    //    }

    //    if ($res = $this->verifyField($request->all(),[
       //     'hand_img' => 'required',
     //   ])) return $res;

        $user = $this->current_user();
        $params = $request->only(['hand_img']);
        $domain = env('IMG_URL');
        $params['hand_img'] = strpos($params['hand_img'], $domain) === true ? $params['hand_img'] : $domain.'/storage/'.$params['hand_img'];

        // 判断图片
        if($params['hand_img']){
            if(!strstr($params['hand_img'], 'https://')){
                $params['hand_img'] = 'https://'.$_SERVER['HTTP_HOST'].'/storage/'.$data['hand_img'];
            }
        }
        // 短信验证
        $account = $request->input('account');
        $params['gao_country_code'] = $request->input('country_code','86');
        $params['gao_phone'] = $account;
       // $checkResult = checkSMSCode($account,$request->code,'',$request->country_code);
      //  if ($checkResult !== true) return $this->error(4001,$checkResult);

        $res = $userService->topAuth($user,$params);
        if(!$res){
            return $this->error();
        }
        return $this->success('提交成功');
    }

    public function getGradeInfo()
    {
        $user = $this->current_user();

        $data['user'] = $user;
        $data['grade'] = UserGrade::query()->where('status',1)->orderBy('grade_id','asc')->get();
        $grade_explain = Article::query()->where('category_id',ArticleCategory::$typeMap['grade_remark'])->first();
        $grade_explain->makeHidden('translations');
        $data['remark'] = $grade_explain;

        return $this->successWithData($data);
    }

    //登陆日志
    public function getLoginLogs(Request $request)
    {
        $user = $this->current_user();

        $per_page = $request->input('per_page',10);

        $data = UserLoginLog::query()->where('user_id',$user['user_id'])->orderBy('login_time','desc')->paginate($per_page);
        return $this->successWithData($data);
    }

}
