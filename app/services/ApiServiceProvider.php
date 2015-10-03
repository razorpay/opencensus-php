<?php

namespace Services;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ApiServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('slack', function($app)
        {
            return new Slack($app);
        });

        $this->app->bindShared('mailgun', function($app)
        {
            return new Mailgun($app);
        });

        $this->app->bindShared('instance', function($app)
        {
            return new AwsInstance($app);
        });

        $this->app->bindShared('exception.handler', function($app)
        {
            return new \EE\Exception\Handler($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('slack', 'mailgun', 'instance', 'exception.handler');
    }
}
