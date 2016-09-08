<?php

namespace App\Session;

use Illuminate\Session\SessionManager;
use App\Session\CustomSessionManager;

class CustomSessionManager extends SessionManager
{
	protected function createDatabaseDriver()
    {
        $connection = $this->getDatabaseConnection();

        $table = $this->app['config']['session.table'];

        $lifetime = $this->app['config']['session.lifetime'];

        return $this->buildSession(new CustomDatabaseSessionHandler($connection, $table, $lifetime, $this->app));
    }
}