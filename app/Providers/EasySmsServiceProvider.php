<?php

namespace App\Providers;

use App\SMSGateways\UnnameableGateway;
use Illuminate\Support\ServiceProvider;
use Overtrue\EasySms\EasySms;

class EasySmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(EasySms::class, function ($app) {
            return new EasySms(config('easysms'));
        });

        $this->app->alias(EasySms::class, 'easysms');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // 导入网关
        $this->app->make('easysms')->extend('unnameable', function () {
            return new UnnameableGateway(config('easysms.gateways.unnameable'));
        });
    }
}
