<?php

namespace RZP\Services;

use RZP;
use Swift_Mailer;
use Http\Mock\Client as MockHttplug;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Models\Dispute;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Transfer;
use RZP\Models\Promotion;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\BankAccount;
use RZP\Models\Gateway\File;
use RZP\Models\Admin as Admin;
use RZP\Models\Payment\Refund;
use RZP\Gateway\GatewayManager;
use RZP\Models\Plan\Subscription;
use RZP\Services\GatewayFileManager;
use RZP\Models\Plan\Subscription\Addon;


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
        $this->registerTraceProcessors();

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

        $this->app->bind('exception.handler', function($app)
        {
            return new \RZP\Exception\Handler($app);
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

        $this->app->bind('raven', function($app)
        {
            return new Raven($app);
        });

        $this->app->singleton('authservice', function($app)
        {
            return new AuthService($app);
        });

        $this->app->singleton('es', function($app)
        {
            return new EsClient($app);
        });

        $this->app->singleton('repo', function($app)
        {
            return new \RZP\Base\RepositoryManager($app);
        });

        $this->app->singleton('upi.client', function($app)
        {
            return new \Razorpay\UPI\Client;
        });

        $this->app->singleton('segment', function($app)
        {
            return new EventTrackerClient($app);
        });

        $this->app->singleton('eventManager', function($app)
        {
            $harvesterClientMock = $app['config']->get('applications.harvester.mock');

            if ($harvesterClientMock === true)
            {
                return new Mock\HarvesterClient($app);
            }

            return new HarvesterClient($app);
        });

        $this->app->singleton('gateway_file', function($app)
        {
            return new GatewayFileManager($app);
        });

        $this->registerApiMutex();

        $this->registerMaxMind();

        $this->registerElfin();

        $this->registerExchange();

        $this->registerQueueableEntityResolver();

        $this->registerMorphRelationMaps();

        $this->registerSesClient();

        $this->registerDrip();

        $this->registerSns();

        $this->registerWorkflow();

        $this->registerHttplugMockClient();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
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
            'eventManager',
            'upi.client',
            'webhook.inferno',
            'exchange',
            'pigeon',
            'workflow',
            'authservice',
            'sns',
        ];
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

    protected function registerMorphRelationMaps()
    {
        Relation::morphMap([
            // heimdall
            'org'             => Admin\Org\Entity::class,
            'group'           => Admin\Group\Entity::class,
            'admin'           => Admin\Admin\Entity::class,
            'role'            => Admin\Role\Entity::class,
            'permission'      => Admin\Permission\Entity::class,

            // line items
            'invoice'         => Invoice\Entity::class,
            'addon'           => Addon\Entity::class,

            // transfers
            'transfer'        => Transfer\Entity::class,
            'reversal'        => Reversal\Entity::class,
            'customer'        => Customer\Entity::class,

            // file store
            'merchant'        => Merchant\Entity::class,
            'merchant_detail' => Merchant\Detail\Entity::class,
            'batch'           => Batch\Entity::class,
            'gateway_file'    => Gateway\File\Entity::class,

            'account'         => Merchant\Account\Entity::class,

            // transaction
            'adjustment'      => Adjustment\Entity::class,
            'payment'         => Payment\Entity::class,
            'refund'          => Payment\Refund\Entity::class,
            'settlement'      => Settlement\Entity::class,
            'payout'          => Payout\Entity::class,

            'bank_account'    => BankAccount\Entity::class,

            'subscription'    => Subscription\Entity::class,
            'promotion'       => Promotion\Entity::class,

            'dispute'         => Dispute\Entity::class,
        ]);
    }

    protected function registerSesClient()
    {
        $this->app->singleton('pigeon', function ($app)
        {
            $swiftMailer =  new Swift_Mailer($app['swift.transport']->driver('ses'));

            $mailer = new Mailer(
                $app['view'], $swiftMailer, $app['events'], 'pigeon'
            );

            $mailer->setContainer($app);

            if ($app->bound('queue'))
            {
                $mailer->setQueue($app['queue.connection']);
            }

            return $mailer;
        });
    }

    protected function registerDrip()
    {
        $this->app->singleton('drip', function ($app)
        {
            $dripMock = $app['config']->get('applications.drip.mock');

            if ($dripMock === true)
            {
                return new Mock\Drip($app);
            }

            return new Drip($app);
        });
    }

    protected function registerSns()
    {
        $this->app->singleton('sns', function ($app)
        {
            $snsMock = $app['config']->get('applications.sns.mock');

            if ($snsMock === true)
            {
                return new Aws\Mock\Sns($app);
            }

            return new Aws\Sns($app);
        });
    }

    protected function registerWorkflow()
    {
        $this->app->singleton('workflow', function ($app)
        {
            return new Workflow\Service($app);
        });
    }

    protected function registerHttplugMockClient()
    {
        $this->app['httplug']->extend('mock', function()
        {
            return new MockHttplug;
        });
    }

    protected function registerTraceProcessors()
    {
        $apiProcessor = new RZP\Trace\ApiTraceProcessor($this->app);

        $this->app['trace']->pushProcessor($apiProcessor);
    }
}
