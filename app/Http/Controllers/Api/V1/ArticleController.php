<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;

class ArticleController extends ApiController
{

    //文章列表
    public function article_list(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'type' => 'required|string',
        ])) return $res;

        $category_id = ArticleCategory::$typeMap[$request->type];
        if(blank($category_id)) return $this->error(4001,'参数错误');

        $articles = Article::query()->where(['category_id'=>$category_id])->where('status',1)->orderBy('order','desc')->orderByDesc('created_at')->paginate();
        $data = $articles->makeHidden('translations');
        $articles->data = $data;

        return $this->successWithData($articles);
    }

    //文章详情
    public function article_detail(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'id' => 'required|integer',
        ])) return $res;

        $article = Article::query()->findOrFail($request->id);
        $article = $article->makeHidden('translations');

        $article->increment('view_count');

        return $this->successWithData($article);
    }

    //服务详情
    public function service_detail(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'id' => 'required|integer',
        ])) return $res;

        $article = Article::query()->where("category_id",$request->id)->first();
        $article = $article->makeHidden('translations');

        $article->increment('view_count');

        return $this->successWithData($article);
    }

    private static function set_cate ($cate) {
        
        $tit = [
            '新手教程' => "The guidebook for beginners",
            '交易策略' => "Trading strategy",
            '行业研究' => "Industry research",
            '市场动态' => "Market dynamics"
        ];
        
        $content = [
            '如何购买比特币' => "How to buy bitcoin？",
            '什么是区块链？' => "What is Blockchain？",
            '什么是比特币？' => "What is Bitcoin?",
            '什么是KYC验证？如何完成KYC验证？' => "What is KYC validation? How do I complete KYC validation?",
            '零基础学习币市分析| 4 看跌K线组合' => "Zero based learning currency market analysis | 4 put K line combination",
            '零基础学币市分析|1 从价格到K线' => "Zero based learning currency market analysis | 1 K line from the price",
            '零基础学币市分析|3 看涨K线组合' => 'Zero based learning currency market analysis | 3 bullish K line combination',
            '零基础学习币市分析| 2 单根K线的演变及寓意' => 'Zero based learning c, analysis the evolution of the single root | 2 K line and implication',
            '比特币BTC减半套利策略：交易“减半”' => 'Bitcoin BTC Halve Arbitrage Strategy: Trading Halved',
            '区块链如何改变商业价值？唯有颠覆！' => 'How does blockchain change business value?  Only subversion!',
            '莱特币（LTC）现状调查研究' => 'Investigation on the status quo of Litecoin (LTC)',
            '简单易实操的币市绝对收益策略——中低频网格套利' => 'Simple and easy to operate absolute return strategy of currency market -- medium and low frequency grid arbitrage',
            '比特币看起来超买，但分析师淡化了对下跌的恐惧' => 'Bitcoin looks overbought, but analysts play down fears of a fall',
            '预期进一步的比特币收益将使期货合约清算蒙上阴影' => 'Further bitcoin gains are expected to cast a shadow over futures contract clearing',
            '市场裹足：比特币的价格和以太的主导地位坐在2020年的高点' => 'Market Wrap: Bitcoins price and Ethereums dominance sit at 2020 highs',
            '黄金达到历史新高，因为比特币突破$ 11k' => 'Gold hits an all-time high as Bitcoin breaks through $11k ',
        ];
        
        foreach ($cate as &$item) {
            
            if (array_key_exists($item['name'], $tit)) {
                
                $item['name'] = $tit[$item['name']];
            }
            
            // var_dump($item['article'])
            // for ($i = 0; $i < count($cate['article']); $i++) {
            //      // code...
            // }
            
            foreach ($item['article'] as &$bar) {
                
                if (array_key_exists($bar['title'], $content)) {
                    
                    $bar['title'] = $content[$bar['title']];
                }
            }
        }
        
        return $cate;
    }

    #获取当前分类下所有文章
    public function kindDown(){

       /* $all = ArticleCategory::query()->get();
        $all = $all->makeHidden("translations")->toArray();
        $mark = "";
        foreach ($all as $items ){
            if( $items["name"] == "学院" || $items["name"] == "College" || $items["name"] == "學院"){
                $mark = $items["id"];
            }
        }*/

        $category =  ArticleCategory::query()->where("id",5)->value("id");

        if( !$category ) return $this->successWithData($category);
        $categorys =   ArticleCategory::query()->where("pid",$category)->get();
        $categorys = $categorys->makeHidden("translations")->toArray();

        $k=0;
        $arr = array();
        foreach ( $categorys  as $key => $val){

            if( $val["id"] == "23" || $val["id"] == "24" || $val["id"] == "25" || $val["id"] == "26" ){

                $articles  =  Article::query()->where("category_id",$val["id"])->first();
                $articles = $articles->makeHidden("translations")->toArray();
                $arr[$k]["id"] = $articles["id"];
                $arr[$k]["name"] = $articles["title"];
                $arr[$k]["poster"] = $articles["cover"];
                $arr[$k]["url"]= $articles["excerpt"];
                $k++;
            }else{
                $cate[$key]["id"] = $val["id"];
                $cate[$key]["name"] = $val["name"];
                $article  =  Article::query()->where("category_id",$val["id"])->orderByDesc("order")->limit(4)->get();
                $cate[$key]["article"] = $article->makeHidden('translations')->toArray();
            }
        }
        $array["bannerList"] = $arr;
        $array["categoryList"] = self::set_cate($cate);

        return $this->successWithData($array);

    }

    #文章列表
    public function articleList(Request $request){

        if ($res = $this->verifyField($request->all(),[
            'id' => 'required|string',
        ])) return $res;

       // $categorys =   ArticleCategory::query()->where("pid",$request->id)->get();
        $article   =   Article::query()->where("category_id",$request->id)->orderByDesc("created_at")->get();
        if( !$article ) return $this->successWithData("","fail");

        $categorys = $article->makeHidden("translations")->toArray();

        return $this->successWithData($categorys);
    }


    #推荐文章
    public function recommend(){

        $article = Article::query()->where("is_recommend",1)->orderByDesc("created_at")->limit(4)->get();

        $article = $article->makeHidden("translations");
        return $this->successWithData($article);
    }


    public function notice(){
        $notice = Article::query()->where("category_id",4)->get();
        $notice = Article::query()->makeHidden("translations");
        return $this->successWithData($notice);
    }



}
