<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GoogleOauthMockServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if (config('oauth.mock') === true)
        {
            require __DIR__.'/../selenium/init.php';
        }
    }

    public function register()
    {
        ;
    }
}
