<?php

$api->group(['namespace' => 'V1'], function ($api) {

    $api->get("indexList","IndexController@indexList");//首页数据
    $api->get("blackList","IndexController@blackList");//黑名单

    $api->post("option","IndexController@collect");//添加自选
    $api->get("getCollect","IndexController@getCollect")->middleware(['auth.api']);//获取自选
    $api->get("cataLog","IndexController@cataLog");//首页博`客数据
    $api->get("college","ArticleController@kindDown");//获取文章分类下所有文章
    $api->get("contact","IndexController@relevance");//联系我们信息
    $api->get("notice","IndexController@sysNotice");//系统公告
    $api->get("help","IndexController@dealStrat");//截取转义的文章内容
    $api->get("services","IndexController@services");//获取首页底部参数
    $api->get("marketDynamics","IndexController@marketdynamic");//市场动态
    $api->post("contactUs","IndexController@contactUs");//联系我们表单
    $api->get("downMarket","MarketController@index");//行情页3个行情展示
    $api->get("articleList","ArticleController@articleList");//分类下所有文章
    $api->get("categoryList","ArticleCategoryController@categoryList");//学院下的分类
    $api->get("recommend","ArticleController@recommend");//是否推荐
    $api->get("category/kind","ArticleCategoryController@kind");//分类列表(平级)
    $api->get("category/serviceList","ArticleCategoryController@serviceList");//服务类列表(平级)
    $api->get("article/serviceDetail","ArticleController@service_detail");//服务类文章
    $api->get("about/advices","IndexController@advices");//联系我们咨询项目
    $api->get("index/logo","IndexController@logo");//logo
    $api->get("index/bottom","IndexController@bottom");//底部
    $api->get("index/navigation","IndexController@up");//导航栏

    $api->get('/test', function () {
        // ASCII
        return json_decode('{"a": "\u8d44"}', true);
        // var_dump(unicodeDecode('\u8d44\u4ea7\u589e\u52a0'));
        // return baiduTransAPI('{"title":"ETH\u8d44\u4ea7\u589e\u52a0"}', 'zh', 'en');
    });
});


















