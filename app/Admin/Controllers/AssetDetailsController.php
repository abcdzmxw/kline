<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/29
 * Time: 19:36
 */

namespace App\Admin\Controllers;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\TransferRecord;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class AssetDetailsController extends  AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(TransferRecord::with(['user']), function (Grid $grid) {
            // xlsx
            $titles = ['id' => 'ID', 'user_id'=>'UID','username'=>'用户名','coin_name' => '币名','direction'=>'方向', 'amount' => '金额','datetime'=>'时间','status'=>'状态'];
            $grid->export()->titles($titles)->rows(function (array $rows) use ($titles){
                foreach ($rows as $index => &$row) {
                    $row['username'] = $row['user']['username'];
                    $row['datetime'] = date('Y-m-d H:i:s', $row['datetime']);
                    $row['direction'] = (TransferRecord::$accountMap[$row['draw_out_direction']] ?? '--') . ' -> ' . (TransferRecord::$accountMap[$row['into_direction']] ?? '--');
                    $row['status'] = TransferRecord::$statusMap[$row['status']];
                }
                return $rows;
            })->xlsx();

            $grid->model()->orderByDesc('id');
            // 这里的字段会自动使用翻译文件
            $grid->id;
            $grid->user_id;
            $grid->column('user.username','用户名');
            $grid->coin_name;
            $grid->column('direction','方向')->display(function (){
                return (TransferRecord::$accountMap[$this->draw_out_direction] ?? '--') . ' -> ' . (TransferRecord::$accountMap[$this->into_direction] ?? '--');
            });
            $grid->amount;
            $grid->column('datetime','时间')->display(function ($datetime) {
                return date('Y-m-d H:i:s', $datetime);
            });
            $grid->status->using(TransferRecord::$statusMap)->dot([1=>'success',2=>'error']);

            $grid->disableActions();
            $grid->disableCreateButton();
//            $grid->disableRowSelector();

            $grid->filter(function(Grid\Filter $filter){
                $filter->whereBetween('datetime',function ($q){
                    $start = $this->input['start'] ? strtotime($this->input['start']) : null;
                    $end = $this->input['end'] ? strtotime($this->input['end']) : null;
                    $q->whereBetween('datetime',[$start,$end]);
                })->datetime()->width(4);
                $filter->equal('user_id', '会员ID')->width(3);
                $filter->like('coin_name', '币种名字')->width(3);

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
        return Show::make($id, new TransferRecord(), function (Show $show) {
            // 这里的字段会自动使用翻译文件
            $show->id;
            $show->coin_name;
            $show->draw_out_direction;
            $show->into_direction;
            $show->amount;
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
        return Form::make(new TransferRecord(), function (Form $form) {
            // 这里的字段会自动使用翻译文件
            $form->display('id');
            $form->text('coin_name');
            $form->text('draw_out_direction');
            $form->text('into_direction');
            $form->text('amount');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
