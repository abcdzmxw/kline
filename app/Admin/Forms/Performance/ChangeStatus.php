<?php

namespace App\Admin\Forms\Performance;

use App\Models\Performance;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ChangeStatus extends Form
{
    // 增加一个自定义属性保存ID
    protected $id;

    // 构造方法的参数必须设置默认值
    public function __construct($id = null)
    {
        $this->id = $id;

        parent::__construct();
    }

    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return Response
     */
    public function handle(array $input)
    {
        $id = $input['id'] ?? null;
        $status = $input['status'] ?? 0;
        $remark = $input['remark'] ?? '';

        if (! $id) {
            return $this->error('参数错误');
        }

        $item = Performance::query()->find($id);
        if (! $item) {
            return $this->error('记录不存在');
        }

        // 非待审核状态 不可撤销
//        if($item['status'] != Performance::status_wait_settle){
//            return $this->error('非待审核状态,不可审核');
//        }

        DB::beginTransaction();
        try{
            // 更新记录
            $update_data = ['status' => $status ,'remark' => $remark];
            $item->update($update_data);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->success('审核成功');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->radio('status')->options(array_only(Performance::$statusMap,[1,2]))->rules('required');
        $this->textarea('remark','备注');

        // 设置隐藏表单，传递用户id
        $this->hidden('id')->value($this->id);
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [];
    }
}
