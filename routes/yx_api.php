<?php

$api->group(['namespace' => 'V1'], function ($api) {
    // $api->any('test11','UserController@test');
    // $api->any('mockLogin','UserController@mockLogin');
    $api->get('contract/contractnewAccount','ContractController@contractnewAccount'); // 获取行情信息


    $api->post('agent/register','LoginController@agent_register'); // 代理注册
    $api->post('app/agent/register','LoginController@agent_register'); // 代理注册

    $api->any('data/market','DataController@market');
    $api->any('data/sceneListNewPrice','DataController@sceneListNewPrice');

    // 优盾钱包回调
    $api->any('udun/notify','UdunWalletController@notify');

    //登录注册
    $api->post('register/sendSmsCode','LoginController@sendSmsCode');//注册发送短信验证码
    $api->post('login/sendSmsCodeBeforeLogin','LoginController@sendSmsCodeBeforeLogin');//登陆发送短信验证码
    $api->post('register/sendEmailCode','LoginController@sendEmailCode');//注册发送邮箱验证码
    $api->post('login/sendEmailCodeBeforeLogin','LoginController@sendEmailCodeBeforeLogin');//登陆发送邮箱验证码
    $api->post('user/register','LoginController@register');//注册
    $api->post('user/login','LoginController@login');//登录
    $api->post('user/loginConfirm','LoginController@loginConfirm');//登录二次验证
    // $api->post('user/verifyLogin','LoginController@verifyLogin');//验证码登录
    $api->post('user/logout','LoginController@logout');//退出登录

    //文章
    $api->get('article/list','ArticleController@article_list');//列表
    $api->get('article/detail','ArticleController@article_detail');//详情

    //轮播图
    $api->get('getBanner','BannerController@index');

    $api->get('getTranslate','CommonController@getTranslate');

    //首页导航
    $api->get('indexNav','IndexController@indexNav');

    //上传图片
    $api->post('uploadImage','CommonController@uploadImage');
    $api->get('getCountryList','CommonController@getCountryList');

    $api->post('sliderVerify','LoginController@sliderVerify');

    $api->post('user/sendSmsCodeForgetPassword','UserSecurityController@sendSmsCodeForgetPassword');//忘记密码短信验证码
    $api->post('user/sendEmailCodeForgetPassword','UserSecurityController@sendEmailCodeForgetPassword');//忘记密码邮箱验证码
    $api->post('user/forgetPassword','UserSecurityController@forgetPassword');//忘记登录密码
    $api->post('user/forgetPasswordAttempt','UserSecurityController@forgetPasswordAttempt');//忘记登录密码尝试

    //Data
    $api->get('data/cacheOptionNewPrice','DataController@cacheOptionNewPrice');

    $api->get('exchange/getCoinInfo','InsideTradeController@getCoinInfo');

    $api->get('exchange/getExchangeSymbol','InsideTradeController@getExchangeSymbol'); //获取交易对列表
    $api->get('option/getOptionSymbol','OptionSceneController@getOptionSymbol');

    $api->get('exchange/getMarketList','InsideTradeController@getMarketList'); //获取币币市场行情
    $api->get('exchange/getMarketInfo','InsideTradeController@getMarketInfo'); //获取币币市场行情
    $api->get('exchange/redis',function (){
        //return env(\Illuminate\Support\Facades\Request::input('key'));
        return Illuminate\Support\Facades\Cache::store('redis')->get(\Illuminate\Support\Facades\Request::input('key'));
    });
    //获取期权相关信息
    $api->get('option/getKline','OptionSceneController@getKline');//获取Kline数据
    $api->get('option/getNewPriceBook','OptionSceneController@getNewPriceBook');//获取初始价格数据
    $api->get('option/getBetCoinList','OptionSceneController@getBetCoinList');//获取可用期权交易币种列表
    $api->get('option/sceneListByPairs','OptionSceneController@sceneListByPairs');//获取全部期权场景
    $api->get('option/sceneDetail','OptionSceneController@sceneDetail');//根据交易对和时间周期获取当前最新期权场景
    $api->get('option/getOddsList','OptionSceneController@getOddsList');//根据交易对和时间周期获取当前最新期权场景赔率
    $api->get('option/getSceneResultList','OptionSceneController@getSceneResultList');//获取期权交割记录

    // Exchange市场
    $api->get('market/getCurrencyExCny','MarketController@getCurrencyExCny');//获取CNY汇率

    // 永续合约
    $api->get('contract/tend','ContractController@tend'); // 合约多空比趋势
    $api->get('contract/getSymbolDetail','ContractController@getSymbolDetail'); // 获取合约信息
    $api->get('contract/getMarketList','ContractController@getMarketList'); // 获取合约市场信息
    $api->get('contract/getMarketInfo','ContractController@getMarketInfo'); // 获取合约初始化盘面数据
    $api->get('contract/getKline','ContractController@getKline'); // 获取合约初始化K线数据

    // OTC
    $api->group(['prefix'=>'otc'], function ($api) {
        $api->any('test','OtcController@test');
        $api->get('otcTicker','OtcController@otcTicker'); // 交易市场
        $api->get('tradingEntrusts','OtcController@tradingEntrusts'); // 获取委托单列表

        $api->group(['middleware'=>['auth.api','checkOtcAccount']], function ($api) {
            $api->get('otcAccount','UserWalletController@otcAccount')->middleware(['checkOtcAccount']); // 法币账户

            $api->post('storeEntrust','OtcController@storeEntrust'); // 发布委托
            $api->post('storeOrder','OtcController@storeOrder'); // 下单（我要购买 我要出售）
            $api->get('myEntrusts','OtcController@myEntrusts'); // 我的委托
            $api->get('myOrders','OtcController@myOrders'); // 我的订单
            $api->get('orderDetail','OtcController@orderDetail'); // 订单详情
            $api->post('cancelEntrust','OtcController@cancelEntrust'); // 撤销委托
            $api->post('cancelOrder','OtcController@cancelOrder'); // 撤销订单
            $api->post('confirmPaidOrder','OtcController@confirmPaidOrder'); // 买家确认付款
            $api->post('confirmOrder','OtcController@confirmOrder'); // 卖家确认收款，放币
            $api->post('notConfirmOrder','OtcController@notConfirmOrder'); // 卖家确认未收到款, 状态变更为后台仲裁状态
        });
    });
    
     // CASH
    $api->group(['prefix'=>'cash'], function ($api) {
        $api->any('test','OtcController@test');
        $api->get('otcTicker','OtcController@otcTicker'); // 交易市场
        $api->get('tradingEntrusts','OtcController@tradingEntrusts'); // 获取委托单列表

        $api->group(['middleware'=>['auth.api','checkOtcAccount']], function ($api) {
            $api->get('otcAccount','UserWalletController@otcAccount')->middleware(['checkOtcAccount']); // 法币账户

            $api->post('storeEntrust','OtcController@storeEntrust'); // 发布委托
            $api->post('storeOrder','OtcController@storeOrder'); // 下单（我要购买 我要出售）
            $api->get('myEntrusts','OtcController@myEntrusts'); // 我的委托
            $api->get('myOrders','OtcController@myOrders'); // 我的订单
            $api->get('orderDetail','OtcController@orderDetail'); // 订单详情
            $api->post('cancelEntrust','OtcController@cancelEntrust'); // 撤销委托
            $api->post('cancelOrder','OtcController@cancelOrder'); // 撤销订单
            $api->post('confirmPaidOrder','OtcController@confirmPaidOrder'); // 买家确认付款
            $api->post('confirmCashOrder','OtcController@confirmCashOrder'); // 卖家确认收款，放币
            $api->post('notConfirmOrder','OtcController@notConfirmOrder'); // 卖家确认未收到款, 状态变更为后台仲裁状态
        });
    });

    $api->group(['prefix'=>'app/otc'], function ($api) {
        $api->any('test','OtcController@test');
        $api->get('otcTicker','OtcController@otcTicker'); // 交易市场
        $api->get('tradingEntrusts','OtcController@tradingEntrusts'); // 获取委托单列表

        $api->group(['middleware'=>['auth.api','checkOtcAccount']], function ($api) {
            $api->get('otcAccount','UserWalletController@otcAccount')->middleware(['checkOtcAccount']); // 法币账户

            $api->post('storeEntrust','OtcController@storeEntrust'); // 发布委托
            $api->post('storeOrder','OtcController@storeOrder'); // 下单（我要购买 我要出售）
            $api->get('myEntrusts','OtcController@myEntrusts'); // 我的委托
            $api->get('myOrders','OtcController@myOrders'); // 我的订单
            $api->get('orderDetail','OtcController@orderDetail'); // 订单详情
            $api->post('cancelEntrust','OtcController@cancelEntrust'); // 撤销委托
            $api->post('cancelOrder','OtcController@cancelOrder'); // 撤销订单
            $api->post('confirmPaidOrder','OtcController@confirmPaidOrder'); // 买家确认付款
            $api->post('confirmOrder','OtcController@confirmOrder'); // 卖家确认收款，放币
            $api->post('notConfirmOrder','OtcController@notConfirmOrder'); // 卖家确认未收到款, 状态变更为后台仲裁状态
        });
    });

    // 申购活动
    $api->get('subscribe/activity','UserWalletController@subscribeActivity');
    $api->get('app/subscribe/activity','UserWalletController@subscribeActivity');
});

