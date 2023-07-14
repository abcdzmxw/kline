<?php


namespace App\Services\ExchangeRateService;


use App\Services\ExchangeRateService\lib\Fxhapi;
use App\Traits\RedisTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    use RedisTool;

    private $server;

    private $ttl = 60;

    public function __construct()
    {
        $this->server = new Fxhapi();
    }

    public function getCurrencyExCny_copy($coin_name)
    {
        $tickers = $this->getTickers();
        return array_first($tickers,function ($value, $key) use ($coin_name){
            return $value['symbol'] == $coin_name;
        });
    }

    public function getCurrencyExCny($coin_name)
    {
        $tickers = $this->getTickers();
        if(blank($tickers)){
            return '';
        }
        return array_first($tickers,function ($value, $key) use ($coin_name){
            return $value['symbol'] == $coin_name;
        });
    }

    private function getTickers($currency = 'cny')
    {
        $key = 'ExchangeRate_' . $currency;
        $keyTtl = 'ExchangeRate_ttl_' . $currency;

        if (!Cache::has($keyTtl)){
            $result = $this->server->getTickers($currency);
            if($result) {
                Cache::put($key, $result, 60000);
            }
            Cache::put($keyTtl,1,60);
        }
        return Cache::get($key);
        /*
        if (Cache::has($key)){
            $result = Cache::get($key);
        }else{
            $result = $this->server->getTickers($currency);
            Cache::put($key,$result,60);
        }
        */

        return $result;
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
