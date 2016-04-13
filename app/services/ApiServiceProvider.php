<?php

namespace Services;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Gateway\GatewayManager;

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

        $this->app->bindShared('gateway', function($app)
        {
            return new GatewayManager($app);
        });

        $this->app->bindShared('webhook.inferno', function($app)
        {
            return new \Models\Merchant\Webhook\Inferno;
        });

        $this->app->bindShared('card.tokenex', function($app)
        {
            return new TokenEx($app);
        });

        $this->app->bindShared('raven', function($app)
        {
            return new Raven($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'mailgun',
            'instance',
            'exception.handler',
            'gateway',
            'webhook.inferno',
            'card.tokenex',
            'raven',
        );
    }
}
