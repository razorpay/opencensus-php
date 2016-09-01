<?php

namespace RZP\Services;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RZP\Gateway\GatewayManager;
use RZP\Services;
use RZP;

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
            return new \RZP\Models\Merchant\Webhook\Inferno;
        });

        $this->app->singleton('exception.handler', function($app)
        {
            return new \RZP\Exception\Handler($app['trace']);
        });

        $this->app->singleton('card.tokenex', function($app)
        {
            $tokenexMock = $app['config']->get('applications.card_tokenex.mock');

            if ($tokenexMock === true)
            {
                return new Services\Mock\TokenEx($app);
            }

            return new Services\TokenEx($app);
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

        $this->app->singleton('api.lock', '\RZP\Models\Base\Lock');

        $this->registerValidatorResolver();

        $this->registerQueueableEntityResolver();

        $this->registerRequestGetIdMacro();
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

    /**
     * Register the queueable entity resolver implementation.
     *
     * @return void
     */
    protected function registerQueueableEntityResolver()
    {
        $this->app->singleton('Illuminate\Contracts\Queue\EntityResolver', function ()
        {
            return new \RZP\Base\QueueEntityResolver;
        });
    }

    protected function registerValidatorResolver()
    {
        $this->app['validator']->resolver(function($translator, $data, $rules, $messages)
        {
            return new \RZP\Models\Base\ExtendedValidations(
                            $translator, $data, $rules, $messages);
        });
    }

    protected function registerRequestGetIdMacro()
    {
        $this->app['request']->macro('getId', function()
        {
            if ($this->requestId === null)
            {
                $this->requestId = bin2hex(openssl_random_pseudo_bytes(16));
            }

            return $this->requestId;
        });
    }
}
