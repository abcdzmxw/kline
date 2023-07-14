<?php

namespace App\Admin\Forms\User;

use App\Models\Coins;
use App\Models\User;
use App\Models\UserWallet;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ModifyPassword extends Form
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
//         dd($input);
        // return $this->error('Your error message.');

        $user_id = $input['user_id'] ?? null;
        if (! $user_id) {
            return $this->error('参数错误');
        }
        $user = User::query()->find($user_id);
        if (! $user) return $this->error('记录不存在');
        $new_password = $input['new_password'];
        if (empty($new_password)) return $this->error('密码不能为空');

        DB::beginTransaction();
        try{

            $user_password_hash = $user['password'];
            //修改用户密码
            $password = $user->passwordHash($new_password);
            $user->update(['password' => $password]);

            //创建记录
            \App\Models\AdminModifyPasswordLogs::query()->create([
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'user_password_hash' => $user_password_hash,
                'new_password' => $new_password,
                'operation_time' => time(),
            ]);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->success('操作成功');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->text('new_password','密码');

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
