<?php

namespace App\Admin\Controllers;

use App\Admin\Filters\TimestampBetween;
use App\Models\UserLoginLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Repositories\OperationLog;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Dcat\Admin\Controllers\AdminController;

class UserLogController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new UserLoginLog(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            $grid->column('user_id', '用户ID');
            $grid->column('username', '登录用户名字');
            $grid->column('login_ip', '登录IP地址')->filterByValue();
            $grid->column('login_type', '登录类型');
            $grid->column('login_site', '登录地点');
            $grid->column('login_time', '登录时间')->display(function($v){
                return blank($v) ? '' : date('Y-m-d H:i:s',$v);
            });

            $grid->disableCreateButton();
            $grid->disableQuickEditButton();
            $grid->disableEditButton();
            $grid->disableViewButton();
            $grid->disableBatchDelete();
            $grid->disableActions();

            // xlsx
            $titles = ['id' => '序号', 'user_id' => '用户ID', 'username'=>'登录用户名字','login_ip'=>'登录IP地址','login_type' => '登录类型', 'login_site' => '登录地点','login_time'=>'登录时间'];
            $grid->export()->titles($titles)->rows(function (array $rows) use ($titles){
                foreach ($rows as $index => &$row) {
                    $row['login_time'] = date('Y-m-d H:i:s', $row['login_time']);
                }
                return $rows;
            })->xlsx();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id','UID')->width(3);
                $filter->where('username',function($q){
                    $q->where('username',$this->input);
                },"用户名")->width(3);
                $filter->equal('login_type','登录类型')->width(3);
                $filter->use(new TimestampBetween('login_time',"登陆时间"))->datetime()->width(4);
            });
        });
    }
}
