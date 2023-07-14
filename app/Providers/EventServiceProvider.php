<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
//        Registered::class => [
//            SendEmailVerificationNotification::class,
//        ],
     //'pledgeOrder.created' => [
     //   'App\\Handlers\\Events\\PledgeUpgradeEvent@pledgeOrderCreated',
  //  ],
        'App\Events\UserRegisterEvent' => [
            'App\Listeners\UserRegisterListener',
        ],
        'App\Events\UserLoginEvent' => [
            'App\Listeners\UserLoginListener',
        ],
        'App\Events\UserUpgradeEvent' => [
            'App\Listeners\UserUpgradeListener',
        ],
      'App\Events\PledgeUpgradeEvent' => [
          'App\Listeners\PledgeUpgradeListener',
       ],
        'App\Events\TriggerEntrustEvent' => [
            'App\Listeners\TriggerEntrustListener',
        ],
        'App\Events\ExchangeBuyEvent' => [
            'App\Listeners\ExchangeBuyListener',
        ],
        'App\Events\ExchangeSellEvent' => [
            'App\Listeners\ExchangeSellListener',
        ],
        'App\Events\HandDividendEvent' => [
            'App\Listeners\HandDividendListener',
        ],
        'App\Events\TestTradeOrderEvent' => [
            'App\Listeners\TestTradeOrderListener',
        ],
        'App\Events\SystemFlatEvent' => [
            'App\Listeners\SystemFlatListener',
        ],
        'App\Events\WithdrawEvent' => [
            'App\Listeners\WithdrawListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
