<?php

namespace RZP\Services;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RZP\Gateway\GatewayManager;

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
        $this->app->singleton('mailgun', function($app)
        {
            return new Mailgun($app);
        });

        $this->app->singleton('instance', function($app)
        {
            return new AwsInstance($app);
        });

        $this->app->singleton('gateway', function($app)
        {
            return new GatewayManager($app);
        });

        $this->app->singleton('webhook.inferno', function($app)
        {
            return new RZP\Models\Merchant\Webhook\Inferno;
        });

        $this->app->singleton('card.tokenex', function($app)
        {
            return new TokenEx($app);
        });

        $this->app->singleton('raven', function($app)
        {
            return new Raven($app);
        });

        $this->app->singleton('es', function($app)
        {
            return new EsClient($app);
        });

        $this->app->singleton('repo', function($app)
        {
            return new \RZP\Base\RepositoryManager($app);
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
            'repo',
            'es',
        );
    }
}
