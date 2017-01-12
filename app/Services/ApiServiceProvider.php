<?php

namespace RZP\Services;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use RZP\Models\Admin as Admin;
use RZP\Constants as Constants;
use RZP\Gateway\GatewayManager;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
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
            $mailgunMock = $app['config']->get('applications.mailgun.mock');

            if ($mailgunMock === true)
            {
                return new Mock\Mailgun($app);
            }

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
                return new Mock\TokenEx($app);
            }

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

        $this->app->singleton('segment', function($app)
        {
            return new SegmentClient($app);
        });

        $this->app->singleton('upi.client', function($app)
        {
            return new \Razorpay\UPI\Client;
        });

        $this->registerApiMutex();

        $this->registerMaxMind();

        $this->registerElfin();

        $this->registerExchange();

        $this->registerValidatorResolver();

        $this->registerQueueableEntityResolver();

        $this->registerMorphRelationMaps();

        $this->registerStoreProvider();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'api.mutex',
            'bitly',
            'card.tokenex',
            'es',
            'exception.handler',
            'gateway',
            'instance',
            'mailgun',
            'maxmind',
            'raven',
            'repo',
            'elfin',
            'segment',
            'upi.client',
            'webhook.inferno',
            'exchange',
            'store'
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
        $this->app['validator']->resolver(function($translator, $data, $rules, $messages, $customAttributes)
        {
            return new \RZP\Models\Base\ExtendedValidations(
                            $translator, $data, $rules, $messages, $customAttributes);
        });
    }

    protected function registerMaxMind()
    {
        $this->app->singleton('maxmind', function($app)
        {
            $maxmindMock = $app['config']->get('applications.maxmind.mock');

            if ($maxmindMock === true)
            {
                return new Mock\MaxMind($app);
            }

            return new MaxMind($app);
        });
    }

    protected function registerElfin()
    {
        $this->app->singleton('elfin', function($app)
        {
            $mock = $app['config']->get('applications.elfin.mock');

            if ($mock)
            {
                return new Elfin\Mock\Service($app['config'], $app['trace']);
            }

            return new Elfin\Service($app['config'], $app['trace']);
        });
    }

    protected function registerExchange()
    {
        $this->app->singleton('exchange', function($app)
        {
            $exchangeMock = $app['config']->get('applications.exchange.mock');

            if ($exchangeMock === true)
            {
                return new Mock\Exchange($app);
            }

            return new Exchange($app);
        });
    }

    protected function registerApiMutex()
    {
        $this->app->singleton('api.mutex', function($app)
        {
            $lockMock = $app['config']->get('services.mutex.mock');

            if ($lockMock === true)
            {
                return new Mock\Mutex($app);
            }

            return new Mutex($app);
        });
    }

    protected function registerStoreProvider()
    {
        $this->app->singleton('store', function ($app)
        {
            $storeMock = $app['config']->get('services.store.mock');

            if ($storeMock === true)
            {
                return new Mock\StoreManager($app);
            }

            return new StoreManager($app);
        });
    }

    protected function registerMorphRelationMaps()
    {
        Relation::morphMap([
            // heimdall
            'org'             => Admin\Org\Entity::class,
            'group'           => Admin\Group\Entity::class,
            'admin'           => Admin\Admin\Entity::class,
            'role'            => Admin\Role\Entity::class,
            'permission'      => Admin\Permission\Entity::class,
            'invoice'         => Invoice\Entity::class,
            'merchant'        => Merchant\Entity::class,
            'merchant_detail' => Merchant\Detail\Entity::class,
        ]);
    }
}
