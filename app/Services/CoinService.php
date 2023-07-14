<?php


namespace App\Services;


use App\Models\UserWallet;
use App\Services\CoinService\BitCoinService;
use App\Services\CoinService\GethService;
use App\Services\CoinService\Interfaces\CoinServiceInterface;
use App\Services\CoinService\TronService;

class CoinService
{
//    public function __construct()
//    {
//
//    }

    /*创建区块钱包地址*/
    public function createBlockAccount(CoinServiceInterface $coinService,$wallet)
    {
        if ($wallet['coin_name'] == 'BTC'){
            if ($address = $coinService->newAccount()){
                $wallet->update(['wallet_address' => $address]);
                return $address;
            }
        }elseif ($wallet['coin_name'] == 'ETH'){
            if ($address = $coinService->newAccount()){
                $wallet->update(['wallet_address' => $address]);
                return $address;
            }
        }elseif ($wallet['coin_name'] == 'TRX'){
            $res = $coinService->newAccount();
            if($res instanceof \IEXBase\TronAPI\TronAddress){
                $raw_data = $res->getRawData();
                $address = $res->getAddress(true);
                $wallet->update(['wallet_address' => $address,'raw_data' => $raw_data]);
                return $address;
            }else{
                return '';
            }
        }
    }

    public function createERC20USDTAccount($wallet)
    {
        $eth_account = UserWallet::query()->where(['user_id'=>$wallet['user_id'],'coin_name'=>'ETH'])->first();
        if(!blank($eth_account)){
            if(blank($eth_account['wallet_address'])){
                $address = $this->createBlockAccount(new GethService(),$eth_account);
                return $wallet->update(['wallet_address' => $address]);
            }else{
                return $wallet->update(['wallet_address' => $eth_account['wallet_address']]);
            }
        }
    }

    public function createTRC20USDTAccount($wallet)
    {
        $account = (new TronService())->newAccount();
        if($account instanceof \IEXBase\TronAPI\TronAddress){
            $raw_data = $account->getRawData();
            $private_key = $account->getPrivateKey();
            $address = $account->getAddress(true);
            return $wallet->update(['trx_wallet_address' => $address,'private_key'=>$private_key,'raw_data' => json_encode($raw_data)]);
        }

//        $trx_account = UserWallet::query()->where(['user_id'=>$wallet['user_id'],'coin_name'=>'TRX'])->first();
//        if(!blank($trx_account)){
//            if(blank($trx_account['wallet_address'])){
//                $address = $this->createBlockAccount(new TronService(),$trx_account);
//                return $wallet->update(['trx_wallet_address' => $address]);
//            }else{
//                return $wallet->update(['trx_wallet_address' => $trx_account['wallet_address']]);
//            }
//        }
    }

    public function createOMNIUSDTAccount($wallet)
    {
        $btc_account = UserWallet::query()->where(['user_id'=>$wallet['user_id'],'coin_name'=>'BTC'])->first();
        if(!blank($btc_account)){
            if(blank($btc_account['wallet_address'])){
                $address = $this->createBlockAccount(new BitCoinService(),$btc_account);
                return $wallet->update(['omni_wallet_address' => $address]);
            }else{
                return $wallet->update(['omni_wallet_address' => $btc_account['wallet_address']]);
            }
        }
    }


    /**
     * 优盾钱包
     * @param $wallet
     * @param $coin_name
     * @param null $addressType 钱包类型 1：USDT-OMNI 2：USDT-ERC20 3：USDT-TRC20
     * @return mixed
     */
    public function createAddress($wallet,$coin_name,$addressType = null)
    {
        $map = [
            'BTC' => 0,
            'ETH' => 60,
            'TRX' => 195,
        ];
        $coinType = $map[$coin_name] ?? null;
        if(blank($coinType)) return ;

        if(blank($addressType)){
            $res = (new UdunWalletService())->createAddress($coinType);
            info($res);
            if($res['code'] == 200){
                $address = $res['data']['address'];
                $wallet->update(['wallet_address' => $address]);
            }
        }else{
            if($addressType == 1){
                $account = UserWallet::query()->where(['user_id'=>$wallet['user_id'],'coin_name'=>'BTC'])->first();
                $field = 'omni_wallet_address';
            }elseif ($addressType == 2){
                $account = UserWallet::query()->where(['user_id'=>$wallet['user_id'],'coin_name'=>'ETH'])->first();
                $field = 'wallet_address';
            }else{
                $account = UserWallet::query()->where(['user_id'=>$wallet['user_id'],'coin_name'=>'TRX'])->first();
                $field = 'trx_wallet_address';
            }

            if(empty($account['wallet_address'])){
                $res = (new UdunWalletService())->createAddress($coinType);
                if($res['code'] == 200){
                    $address = $res['data']['address'];
                    $wallet->update([$field => $address]);
                    $account->update(['wallet_address' => $address]);
                }else{
                    info(json_encode($res));
                }
            }else{
                return $wallet->update([$field => $account['wallet_address']]);
            }
        }
    }

}
