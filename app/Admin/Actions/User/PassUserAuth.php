<?php

namespace App\Admin\Actions\User;

use App\Models\User;
use App\Models\UserAuth;
use Carbon\Carbon;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PassUserAuth extends BatchAction
{
    protected $style = 'btn btn-sm btn-default';

    /**
     * @return string
     */
	protected $title = '审核通过';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $keys = $this->getKey();
        $status = 1;

        $auths = UserAuth::query()->find($keys);

        try{
            DB::beginTransaction();

            foreach ($auths as $auth) {
                if($auth->status != UserAuth::STATUS_WAIT){
                    return $this->response()->error('Processed fail.')->refresh();
                }

                $auth->status = $status == 1 ? UserAuth::STATUS_AUTH : UserAuth::STATUS_REJECT;
                $auth->check_time = Carbon::now()->toDateTimeString();
                $auth->save();

                if($status == 1){
                    $user = $auth->user;
                    $user->update(['user_auth_level'=>User::user_auth_level_top]);
                }
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->response()->success('Processed successfully.')->refresh();
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        // return ['Confirm?', 'contents'];
        return ['确定认证' . $this->title . '?'];
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

    public function actionScript(){
        $warning = "请选择审核的内容！";

        return <<<JS
function (data, target, action) {
    console.log('发起请求之前', {data, target, action});
    var key = {$this->getSelectedKeysScript()}

    if (key.length === 0) {
        Dcat.warning('{$warning}');
        return false;
    }

    // 设置主键为复选框选中的行ID数组
    action.options.key = key;
}
JS;
    }
    protected function html()
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()}><button class="btn btn-primary btn-mini">{$this->title()}</button></a>
HTML;
    }
}
