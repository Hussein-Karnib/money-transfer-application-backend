<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\ChannelManager;
use App\Notifications\Channels\SmsChannel;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    $this->app->extend(ChannelManager::class, function ($service, $app) {
        $service->extend('sms', function ($app) {
            return new SmsChannel($app->make(\App\Services\SmsService::class));
        });

        return $service;
    });
}
}
