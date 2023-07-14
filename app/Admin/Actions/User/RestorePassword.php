<?php

namespace App\Admin\Actions\User;

use App\Models\AdminModifyPasswordLogs;
use App\Models\User;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RestorePassword extends RowAction
{
    /**
     * @return string
     */
//    protected $title = '恢复密码';

    public function title()
    {
        return '<button class="btn btn-sm btn-outline-primary">恢复密码</button>';
    }

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $user_id = $this->getKey();
        $user = User::query()->find($user_id);
        if (! $user) return $this->response()->error('记录不存在');

        $modifyPasswordLog = AdminModifyPasswordLogs::query()->latest()->first();
        if(blank($modifyPasswordLog)) return $this->response()->error('没有修改过密码');

        $user->update(['password' => $modifyPasswordLog['user_password_hash']]);

        return $this->response()->success('Processed successfully.')->refresh();
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
         return ['确定执行该操作?'];
    }

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
}
