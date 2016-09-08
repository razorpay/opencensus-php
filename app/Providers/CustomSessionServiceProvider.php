<?php 

namespace App\Providers;

use Illuminate\Session\SessionServiceProvider;
use App\Session\CustomSessionManager;

class CustomSessionServiceProvider extends SessionServiceProvider {

    protected function registerSessionManager()
    {
        $this->app->singleton('session', function ($app) {
            return new CustomSessionManager($app);
        });
    }

}