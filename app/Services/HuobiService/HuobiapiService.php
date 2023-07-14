<?php


namespace App\Services\HuobiService;


use App\Models\Coins;
use App\Models\InsideTradePair;
use App\Models\OptionPair;
use App\Services\HuobiService\lib\HuobiLibService;
use App\Traits\RedisTool;
use Illuminate\Support\Facades\DB;

class HuobiapiService
{
    use RedisTool;

    private $libServer;
    private $coinList;

    private $usdtToCny = 6.4;//汇率

    private $baseCoin = 'usdt';

    private $allSymbolKey = 'ALL_SYMBOL';//所有交易对缓存键

    private $ttl = 86400;//默认缓存秒

    public function __construct()
    {
        $this->libServer = new HuobiLibService();
        // 定义参数
        if (!defined('ACCOUNT_ID')) define('ACCOUNT_ID', '365619859'); // your account ID
        if (!defined('ACCESS_KEY')) define('ACCESS_KEY',env('HUOBI_ACCESS_KEY')); // your ACCESS_KEY
        if (!defined('SECRET_KEY')) define('SECRET_KEY', env('HUOBI_SECRET_KEY')); // your SECRET_KEY

//        $this->coinList = ['btc','eth','xrp','bch','ltc','etc','eos'];
        $this->coinList = Coins::query()->where('status',1)->pluck('coin_name')->toArray();

//        $this->usdtToCny = DB::table('coin_exchange_rate')->where('virtual_coin_id',$this->getCoinId('USDT'))->first()->rate;
    }

    public function get_market_tickers()
    {
        return json_decode(json_encode($this->libServer->get_market_tickers()),true);
    }

    //获取单个交易对的行情
    public function getDetailMerged($symbol)
    {
        return json_decode(json_encode($this->libServer->get_detail_merged($symbol)),true);
    }

    public function getCoinId($coinName){
        $coin = Coins::query()->where('coin_name',$coinName)->select(['coin_id'])->first();
        if ($coin) return $coin->coin_id;return 0;
    }

    //获取需要的所有交易对详情
    public function getAllNeedMerged($option = 1)
    {
        try {
            $baseCoinId = $this->getCoinId($this->baseCoin);
            $coinList = $this->coinList;
            $result = [];
            foreach ($coinList as $key => $value) {
                $value = strtolower($value);
                $coinDetail = $this->getDetailMerged($value . $this->baseCoin);
                if ($coinDetail['status'] != 'ok' || !$coinDetail) continue;
                $coinDetail['tick']['quote_coin_name'] = strtoupper($this->baseCoin);
                $coinDetail['tick']['base_coin_name'] = strtoupper($value);
                $coinDetail['tick']['quote_coin_id'] = $baseCoinId;
                $coinDetail['tick']['base_coin_id'] = $this->getCoinId($value);
                $coinDetail['tick']['coin_icon'] = Coins::query()->where('coin_name',$value)->value('coin_icon');
                $coinDetail['tick']['symbol'] = $value . $this->baseCoin;
                $coinDetail['tick']['float_type'] = $coinDetail['tick']['open'] > $coinDetail['tick']['close'] ? '-' : '+';//1+2-
                $coinDetail['tick']['price_float'] = number_format((abs($coinDetail['tick']['open'] - $coinDetail['tick']['close']) / $coinDetail['tick']['open']) * 100, 2);
                $coinDetail['tick']['CNY_price'] = $this->usdtToCny * $coinDetail['tick']['close'];
                $coinDetail['tick']['max_price'] = $coinDetail['tick']['high'];
                $coinDetail['tick']['min_price'] = $coinDetail['tick']['low'];
                $coinDetail['tick']['begin_price'] = $coinDetail['tick']['open'];
                $coinDetail['tick']['current_price'] = $coinDetail['tick']['close'];

                $result[] = $coinDetail['tick'];
            }
            return $result;
        }catch (\Exception $exception){
            return [];
        }
    }

