<?php

namespace App\Providers;

use Artdarek\OAuth\OAuth;
use Artdarek\OAuth\OAuthServiceProvider as ServiceProvider;

class OAuthServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('oauth', function ($app) {
            return new OAuth();
        });
    }

}
