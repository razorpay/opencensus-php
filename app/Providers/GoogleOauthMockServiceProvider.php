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
        if (getenv('APP_ENV') === 'testing')
        {
            require __DIR__.'/../selenium/init.php';
        }
    }

    public function register()
    {
        ;
    }
}
