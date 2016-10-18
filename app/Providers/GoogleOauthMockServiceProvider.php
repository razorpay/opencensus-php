<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GoogleOauthMockServiceProvider extends ServiceProvider
{
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('OAUTH_MOCK') === true)
        {
            require __DIR__.'/../selenium/init.php';
        }
    }

    public function register()
    {
        ;
    }
}
