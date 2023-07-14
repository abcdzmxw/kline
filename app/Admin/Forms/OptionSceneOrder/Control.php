<?php

namespace App\Admin\Forms\OptionSceneOrder;

use App\Models\OptionSceneOrder;
use Dcat\Admin\Widgets\Form;
use Symfony\Component\HttpFoundation\Response;

class Control extends Form
{
    protected $end_price;

    protected $order_id;

    // 构造方法的参数必须设置默认值
    public function __construct($order_id = null)
    {
        $this->order_id = $order_id;

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
        $order_id = $input['order_id'] ?? null;
        if (! $order_id) {
            return $this->error('参数错误');
        }
        $end_price = $input['referrer'];
        $optionSceneOrder = new OptionSceneOrder();
        $optionSceneOrder->where('order_id', $order_id)->update([
          'end_price' => $end_price,
        ]);
        return $this->success('Processed successfully.');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->text('referrer','收盘价格')->rules('required');

        // 设置隐藏表单，传递用户id
        $this->hidden('order_id')->value($this->order_id);
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
