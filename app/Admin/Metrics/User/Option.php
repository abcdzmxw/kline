<?php


namespace App\Admin\Metrics\User;


use App\Models\Agent;
use App\Models\OptionBetCoin;
use App\Models\OptionSceneOrder;
use Carbon\Carbon;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Metrics\Bar;
use Illuminate\Http\Request;

class Option extends Bar
{
//    protected $id;
//
//    public function __construct($title = null, $icon = null, $id = 3)
//    {
//        $this->id = $id;
//
//        parent::__construct($title, $icon);
//    }

    /**
     * 初始化卡片内容
     */
    protected function init()
    {
        parent::init();

        $color = Admin::color();

        $dark35 = $color->blue2();

        // 卡片内容宽度
        $this->contentWidth(11, 1);
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

        $this->request('POST',$this->getRequestUrl(),['user_id' => request()->input('key')]); // 设置API请求地址 携带参数

//        $this->handle(request());
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
        $referrer = $request->get('user_id',null);

        $default_coin = OptionBetCoin::query()->where('is_bet',1)->orderBy('sort','desc')->orderBy('coin_id','asc')->value('coin_id');
        $coin_id = $request->get('option',$default_coin);

        $today_date = Carbon::today()->format('Y-m-d');

        $builder1 =
        $builder2 =
        $builder3 =
        $builder4 =
        $builder5 =
        $builder6 =
        $builder7 =
        $builder8 =
        $builder9 =
        $builder10 =
        $builder11 =
        $builder12 =
        $builder13 =
             OptionSceneOrder::query()->whereHas('user',function ($q)use($referrer){$q->where('user_id',$referrer);})->where(['bet_coin_id'=>$coin_id]);

        $value2 = $builder1->sum('delivery_amount'); // 交割结果统计
        $value1 = $builder2->sum('bet_amount'); // 用户总下单量
        $value3 = $builder3->sum('fee'); // 总手续费
        $value4 = $builder4->whereDate('created_at',$today_date)->sum('bet_amount'); // 用户总下单量
        $value5 = $builder5->whereDate('created_at',$today_date)->sum('delivery_amount'); // 交割结果统计
        $value6 = $builder6->whereDate('created_at',$today_date)->sum('fee'); // 总手续费

        // 卡片内容
        $this->withContent($referrer,$value1,$value2,$value3,$value4,$value5,$value6);

        // 图表数据
        $this->withChart([
            [
                'name' => 'Bet amount',
                'data' => [
                    $builder7->whereDate('created_at',Carbon::today()->subDays(6)->format('Y-m-d'))->sum('bet_amount'),
                    $builder8->whereDate('created_at',Carbon::today()->subDays(5)->format('Y-m-d'))->sum('bet_amount'),
                    $builder9->whereDate('created_at',Carbon::today()->subDays(4)->format('Y-m-d'))->sum('bet_amount'),
                    $builder10->whereDate('created_at',Carbon::today()->subDays(3)->format('Y-m-d'))->sum('bet_amount'),
                    $builder11->whereDate('created_at',Carbon::today()->subDays(2)->format('Y-m-d'))->sum('bet_amount'),
                    $builder12->whereDate('created_at',Carbon::today()->subDays(1)->format('Y-m-d'))->sum('bet_amount'),
                    $builder13->whereDate('created_at',Carbon::today()->format('Y-m-d'))->sum('bet_amount'),
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
     * @param int $referrer
     * @param string $value1
     * @param string $value2
     * @param string $value3
     * @param string $value4
     * @param string $value5
     * @param string $value6
     *
     * @return $this
     */
    public function withContent($referrer,string $value1 , string $value2, string $value3, string $value4, string $value5, string $value6)
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
