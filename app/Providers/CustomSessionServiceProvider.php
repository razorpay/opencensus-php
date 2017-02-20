<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Session;
use App\Session\CustomDatabaseSessionHandler;
use App\Session\CustomCacheBasedSessionHandler;

class CustomSessionServiceProvider extends ServiceProvider {

    public function boot()
	{
		Session::extend('custom_database', function ($data)
		{
			$connection = $this->app['db']->connection($this->app['config']['session.connection']);

	        $table = $this->app['config']['session.table'];

	        $lifetime = $this->app['config']['session.lifetime'];

			return new CustomDatabaseSessionHandler($connection, $table, $lifetime, $this->app);
		});

        Session::extend('custom_redis', function ($app)
        {
            // Taken from Illuminate\Session\SessionManager

            $minutes = $app['config']['session.lifetime'];

            $handler = new CustomCacheBasedSessionHandler(clone $app['cache']->driver('redis'), $minutes);

            $handler->getCache()->getStore()->setConnection($app['config']['session.connection']);

            return $handler;
        });
	}

	public function register()
	{

	}

}
