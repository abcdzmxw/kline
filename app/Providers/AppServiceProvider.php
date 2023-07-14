<?php

namespace App\Providers;

use App\Models\PledgeOrder;
use App\Models\ContractPosition;
use App\Models\InsideTradeOrder;
use App\Models\TestTradeOrder;
use App\Observers\ContractPositionObserver;
use App\Observers\InsideTradeOrderObserver;
use App\Observers\PledgeOrderObserver;
use App\Observers\TestTradeOrderObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app('api.exception')->register(function (\Exception $exception) {
            $request = \Illuminate\Http\Request::capture();
            return app('App\Exceptions\Handler')->render($request, $exception);
        });

        InsideTradeOrder::observe(InsideTradeOrderObserver::class);
        ContractPosition::observe(ContractPositionObserver::class);
        PledgeOrder::observe(PledgeOrderObserver::class);
//        TestTradeOrder::observe(TestTradeOrderObserver::class);
    }
}