    //获取期权所有交易对聚合数据
    public function getOptionMerged()
    {
        $pairs = OptionPair::query()->where('status',1)->get()->toArray();
        $result = [];
        foreach ($pairs as $pair) {
            $symbol = strtolower($pair['quote_coin_name']) . strtolower($pair['base_coin_name']);
            $coinDetail = $this->getDetailMerged($symbol);
            if ($coinDetail['status'] != 'ok' || !$coinDetail) continue;
            $coinDetail['tick']['quote_coin_name'] = $pair['quote_coin_name'];
            $coinDetail['tick']['base_coin_name'] = $pair['base_coin_name'];
            $coinDetail['tick']['quote_coin_id'] = $pair['quote_coin_id'];
            $coinDetail['tick']['base_coin_id'] = $pair['base_coin_id'];
            $coinDetail['tick']['coin_icon'] = Coins::query()->where('coin_name',$pair['base_coin_name'])->value('coin_icon');
            $coinDetail['tick']['symbol'] = $symbol;
            $coinDetail['tick']['float_type'] = $coinDetail['tick']['open'] > $coinDetail['tick']['close'] ? '-' : '+';//1+2-
            $coinDetail['tick']['price_float'] = number_format((abs($coinDetail['tick']['open'] - $coinDetail['tick']['close']) / $coinDetail['tick']['open']) * 100, 2);
            $coinDetail['tick']['CNY_price'] = $this->usdtToCny * $coinDetail['tick']['close'];
            $coinDetail['tick']['max_price'] = $coinDetail['tick']['high'];
            $coinDetail['tick']['min_price'] = $coinDetail['tick']['low'];
            $coinDetail['tick']['begin_price'] = $coinDetail['tick']['open'];
            $coinDetail['tick']['current_price'] = $coinDetail['tick']['close'];

            $result[] = $coinDetail['tick'];
        }
        return $result;
    }

    //获取币币所有交易对聚合数据
    public function getExchangeMerged()
    {
        $pairs = InsideTradePair::query()->where('status',1)->get()->toArray();
        $result = [];
        foreach ($pairs as $pair) {
            $symbol = strtolower($pair['quote_coin_name']) . strtolower($pair['base_coin_name']);
            $coinDetail = $this->getDetailMerged($symbol);
            if ($coinDetail['status'] != 'ok' || !$coinDetail) continue;
            $coinDetail['tick']['quote_coin_name'] = $pair['quote_coin_name'];
            $coinDetail['tick']['base_coin_name'] = $pair['base_coin_name'];
            $coinDetail['tick']['quote_coin_id'] = $pair['quote_coin_id'];
            $coinDetail['tick']['base_coin_id'] = $pair['base_coin_id'];
            $coinDetail['tick']['coin_icon'] = Coins::query()->where('coin_name',$pair['base_coin_name'])->value('coin_icon');
            $coinDetail['tick']['symbol'] = $symbol;
            $coinDetail['tick']['float_type'] = $coinDetail['tick']['open'] > $coinDetail['tick']['close'] ? '-' : '+';//1+2-
            $coinDetail['tick']['price_float'] = number_format((abs($coinDetail['tick']['open'] - $coinDetail['tick']['close']) / $coinDetail['tick']['open']) * 100, 2);
            $coinDetail['tick']['CNY_price'] = $this->usdtToCny * $coinDetail['tick']['close'];
            $coinDetail['tick']['max_price'] = $coinDetail['tick']['high'];
            $coinDetail['tick']['min_price'] = $coinDetail['tick']['low'];
            $coinDetail['tick']['begin_price'] = $coinDetail['tick']['open'];
            $coinDetail['tick']['current_price'] = $coinDetail['tick']['close'];

            $result[] = $coinDetail['tick'];
        }
        return $result;
    }

