<?php

namespace App\Admin\Forms\Agent;

use App\Models\User;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AgentPertain extends Form
{
    // 增加一个自定义属性保存ID
    protected $user_id;

    // 构造方法的参数必须设置默认值
    public function __construct($user_id = null)
    {
        $this->user_id = $user_id;

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
        $user_id = $input['user_id'] ?? null;
        if (! $user_id) {
            return $this->error('参数错误');
        }
        $user = User::query()->where(['user_id'=>$user_id,'is_agency'=>1])->first();
        if (blank($user)) return $this->error('非代理');
        $referrer = $input['referrer'];
        $agent = User::query()->where(['user_id'=>$referrer,'is_agency'=>1])->first();
        if (blank($agent)) return $this->error('代理不存在');

        if ($user['deep'] - $agent['deep'] != 1) return $this->error('代理级别有误');

        DB::beginTransaction();
        try{

            // 更新用户
            $user->update([
                'pid' => $referrer,
                'referrer' => $referrer,
            ]);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->success('Processed successfully.');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->text('referrer','上级代理ID')->rules('required');

        // 设置隐藏表单，传递用户id
        $this->hidden('user_id')->value($this->user_id);
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
