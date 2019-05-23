<?php

namespace RZP\Services;

use RZP;
use Redis;
use Swift_Mailer;
use Razorpay\OAuth\Application;
use Illuminate\Database\Connection;
use Http\Mock\Client as MockHttplug;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Database\MySqlConnection as IlluminateMySqlConnection;

use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Models\User;
use RZP\Services\FTS;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Models\Payout;
use RZP\Models\Contact;
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
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Models\BankTransfer;
use RZP\Models\EntityOrigin;
use RZP\Constants\Entity as E;
use RZP\Models\Admin as Admin;
use RZP\Models\VirtualAccount;
use RZP\Gateway\GatewayManager;
use RZP\Models\Workflow\Action;
use RZP\Models\Plan\Subscription;
use RZP\Base\Database\MySqlConnection;
use RZP\Models\Plan\Subscription\Addon;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Gateway\File as GatewayFile;
use RZP\Services\Beam\Service as BeamService;
use RZP\Models\Merchant\Request as MerchantRequest;

class ApiServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Registering observers for eloquent events here.
     * Used for invalidating cached entities on update
     */
    public function boot()
    {
        foreach (E::CACHED_ENTITIES as $entity => $_)
        {
            if ($entity !== E::AUTH_TOKEN)
            {
                $entityClass = E::getEntityClass($entity);
                $entityObserverClass = E::getEntityObserverClass($entity);

                $entityClass::observe($entityObserverClass);
            }
        }

        // attaching payment observer since its invalidates
        // the upi status on update
        $entityClass = E::getEntityClass(E::PAYMENT);
        $entityObserverClass = E::getEntityObserverClass(E::PAYMENT);
        $entityClass::observe($entityObserverClass);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTraceProcessors();
        $this->registerGatewayProcessors();

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

        $this->app->singleton('razorx', function($app)
        {
            return new RazorXClient($app);
        });

        $this->app->singleton('card.cardVault', function($app)
        {
            $cardVaultMock = $app['config']->get('applications.card_vault.mock');

            if ($cardVaultMock === true)
            {
                return new Mock\CardVault($app);
            }

            return new CardVault($app);
        });

        $this->app->singleton('cps', function($app)
        {
            $cpsMock = $app['config']->get('applications.cps.mock');

            if ($cpsMock === true)
            {
                return new Mock\CorePaymentService($app);
            }

            return new CorePaymentService($app);
        });

        $this->app->singleton('card.otpelf', function($app)
        {
            $mock = $app['config']->get('applications.otpelf.mock');

            $implementation = $mock ? Mock\OtpElf::class : OtpElf::class;

            return new $implementation($app);
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

        $this->app->singleton('diag', function($app)
        {
            return new DiagClient($app);
        });


        $this->app->singleton('eventManager', function($app)
        {
            $harvesterClientMock = $app['config']->get('applications.harvester.mock');

            if ($harvesterClientMock === true)
            {
                return new Mock\HarvesterClient($app);
            }

            return new Harvester\HarvesterClient($app);
        });

        $this->app->singleton('ufh.service', function ($app)
        {
            $ufhServiceMock = $app['config']->get('applications.ufh.mock');

            if ($ufhServiceMock === true)
            {
                return new Mock\UfhService($app);
            }

            return new UfhService($app);
        });

        $this->app->singleton('gateway_file', function($app)
        {
            return new GatewayFileManager($app);
        });

        $this->registerShieldClient();

        $this->app->singleton('beam', function($app)
        {
            $beamServiceMock = $app['config']->get('applications.beam.mock');

            if ($beamServiceMock === true)
            {
                return new Mock\BeamService($app);
            }

            return new BeamService($app);
        });

        $this->app->singleton('module', function($app)
        {
            return new RZP\Modules\Manager($app);
        });

        $this->registerShield();

        $this->registerRedisDualWrite();

        $this->registerApiMutex();

        $this->registerMaxMind();

        $this->registerRaven();

        $this->registerBatchService();

        $this->registerScrooge();

        $this->registerElfin();

        $this->registerExchange();

        $this->registerQueueableEntityResolver();

        $this->registerMorphRelationMaps();

        $this->registerSesClient();

        $this->registerDrip();

        $this->registerSns();

        $this->registerWorkflow();

        $this->registerHttplugMockClient();

        $this->registerGeolocation();

        $this->registerPincodeSearch();

        $this->registerDatabaseConnection();

        $this->registerMyOperator();

        $this->registerKubernetesClient();

        $this->registerFTSCreateAccount();

        $this->registerFTSRegisterAccount();

        $this->registerFTSFundTransfer();
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
            'razorx',
            'es',
            'exception.handler',
            'gateway',
            'instance',
            'mailgun',
            'maxmind',
            'raven',
            'batchService',
            'scrooge',
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
            'pincodesearch',
            'shield.service',
            'beam',
            'fts_create_account',
            'fts_register_account',
            'fts_fund_transfer',
            'diag',
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

    protected function registerRaven()
    {
        $this->app->bind('raven', function($app)
        {
            $mock = $app['config']->get('applications.raven.mock');

            $implementation = $mock ? Mock\Raven::class : Raven::class;

            return new $implementation($app);
        });
    }

    protected function registerBatchService()
    {
        $this->app->bind('batchService', function($app)
        {
            $mock = $app['config']->get('applications.batch.mock');

            $implementation = $mock ? Mock\BatchMicroService::class : BatchMicroService::class;

            return new $implementation($app);
        });
    }

    protected function registerScrooge()
    {
        $this->app->bind('scrooge', function($app)
        {
            $mock = $app['config']->get('applications.scrooge.mock');

            $implementation = $mock ? Mock\Scrooge::class : Scrooge::class;

            return new $implementation($app);
        });
    }

    protected function registerRedisDualWrite()
    {
        $this->app->singleton('redisdualwrite', function($app)
        {
            $lockMock = $app['config']->get('services.mutex.mock');

            if ($lockMock === true)
            {
                return new Mock\Mutex($app);
            }

            return new RedisDualWrite($app);
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

            // this is still required for testing,
            // until we move redis_labs config as default connection
            $mutex = new Mutex($app);

            $mutex->setRedisClient(Redis::Connection());

            return $mutex;
        });
    }

    protected function registerMorphRelationMaps()
    {
        Relation::morphMap([
            // heimdall
            'org'                       => Admin\Org\Entity::class,
            'group'                     => Admin\Group\Entity::class,
            'admin'                     => Admin\Admin\Entity::class,
            'role'                      => Admin\Role\Entity::class,
            'permission'                => Admin\Permission\Entity::class,

            'user'                      => User\Entity::class,

            // line items
            'invoice'                   => Invoice\Entity::class,
            'addon'                     => Addon\Entity::class,

            // transfers
            'transfer'                  => Transfer\Entity::class,
            'reversal'                  => Reversal\Entity::class,
            'customer'                  => Customer\Entity::class,

            // file store
            'merchant'                  => Merchant\Entity::class,
            'merchant_detail'           => Merchant\Detail\Entity::class,
            'batch'                     => Batch\Entity::class,
            'gateway_file'              => GatewayFile\Entity::class,

            // transaction
            'adjustment'                => Adjustment\Entity::class,
            'payment'                   => Payment\Entity::class,
            'card'                      => Card\Entity::class,
            'order'                     => Order\Entity::class,
            'refund'                    => Payment\Refund\Entity::class,
            'settlement'                => Settlement\Entity::class,
            'payout'                    => Payout\Entity::class,
            'transaction'               => Transaction\Entity::class,
            'fund_account_validation'   => FundAccount\Validation\Entity::class,
            'customer_transaction'      => Customer\Transaction\Entity::class,

            'bank_account'              => BankAccount\Entity::class,
            'vpa'                       => Vpa\Entity::class,
            'virtual_account'           => VirtualAccount\Entity::class,
            'bank_transfer'             => BankTransfer\Entity::class,

            'subscription'              => Subscription\Entity::class,
            'promotion'                 => Promotion\Entity::class,

            'dispute'                   => Dispute\Entity::class,

            'workflow_action'           => Action\Entity::class,

            'merchant_request'          => MerchantRequest\Entity::class,

            'subscription_registration' => SubscriptionRegistration\Entity::class,

            'contact'                   => Contact\Entity::class,

            'entity_origin'             => EntityOrigin\Entity::class,

            'application'               => Application\Entity::class,
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

    protected function registerGeolocation()
    {
        $this->app->singleton('geolocation', function($app)
        {
            return new Geolocation\Service($app);
        });
    }

    protected function registerTraceProcessors()
    {
        $apiProcessor = new RZP\Trace\ApiTraceProcessor($this->app);

        $this->app['trace']->pushProcessor($apiProcessor);
    }

    protected function registerGatewayProcessors()
    {
        $apiProcessor = new RZP\Trace\GatewayTraceProcessor($this->app);

        $this->app['trace']->pushProcessor($apiProcessor, 'gateway');
    }

    protected function registerPincodeSearch()
    {
        $this->app->singleton('pincodesearch', function($app)
        {
            $mock = $app['config']->get('applications.pincodesearch.mock');

            $implementation = $mock ? Mock\PincodeSearch::class : PincodeSearch::class;

            return new $implementation($app);
        });
    }

    protected function registerShieldClient()
    {
        $this->app->singleton('shield', function($app)
        {
            $mock = $app['config']->get('applications.shield.mock');

            $implementation = $mock ? Mock\ShieldClient::class : ShieldClient::class;

            return new $implementation;
        });
    }

    protected function registerShield()
    {
        $this->app->singleton('shield.service', function($app)
        {
            return new Shield($app);
        });
    }

    protected function registerDatabaseConnection()
    {
        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            //
            // If the connection config has lag_check configuration set use the
            // custom MySqlConnection class. If no, then we use the default connection class.
            //
            if (isset($config['lag_check']) === true)
            {
                return new MySqlConnection($connection, $database, $prefix, $config);
            }

            return new IlluminateMySqlConnection($connection, $database, $prefix, $config);
        });
    }

    protected function registerMyOperator()
    {
        $this->app->singleton('myoperator', function()
        {
            $config = $this->app->config->get('applications.myoperator');
            $impl   = $config['mock'] ? Mock\MyOperator::class : MyOperator::class;

            return new $impl($this->app->trace, $config);
        });
    }

    protected function registerKubernetesClient()
    {
        $this->app->singleton('k8s_client', function($app)
        {
            return new KubernetesClient($app);
        });
    }

    protected function registerFTSCreateAccount()
    {
        $this->app->bind('fts_create_account', function($app)
        {
            $mock = $app['config']->get('applications.fts.mock');

            $implementation = $mock ? Mock\FTS\CreateAccount::class : FTS\CreateAccount::class;

            return new $implementation($app);
        });
    }

    protected function registerFTSRegisterAccount()
    {
        $this->app->bind('fts_register_account', function($app)
        {
            $mock = $app['config']->get('applications.fts.mock');

            $implementation = $mock ? Mock\FTS\RegisterAccount::class : FTS\RegisterAccount::class;

            return new $implementation($app);
        });
    }

    protected function registerFTSFundTransfer()
    {
        $this->app->bind('fts_fund_transfer', function($app)
        {
            $mock = $app['config']->get('applications.fts.mock');

            $implementation = $mock ? Mock\FTS\FundTransfer::class : FTS\FundTransfer::class;

            return new $implementation($app);
        });
    }
}
