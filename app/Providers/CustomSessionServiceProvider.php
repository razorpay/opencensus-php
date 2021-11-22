<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Session;
use App\Session\CustomDatabaseSessionHandler;
use App\Session\CustomCacheBasedSessionHandler;

class CustomSessionServiceProvider extends ServiceProvider {

    public function boot()
	{
        Session::extend('custom_redis', function ($app)
        {
            // Taken from Illuminate\Session\SessionManager

            $loggedInUserSessionTimeout = $app['config']['session.lifetime'];

            $nonLoggedInUserSessionTimeout = $app['config']['session.non_logged_in_lifetime'];

            $handler = new CustomCacheBasedSessionHandler(clone $app['cache']->driver('redis'),
                $loggedInUserSessionTimeout,
                $nonLoggedInUserSessionTimeout);

            $handler->getCache()->getStore()->setConnection($app['config']['session.connection']);

            return $handler;
        });
	}

	public function register()
	{

	}

}
