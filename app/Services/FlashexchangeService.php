<?php

namespace App\Services;

use App\Events\WithdrawEvent;
use App\Handlers\ContractTool;
use App\Models\ContractPair;
use App\Models\ContractPosition;
use App\Models\ListingApplication;
use App\Models\OtcAccount;
use App\Models\OtcCoinlist;
use App\Models\SustainableAccount;
use App\Models\Recharge;
use App\Exceptions\ApiException;
use App\Models\Coins;
use App\Models\User;
use App\Models\UserDepositAddress;
use App\Models\UserPaymentMethod;
use App\Models\UserSubscribe;
use App\Models\UserSubscribeRecord;
use App\Models\UserTransferTranslation;
use App\Models\Withdraw;
use App\Models\UserWallet;
use App\Models\TransferRecord;
use App\Models\WithdrawalManagement;
use App\Models\FlashExchange;
use App\Models\FlashExchangeConfig;
use App\Models\UserRestrictedTrading;  // 用户限制交易
use App\Services\HuobiService\HuobiapiService;
use App\Services\HuobiService\lib\HuobiLibService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SubscribeActivity;
use Carbon\Carbon;

class FlashexchangeService
{
    //  闪兑币种列表
    public function currency_list($params)
    {
        return Coins::where(['is_flash_exchange'=>'1','status'=>'1'])->get();
    }

    //  获取币种余额
    public function getBalance($user,$params)
    {
        if(empty($params['account'])){
            throw new ApiException('异常');
        }
    	// 获取参数
        $account_type = $params['account'];

        // 获取余额
        $wallet = UserWallet::where(['user_id'=>$user['user_id'],'coin_id'=>$params['account']])->first();
        return $wallet;
    }

    // 获取汇率
    public function exchange_rate($params)
    {
        // 获取余额
        $form_account = UserWallet::where(['coin_id'=>$params['from_account']])->first();
        $to_account = UserWallet::where(['coin_id'=>$params['to_account']])->first();
        // 获取汇率
        // 小白-获取行情
        if($form_account['coin_name'] == 'USDT'){
       		$cacheKey = 'market:' . strtolower($to_account['coin_name']) . strtolower($form_account['coin_name']) . '_newPrice';
        }else{
        	$cacheKey = 'market:' . strtolower($form_account['coin_name']) . strtolower($to_account['coin_name']) . '_newPrice';
        }
        $cacheData = Cache::store('redis')->get($cacheKey);
        if(empty($cacheData['price']) || $cacheData['price'] <=0){
            throw new ApiException('行情通讯失败');
        }
        $hang_price = $cacheData['price'];
        return $hang_price;
    }

