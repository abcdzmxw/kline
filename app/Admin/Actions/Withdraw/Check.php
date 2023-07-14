<?php

namespace App\Admin\Actions\Withdraw;

use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Check extends RowAction
{
    /**
     * @return string
     */
	protected $title = '提币审核';

    public function render()
    {
        $id = "withdraw-{$this->getKey()}";

        // 模态窗
        $this->modal($id);

        return <<<HTML
<span class="grid-expand" data-toggle="modal" data-target="#{$id}">
   <a class="btn btn-sm btn-outline-primary" href="javascript:void(0)">审核</a>
</span>
HTML;
    }

    protected function modal($id)
    {
        // 工具表单
        $form = new \App\Admin\Forms\Withdraw\Check($this->getKey());

        // 在弹窗标题处显示当前行的用户名
        $username = $this->row->username;

        // 通过 Admin::html 方法设置模态窗HTML
        Admin::html(
            <<<HTML
<div class="modal fade" id="{$id}">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">提币审核 - {$username}</h4>
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        {$form->render()}
      </div>
    </div>
  </div>
</div>
HTML
        );
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
        // dump($this->getKey());

//        return $this->response()->success('Processed successfully.')->redirect('/');
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        // return ['Confirm?', 'contents'];
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
