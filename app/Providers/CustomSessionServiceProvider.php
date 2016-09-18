<?php 

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Session;
use App\Session\CustomDatabaseSessionHandler;

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
	}

	public function register()
	{

	}

}