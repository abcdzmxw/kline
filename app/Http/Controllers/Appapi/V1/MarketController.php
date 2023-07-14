<?php


namespace App\Http\Controllers\Appapi\V1;


use App\Models\Collect;
use App\Models\InsideTradePair;
use App\Services\ExchangeRateService\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarketController extends ApiController
{
    public function getCurrencyExCny(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_name' => 'required', //币种名称 BTC
        ])) return $vr;

        $coin_name = $request->input('coin_name');
        $detail = (new ExchangeRateService())->getCurrencyExCny($coin_name);
        if(blank($detail)) return $this->successWithData([]);
        return $this->successWithData($detail);
    }

    public function index(){


        $user = $this->current_user();
        if(blank($user)){
            $collect = [];
        }else{
            $collect = Collect::query()->where(array("user_id"=>$user->user_id))->pluck('pair_name')->toArray();
        }
        $data = InsideTradePair::query()->where("status",1)->orderBy('sort','asc')->get()->groupBy('quote_coin_name')->toArray();
        $kk = 0;
        foreach ($data as $coin_key => $items){

            $market[$kk]['coin_name'] = $coin_key;
            $quote_coin_name = strtolower($coin_key);
            foreach ($items as $key2 => $item){
                $market[$kk]['marketInfoList'][$key2] = Cache::store('redis')->get('market:' . $item['symbol'] . '_detail');
                $market[$kk]['marketInfoList'][$key2]['coin_name'] = $quote_coin_name;
                $market[$kk]['marketInfoList'][$key2]["pair_name"] = $item['pair_name'];
                $market[$kk]['marketInfoList'][$key2]["pair_id"] = $item['pair_id'];
                if(in_array($item['pair_name'],$collect)){
                    $market[$kk]['marketInfoList'][$key2]["is_collect"] = 1;
                }else{
                    $market[$kk]['marketInfoList'][$key2]["is_collect"] = 0;
                }

                $market[$kk]['marketInfoList'][$key2]["marketInfoList"] = $item['base_coin_name'];
            }
            $kk++;
        }

        $k = 0;
        $symbols = [];
        foreach ($market as $key=> $items) {

            foreach ($items["marketInfoList"] as $coin) {

                $mark = strtolower($coin["marketInfoList"]) . strtolower($items["coin_name"]);

                // 取实时的交易价格
                $symbol_name = 'market:' . $mark . '_newPrice';
                $data = Cache::store('redis')->get($symbol_name);

                $symbols[$k]['pair'] = $coin["marketInfoList"] /*. "/" . $items["coin_name"]*/;
                $symbols[$k]["price"] = $data["price"];

                $symbols[$k]['increase'] = (float)$data["increase"];
                $symbols[$k]['increaseStr'] = $data["increaseStr"];
                $k++;
            }
        }

       return $this->successWithData($symbols);
    }

}
