<?php


namespace App\Http\Controllers\Api\V1;
use App\Admin\Forms\Setting;
use App\Models\Admin\AdminSetting;
use App\Models\Advice;
use App\Models\AdviceCategory;
use App\Models\AdvicesCategory;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Banner;
use App\Models\BlackList;
use App\Models\Coins;
use App\Models\Collect;
use App\Models\ContactInfo;
use App\Models\InsideTradePair;
use App\Models\Navigation;
use App\Services\UserService;
use GatewayWorker\Lib\Db;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\App;


class IndexController extends ApiController
{
    protected  $user;
    public function __construct(UserService $userService)
    {
        $this->user = $userService;
    }

    #首页数据
    public function indexList(){

        $banner = Banner::query()->where(["status"=>1,"location_type"=>1])->limit(4)->get();
        $banner = $banner->makeHidden("translations")->map(function ($item, $key) {
            if (strpos($item['imgurl'],'http') === false){
                $item['imgurl'] = getFullPath($item['imgurl']);
            }
            return $item;
        })->toArray();

        $banner2 = Banner::query()->where(["status"=>1,"location_type"=>2])->limit(4)->get();
        $banner2 = $banner2->makeHidden("translations")->map(function ($item, $key) {
            if (strpos($item['imgurl'],'http') === false){
                $item['imgurl'] = getFullPath($item['imgurl']);
            }
            return $item;
        })->toArray();

        $icon = Article::query()->where(["status"=>1,"category_id"=>20])->limit(5)->get();
        $icon = $icon->makeHidden("translations")->toArray();

        $market = [];
        $brokenLine = [];

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
                $cache_data = Cache::store('redis')->get('market:' . $item['symbol'] . '_detail');
                $market[$kk]['marketInfoList'][$key2] = $cache_data;
                $market[$kk]['marketInfoList'][$key2]['coin_name'] = $item['base_coin_name'];
                $market[$kk]['marketInfoList'][$key2]['coin_icon'] = Coins::icon($item['base_coin_name']);
                $market[$kk]['marketInfoList'][$key2]["pair_name"] = $item['pair_name'];
                $market[$kk]['marketInfoList'][$key2]["pair_id"] = $item['pair_id'];
                if(in_array($item['pair_name'],$collect)){
                    $market[$kk]['marketInfoList'][$key2]["is_collect"] = 1;
                }else{
                    $market[$kk]['marketInfoList'][$key2]["is_collect"] = 0;
                }

                if($item['pair_name'] == 'BTC/USDT'){
                    $brokenLine[0] = $market[$kk]['marketInfoList'][$key2];
                }
                if($item['pair_name'] == 'ETH/USDT'){
                    $brokenLine[1] = $market[$kk]['marketInfoList'][$key2];
                }
                if($item['pair_name'] == 'LTC/USDT'){
                    $brokenLine[2] = $market[$kk]['marketInfoList'][$key2];
                }

                $market[$kk]['marketInfoList'][$key2]["marketInfoList"] = $item['base_coin_name'];
            }
            $kk++;
        }

        $k = 0;
        $symbols = [];
        foreach ($market as $key=> $items) {

            foreach ($items["marketInfoList"] as  $coin) {

                $mark = strtolower($coin["marketInfoList"]).strtolower($items["coin_name"]);

                // 取实时的交易价格
                $symbol_name = 'market:' . $mark . '_newPrice';
                $data = Cache::store('redis')->get($symbol_name);

                $symbols[$k]['pair'] = $coin["marketInfoList"]."/".$items["coin_name"];
                $symbols[$k]["price"] = $data["price"];

                $symbols[$k]['increase'] = (float)$data["increase"];
                $symbols[$k]['increaseStr'] = $data["increaseStr"];
                $k++;
            }

        }

        $arr["iconList"] = $icon;
        $arr["homeList"] = $symbols;
        #市场动态(最新公告)
        $category_id = ArticleCategory::$typeMap["marketAction"];
        $bazaar = Article::query()->where(array("category_id"=>$category_id))->orderByDesc("created_at")->limit(1)->get();
        $arr["articleList"] = $bazaar->makeHidden("translations")->toArray();

        $category_id2 = ArticleCategory::$typeMap["notice"];
        $bazaar2 = Article::query()->where(array("category_id"=>$category_id2))->orderByDesc("created_at")->limit(1)->get();
        $arr["noticeList"] = $bazaar2->makeHidden("translations")->toArray();

        $arr["marketList"] = $market;
        $arr["bannerList"] = $banner;
        $arr["pcBannerList"] = $banner2;
        $arr["brokenLine"] = $brokenLine;
        return $this->successWithData($arr);

    }


    #首页数据
    public function indexList2(){
        
        $banner = Banner::query()->where(["status"=>1,"location_type"=>1])->limit(4)->get();
        $banner = $banner->makeHidden("translations")->map(function ($item, $key) {
            if (strpos($item['imgurl'],'http') === false){
                $item['imgurl'] = getFullPath($item['imgurl']);
            }
            return $item;
        })->toArray();

        $banner2 = Banner::query()->where(["status"=>1,"location_type"=>2])->limit(4)->get();
        $banner2 = $banner2->makeHidden("translations")->map(function ($item, $key) {
            if (strpos($item['imgurl'],'http') === false){
                $item['imgurl'] = getFullPath($item['imgurl']);
            }
            return $item;
        })->toArray();

        $icon = Article::query()->where(["status"=>1,"category_id"=>20])->limit(5)->get();
        $icon = $icon->makeHidden("translations")->toArray();

        $market = [];
        $brokenLine = [];

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
                $cache_data = Cache::store('redis')->get('market:' . $item['symbol'] . '_detail');
                
                $market[$kk]['marketInfoList'][$key2] = $cache_data;
                $market[$kk]['marketInfoList'][$key2]['coin_name'] = $item['base_coin_name'];
                $market[$kk]['marketInfoList'][$key2]['coin_icon'] = Coins::icon($item['base_coin_name']);
                $market[$kk]['marketInfoList'][$key2]["pair_name"] = $item['pair_name'];
                $market[$kk]['marketInfoList'][$key2]["pair_id"] = $item['pair_id'];
                if(in_array($item['pair_name'],$collect)){
                    $market[$kk]['marketInfoList'][$key2]["is_collect"] = 1;
                }else{
                    $market[$kk]['marketInfoList'][$key2]["is_collect"] = 0;
                }

                if($item['pair_name'] == 'BTC/USDT'){
                    $brokenLine[0] = $market[$kk]['marketInfoList'][$key2];
                }
                if($item['pair_name'] == 'ETH/USDT'){
                    $brokenLine[1] = $market[$kk]['marketInfoList'][$key2];
                }
                if($item['pair_name'] == 'LTC/USDT'){
                    $brokenLine[2] = $market[$kk]['marketInfoList'][$key2];
                }

                $market[$kk]['marketInfoList'][$key2]["marketInfoList"] = $item['base_coin_name'];
            }
            $kk++;
        }

        $k = 0;
        $symbols = [];
        foreach ($market as $key=> $items) {

            foreach ($items["marketInfoList"] as  $coin) {

                $mark = strtolower($coin["marketInfoList"]).strtolower($items["coin_name"]);

                // 取实时的交易价格
                $symbol_name = 'market:' . $mark . '_newPrice';
                $data = Cache::store('redis')->get($symbol_name);

                $symbols[$k]['pair'] = $coin["marketInfoList"]."/".$items["coin_name"];
                $symbols[$k]["price"] = $data["price"];

                $symbols[$k]['increase'] = (float)$data["increase"];
                $symbols[$k]['increaseStr'] = $data["increaseStr"];
                $k++;
            }

        }

        $arr["iconList"] = $icon;
        $arr["homeList"] = $symbols;
        #市场动态(最新公告)
        $category_id = ArticleCategory::$typeMap["marketAction"];
        $bazaar = Article::query()->where(array("category_id"=>$category_id))->orderByDesc("created_at")->limit(1)->get();
        $arr["articleList"] = $bazaar->makeHidden("translations")->toArray();

        $arr["marketList"] = $market;
        $arr["bannerList"] = $banner;
        $arr["pcBannerList"] = $banner2;
        $arr["brokenLine"] = $brokenLine;
        return $this->successWithData($arr);

    }




    #黑名单
    public function blackList(){

        $ip = get_client_ip();

        $name = $this->get_info($ip);
        $black = BlackList::query()->where("nation_name",$name)->first();
        if( $black ){
            return $this->successWithData(true);
        }
        return  $this->responseJson("400","fail",false);
    }

    #添加取消自选交易对
    public function collect(Request $request){
        $user = $this->current_user();
        if( empty($user)) return $this->error("400","当前用户未登陆");

        if ( $res = $this->verifyField($request->all(),[
            /*'pair_id'=>'required|integer',*/
            'pair_name'=>'required'
        ])) return $res;

        $data = $request->all();
        $pair_name = $data["pair_name"];
        $data["user_id"] = $user->user_id;

        $where = array("user_id"=>$data["user_id"],"pair_name"=>$pair_name);

        $result = Collect::query()->where($where)->first();

        if( $result ){
            Collect::query()->where($where)->delete();
            return $this->responseJson(200,"cancelSuccess",false);
        }

        $data["created_at"] = time();
        Collect::create($data);
        return $this->responseJson(200,"addSuccess",true);
    }

    #获取自选交易对
    public function getCollect(Request $request){

        $user = $this->current_user();
        if( empty($user)) return $this->error(400,"当前用户未登陆");

        $result = Collect::query()->where(array("user_id"=>$user->user_id))->pluck('pair_name')->toArray();

        if( !$result ){
            return  $this->responseJson(200,"success",[]);
        }

        foreach ( $result as $itmes ){

            $symbol = strtolower(str_before($itmes,'/') . str_after($itmes,'/'));
            $quote_coin_name = strtolower(str_after($itmes,'/'));
            $cache_data =  Cache::store('redis')->get('market:' . $symbol . '_detail');
            $cache_data['pair_name'] = $itmes;
            $data[] = $cache_data;
        }

        return  $this->responseJson(200,"success",$data);
    }

    #帮助中心分类参数
    public function cataLog(){

        $article = ArticleCategory::query()->select("id")->find([1,2,3]);
        $article = $article->makeHidden("translations")->toArray();
        return $this->responseJson("200","successs",$article);
    }

    #联系我们详情信息
    public function relevance(){
        $info = ContactInfo::query()->select("url")->get();
        if(blank($info)) return $this->successWithData([],"fail");
        $advice = $info->makeHidden("translations");
        $k = 0;
        $arr= array();
        foreach ( $advice as $val ){
            if( $k == 0 ){
                 $arr["contact"] = $val["url"];
            }elseif( $k == 1 ){
                $arr["email"] = $val["url"];
            }elseif( $k == 2 ){
                $arr["service"] = $val["url"];
            }else{
                $arr["media"] = $val["url"];
            }
            $k++;
        }
        return $this->successWithData($arr);

    }

    #首页系统公告
    public function sysNotice(){
        $noticle = Article::query()->where("category_id",4)->limit(5)->get();//1：未读 0：已读
        $noticle = $noticle->makeHidden("translations")->toArray();
        return $this->successWithData($noticle);
    }

    #联系我们
    public function contactUs(Request $request){

        if ($res = $this->verifyField($request->all(),[
            'realname'=> 'required|string',
            'email'   => 'required|string',
            'contents'=> 'required|string',
            'category_id'=>'required|string'
        ]));

        $res = Advice::create($request->all());
        if( $res ) return $this->successWithData("success","提交成功");
        return $this->successWithData("fail","参数错误");
    }


    public function logo()
    {
        $arr = array();
        $setting = AdminSetting::query()->where('module','website')->get()->toArray();
        if( blank($setting)) return $this->successWithData($arr);
        foreach ($setting as $value){
            if($value['type'] == 'image'){
                $arr[$value["key"]] = getFullPath($value["value"]);
            }else{
                $arr[$value["key"]] = $value["value"];
            }
        }
        return $this->successWithData($arr);
    }

    #获取服务信息
    public function services_copy(){

        $res = ArticleCategory::query()
            ->where("pid",3)
            ->select("id")
            ->get();
        $res = $res->makeHidden("translations")->toArray();

        // var_dump($res);
        return $this->successWithData($res);
    }
    
    public function services(){

        $res = ArticleCategory::query()
            ->where("pid",3)
            ->select("id")
            ->get();
        $res = $res->makeHidden("translations")->toArray();
        // 没用显示英文
        if (isset($res[0]['name']) == null) {
            
            App::setlocale('en');
            $res = ArticleCategory::query()
                ->where("pid",3)
                ->select("id")
                ->get();
                
            $res = $res->makeHidden("translations")->toArray();
        }
        return $this->successWithData($res);
    }
    

    #版权信息
    public function copyright(){

        $classID = ArticleCategory::query()->where("id","31")->value("id");
        $article = Article::query()->where("category_id",$classID)->first();
        $article =  $article->makeHidden("translations")->toArray();
        return $article;
    }

    #市场动态×
    public function marketdynamic_copy(Request $request){
        if ( $res = $this->verifyField($request->all(),[
            'limit'=>'required|string'
        ])) return $res;

        $move = ArticleCategory::query()->where("id",32)->value("id");
        $trends = Article::query()->where('category_id',$move)->orderByDesc("created_at")->limit($request->limit)->get();
        $trends = $trends->makeHidden("translations")->toArray();
       return $this->successWithData($trends);
    }

    #市场动态×
    public function marketdynamic(Request $request){
        if ( $res = $this->verifyField($request->all(),[
            'limit'=>'required|string'
        ])) return $res;

        $move = ArticleCategory::query()->where("id",32)->value("id");
        $trends = Article::query()->where('category_id',$move)->where('status','1')->orderByDesc("created_at")->limit($request->limit)->get();
        $trends = $trends->makeHidden("translations")->toArray();
       return $this->successWithData($trends);
    }

    #获取底部信息
    public function bottom(){
        #服务
        $service = Navigation::query()->where(array("type"=>2,"status"=>1))->orderByDesc("created_at")->limit(5)->get();
        if( blank($service) ) {
            $index["serviceList"] =[];
        }else{
            $index["serviceList"] = $service->makeHidden("translations")->toArray();
        }

        $college = Navigation::query()->where(array("type"=>3,"status"=>1))->orderByDesc("created_at")->limit(5)->get();
        if( blank($college) ) {
            $index["collegeList"] =[];
        }else{
            $index["collegeList"] = $college->makeHidden("translations")->toArray();
        }

        #联系我们
        $rele = $this->relevance();
        if( !isset($rele->original)){
            $index["contact"] = "";
        }else{
            $index["contact"] = $rele->original["data"];
        }

        #版权信息
        $index["copyright"] = AdminSetting::query()->where(array("module"=>'website',"key"=>"copyright"))->first()->toArray();

        return $this->successWithData($index);

    }

    #获取顶部数据
    public function up(){
        $up = Navigation::query()->where(array("type"=>1,"status"=>1))->limit(10)->get();

        if( blank($up->toArray()) ) return $this->successWithData([]);
        $up = $up->makeHidden("translations")->toArray();
        return $this->successWithData($up);
    }

    #联系我们类
    public function advices(){
        $adv = AdvicesCategory::query()->where(array("status"=>1))->select("id")->orderBy("order")->get();
        if(blank($adv)) return $this->successWithData([],"fail");
        $advice = $adv->makeHidden("translations");

        return $this->successWithData($advice);
    }

    public static function get_info($ip)
    {
        $url = "http://whois.pconline.com.cn/jsFunction.jsp?callback=jsShow&ip=" . $ip;

        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        $info = iconv('GB2312', 'UTF-8', $output); //因为是js调用 所以接收到的信息为字符串，注意编码格式
        return  self::substr11($info);  //ArrayHelper是助手函数 可以将下面的方法追加到上面
    }

    public static function substr11($str)
    {
        preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $str, $regs);//preg_match_all（“正则表达式”,"截取的字符串","成功之后返回的结果集（是数组）"）
        $s = join('', $regs[0]);//join("可选。规定数组元素之间放置的内容。默认是 ""（空字符串）。","要组合为字符串的数组。")把数组元素组合为一个字符串
        $s = mb_substr($s, 0, 80, 'utf-8');//mb_substr用于字符串截取，可以防止中文乱码的情况
        return $s;
    }




}
