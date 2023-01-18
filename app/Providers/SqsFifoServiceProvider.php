<?php

namespace RZP\Providers;

use Illuminate\Support\ServiceProvider;
use RZP\SqsFifoSubscriber\Queue\Connectors\SqsFifoConnector;

class SqsFifoServiceProvider extends ServiceProvider
{
    /**
     * Bootstraps the 'queue' with a new connector 'sqs-fifo'
     *
     * @return void
     */
    public function boot()
    {
        $this->app['queue']->extend('sqs-fifo', function () {
            return new SqsFifoConnector();
        });
    }
}
