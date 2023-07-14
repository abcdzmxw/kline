<?php


namespace App\Admin\Metrics\Dashboard;


use App\Models\OptionBetCoin;
use App\Models\OptionSceneOrder;
use Carbon\Carbon;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Metrics\Bar;
use Illuminate\Http\Request;

class Option extends Bar
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
        $this->title('期权交易');
        // 设置下拉选项
        $coin_options = OptionBetCoin::query()->where('is_bet',1)->orderBy('sort','desc')->orderBy('coin_id','asc')->pluck('coin_name','coin_id')->toArray();
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
        $default_coin = OptionBetCoin::query()->where('is_bet',1)->orderBy('sort','desc')->orderBy('coin_id','asc')->value('coin_id');
        $coin_id = $request->get('option',$default_coin);

        $today_date = Carbon::today()->format('Y-m-d');

        $value1 = OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->sum('bet_amount'); // 用户总下单量
        $value2 = OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->sum('delivery_amount'); // 交割结果统计
        $value3 = OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->sum('fee'); // 总手续费
        $value4 = OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',$today_date)->sum('bet_amount'); // 用户总下单量
        $value5 = OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',$today_date)->sum('delivery_amount'); // 交割结果统计
        $value6 = OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',$today_date)->sum('fee'); // 总手续费

        // 卡片内容
        $this->withContent($value1,$value2,$value3,$value4,$value5,$value6);

        // 图表数据
        $this->withChart([
            [
                'name' => 'Bet amount',
                'data' => [
                    OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',Carbon::today()->subDays(6)->format('Y-m-d'))->sum('bet_amount'),
                    OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',Carbon::today()->subDays(5)->format('Y-m-d'))->sum('bet_amount'),
                    OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',Carbon::today()->subDays(4)->format('Y-m-d'))->sum('bet_amount'),
                    OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',Carbon::today()->subDays(3)->format('Y-m-d'))->sum('bet_amount'),
                    OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',Carbon::today()->subDays(2)->format('Y-m-d'))->sum('bet_amount'),
                    OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',Carbon::today()->subDays(1)->format('Y-m-d'))->sum('bet_amount'),
                    OptionSceneOrder::query()->where(['bet_coin_id'=>$coin_id])->whereDate('created_at',Carbon::today()->format('Y-m-d'))->sum('bet_amount'),
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
     * @param string $value1
     * @param string $value2
     * @param string $value3
     * @param string $value4
     * @param string $value5
     * @param string $value6
     *
     * @return $this
     */
    public function withContent(string $value1 , string $value2, string $value3, string $value4, string $value5, string $value6)
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
        <div class="chart-info d-flex justify-content-between mb-1" >
          <div class="product-result" style="margin-left: 20px;">
              <span>类型</span>
          </div>
          <div class="product-result">
              <span>总计</span>
          </div>
          <div class="product-result">
              <span>今日</span>
          </div>
    </div>

        <div class="chart-info d-flex justify-content-between mb-1" >
                  <div class="series-info d-flex align-items-center">
                      <i class="fa fa-circle-o text-bold-700 text-primary"></i>
                      <span class="text-bold-600 ml-50">用户总下单量</span>
                  </div>
                  <div class="product-result">
                      <span>{$value1}</span>
                  </div>
                  <div class="product-result">
                      <span>{$value4}</span>
                  </div>
            </div>

            <div class="chart-info d-flex justify-content-between mb-1">
                  <div class="series-info d-flex align-items-center">
                      <i class="fa fa-circle-o text-bold-700 text-warning"></i>
                      <span class="text-bold-600 ml-50">交割结果统计</span>
                  </div>
                  <div class="product-result">
                      <span>{$value2}</span>
                  </div>
                  <div class="product-result">
                      <span>{$value5}</span>
                  </div>
            </div>

            <div class="chart-info d-flex justify-content-between mb-1" >
                  <div class="series-info d-flex align-items-center">
                      <i class="fa fa-circle-o text-bold-700 text-primary"></i>
                      <span class="text-bold-600 ml-50">总手续费</span>
                  </div>
                  <div class="product-result">
                      <span>{$value3}</span>
                  </div>
                  <div class="product-result">
                      <span>{$value6}</span>
                  </div>
            </div>
    </div>

    <a href="/admin/option-order" class="btn btn-primary shadow waves-effect waves-light">View Details <i class="feather icon-chevrons-right"></i></a>
</div>
HTML
        );
    }
}
