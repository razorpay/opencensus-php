<?php

namespace RZP\Providers;

use Illuminate\Support\ServiceProvider;
use RZP\SqsRawSubscriber\Queue\Connectors\SqsRawConnector;

class SqsRawServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // nothing to register
    }

    /**
     * Bootstraps the 'queue' with a new connector 'sqs-raw'
     *
     * @return void
     */
    public function boot()
    {
        $this->app['queue']->extend('sqs-raw', function () {
            return new SqsRawConnector;
        });
    }
}