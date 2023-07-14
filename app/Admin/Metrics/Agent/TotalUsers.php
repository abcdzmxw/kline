<?php

namespace App\Admin\Metrics\Agent;

use App\Models\Agent;
use App\Models\User;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Card;
use Dcat\Admin\Widgets\Metrics\Round;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class TotalUsers extends Round
{

    /**
     * 卡片底部内容.
     *
     * @var string|Renderable|\Closure
     */
    protected $footer;

    /**
     * 初始化卡片.
     */
    protected function init()
    {
        parent::init();

        $this->title('用户统计');

        // 卡片内容宽度
        $this->contentWidth(8, 4);

//        $this->height(150);

//        $this->dropdown([
//            '7' => '最近7天',
//            '30' => '最近30天',
//            '365' => '最近一年',
//        ]);

        $this->request('POST',$this->getRequestUrl(),['referrer'=>request()->input('key')]); // 设置API请求地址 携带参数
    }

    /**
     * 处理请求.
     *
     * @param Request $request
     *
     * @return void
     */
    public function handle(Request $request)
    {
        $referrer = $request->get('referrer',null);
        $baseAgentIds = Agent::getBaseAgentIds($referrer);

        $today_date = Carbon::today()->format('Y-m-d');

        $total = User::query()->whereIn('referrer',$baseAgentIds)->count();
        $total_auth = User::query()->whereIn('referrer',$baseAgentIds)->where('user_auth_level',User::user_auth_level_top)->count();
        $today = User::query()->whereIn('referrer',$baseAgentIds)->whereDate('created_at',$today_date)->count();
        $today_auth = User::query()->whereIn('referrer',$baseAgentIds)->whereDate('created_at',$today_date)->where('user_auth_level',User::user_auth_level_top)->count();

        $this->withContent($referrer,$total, $total_auth, $today,$today_auth);

        // 图表数据
        $this->withChart([$total, $total_auth, $today_auth]);

        // 总数
        $this->chartTotal('Total', $total);
    }

    /**
     * 设置卡片底部内容.
     *
     * @param string|Renderable|\Closure $footer
     *
     * @return $this
     */
    public function footer($footer)
    {
        $this->footer = $footer;

        return $this;
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
     * 卡片内容.
     *
     * @param int $referrer
     * @param int $total
     * @param int $total_auth
     * @param int $today
     * @param int $today_auth
     *
     * @return $this
     */
    public function withContent($referrer,$total, $total_auth, $today,$today_auth)
    {
        $minHeight = '183px';

        return $this->content(
            <<<HTML
<div class="d-flex p-1 flex-column justify-content-between" style="padding-top: 0;width: 100%;height: 100%;min-height: {$minHeight}">
    <div class="chart-info d-flex justify-content-between mb-1" >
          <div class="product-result" style="margin-left: 20px;">
              <span>用户类型</span>
          </div>
          <div class="product-result">
              <span>累计</span>
          </div>
          <div class="product-result">
              <span>今日新增</span>
          </div>
    </div>

    <div class="chart-info d-flex justify-content-between mb-1" >
          <div class="series-info d-flex align-items-center">
              <i class="fa fa-circle-o text-bold-700 text-primary"></i>
              <span class="text-bold-600 ml-50">注册用户</span>
          </div>
          <div class="product-result">
              <span>{$total}</span>
          </div>
          <div class="product-result">
              <span>{$today}</span>
          </div>
    </div>

    <div class="chart-info d-flex justify-content-between mb-1">
          <div class="series-info d-flex align-items-center">
              <i class="fa fa-circle-o text-bold-700 text-warning"></i>
              <span class="text-bold-600 ml-50">实名用户</span>
          </div>
          <div class="product-result">
              <span>{$total_auth}</span>
          </div>
          <div class="product-result">
              <span>{$today_auth}</span>
          </div>
    </div>

    <div class="chart-info d-flex justify-content-between mb-1">
          <div class="series-info d-flex align-items-center">
              <i class="fa fa-circle-o text-bold-700 text-warning"></i>
              <span class="text-bold-600 ml-50">实名用户</span>
          </div>
          <div class="product-result">
              <span>{$total_auth}</span>
          </div>
          <div class="product-result">
              <span>{$today_auth}</span>
          </div>
    </div>

    <a href="/admin/users?referrer={$referrer}" class="btn btn-primary shadow waves-effect waves-light">View Details <i class="feather icon-chevrons-right"></i></a>
</div>
HTML
        );
    }

    /**
     * 渲染卡片底部内容.
     *
     * @return string
     */
    public function renderFooter()
    {
        return $this->toString($this->footer);
    }
}
