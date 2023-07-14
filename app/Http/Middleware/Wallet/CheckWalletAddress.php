<?php

namespace App\Http\Middleware\Wallet;

use App\Models\Coins;
use App\Models\UserWallet;
use App\Services\CoinService;
use Closure;

class CheckWalletAddress
{
    private $coinService;

    public function __construct(CoinService $coinService)
    {
        $this->coinService = $coinService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth('api')->user();
        $coin_id = $request->input('coin_id');
        if(! Coins::is_recharge($coin_id)) return $next($request);

        if($user){
            $address_type = $request->input('address_type',2); // 默认erc_20
            $wallet = UserWallet::query()->where(['user_id' => $user['user_id'], 'coin_id' => $coin_id])->first();
            if(blank($wallet)) return $next($request);

            if(config('coin.udun_switch') === true){
                // 优盾钱包
                if($coin_id == 1){
                    // USDT 1:btc_usdt 2:eth_usdt 3:trx_usdt
                    if ($address_type == 1) {
                        if (blank($wallet['omni_wallet_address']))
                            $this->coinService->createAddress($wallet,'BTC',$address_type);
                    }elseif($address_type == 2){
                        if (blank($wallet['wallet_address']))
                            $this->coinService->createAddress($wallet,'ETH',$address_type);
                    }else{
                        if (blank($wallet['trx_wallet_address']))
                            $this->coinService->createAddress($wallet,'TRX',$address_type);
                    }
                }else{
                    if (blank($wallet['wallet_address'])){
                        $this->coinService->createAddress($wallet,$wallet['coin_name']);
                    }
                }
            }else{
                if($coin_id == 1){
                    // USDT 1:btc_usdt 2:eth_usdt 3:trx_usdt
                    if ($address_type == 1) {
                        if (blank($wallet['omni_wallet_address']))
                            $this->coinService->createOMNIUSDTAccount($wallet);
                    }elseif($address_type == 2){
                        if (blank($wallet['wallet_address']))
                            $this->coinService->createERC20USDTAccount($wallet);
                    }else{
                        if (blank($wallet['trx_wallet_address']))
                            $this->coinService->createTRC20USDTAccount($wallet);
                    }
                }else{
                    if (blank($wallet['wallet_address'])){
                        switch ($wallet['coin_name']){
                            case 'BTC':
                                $this->coinService->createBlockAccount(new \App\Services\CoinService\BitCoinService(),$wallet);
                                break;
                            case 'ETH':
                                $this->coinService->createBlockAccount(new \App\Services\CoinService\GethService(),$wallet);
                                break;
                            case 'TRX':
                                $this->coinService->createBlockAccount(new \App\Services\CoinService\TronService(),$wallet);
                                break;
                        }
                    }
                }
            }
        }

        return $next($request);
    }

}
