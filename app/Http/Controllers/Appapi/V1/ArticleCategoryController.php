<?php


namespace App\Http\Controllers\Appapi\V1;


use App\Models\ArticleCategory;
use Dingo\Api\Http\Request;

class ArticleCategoryController extends ApiController
{
    #学院下的分类
    public function categoryList(){

        return $this->successWithData($this->lists());
    }

    #学院分类信息
    public function floor(){

       /* $category =  ArticleCategory::query()->where("id",5)->first();
        $category = $category->makeHidden("translations")->toArray();*/

        $cate = $this->lists();
       // $arr = array(/*"id"=>$category["id"],"name"=>$category["name"],*/"category"=>$cate);
        //return $arr;
        return $cate;
    }

    public function lists(){

       /* $all = ArticleCategory::query()->get();
        $all = $all->makeHidden("translations")->toArray();
        $mark = "";
        foreach ($all as $items ){
            if( $items["name"] == "学院" || $items["name"] == "College" || $items["name"] == "學院"){
                $mark = $items["id"];
            }
        }
        $category =  ArticleCategory::query()->where("id",5)->value("id");
        if( !$category ) return $this->successWithData($category);*/

        $categorys =   ArticleCategory::query()->where("pid",5)->get();
        $categorys = $categorys->makeHidden("translations")->toArray();

        $cate = array();
        $k = 0;
        foreach ( $categorys  as   $val){

            if( $val["id"] == "23" || $val["id"] == "24" || $val["id"] == "25" || $val["id"] == "26" ){
                continue;
            }else{
                $cate[$k]["id"] = $val["id"];
                $cate[$k]["name"] = $val["name"];
                $cate[$k]["url"] = $val["url"];
                $k++;
            }

        }
        if (empty($cate)) return false;
        return $cate;
    }

    public function kind(Request $request){
        if ($res = $this->verifyField($request->all(),[
            'cid' => 'required|string',
        ])) return $res;
        $kind = ArticleCategory::find($request->cid);
        if( $kind->pid == 0 )return $this->successWithData([]);
        $class = ArticleCategory::query()->where("pid",$kind->pid)->get();
        $class = $class->makeHidden("translations")->toArray();
        return $this->successWithData($class);

    }

    #服务类列表
    public function serviceList(){
        //$cate = ArticleCategory::query()->where("id",3)->value("pid");
        $category = ArticleCategory::query()->select("id"/*,"url"*/)->where("pid",3)->get();
        $category = $category->makeHidden("translations")->toArray();
        return $this->successWithData($category);
    }



}
