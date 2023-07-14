<?php

namespace App\Admin\Controllers;

use App\Models\Banner;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class BannerController extends AdminController
{
    /**
     * 轮播图和图标管理
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Banner(), function (Grid $grid) {
//            $grid->model()->where("location_type",1);
            $grid->id->sortable();
            $grid->imgurl->image('',50,50);
            $grid->location_type->using(Banner::$locationTypeMap)->label();
            $grid->tourl;
            $grid->tourl_type->using(Banner::$tourlTypeMap)->label();
            $grid->status->switch();
            $grid->order;
            $grid->created_at->sortable();
//            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Banner(), function (Show $show) {
            $show->id;
            $show->imgurl;
            $show->location_type;
            $show->tourl;
            $show->tourl_type;
            $show->status;
            $show->order;
            $show->created_at;
            $show->updated_at;
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Banner(), function (Form $form) {
            $form->display('id');
            $form->select('location_type')->options(Banner::$locationTypeMap)->default(1);
            $form->select('tourl_type')->options(Banner::$tourlTypeMap)->default(0);
            $form->switch('status')->default(1);
            $form->number('order')->default(1);
            $form->text('tourl')->default('#');
            $form->hasMany('translations','图片', function (Form\NestedForm $form){
                
                $lang = [
                    'id' => '印尼文',
                    'zh-CN'=>'中',
                    'vie' => '越南文',
                    'en'=>'英',
                    "zh-TW"=>"繁体",
                    'kor' => '韩文',
                    'jp' => '日文',
                    'de' => '德文',
                    'it' => '意大利文',
                    // 'nl' => "荷兰文",
                    'pl' => '波兰文',
                    'pt' => '葡萄牙文',
                    'spa' => '西班牙文',
                    'swe' => '瑞典文',
                     'tr'=>'土耳其文',
                    'uk' => '乌克兰文',
                    'fin' => '芬兰文',
                    'fra'  => '法国文',
                ];
                
                $form->select('locale')->options($lang)->default('id')->label();
                $form->image('imgurl')->uniqueName()->autoUpload();
            });

//            $form->saving(function(Form $form){
//                if( blank($form->location_type)) return $form->error("请选择显示的位置");
//            });


        });
    }
}