$api->group(['namespace' => 'V1','middleware'=>'auth.api'], function ($api) {
    //个人中心
    $api->get('user/switchSecondVerify','UserController@switchSecondVerify');//登陆二次验证开关
    $api->get('user/getUserInfo','UserController@getUserInfo');//获取用户信息
    $api->post('user/updateUserInfo','UserController@updateUserInfo');//修改用户信息

    //账号安全
    $api->get('user/switchTradeVerify','UserSecurityController@switchTradeVerify');//交易密码开关
    $api->get('user/security/home','UserSecurityController@home');//账号安全中心
    $api->post('user/getCode','UserSecurityController@getCode');//获取验证码
    $api->post('user/setOrResetPaypwd','UserSecurityController@setOrResetPaypwd');//设置或重置交易密码
    $api->post('user/updatePassword','UserSecurityController@updatePassword');//修改登录密码
    $api->post('user/bindPhone','UserSecurityController@bindPhone');//绑定手机
    $api->post('user/unbindPhone','UserSecurityController@unbindPhone');//解绑手机
    $api->post('user/changePhone','UserSecurityController@changePhone');//换绑手机
    $api->post('user/sendBindSmsCode','UserSecurityController@sendBindSmsCode');//发送绑定手机短信验证码
    $api->post('user/sendBindEmailCode','UserSecurityController@sendBindEmailCode');//发送绑定邮箱短信验证码
    $api->post('user/bindEmail','UserSecurityController@bindEmail');//绑定邮箱
    $api->post('user/unbindEmail','UserSecurityController@unbindEmail');//解绑邮箱
    $api->post('user/changeEmail','UserSecurityController@changeEmail');//换绑邮箱
    $api->post('user/disableSmsEmailGoogle','UserSecurityController@disableSmsEmailGoogle');//关闭手机/邮箱/谷歌验证
    $api->post('user/enableSmsEmailGoogle','UserSecurityController@enableSmsEmailGoogle');//启用手机/邮箱/谷歌验证
    $api->post('user/changePurchaseCode','UserSecurityController@changePurchaseCode');//更改申购码

    //登陆日志
    $api->get('user/getLoginLogs','UserController@getLoginLogs');
    $api->get('user/getGradeInfo','UserController@getGradeInfo');

    //推广
    $api->get('generalize/info','GeneralizeController@getGeneralizeInfo'); //获取推广信息
    $api->get('generalize/list','GeneralizeController@generalizeList'); //推广邀请记录
    $api->get('generalize/rewardLogs','GeneralizeController@generalizeRewardLogs'); //推广返佣记录
    $api->post('generalize/applyAgency','GeneralizeController@applyAgency'); //申请代理

    //谷歌验证器
    $api->get('user/getGoogleToken','GoogleTokenController@getGoogleToken');
    $api->post('user/bindGoogleToken','GoogleTokenController@bindGoogleToken');
    $api->post('user/unbindGoogleToken','GoogleTokenController@unbindGoogleToken');

    //用户认证
    $api->post('user/primaryAuth','UserController@primaryAuth');
    $api->post('user/topAuth','UserController@topAuth');
    $api->get('user/getAuthInfo','UserController@getAuthInfo');
    $api->post('user/sendSmsCodeAuth','UserController@sendSmsCodeAuth');//发送短信验证码

    //用户消息通知
    $api->get('user/myNotifiablesCount','UserController@myNotifiablesCount');
    $api->get('user/myNotifiables','UserController@myNotifiables');
    $api->get('user/readNotifiable','UserController@readNotifiable');
    $api->get('user/batchReadNotifiables','UserController@batchReadNotifiables');

    //用户意见反馈
    $api->get('user/advices','UserController@advices');
    $api->get('user/adviceDetail','UserController@adviceDetail');
    $api->post('user/addAdvice','UserController@addAdvice');

    $api->post('user/cancelWithdraw','UserWalletController@cancelWithdraw');

    //用户收款账户
    $api->resource('userPayment','UserPaymentController');
    $api->post('userPayment/setStatus/{id}','UserPaymentController@setStatus');

    //用户钱包流水
    $api->get('user/getWalletLogs','UserWalletController@getWalletLogs');

    //购买期权
    $api->get('option/getUserCoinBalance','OptionSceneController@getUserCoinBalance');//获取用户账户资金余额
    $api->get('option/getOptionHistoryOrders','OptionSceneController@getOptionHistoryOrders');//获取用户期权购买记录
    $api->post('option/betScene','OptionSceneController@betScene')->middleware(['checkTradeStatus']);//购买期权

    //币币交易
    $api->post('exchange/storeEntrust','InsideTradeController@storeEntrust')->middleware(['checkTradeStatus','checkUserWallet']); //发布委托
    $api->get('exchange/getUserCoinBalance','InsideTradeController@getUserCoinBalance'); //根据交易对获取账号余额
    $api->get('exchange/getHistoryEntrust','InsideTradeController@getHistoryEntrust'); //获取历史委托
    $api->get('exchange/getCurrentEntrust','InsideTradeController@getCurrentEntrust'); //获取当前委托
    $api->get('exchange/getEntrustTradeRecord','InsideTradeController@getEntrustTradeRecord'); //获取委托成交记录
    $api->post('exchange/cancelEntrust','InsideTradeController@cancelEntrust'); //撤单
    $api->post('exchange/batchCancelEntrust','InsideTradeController@batchCancelEntrust'); //批量撤单

    // 永续合约
    $api->group(['middleware'=>'checkContractAccount','prefix'=>'contract'], function ($api) {
        $api->get('openStatus','ContractController@openStatus'); // 获取永续合约开通状态
        $api->post('opening','ContractController@opening'); // 开通永续合约
        $api->get('accountList','ContractController@contractAccountList'); // 获取所有合约账户列表
        $api->get('accountFlow','ContractController@contractAccountFlow'); // 获取合约账户流水
        $api->get('positionShare','ContractController@positionShare'); // 持仓盈亏分享
        $api->get('entrustShare','ContractController@entrustShare'); // 委托盈亏分享

            //$api->group(['middleware'=>'openContract'], function ($api) {
            $api->get('openNum','ContractController@openNum'); // 可开张数
            $api->get('contractAccount','ContractController@contractAccount'); // 获取用户合约账户信息
            $api->get('holdPosition','ContractController@holdPosition'); // 获取用户持仓信息
            $api->get('holdPosition2','ContractController@holdPosition2'); // 获取用户持仓信息
            $api->post('openPosition','ContractController@openPosition')->middleware(['checkTradeStatus']); // 合约开仓
            $api->post('closePosition','ContractController@closePosition')->middleware(['checkTradeStatus']); // 合约平仓
            $api->post('closeAllPosition','ContractController@closeAllPosition')->middleware(['checkTradeStatus']); // 市价全平
            $api->post('onekeyAllFlat','ContractController@onekeyAllFlat')->middleware(['checkTradeStatus']); // 一键全平
            $api->post('onekeyReverse','ContractController@onekeyReverse')->middleware(['checkTradeStatus']); // 一键反向
            $api->post('setStrategy','ContractController@setStrategy')->middleware(['checkTradeStatus']); // 设置止盈止损
            $api->post('cancelEntrust','ContractController@cancelEntrust');
            $api->post('batchCancelEntrust','ContractController@batchCancelEntrust');
            $api->get('getCurrentEntrust','ContractController@getCurrentEntrust');
            $api->get('getHistoryEntrust','ContractController@getHistoryEntrust');
            $api->get('getEntrustDealList','ContractController@getEntrustDealList');
            $api->get('getDealList','ContractController@getDealList');
            //        });
    });

    // 资金划转
    $api->get('wallet/accounts','UserWalletController@accounts')->middleware(['checkContractAccount']);
    $api->get('wallet/accountPairList','UserWalletController@accountPairList');
    $api->get('wallet/coinList','UserWalletController@coinList');
    $api->get('wallet/getBalance','UserWalletController@getBalance');
    $api->post('wallet/transfer','UserWalletController@transfer')->middleware(['checkContractAccount']);
    $api->get('wallet/transferRecords','UserWalletController@transferRecords');

});

// 闪兑功能
$api->group(['namespace' => 'V1'], function ($api) {
    // 获取币种列表
    $api->get('flashexchange/currency_list','FlashexchangeController@currency_list');
    $api->get('flashexchange/exchange_rate','FlashexchangeController@exchange_rate');  // 汇率
});

$api->group(['namespace' => 'V1','middleware'=>'auth.api'], function ($api) {
    // 获取币种余额
    $api->get('flashexchange/getBalance','FlashexchangeController@getBalance');
    $api->post('flashexchange/flicker','FlashexchangeController@flicker');   // 开始闪兑
    $api->post('flashexchange/flicker_list','FlashexchangeController@flicker_list');   // 开始闪兑
});

