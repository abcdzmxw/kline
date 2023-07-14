<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/23
 * Time: 16:54
 */

namespace App\Admin\Forms\Withdraw;

use App\Models\ListingApplication;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApplicationCheck extends Form
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
        // dump($input);

        // return $this->error('Your error message.');

//        return $this->success('Processed successfully.', '/');

        $id = $input['id'] ?? null;
        $status = $input['status'] ?? 0;
        $remark = $input['remark'] ?? '';

        if (! $id) {
            return $this->error('参数错误');
        }

        $item = ListingApplication::query()->find($id);
        if (! $item) {
            return $this->error('记录不存在');
        }

        DB::beginTransaction();
        try{
            // 更新记录
            $result=ListingApplication::query()->where(['id'=>$id])->update(['status' => $status , 'check_time' => time() ,'remark' => $remark]);
            if($result)
            {
                DB::commit();
            }

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
        $this->radio('status')->options(array_only(ListingApplication::$statusMap,[1,2]))->rules('required|in:1,2');
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