    // 开始闪兑
    public function flicker($user,$params)
    {
    	if(empty($params['amount'])){
    		throw new ApiException('请输入数量');
    	}
    	if(empty($params['from_account'])){
    		throw new ApiException('请选择转换币种');
    	}
    	if(empty($params['to_account'])){
    		throw new ApiException('请选择转换币种');
    	}
    	if($params['amount'] <= 0){
    		throw new ApiException('请输入数量');
    	}

        // 小白
        // 获取余额
        $form_account = Coins::where(['coin_id'=>$params['from_account']])->first();
        $to_account = Coins::where(['coin_id'=>$params['to_account']])->first();

        if($form_account['sell_restricted'] == 1){
            throw new ApiException('已进入锁仓期，请等待硬币上市');
        }
        if($to_account['buy_restricted'] == 1){
            throw new ApiException('发行结束，已无申购额度');
        }

        // 获取卖出限制
        $sell_restricted = UserRestrictedTrading::where(['user_id'=>$user['user_id'],'coin_id'=>$params['from_account'],'type'=>'2','direction'=>'2','status'=>'1'])->first();
        if($sell_restricted){
            throw new ApiException('已进入锁仓期，请等待硬币上市');
        }
        // 获取买入限制
        $buy_restricted = UserRestrictedTrading::where(['user_id'=>$user['user_id'],'coin_id'=>$params['to_account'],'type'=>'2','direction'=>'1','status'=>'1'])->first();
        if($buy_restricted){
            throw new ApiException('发行结束，已无申购额度');
        }

        // 获取汇率
        // 小白-获取行情
        if($form_account['coin_name'] == 'USDT'){
       		$cacheKey = 'market:' . strtolower($to_account['coin_name']) . strtolower($form_account['coin_name']) . '_newPrice';
        }else{
        	$cacheKey = 'market:' . strtolower($form_account['coin_name']) . strtolower($to_account['coin_name']) . '_newPrice';
        }
        $cacheData = Cache::store('redis')->get($cacheKey);
        if(empty($cacheData['price']) || $cacheData['price'] <=0){
            throw new ApiException('行情通讯失败');
        }
        $hang_price = $cacheData['price'];

        // 获取用户信息
        $from_wallet = UserWallet::where(['user_id'=>$user['user_id'],'coin_id'=>$params['from_account']])->first();
        if(empty($from_wallet)){
        	throw new ApiException('钱包异常');
        }
        // 判断余额是否足够
        if($from_wallet['usable_balance'] < $params['amount']){
			throw new ApiException('余额不足');
        }

        // 获取用户信息
        $to_wallet = UserWallet::where(['user_id'=>$user['user_id'],'coin_id'=>$params['to_account']])->first();
        if(empty($to_wallet)){
        	throw new ApiException('钱包异常');
        }

        // 获取转换后的金额
        if($form_account['coin_name'] != 'USDT'){
       		$to_amount = floor(($params['amount'] * $hang_price) * 100000) / 100000;
       	}else{
       		$to_amount = floor(($params['amount'] / $hang_price) * 100000) / 100000;
       	}


        $flash_exchange_config = FlashExchangeConfig::where(['status'=>1])->first();
        $flash_exchange_rate = $flash_exchange_config['flash_exchange_rate'];
       	// 计算手续费
       	$to_amount_fee = $to_amount * ($flash_exchange_rate / 100);
        // 开始创建订单
        $flash_exchange_data = array(
        	'user_id'		=> $user->user_id,
        	'from_coin_id'	=> $params['from_account'],
        	'from_coin_name'=> $form_account['coin_name'],
        	'to_coin_id'	=> $params['to_account'],
        	'to_coin_name'	=> $to_account['coin_name'],
        	'amount' 		=> $params['amount'],
        	'hang_price'	=> $hang_price,
            'to_amount'     => $to_amount,
            'to_amount_rate'=> $flash_exchange_rate,
        	'to_amount_fee'=> $to_amount_fee,
        );

        DB::beginTransaction();
        try{
        	// 创建记录
        	FlashExchange::create($flash_exchange_data);
        	// 扣除用户余额
        	$user->update_wallet_and_log($form_account['coin_id'], 'usable_balance', -$params['amount'],UserWallet::asset_account, 'flash_exchange');
        	$user->update_wallet_and_log($to_account['coin_id'], 'usable_balance', $to_amount,UserWallet::asset_account, 'flash_exchange');
        	if($to_amount_fee > 0){
        		$user->update_wallet_and_log($to_account['coin_id'], 'usable_balance', -$to_amount_fee,UserWallet::asset_account, 'flash_exchange_fee');
        	}

        	DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return api_response()->error(200,$e->getMessage());
        }

        return api_response()->success(200,"闪兑成功");
    }

    //  闪兑币种列表
    public function flicker_list($user,$params)
    {
        $result = FlashExchange::query()->where(['user_id' => $user['user_id']])->orderBy("id", 'desc')->paginate();
        // 获取币种信息
        foreach($result as $k=>$v){
            $coin = Coins::where(['coin_id'=>$v['from_coin_id']])->first();
            $result[$k]['coin_img'] = 'https://server.ktbcoin.com/storage/'.$coin['coin_icon'];
        }
        
        return api_response()->success('SUCCESS', $result);
    }
}
