<?php

namespace App\Admin\Forms\Agent;

use App\Models\Agent;
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

        if (! $id) {
            return $this->error('参数错误');
        }

        $item = Agent::query()->find($id);
        if (! $item) {
            return $this->error('记录不存在');
        }

        DB::beginTransaction();
        try{
            // 更新记录
            $update_data = ['status' => $status];
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
        $this->radio('status')->options(array_only(Agent::$userStatusMap,[0,1]))->rules('required');

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
