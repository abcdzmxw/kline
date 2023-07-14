<?php

namespace App\Http\Controllers\Appapi\V1;

use App\Models\Advice;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\InsideTradeOrder;
use App\Models\InsideTradePair;
use App\Models\OptionPair;
use App\Models\OptionScene;
use App\Models\OptionTime;
use App\Models\UserGrade;
use App\Models\UserWallet;
use App\Models\Withdraw;
use App\Models\Coins;
use App\Models\Recharge;
use App\Models\SustainableAccount;
use App\Models\TransferRecord;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Services\HuobiService\HuobiapiService;
use App\Services\UserService;
use App\Exceptions\ApiException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\models\UserCoinName;
use Jenssegers\Agent\Agent;


class UserController extends ApiController
{
    public function test(){
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

    public function myNotifiables(Request $request)
    {
        $user = $this->current_user();

        $notifiables = $user->notifications()->latest()->paginate();

        //获取列表 全部标记已读
        $user->unreadNotifications->markAsRead();

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
            'front_img' => 'required',
            'back_img' => 'required',
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
        $account = $request->input('account')?:$request->input('phone');

        
        $params['front_img'] = $request->input('front_img');
        $params['back_img'] = $request->input('back_img');

        // 短信验证
        /*
        $params['chu_phone'] = $account;
        $params['chu_country_code'] = $request->input('country_code','86');
        $code = $request->input('code')?:$request->input('sms_code');
        $checkResult = checkSMSCode($account,$code,'',$request->country_code);
        if ($checkResult !== true) return $this->error(4001,$checkResult);
        */

        // if ( !$this->isIdentityCard($params['id_card']) ) return $this->error(0,'身份证不合法');
        $res = $userService->primaryAuth($user,$params);
        if(!$res){
            return $this->error();
        }
        return $this->success('认证成功');
    }

    //高级认证
    public function topAuth(Request $request,UserService $userService)
    {
        if(empty($request->input('hand_img'))){
            return $this->error(0,'Picture upload failed');
        }

        if ($res = $this->verifyField($request->all(),[
            'hand_img' => '',
        ])) return $res;

        $user = $this->current_user();
        $params['hand_img'] = $request->input('hand_img');
        /*
        // 短信验证
        $account = $request->input('account')?:$request->input('phone');
        $params['gao_country_code'] = $request->input('country_code','86');
        $params['gao_phone'] = $account;
        $code = $request->input('code')?:$request->input('sms_code');
        $checkResult = checkSMSCode($account,$code,'',$request->country_code);
        if ($checkResult !== true) return $this->error(4001,$checkResult);
        */
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