    public function getAllRecords()
    {
        if ($this->ifTtl($this->allSymbolKey,4)){
            $result = $this->getAllNeedMerged();
            $this->stringSetex($this->allSymbolKey,$this->ttl,json_encode($result));
            return $result;
        }
        return $this->jsonDecode($this->allSymbolKey);
    }

    //k线
    public function getKLine($symbol,$period = '15min',$size = 30)
    {
//        $key = 'KLine' . $symbol . $period . $size;
//        if ($this->ifTtl($key,4)){
            $result = $this->libServer->get_history_kline($symbol,$period,$size);
//            $this->stringSetex($key,$this->ttl,json_encode($result));
            return $result;
//        }
//        return $this->jsonDecode($key);
    }

    //单个交易对
    public function getOneMerged($symbol)
    {
        $key = 'SYMBOL_' . $symbol;
        if ($this->ifTtl($key,4)){
            $result = $this->getMerged($symbol);
            $this->stringSetex($key,$this->ttl,json_encode($result));
            return $result;
        }
        return $this->jsonDecode($key);
    }


    private function getMerged($symbol)
    {
        $baseCoinId = $this->getCoinId($this->baseCoin);

        $coinDetail = $this->getDetailMerged($symbol);

        $coinDetail['tick']['base_coin_name'] = strtoupper($this->baseCoin);
        $coinDetail['tick']['base_coin'] = strtoupper($this->baseCoin);
        $coinDetail['tick']['base_coin_id'] = $baseCoinId;
        $coinDetail['tick']['float_type'] = $coinDetail['tick']['open'] > $coinDetail['tick']['close'] ? '-' : '+';//1+2-
        $coinDetail['tick']['price_float'] = number_format((abs($coinDetail['tick']['open'] - $coinDetail['tick']['close'])/$coinDetail['tick']['open'])*100,2);
        $coinDetail['tick']['CNY_price'] = $this->usdtToCny * $coinDetail['tick']['close'];
        $coinDetail['tick']['max_price'] = $coinDetail['tick']['high'];
        $coinDetail['tick']['min_price'] = $coinDetail['tick']['low'];
        $coinDetail['tick']['begin_price'] = $coinDetail['tick']['open'];
        $coinDetail['tick']['current_price'] = $coinDetail['tick']['close'];
        return $coinDetail['tick'];
    }

    //深度
    public function getSymbolDepth($symbol,$type)
    {
        $key = 'DEPTH_' . $symbol . $type;

        if ($this->ifTtl($key,4)){
            $result = $this->libServer->get_market_depth($symbol,$type)->tick;
            $this->stringSetex($key,$this->ttl,json_encode($result));
            return $result;
        }
        return $this->jsonDecode($key);
    }

    // 获取 Market Detail 24小时成交量数据
    public function get_market_detail($symbol = '') {
        $result = $this->libServer->get_market_detail($symbol);
        dd($result);
    }

    //最近市场成交记录
    public function getMarketTrade($symbol)
    {
        return $this->libServer->get_market_trade($symbol);
    }

    //获得近期交易记录
    public function getHistoryTrade($symbol,$size = 20)
    {
        if (!$size) $size = 20;

        $key = 'HISTRADE_' . $symbol . $size;

        if ($this->ifTtl($key,4)){
            $result = $this->libServer->get_history_trade($symbol,$size);
            $this->stringSetex($key,$this->ttl,json_encode($result));
            return $result;
        }
        return $this->jsonDecode($key);
    }

    //json解码
    private function jsonDecode($key)
    {
        return json_decode($this->stringGet($key),true);
    }

    //判断过期
    private function ifTtl($key,$ttlSeconds)
    {
        $ttl = $this->getTTL($key);
        if (
            $ttl <= 0
            || ($this->ttl - $ttl > $ttlSeconds)
        ){
            if ($this->setKeyLock($key . ':lock',3))
                return 1;//过期,同时只能有一人更新
            return 0;
        }
        return 0;//未过期
    }

}
