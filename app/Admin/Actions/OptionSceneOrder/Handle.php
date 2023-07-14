<?php

namespace App\Admin\Actions\OptionSceneOrder;

use App\Models\OptionSceneOrder;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Handle extends BatchAction
{
    /**
     * @return string
     */
	protected $title = '结算异常订单';

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

        $orders = OptionSceneOrder::query()->find($keys);

        try{
            DB::beginTransaction();

            foreach ($orders as $order) {
                if($order->status != OptionSceneOrder::status_delivered){
                    return $this->response()->error('Processed fail.')->refresh();
                }


            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->response()->success('Processed successfully.')->redirect('/');
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        // return ['Confirm?', 'contents'];
        return ['确定' . $this->title . '?'];
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
        $warning = "请选择内容！";

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
