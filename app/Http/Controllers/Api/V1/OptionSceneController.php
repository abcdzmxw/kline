<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\CoinData;
use App\Models\Coins;
use App\Models\OptionBetCoin;
use App\Models\OptionPair;
use App\Models\UserWallet;
use App\Services\HuobiService\HuobiapiService;
use App\Services\SceneService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptionSceneController extends ApiController
{
    //期权

    protected $sceneService;

    public function __construct(SceneService $sceneService)
    {
        $this->sceneService = $sceneService;
    }

    //获取全部期权场景
    public function sceneListByPairs(Request $request)
    {
        $data = $this->sceneService->sceneListByPairs();
        return $this->successWithData($data);
    }

    //根据交易对和时间周期获取当前最新期权场景
    public function sceneDetail(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'pair_id' => 'required|integer',
            'time_id' => 'required|integer',
        ])) return $vr;

        $params = $request->all();

        $data = $this->sceneService->sceneDetail($params);
        return $this->successWithData($data);
    }

    //根据交易对和时间周期获取当前最新期权场景赔率
    public function getOddsList(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'pair_id' => 'required|integer',
            'time_id' => 'required|integer',
        ])) return $vr;

        $params = $request->all();

        $data = $this->sceneService->getOddsList($params);
        return $this->successWithData($data);
    }

    //获取期权交割记录
    public function getSceneResultList(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'pair_id' => 'required|integer',
            'time_id' => 'required|integer',
        ])) return $vr;

        $params = $request->all();

        $data = $this->sceneService->getSceneResultList($params);
        return $this->successWithData($data);
    }

    public function getOptionSymbol()
    {
        $data = OptionPair::query()->where('status',1)->get();
        return $this->successWithData($data);
    }

    //获取期权用户买入记录
    public function getOptionHistoryOrders(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'pair_id' => 'integer',
            'time_id' => 'integer',
            'status' => 'integer|in:1,2', //1待交割 2已交割
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->all();

        $data = $this->sceneService->getOptionHistoryOrders($user,$params);
        return $this->successWithData($data);
    }

    //获取可用来购买期权交易的币种列表
    public function getBetCoinList()
    {
        $coins = OptionBetCoin::query()->where('is_bet',1)->orderByDesc('sort')->get();
        return $this->successWithData($coins);
    }

    //获取用户期权账户余额
    public function getUserCoinBalance(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'coin_id' => 'required|integer',
        ])) return $vr;

        $user = $this->current_user();

        $wallet = UserWallet::query()->where(['user_id' => $user['user_id'] ,'coin_id' => $request->coin_id])->firstOrFail();

        return $this->successWithData($wallet);
    }

    //获取初始价格数据
    public function getNewPriceBook(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'symbol' => 'required',
        ])) return $vr;

        $params = $request->all();
        $symbol = strtolower(str_before($params['symbol'],'/') . str_after($params['symbol'],'/'));

        $history_data_key = 'market:' . $symbol . '_newPriceBook';
        $history_cache_data = Cache::store('redis')->get($history_data_key);
        return $this->successWithData($history_cache_data);
    }

    //获取初始Kline数据
    public function getKline(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'symbol' => 'required',
            'period' => 'required',
            'size' => 'required',
            'zip' => '',
        ])) return $vr;

        $params = $request->all();
        $zip = $request->input('zip',0);
        if(strpos($params['symbol'],'/') !== false){
            $symbol = strtolower(str_before($params['symbol'],'/') . str_after($params['symbol'],'/'));
        }else{
            $symbol = $params['symbol'];
        }

        $history_data_key = 'market:' . $symbol . '_kline_book_' . $params['period'];
        $history_cache_data = Cache::store('redis')->get($history_data_key);
        $data['data'] = $history_cache_data;
        $data['ch'] = "market.".$symbol.".kline.".$params['period'];
        $data['ts'] = Carbon::now()->getPreciseTimestamp(3);
        $data['status'] = 'ok';

        $coins = config('coin.exchange_symbols');
        foreach ($coins as $coin => $class){
            $coin = strtolower($coin);
            if($symbol == $coin . 'usdt'){
                $data['data'] = $class::getKlineData($symbol,$params['period'],$params['size']);
                $data['ch'] = "market.".$symbol.".kline.".$params['period'];
                $data['ts'] = Carbon::now()->getPreciseTimestamp(3);
                $data['status'] = 'ok';

                break;
            }
        }

        if($zip){
            $json = json_encode($data['data']);
            $gzstr = gzcompress($json);
            $data['data'] = base64_encode($gzstr);
            return $this->successWithData($data);
        }else{
            return $this->successWithData($data);
        }
    }

    //购买期权
    public function betScene(Request $request)
    {
        if(empty($request->input('bet_amount'))){
            return $this->error(0,'投注金额不能为空');
        }
        if ($vr = $this->verifyField($request->all(),[
            'bet_amount' => 'required|numeric',
            'bet_coin_id' => 'required|integer',
            'scene_id' => 'required|integer',
            'odds_uuid' => 'required',
        ])) return $vr;

        $user = $this->current_user();
        $params = $request->only(['bet_amount','bet_coin_id','scene_id','odds_uuid']);

        $orderLockKey = 'option_order_lock:' . $user['user_id'];
        if (!$this->setKeyLock($orderLockKey,2)){ //订单锁
            return $this->error();
        }

        // 小白
        // 用户权限判断  如果没有高级认证不可参与交易
        if($user->user_auth_level == 0){
            return $this->error(0,'请完成实名认证'); // 使用公共语言
        }

        $res = $this->sceneService->betScene($user,$params);
        if(!$res){
            return $this->error(0,'买入失败');
        }
        return $this->success('买入成功',true);
    }

}
