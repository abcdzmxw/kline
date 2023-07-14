<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CreateOptionScene::class,
        \App\Console\Commands\CheckUserAuth::class,
        \App\Console\Commands\CancelScene::class,
        \App\Console\Commands\UpdateExchangeRate::class,
        \App\Console\Commands\DealRobot::class,
        \App\Console\Commands\ContractDealRobot::class,
        \App\Console\Commands\collection::class,
        \App\Console\Commands\FlatPosition::class,

        \App\Console\Commands\FakeKline::class,
         \App\Console\Commands\OptionDeliveryListen::class,
         \App\Console\Commands\PledgeRewardSpecialListen::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //创建期权场景 每五分钟
        $schedule->command('createOptionScene')->everyMinute()->withoutOverlapping()->runInBackground();
      //  $schedule->command('option:delivery')->everyMinute()->withoutOverlapping()->runInBackground();
        //用户认证系统自动审核通过
//        $schedule->command('checkUserAuth')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
        // 异常期权场景处理
        $schedule->command('cancelScene')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
        // Exchange委托取消
        $schedule->command('cancelBuyEntrust')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
        $schedule->command('cancelSellEntrust')->everyFiveMinutes()->withoutOverlapping()->runInBackground();

        // 更新USD-CNY汇率
        $schedule->command('updateExchangeRate')->hourly()->withoutOverlapping()->runInBackground();
        // K线
        $schedule->command('fakeKline')->dailyAt('23:00')->withoutOverlapping()->runInBackground();

        // 归集任务
        //$schedule->command('collection')->everyMinute()->withoutOverlapping()->runInBackground();

        // erc20usdt充值扫描
        //$schedule->command('ethtokentx')->everyTenMinutes()->withoutOverlapping()->runInBackground();

        // TRC20-USDT交易
        //$schedule->command('TrxTokenTransactions')->everyTenMinutes()->withoutOverlapping()->runInBackground();

        // 资金费收取
        $schedule->command('capitalCost')->dailyAt('00:00')->withoutOverlapping()->runInBackground();
        $schedule->command('capitalCost')->dailyAt('08:00')->withoutOverlapping()->runInBackground();
        $schedule->command('capitalCost')->dailyAt('16:00')->withoutOverlapping()->runInBackground();

        // 解除质押挖矿
        $schedule->command('PledgeUnlock')->dailyAt('00:01')->withoutOverlapping()->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
