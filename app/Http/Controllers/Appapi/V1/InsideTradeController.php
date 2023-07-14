<?php

namespace App\Http\Controllers\Appapi\V1;

use App\Http\Controllers\Controller;
use App\Models\Coins;
use App\Models\InsideTradeBuy;
use App\Models\InsideTradePair;
use App\Models\InsideTradeSell;
use App\Models\UserWallet;
use App\Services\ExchangeRateService\ExchangeRateService;
use App\Services\InsideTradeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class InsideTradeController extends ApiController
{
    // 币币交易

    protected $service;

    public function __construct(InsideTradeService $service)
    {
        $this->service = $service;
    }

    // TODO 获取盘口信息
    public function getNewPriceList(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'symbol' => 'required', //交易对 参数格式：BTC/USDT
            'depth' => 'in:0.01,0.1,1,10,100',
        ])) return $vr;

        $symbol = $request->input('symbol');
        $depth = $request->input('depth',0.01);

    }

    public function getCoinInfo_copy(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_name' => 'required', //币种名称 参数格式：BTC
        ])) return $vr;

        $coin_name = $request->input('coin_name');
        $coin = Coins::query()->where('coin_name',$coin_name)->first();

        if(blank($coin)) return $this->error();
        return $this->successWithData($coin);
    }

    private static function trans ($msg) {

        $from = 'zh';
        $to = App::getLocale();

        if ($from === $to) return $msg;

        return baiduTransAPI($msg, $from, $to);
    }

    public function getCoinInfo(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_name' => 'required', //币种名称 参数格式：BTC
        ])) return $vr;

        $coin_name = $request->input('coin_name');
        $coin = Coins::query()->where('coin_name',$coin_name)->first();

        if(blank($coin)) return $this->error();

        // 调用接口翻译文字
        $coin->coin_content = self::trans($coin->coin_content);

        return $this->successWithData($coin);
    }

    public function getMarketList()
    {
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
            foreach ($items as $key2 => $item){
                $cd = Cache::store('redis')->get('market:' . $item['symbol'] . '_detail');
                $cd['price'] = $cd['close'];
                $cd['qty_decimals'] = $item['qty_decimals'];
                $cd['price_decimals'] = $item['price_decimals'];
                $cd['min_qty'] = $item['min_qty'];
                $cd['min_total'] = $item['min_total'];
                $cd['coin_name'] = $item['base_coin_name'];
                $cd['coin_icon'] = Coins::icon($item['base_coin_name']);
                $cd['pair_id'] = $item['pair_id'];
                $cd['pair_name'] = $item['pair_name'];
                $cd['symbol'] = $item['symbol'];
                $market[$kk]['marketInfoList'][$key2] = $cd;
            }
            $kk++;
        }
        return $this->successWithData($market);
    }

    public function getMarketInfo(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'symbol' => 'required',
        ])) return $vr;
        $params = $request->all();
        if(strpos($params['symbol'],'/') !== false){
            $symbol = strtolower(str_before($params['symbol'],'/') . str_after($params['symbol'],'/'));
        }else{
            $symbol = $params['symbol'];
        }

        $buyList = Cache::store('redis')->get('market:' . $symbol . '_depth_buy') ?: [];
        $sellList = Cache::store('redis')->get('market:' . $symbol . '_depth_sell') ?: [];
        $tradeList = Cache::store('redis')->get('market:' . $symbol . '_newPriceBook') ?: [];

        $coins = config('coin.exchange_symbols');
        foreach ($coins as $coin => $class){
            $coin = strtolower($coin);
            if($symbol == $coin . 'usdt'){
                $kline = $class::query()->where('is_1min',1)->where('Date','<',time())->orderByDesc('Date')->first();
                if(blank($kline)){
                    $tradeList = [];
                }else{
                    $kline_cache_data = Cache::store('redis')->get('market:' . $symbol . '_detail');

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

                    // if($coin != strtolower(config('coin.coin_symbol'))){
                        for ($i = 0; $i <= 19; $i++) {
                            if($i == 0){
                                $sellList[$i] = [
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

                                $sellList[$i] = [
                                    'id'=> Str::uuid()->toString(),
                                    "amount"=> round((mt_rand(10000,3000000) / 1000),4),
                                    'price'=> $price,
                                ];
                            }
                        }
                    // }

                    for ($i = 0; $i <= 30; $i++) {
                        if($i == 0){
                            $tradeList[$i] = [
                                'id'=> Str::uuid(),
                                "amount"=> round((mt_rand(10000,3000000) / 1000),4),
                                'price'=> $kline_cache_data['close'],
                                'tradeId'=> Str::uuid()->toString(),
                                'ts'=> Carbon::now()->getPreciseTimestamp(3),
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
                                'ts'=> Carbon::now()->getPreciseTimestamp(3),
                                'increase'=> -0.1626,
                                'increaseStr'=> "-16.26%",
                                'direction'=> mt_rand(0,1) == 0 ? 'buy' : 'sell',
                            ];
                        }
                    }
                }

                break;
            }
        }

        $data = [
            'buyList' => $buyList,
            'sellList' => $sellList,
            'tradeList' => $tradeList,
        ];
        return $this->successWithData($data);
    }

    //获取用户账户余额
    public function getUserCoinBalance(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'symbol' => 'required', //交易对 参数格式：BTC/USDT
        ])) return $vr;

        $user = $this->current_user();
        $symbol = $request->input('symbol');
        $base_coin = str_before($symbol,'/');
        $quote_coin = str_after($symbol,'/');

        $data[$base_coin] = UserWallet::query()->where(['user_id' => $user['user_id'] ,'coin_name' => $base_coin])->first();
        $data[$quote_coin] = UserWallet::query()->where(['user_id' => $user['user_id'] ,'coin_name' => $quote_coin])->first();
        return $this->successWithData($data);
    }

    //发布委托
    public function storeEntrust(Request $request)
    {

        if ($vr = $this->verifyField($request->all(),[
            'direction' => 'required|string|in:buy,sell', //买卖方向
            'type' => 'required|integer|in:1,2,3', //委托方式 1限价交易 2市价交易 3止盈止损
            'symbol' => 'required', //交易对 参数格式：BTC/USDT
            'entrust_price' => 'required_if:type,1,3',
            //'entrust_price' => 'required_if:type,1,3|numeric', //委托价格
            'trigger_price' => 'required_if:type,3',
            //'trigger_price' => 'required_if:type,3|numeric', //触发价
            //'amount' => 'required|numeric', //委托数量
            'amount' => 'required', //委托数量
            'total' => '', //委托总价 市价委托买单
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['type','symbol','entrust_price','amount','trigger_price','total']);

        $orderLockKey = 'inside_entrust_lock:' . $user['user_id'];
        if (!$this->setKeyLock($orderLockKey,3)){ //订单锁
            return $this->error();
        }

        // 小白
        // 用户权限判断  如果没有高级认证不可参与交易
        if($user->user_auth_level == 0){
            return $this->error(0,'请完成实名认证');  // 使用公共语言
        }


        if($request->direction == 'buy'){
            $res = $this->service->storeBuyEntrust($user,$params);
        }else{
            $res = $this->service->storeSellEntrust($user,$params);
        }
        if(!$res){
            return $this->error(0,'下单失败');
        }
        return $this->success('下单成功');
    }

    //撤单
    public function cancelEntrust(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'symbol' => 'required', //交易对 参数格式：BTC/USDT
            'entrust_type' => 'required|in:1,2', //委托类型 1买入 2卖出
            'entrust_id' => 'required', //委托ID
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['entrust_type','entrust_id','symbol']);

        if($params['entrust_type'] == 1){
            $entrust = InsideTradeBuy::query()->where(['symbol'=>$params['symbol'],'user_id'=>$user['user_id'],'id'=>$params['entrust_id']])->firstOrFail();
        }else{
            $entrust = InsideTradeSell::query()->where(['symbol'=>$params['symbol'],'user_id'=>$user['user_id'],'id'=>$params['entrust_id']])->firstOrFail();
        }

        $res = $this->service->cancelEntrust($user,$entrust);
        if(!$res){
            return $this->error();
        }
        return $this->success();
    }

    //批量撤单
    public function batchCancelEntrust(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'symbol' => '', //交易对 参数格式：BTC/USDT
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['symbol']);

        $res = $this->service->batchCancelEntrust($user,$params);
        if(!$res){
            return $this->error();
        }
        return $this->success();
    }

    public function getExchangeSymbol()
    {
        $data = InsideTradePair::query()->where('status',1)->orderBy('sort','asc')->get();
        return $this->successWithData($data);
    }

    //获取历史委托
    public function getHistoryEntrust(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'direction' => 'string|in:buy,sell', //买卖方向
            'type' => 'integer|in:1,2,3', //委托方式 1限价交易 2市价交易
            'symbol' => '', //交易对 参数格式：BTC/USDT
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['direction','type','symbol']);

        $data = $this->service->getHistoryEntrust($user,$params);

        $data2 = $data->toArray();

        for ($i = 0, $len = count($data2['data']); $i < $len; $i++) {

            $data2['data'][$i]['entrust_type_text'] = __($data2['data'][$i]['entrust_type_text']);
            $data2['data'][$i]['status_text'] = __($data2['data'][$i]['status_text']);
        }

        return $this->successWithData($data2);
    }

    //获取当前委托
    public function getCurrentEntrust(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'direction' => 'string|in:buy,sell', //买卖方向
            'type' => 'integer|in:1,2,3', //委托方式 1限价交易 2市价交易
            'symbol' => '', //交易对 参数格式：BTC/USDT
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['direction','type','symbol']);

        $data = $this->service->getCurrentEntrust($user,$params);
        $data2 = $data->toArray();

        for ($i = 0, $len = count($data2['data']); $i < $len; $i++) {

            $data2['data'][$i]['entrust_type_text'] = __($data2['data'][$i]['entrust_type_text']);
            $data2['data'][$i]['status_text'] = __($data2['data'][$i]['status_text']);
        }

        return $this->successWithData($data2);
    }

    //获取委托成交记录
    public function getEntrustTradeRecord(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
//            'symbol' => 'required', //交易对 参数格式：BTC/USDT
            'entrust_type' => 'required|in:1,2', //委托类型 1买入 2卖出
            'entrust_id' => 'required', //委托ID
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['entrust_type','entrust_id']);

        $data = $this->service->getEntrustTradeRecord($user,$params);
        return $this->successWithData($data);
    }

}
