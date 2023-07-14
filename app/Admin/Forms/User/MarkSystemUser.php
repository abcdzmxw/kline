<?php

namespace App\Admin\Forms\User;

use App\Models\Coins;
use App\Models\User;
use App\Models\UserWallet;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MarkSystemUser extends Form
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
        $user = User::query()->find($user_id);
        if (! $user) return $this->error('记录不存在');

        $is_system = $input['is_system'] ?? 0;

        DB::beginTransaction();
        try{

            if($is_system == 1){
                $user->update(['is_system'=>1]);
            }else{
                $user->update(['is_system'=>0]);
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            info($e);
        }

        return $this->success('success');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->radio('is_system','系统账户')->options([0=>'否',1=>'是'])->default(0)->rules('required|in:0,1');

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
