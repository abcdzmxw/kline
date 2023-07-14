<?php

namespace App\Http\Controllers\Appapi\V1;

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
        $array["categoryList"] = $cate;

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
