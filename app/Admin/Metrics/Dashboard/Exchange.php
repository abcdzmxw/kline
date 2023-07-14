<?php


namespace App\Admin\Metrics\Dashboard;


use App\Models\Coins;
use App\Models\InsideTradeOrder;
use Carbon\Carbon;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Metrics\Bar;
use Illuminate\Http\Request;

class Exchange extends Bar
{
    /**
     * 初始化卡片内容
     */
    protected function init()
    {
        parent::init();

        $color = Admin::color();

        $dark35 = $color->blue2();

        // 卡片内容宽度
        $this->contentWidth(5, 7);
        // 标题
        $this->title('币币交易');
        // 设置下拉选项
        $coin_options = Coins::query()->where('status',1)->orderBy('order','desc')->orderBy('coin_id','asc')->pluck('coin_name','coin_id')->toArray();
        $this->dropdown($coin_options);
        // 设置图表颜色
        $this->chartColors([
            $dark35,
            $dark35,
            $dark35,
            $dark35,
            $dark35,
            $color->success(),
        ]);
    }

    /**
     * 处理请求
     *
     * @param Request $request
     *
     * @return mixed|void
     */
    public function handle(Request $request)
    {
        $default_coin = Coins::query()->where('status',1)->orderBy('order','desc')->orderBy('coin_id','asc')->value('coin_id');
        $coin_id = $request->get('option',$default_coin);

        $today_date = Carbon::today()->format('Y-m-d');

        $builder1 = InsideTradeOrder::query()->where('base_coin_id',$coin_id);
        $builder2 = InsideTradeOrder::query()->where('quote_coin_id',$coin_id);

        $total_vol = $builder1->sum('trade_amount') + $builder2->sum('trade_money');
        $total_fee = $builder1->sum('trade_buy_fee') + $builder2->sum('trade_sell_fee');
        $today_vol = $builder1->whereDate('created_at',$today_date)->sum('trade_amount') + $builder2->whereDate('created_at',$today_date)->sum('trade_money');
        $today_fee = $builder1->whereDate('created_at',$today_date)->sum('trade_buy_fee') + $builder2->whereDate('created_at',$today_date)->sum('trade_sell_fee');

        // 卡片内容
        $this->withContent($total_vol,$total_fee,$today_vol,$today_fee, '+5.2%');

        // 图表数据
        $this->withChart([
            [
                'name' => 'Exchange',
                'data' => [
                    $builder1->whereDate('created_at',Carbon::today()->subDays(6)->format('Y-m-d'))->sum('trade_amount') + $builder2->whereDate('created_at',Carbon::today()->subDays(6)->format('Y-m-d'))->sum('trade_money'),
                    $builder1->whereDate('created_at',Carbon::today()->subDays(5)->format('Y-m-d'))->sum('trade_amount') + $builder2->whereDate('created_at',Carbon::today()->subDays(5)->format('Y-m-d'))->sum('trade_money'),
                    $builder1->whereDate('created_at',Carbon::today()->subDays(4)->format('Y-m-d'))->sum('trade_amount') + $builder2->whereDate('created_at',Carbon::today()->subDays(4)->format('Y-m-d'))->sum('trade_money'),
                    $builder1->whereDate('created_at',Carbon::today()->subDays(3)->format('Y-m-d'))->sum('trade_amount') + $builder2->whereDate('created_at',Carbon::today()->subDays(3)->format('Y-m-d'))->sum('trade_money'),
                    $builder1->whereDate('created_at',Carbon::today()->subDays(2)->format('Y-m-d'))->sum('trade_amount') + $builder2->whereDate('created_at',Carbon::today()->subDays(2)->format('Y-m-d'))->sum('trade_money'),
                    $builder1->whereDate('created_at',Carbon::today()->subDays(1)->format('Y-m-d'))->sum('trade_amount') + $builder2->whereDate('created_at',Carbon::today()->subDays(1)->format('Y-m-d'))->sum('trade_money'),
                    $builder1->whereDate('created_at',$today_date)->sum('trade_amount') + $builder2->whereDate('created_at',$today_date)->sum('trade_money'),
                ],
            ],
        ]);
    }

    /**
     * 设置图表数据.
     *
     * @param array $data
     *
     * @return $this
     */
    public function withChart(array $data)
    {
        return $this->chart([
            'series' => $data,
        ]);
    }

    /**
     * 设置卡片内容.
     *
     * @param string $title
     * @param string $value
     * @param string $style
     *
     * @return $this
     */
    public function withContent($total_vol, $total_fee, $today_vol,$today_fee, $style = 'success')
    {
        // 根据选项显示
        $label = strtolower(
            $this->dropdown[request()->option] ?? 'last 7 days'
        );

        $minHeight = '183px';

        return $this->content(
            <<<HTML
<div class="d-flex p-1 flex-column justify-content-between" style="padding-top: 0;width: 100%;height: 100%;min-height: {$minHeight}">
    <div class="text-left">
        <div class="col-12 d-flex flex-column flex-wrap text-center" style="max-width: 220px">

            <div class="chart-info d-flex justify-content-between mb-1" >
                  <div class="series-info d-flex align-items-center">
                      <i class="fa fa-circle-o text-bold-700 text-primary"></i>
                      <span class="text-bold-600 ml-50">总交易量</span>
                  </div>
                  <div class="product-result">
                      <span>{$total_vol}</span>
                  </div>
            </div>

            <div class="chart-info d-flex justify-content-between mb-1">
                  <div class="series-info d-flex align-items-center">
                      <i class="fa fa-circle-o text-bold-700 text-warning"></i>
                      <span class="text-bold-600 ml-50">总手续费</span>
                  </div>
                  <div class="product-result">
                      <span>{$total_fee}</span>
                  </div>
            </div>

            <div class="chart-info d-flex justify-content-between mb-1" >
                  <div class="series-info d-flex align-items-center">
                      <i class="fa fa-circle-o text-bold-700 text-primary"></i>
                      <span class="text-bold-600 ml-50">今日交易量</span>
                  </div>
                  <div class="product-result">
                      <span>{$today_vol}</span>
                  </div>
            </div>

            <div class="chart-info d-flex justify-content-between mb-1">
                  <div class="series-info d-flex align-items-center">
                      <i class="fa fa-circle-o text-bold-700 text-warning"></i>
                      <span class="text-bold-600 ml-50">今日手续费</span>
                  </div>
                  <div class="product-result">
                      <span>{$today_fee}</span>
                  </div>
            </div>

        </div>
    </div>

    <a href="/admin/inside-trade-order" class="btn btn-primary shadow waves-effect waves-light">View Details <i class="feather icon-chevrons-right"></i></a>
</div>
HTML
        );
    }
}
