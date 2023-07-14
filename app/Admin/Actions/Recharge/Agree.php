<?php
/*
 * @Author: your name
 * @Date: 2021-06-02 15:08:59
 * @LastEditTime: 2021-06-04 22:19:29
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \Dcat\app\Admin\Actions\User\Recharge.php
 */

namespace App\Admin\Actions\Recharge;

use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Table;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\RechargeManual;

class Agree extends RowAction
{
    /**
     * 按钮标题
     * @return string
     */
    protected $title = '充值';

    /**
     * @var string
     */
    protected $modalId = 'show-current-user';

    /**
     * Handle the action request.
     * 处理当前动作的请求接口，如果不需要请直接删除
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $id = $this->getKey();

        $this->model = new RechargeManual;
        if (!$this->model->agree($id)) {
            return $this->response()
                ->error('充值失败');
        }
        return $this->response()
            ->success('充值成功')
            ->refresh();
    }

    /**
     * 处理响应的HTML字符串，附加到弹窗节点中
     * 
     * @return string
     */
    protected function handleHtmlResponse()
    {
        return <<<'JS'
function (target, html, data) {
    var $modal = $(target.data('target')); 

    $modal.find('.modal-body').html(html)
    $modal.modal('show')
} 
JS;
    }

    /**
     * 设置HTML标签的属性
     * 
     * @return void
     */
    protected function setUpHtmlAttributes()
    {
        // 添加class
        $this->addHtmlClass('btn btn-sm btn-outline-primary nowrap');

        // 保留弹窗的ID
        // $this->setHtmlAttribute('data-target', '#' . $this->modalId);
        // 设置style样式
        $this->setHtmlAttribute('style', 'white-space:nowrap');
        if ($this->row->status !== 0) {
            $this->addHtmlClass('disabled');
        }

        parent::setUpHtmlAttributes();
    }


    /**
     * 设置按钮的HTML，这里我们需要附加上弹窗的HTML
     *
     * @return string|void
     */
    public function html()
    {
        // 按钮的html
        $html = parent::html();

        return <<<HTML
{$html}        
<div class="modal fade" id="{$this->modalId}" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">{$this->title()}</h4>
      </div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>
HTML;
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['确定充值?', '确定给该用户充值吗？'];
    }

    /**
     * 动作权限判断，返回false则标识无权限，如果不需要可以删除此方法
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }
}
