<?php

namespace RZP\Services;

use GuzzleHttp\Client;
use Illuminate\Cache\CacheManager;
use RZP;
use Cache;
use RZP\Trace\TraceCode;
use Swift_Mailer;
use Buzz\Client\MultiCurl;
use Razorpay\Asv\Config as AsvSdkConfig;
use Razorpay\Asv\Client as AsvSdkClient;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use Razorpay\Outbox\Job\Core;
use Razorpay\OAuth\Application;
use Illuminate\Database\Connection;
use Razorpay\Outbox\Job\Repository;
use Illuminate\Support\Facades\Redis;
use Razorpay\Outbox\Encoder\JsonEncoder;
use Razorpay\Edge\Passport\KeylessHeader;
use Http\Discovery\Psr17FactoryDiscovery;
use Razorpay\Outbox\Encrypt\AES256GCMEncrypt;
use RZP\Services\CircuitBreaker\CircuitBreaker;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use RZP\Services\CircuitBreaker\Store\StoreInterface;
use RZP\Services\CircuitBreaker\Store\RedisClusterStore;
use RZP\Services\Mock\DruidService as MockDruidService;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Database\MySqlConnection as IlluminateMySqlConnection;

use RZP\Models\Vpa;
use RZP\Modules\Acs;
use RZP\Models\Card;
use RZP\Models\User;
use RZP\Services\FTS;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Dispute;
use RZP\Models\Invoice;
use RZP\Models\Options;
use RZP\Models\Payment;
use RZP\Services\Wallet;
use RZP\Models\External;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Transfer;
use RZP\Models\Promotion;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\PaymentLink;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Services\UpiPayment;
use RZP\Models\FundTransfer;
use RZP\Models\BankTransfer;
use RZP\Models\PaperMandate;
use RZP\Models\EntityOrigin;
use RZP\Models\WalletAccount;
use RZP\Services\Settlements;
use RZP\Constants\Entity as E;
use RZP\Models\Admin as Admin;
use RZP\Models\VirtualAccount;
use RZP\Models\BankingAccount;
use RZP\Models\CreditTransfer;
use RZP\Gateway\GatewayManager;
use RZP\Models\Workflow\Action;
use RZP\Models\CreditRepayment;
use RZP\Models\VirtualAccountTpv;
use Swagger\Client\Configuration;
use RZP\Models\Plan\Subscription;
use RZP\Base\Http\Psr18ClientMock;
use RZP\Models\Partner\Commission;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\Database\MySqlConnection;
use AuthzAdmin\Client\Api\AdminAPIApi;
use Swagger\Client\Api\EnforcerAPIApi;
use RZP\Models\Plan\Subscription\Addon;
use RZP\Services\FreshdeskTicketClient;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Gateway\File as GatewayFile;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Dcs\Features\Service as DcsService;
use RZP\Models\Base\DbMigrationMetricsObserver;
use RZP\Base\Database\Connectors\MySqlConnector;
use RZP\Models\Base\EntityInstrumentationObserver;
use RZP\Models\Merchant\Request as MerchantRequest;
use RZP\Services\XPayroll\Service as XPayrollService;
use AuthzAdmin\Client\Configuration as AdminConfiguration;
use RZP\Services\VendorPortal\Service as VendorPortalService;
use RZP\Services\GenericAccountingIntegration\Service as AccountingIntegrationService;
Use RZP\Models\Merchant\Acs\AsvClient\Constant as AsvConstant;
use RZP\Services\VendorPayments\Service as VendorPaymentService;
use RZP\Models\Merchant\OneClickCheckout\ShippingProvider\Service as ShippingProviderService;
use RZP\Models\Merchant\OneClickCheckout\FulfillmentOrder\Service as FulfillmentOrderService;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Service as ShippingMethodProviderService;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethods\Service as ShippingMethodsService;
use RZP\Models\Merchant\OneClickCheckout\ShippingService\Client as ShippingServiceClient;
use RZP\Models\Merchant\OneClickCheckout\RtoPredictionProvider\Service as RtoPredictionProviderService;
use RZP\Models\Merchant\OneClickCheckout\RtoPredictionConfigs as RtoPredictionConfigs;
use RZP\Models\Merchant\OneClickCheckout\RtoPredictionService\Client as RtoPredictionServiceClient;
use RZP\Models\Merchant\OneClickCheckout\MagicAnalyticsProvider\Service as MagicAnalyticsProviderService;
use RZP\Models\Merchant\OneClickCheckout\IntegrationService\Client as IntegrationServiceClient;
use RZP\Models\Merchant\OneClickCheckout\RtoDashboard\Service as RtoDashboardService;
use RZP\Models\Merchant\OneClickCheckout\ShippingService as  ShippingService;
use RZP\Models\Merchant\OneClickCheckout\RtoFileUploadAuditService\Service as RtoFileUploadAuditService;
use RZP\Models\Merchant\OneClickCheckout\RtoFeatureReasonProvider\Service as RtoFeatureReasonProviderService;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService\Client as  MagicCheckoutServiceClient;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\CouponProvider\Service as MagicCheckoutCouponService;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\CodEngine\Service as MagicCheckoutCodEngineService;
use RZP\Models\Merchant\OneClickCheckout\MagicAddressProvider\Service as MagicAddressProviderService;
use RZP\Models\Merchant\OneClickCheckout\MagicAddressService\Client as MagicAddressServiceClient;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\MerchantPluginProvider\Service as MagicCheckoutPluginService;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\PrepayCODProvider\Service as MagicPrepayCODProviderService;

class ApiServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    protected $env;

    const AUTHZ_CLIENT_TIMEOUT_SEC = 10;

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

        // attaching contact observer
        $entityClass = E::getEntityClass(E::CONTACT);
        $entityObserverClass = E::getEntityObserverClass(E::CONTACT);
        $entityClass::observe($entityObserverClass);

        // attaching merchant observer
        $entityClass = E::getEntityClass(E::MERCHANT);
        $entityClass::observe(RZP\Models\Base\MerchantObserver::class);

        // attach audit observer
        foreach (E::AUDITED_ENTITIES as $entity)
        {
            $entityClass = E::getEntityClass($entity);
            $entityClass::observe(RZP\Models\Base\AuditObserver::class);
        }

        // attach instrumentation observer to instrumented entities
        foreach (E::INSTRUMENTED_ENTITIES as $entity)
        {
            $entityClass = E::getEntityClass($entity);
            $entityClass::observe(EntityInstrumentationObserver::class);
        }

        // attach DB Migration metrics observer to DB migration entities
        $this->isRequestSampled = $this->getSamplingCondition();

        if ($this->isRequestSampled === true)
        {
            foreach (E::DB_MIGRATION_ENTITIES as $entity)
            {
                $entityClass = E::getEntityClass($entity);
                $entityClass::observe(DbMigrationMetricsObserver::class);
            }
        }

        // attach account service sync event observer to entities synced between API and account service
        foreach (E::ACS_SYNCED_ENTITIES as $entity) {
            $entityClass = E::getEntityClass($entity);
            if (is_subclass_of($entityClass, RZP\Models\Base\PublicEntity::class) === false)
            {
                throw new RZP\Exception\LogicException('only sub classes of PublicEntity can be synced, ' .
                    'because they provide getMerchantId()');
            }
            $entityClass::observe(Acs\SyncEventObserver::class);
        }
    }

    public function getSamplingCondition()
    {
        $samplePercent = floatval($this->app['config']->get('app.db_migration_metrics_sampling_percent'));
        return rand() % 100000 < $samplePercent * 1000;
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

        $this->app->singleton('wda-client', function($app)
        {
            return new WDAService($app);
        });

        $this->app->singleton('instance', function($app)
        {
            return new AwsInstance($app);
        });

        $this->app->singleton('vendor-payment', function($app)
        {
            return new VendorPaymentService($app);
        });

        $this->app->singleton('accounting-payouts', function($app)
        {
            return new AccountingPayouts\Service($app);
        });

        $this->app->singleton('vendor-portal', function($app)
        {
            return new VendorPortalService($app);
        });

        $this->app->singleton('accounting-integration-service', function($app)
        {
            return new AccountingIntegrationService($app);
        });

        $this->app->singleton('tax-payments', function($app)
        {
            return new TaxPayments\Service($app);
        });

        $this->app->singleton('gateway', function($app)
        {
            return new GatewayManager($app);
        });

        $this->app->bind('exception.handler', function($app)
        {
            return new \RZP\Exception\Handler($app);
        });

        $this->app->singleton('razorx', function($app)
        {
            return new RazorXClient($app);
        });

        $this->app->singleton('hubspot', function($app)
        {
            return new HubspotClient($app);
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

        $this->app->singleton('mpan.cardVault', function($app)
        {
            $cardVaultMock = $app['config']->get('applications.card_vault.mock');

            if ($cardVaultMock === true)
            {
                return new Mock\CardVault($app);
            }

            return new CardVault($app, 'mpan');
        });

        $this->app->singleton('razorpayx.cardVault', function($app)
        {
            $cardVaultMock = $app['config']->get('applications.card_vault.mock');

            if ($cardVaultMock === true)
            {
                return new Mock\CardVault($app);
            }

            return new CardVault($app, 'razorpayx');
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

        $this->app->singleton('card.payments', function($app)
        {
            $cpsMock = $app['config']->get('applications.card_payment_service.mock');

            if ($cpsMock === true)
            {
                return new Mock\CardPaymentService();
            }

            return new CardPaymentService();
        });

        $this->app->singleton('upi.payments', function($app)
        {
            $upsMock = $app['config']->get('applications.upi_payment_service.mock');

            if ($upsMock === true)
            {
                return new UpiPayment\Mock\Service();
            }

            return new UpiPayment\Service();
        });

        $this->app->singleton('nbplus.payments', function($app)
        {
            $nbPlusMock = $app['config']->get('applications.nbplus_payment_service.mock');

            if ($nbPlusMock === true)
            {
                return new Mock\NbPlus\Service();
            }

            return new NbPlus\Service();
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

        $this->app->singleton('salesforce', function($app)
        {
            $salesForceMock = $app['config']->get('applications.salesforce.mock');

            if ($salesForceMock === true)
            {
                return new Mock\SalesForceClient($app);
            }

            return new SalesForceClient($app);
        });

        $this->app->singleton('gateway_downtime_metric', function($app)
        {
            return new DowntimeMetric();
        });

        $this->app->singleton('segment-analytics', function($app)
        {
            return new Segment\SegmentAnalyticsClient($app);
        });

        $this->app->singleton('appsflyer', function($app)
        {
            return new AppsflyerClient($app);
        });

        $this->app->singleton('x-segment', function($app)
        {
            return new Segment\XSegmentClient($app);
        });

        $this->app->singleton('plugins-segment', function($app)
        {
            return new Segment\PluginsSegmentClient($app);
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

        $this->app->singleton('druid.service', function ($app)
        {
            $druidServiceMock = $app['config']->get('services.druid.mock');

            if ($druidServiceMock === true)
            {
                return new MockDruidService();
            }

            return new DruidService();
        });

        $this->app->singleton('datalake.presto', function ($app)
        {
            $dataLakePrestoServiceMock = $app['config']->get('services.presto.mock');

            if ($dataLakePrestoServiceMock === true)
            {
                return new RZP\Services\Mock\DataLakePresto();
            }

            return new DataLakePresto();
        });

        $this->app->singleton('apache.pinot', function ($app)
        {
            $apachePinotServiceMock = $app['config']->get('services.apache_pinot.mock');

            if ($apachePinotServiceMock === true)
            {
                return new RZP\Services\Mock\ApachePinotClient();
            }

            return new ApachePinotClient();
        });

        $this->app->singleton('merchantRiskClient', function ($app)
        {
            return new MerchantRiskClient();
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

        $this->app->singleton('dcs', function($app)
        {
            $dcsServiceMock = $app['config']->get('applications.dcs.mock');

            if ($dcsServiceMock === true)
            {
                return new Mock\DcsServiceClient($app);
            }

            return new DcsService($app);
        });

        $this->app->singleton('module', function($app)
        {
            return new RZP\Modules\Manager($app);
        });

        $this->app->singleton('paymentlinkservice', function($app)
        {
            return new PaymentLinkService($app);
        });

        $this->app->singleton('nocodeappsservice', function($app)
        {
            return new \NoCodeAppsService($app);
        });

        $this->app->singleton('bbpsService', function($app)
        {
            return new Bbps\Service($app);
        });

        $this->app->singleton('outbox', function ($app) {
            $encrypter = new AES256GCMEncrypt(env("OUTBOX_ENCRYPTION_KEY"));
            $encoder   = new JsonEncoder();
            $repo      = new Repository($app['config']->get('database.default'));
            return new Core($encrypter, $encoder, $repo, $app['trace']);
        });

        $this->app->singleton('keyless_header', function ($app) {
            $identifier = config('app.keyless_header.identifier');
            $sender_public_key = config('app.keyless_header.sender.public_key');
            $sender_private_key = config('app.keyless_header.sender.private_key');
            $receiver_public_key = config('app.keyless_header.receiver.public_key');
            return new KeylessHeader(
                $identifier,
                hex2bin($receiver_public_key),
                hex2bin($sender_public_key),
                hex2bin($sender_private_key)
            );
        });

        $this->app->singleton('circuit_breaker', function ($app) {
            $redis = Redis::Connection()->client();

            return new CircuitBreaker(
                new RedisClusterStore($redis),
                'api'
            );

        });

        $this->app->singleton(Acs\SyncEventManager::SINGLETON_NAME, function($app)
        {
            return new Acs\SyncEventManager($app);
        });

        $this->app->singleton(DbRequestsBeforeMigrationMetric::class, function($app)
        {
            return new DbRequestsBeforeMigrationMetric($app);
        });


        $this->registerShield();

        $this->registerShieldSlackClient();

        $this->registerRedisDualWrite();

        $this->registerApiMutex();

        $this->registerRedis();

        $this->registerMaxMind();

        $this->registerRaven();

        $this->registerPayoutLinks();

        $this->registerReminders();

        $this->registerNonBlockingHttp();

        $this->registerSmartRouting();

        $this->registerGovernor();

        $this->registerDoppler();

        $this->registerBatchService();

        $this->registerScrooge();

        $this->registerRazorflow();

        $this->registerElfin();

        $this->registerExchange();

        $this->registerQueueableEntityResolver();

        $this->registerMorphRelationMaps();

        $this->registerSesClient();

        $this->registerDrip();

        $this->registerSns();

        $this->registerWorkflow();

        $this->registerGeolocation();

        $this->registerPincodeSearch();

        $this->registerDatabaseConnection();

        $this->registerMyOperator();

        $this->registerKubernetesClient();

        $this->registerFTSCreateAccount();

        $this->registerFTSRegisterAccount();

        $this->registerFTSFundTransfer();

        $this->registerMozart();

        $this->registerHyperVerge();

        $this->registerMandateHQ();

        $this->registerCareServiceClient();

        $this->registerShippingProviderService();

        $this->registerShippingMethodProviderService();

        $this->registerShippingMethodsService();

        $this->registerShippingServiceClient();

        $this->registerRtoPredictionProviderService();

        $this->registerRtoPredictionServiceClient();

        $this->registerMagicAnalyticsProviderService();

        $this->registerIntegrationServiceClient();

        $this->registerRtoDashboardService();

        $this->registerMerchantConfigInShippingService();

        $this->registerRtoFileUploadAuditService();

        $this->registerRtoFeatureReasonProviderService();

        $this->registerRtoPredictionMerchantModelConfigService();

        $this->registerRtoPredictionMLModelConfigService();

        $this->registerFulfillmentOrderService();

        $this->registerMagicAddressProviderService();

        $this->registerMagicAddressServiceClient();

        $this->registerMagicPrepayCODProviderService();

        $this->registerFreshchatClient();

        $this->registerFreshdeskTicketService();

        $this->registerTokenService();

        $this->registerTerminalsService();

        $this->registerStorkService();

        $this->registerWorkflowsService();

        $this->registerRazorpayXClient();

        $this->registerSettlementsDashboard();

        // RSR-1970 changes
        $this->registerSettlementsMerchantDashboard();

        $this->registerEinvoiceClient();

        $this->registerSettlementApi();

        $this->registerSettlementsReminder();

        $this->registerDCSClient();

        $this->registerWalletApi();

        $this->registerHttpClients();

        $this->registerPGRouter();

        $this->registerBvsHttpClients();

        $this->registerAsvHttpClient();

        $this->registerAsvSdkClient();

        $this->registerPayoutServiceStatus();

        $this->registerPayoutServiceDetail();

        $this->registerCreditTransferPayoutServiceUpdate();

        $this->registerPayoutServiceCancel();

        $this->registerPayoutServiceRetry();

        $this->registerPayoutServiceRedis();

        $this->registerPayoutServiceSchedule();

        $this->registerPayoutServiceDashboardTimeslots();

        $this->registerOnHoldBeneEventUpdate();

        $this->registerOnHoldCron();

        $this->registerPayoutsCreateFailureProcessingCron();

        $this->registerPayoutsUpdateFailureProcessingCron();

        $this->registerUpdateFreePayout();

        $this->registerUpdateMerchantFeatureInPayoutService();

        $this->registerOnHoldSLAUpdate();

        $this->registerPayoutServiceDataConsistencyChecker();

        $this->registerPayoutServiceQueuedInitiate();

        $this->registerPayoutServiceCreate();

        $this->registerPayoutServiceWorkflow();

        $this->registerPayoutServiceGet();

        $this->registerPayoutServiceStatusReasonMap();

        $this->registerPayoutServiceBulkPayouts();

        $this->registerPayoutServiceFetch();

        $this->registerPayoutServiceUpdateAttachments();

        $this->registerFTSChannelNotification();

        $this->registerSettlementsPayout();

        $this->registerErrorMappingService();

        $this->registerBvsLegalDocumentManager();

        $this->registerMerchantRiskAlertClient();

        $this->registerDisputesClient();

        $this->registerPaymentsCrossBorderClient();

        $this->registerPhonepeDowntimeService();

        $this->registerDowntimeSlackNotificationService();

        $this->registerBankingAccountService();

        $this->registerMasterOnboarding();

        $this->registerXPayrollService();

        $this->registerCapitalCollectionsClient();

        $this->registerCapitalEarlySettlementsClient();

        $this->registerCapitalCardsClient();

        $this->registerCacheManager();

        $this->registerLedger();

        $this->registerDeveloperConsole();

        $this->registerMediaService();

        $this->registerSplitz();

        $this->registerGrowth();

        $this->registerPspx();

        $this->registerRelayService();

        $this->registerAuthzXPlatformEnforcerClient();

        $this->registerAuthzXPlatformAdminClient();

        $this->registerPartnerships();

        $this->registerLOSService();

        $this->registerSmartCollect();

        $this->registerCdsHttpClients();

        $this->registerKafkaProducerClient();

        $this->registerMagicCheckoutServiceClient();

        $this->registerMagicCheckoutCouponService();

        $this->registerMagicCodEngineService();

        $this->registerCheckoutService();

        $this->registerMagicCheckoutPluginService();
    }

    protected function registerCacheManager()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('cache.psr6', function ($app) {
            return new Psr16Adapter($app['cache.store']);
        });
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
            'api.redis',
            'bitly',
            'razorx',
            'es',
            'exception.handler',
            'gateway',
            'instance',
            'mailgun',
            'maxmind',
            'raven',
            'reminders',
            'batchService',
            'hyperVerge',
            'mandateHQ',
            'scrooge',
            'razorflow',
            'repo',
            'elfin',
            'segment',
            'eventManager',
            'upi.client',
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
            'nonBlockingHttp',
            'smartRouting',
            'governor',
            'doppler',
            'diag',
            'mozart',
            'hubspot',
            'salesforce',
            'freshdesk_client',
            'token_service',
            'terminals_service',
            'nocodeappsservice',
            'paymentlinkservice',
            'credcase_http_client',
            'pg_router',
            'bvs_http_client',
            'error_mapper',
            'bvs_legal_document_manager',
            'sms_sync',
            'cache',
            'cache.store',
            'cache.psr6',
            'ledger',
            'developer_console',
            'media_service',
            Acs\SyncEventManager::SINGLETON_NAME,
            'outbox',
            'dcs',
            'splitzService',
            'bbpsService',
            'smartcollect',
            'cds_http_client',
            AsvConstant::ASV_HTTP_CLIENT,
            'kafkaProducerClient',
            ASVV2Constant::ASV_SDK_CLIENT
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

    protected function registerPayoutLinks()
    {
        $this->app->bind('payout-links', function($app)
        {
            return new PayoutLinks($app);
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

    protected function registerReminders()
    {
        $this->app->bind('reminders', function($app)
        {
            $mock = $app['config']->get('applications.reminders.mock');

            $implementation = $mock ? Mock\Reminders::class : Reminders::class;

            return new $implementation($app);
        });
    }

    protected function registerNonBlockingHttp()
    {
        $this->app->bind('nonBlockingHttp', function($app)
        {
            $implementation = NonBlockingHttp::class;

            return new $implementation($app);
        });
    }

    protected function registerSmartRouting()
    {
        $this->app->bind('smartRouting', function($app)
        {
            $smartRoutingMock = $app['config']->get('applications.smart_routing.mock');

            if ($smartRoutingMock === true)
            {
                return new Mock\SmartRouting($app);
            }

            return new SmartRouting($app);
        });
    }

    protected function registerGovernor()
    {
        $this->app->singleton('governor', function($app)
        {
            $goverorMock = $app['config']->get('applications.governor.mock');

            if ($goverorMock === true)
            {
                return new Mock\GovernorService($app);
            }

            return new GovernorService($app);
        });
    }

    protected function registerDoppler()
    {
        $this->app->singleton('doppler', function($app)
        {
            $dopplerMock = $app['config']->get('applications.doppler.mock');

            $dopplerTopic = $app['config']->get('applications.doppler.topic');

            if ($dopplerMock === true)
            {
                return new Mock\Doppler($app, $dopplerTopic);
            }

            return new Doppler($app, $dopplerTopic);
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

    protected function registerHyperVerge()
    {
        $this->app->bind('hyperVerge', function($app)
        {
            $mock = $app['config']->get('applications.hyper_verge.mock');

            $implementation = $mock ? Mock\HyperVerge\HyperVerge::class : RZP\Services\HyperVerge::class;

            return new $implementation($app);
        });
    }

    // phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
    protected function registerMandateHQ()
    {
        $this->app->bind('mandateHQ', function($app)
        {
            $mock = $app['config']->get('applications.mandate_hq.mock');

            $implementation = $mock ? Mock\MandateHQ::class : RZP\Services\MandateHQ::class;

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

    protected function registerRazorflow()
    {
        $this->app->bind('razorflow', function($app)
        {
            $mock = $app['config']->get('applications.razorflow.mock');

            $implementation = $mock ? Mock\Razorflow::class : Razorflow::class;

            return new $implementation($app);
        });
    }

    protected function registerRedisDualWrite()
    {
        $this->app->singleton('redisdualwrite', function($app)
        {
            $lockMock = $app['config']->get('applications.redisdualwrite.skip_dual_write');

            if ($lockMock === true)
            {
                return Redis::Connection();
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

            $mutex = new Mutex($app);

            $mutex->setRedisClient(Redis::Connection('mutex_redis'));

            return $mutex;
        });
    }

    protected function registerRedis()
    {
        $this->app->singleton('api.redis', function($app) {

            $mock = $app['config']->get('services.redis.mock');

            if ($mock === true)
            {
                return Redis::Connection();
            }

            return new RedisService($app);
        });
    }

    protected function registerMozart()
    {
        $this->app->bind('mozart', function($app)
        {
            $mock = $app['config']->get('applications.mozart.mock');

            $implementation = $mock ? Mock\Mozart::class : Mozart::class;

            return new $implementation($app);
        });
    }

    // phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
    public function registerRazorpayXClient()
    {
        $this->app->bind('razorpayXClient', function($app)
        {
            $mock = $app['config']->get('applications.razorpayx_client.' . $this->app['rzp.mode'] . '.mock');

            $implementation = $mock ? Mock\RazorpayXClient::class : RazorpayXClient::class;

            return new $implementation($app);
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
            'payment_page_item'         => PaymentPageItem\Entity::class,

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
            'settlement_transfer'       => Settlement\Transfer\Entity::class,
            'payout'                    => Payout\Entity::class,
            'transaction'               => Transaction\Entity::class,
            'fund_account_validation'   => FundAccount\Validation\Entity::class,
            'fund_transfer_attempt'     => FundTransfer\Attempt\Entity::class,
            'customer_transaction'      => Customer\Transaction\Entity::class,
            'external'                  => External\Entity::class,
            'credit_transfer'           => CreditTransfer\Entity::class,

            'bank_account'              => BankAccount\Entity::class,
            'wallet_account'            => WalletAccount\Entity::class,
            'vpa'                       => Vpa\Entity::class,
            'virtual_account'           => VirtualAccount\Entity::class,
            'bank_transfer'             => BankTransfer\Entity::class,
            'virtual_account_tpv'       => VirtualAccountTpv\Entity::class,

            'subscription'              => Subscription\Entity::class,
            'promotion'                 => Promotion\Entity::class,

            'dispute'                   => Dispute\Entity::class,

            'workflow_action'           => Action\Entity::class,

            'merchant_request'          => MerchantRequest\Entity::class,

            'subscription_registration' => SubscriptionRegistration\Entity::class,
            'paper_mandate'             => PaperMandate\Entity::class,

            'payment_page'              => PaymentLink\Entity::class,

            'contact'                   => Contact\Entity::class,

            'entity_origin'             => EntityOrigin\Entity::class,

            'application'               => Application\Entity::class,

            'commission'                => Commission\Entity::class,
            'commission_invoice'        => Commission\Invoice\Entity::class,
            'stakeholder'               => Merchant\Stakeholder\Entity::class,

            'options'                   => Options\Entity::class,

            'banking_account'           => BankingAccount\Entity::class,

            'balance'                   => Merchant\Balance\Entity::class,

            'settlement.ondemand'       => Settlement\Ondemand\Entity::class,

            'credit_repayment'          => CreditRepayment\Entity::class,

            'repayment_breakup'         => RZP\Models\CapitalTransaction\Entity::class,

            'interest_waiver'           => RZP\Models\CapitalTransaction\Entity::class,

            'installment'               => RZP\Models\CapitalTransaction\Entity::class,

            'charge'                    => RZP\Models\CapitalTransaction\Entity::class,

            'partner_activation'        => RZP\Models\Partner\Activation\Entity::class,

            'payouts_batch'             => RZP\Models\Payout\Batch\Entity::class,

            'qr_code'                   => RZP\Models\QrCode\NonVirtualAccountQrCode\Entity::class,
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

        $this->app['trace']->pushNamedProcessor($apiProcessor);
    }

    protected function registerGatewayProcessors()
    {
        $apiProcessor = new RZP\Trace\GatewayTraceProcessor($this->app);

        $this->app['trace']->pushNamedProcessor($apiProcessor, 'gateway');
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

    protected function registerShieldSlackClient()
    {
        $this->app->singleton('shield.slack', function($app)
        {
            $mock = $app['config']->get('applications.shield.slack.mock');

            $implementation = $mock ? Mock\ShieldSlackClient::class : ShieldSlackClient::class;

            return new $implementation;
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


    protected function registerAuthzXPlatformEnforcerClient()
    {
        $this->app->singleton('authzXPlatformEnforcer', function($app)
        {
            $config = $app['config']->get('applications.authzXPlatformEnforcer');

            $mock = $config['mock'];

            if ($mock === true)
            {
                return new Mock\AuthzEnforcerClient();
            }

            $client = new Client([
                'base_uri' => $config['url'],
                'timeout'  => self::AUTHZ_CLIENT_TIMEOUT_SEC,
                'auth'     => [
                    $config['auth']['username'],
                    $config['auth']['password'],
                ],
            ]);

            $configuration = new Configuration;

            $configuration->setUsername($config['auth']['username']);

            $configuration->setPassword($config['auth']['password']);

            $configuration->setHost($config['url']);

            return new EnforcerAPIApi($client, $configuration);
        });
    }

    protected function registerAuthzXPlatformAdminClient()
    {
        $this->app->singleton('authzXPlatformAdmin', function($app)
        {
            $config = $app['config']->get('applications.authzXPlatformAdmin');

            $mock = $config['mock'];

            if ($mock === true)
            {
                return new Mock\AuthzAdminClient();
            }

            $client = new Client([
                'base_uri' => $config['url'],
                'timeout'  => self::AUTHZ_CLIENT_TIMEOUT_SEC,
                'auth'     => [
                    $config['auth']['username'],
                    $config['auth']['password'],
                ],
            ]);

            $configuration = new AdminConfiguration;

            $configuration->setUsername($config['auth']['username']);

            $configuration->setPassword($config['auth']['password']);

            $configuration->setHost($config['url']);

            return new AdminAPIApi($client, $configuration);
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
        $this->app->singleton('proxysql.config', function()
        {
            return new RZP\Base\Database\Config();
        });

        $this->app->singleton('db.connector.mysql', function($app)
        {
            return (new MySqlConnector($app));
        });

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
            $auth   = $this->app['basicauth'];

            return new $impl($this->app->trace, $config, $auth);
        });
    }

    protected function registerKubernetesClient()
    {
        $this->app->singleton('k8s_client', function($app)
        {
            return new KubernetesClient($app);
        });
    }

    // phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
    protected function registerFTSCreateAccount()
    {
        $this->app->bind('fts_create_account', function($app)
        {
            $mock = $app['config']->get('applications.fts.mock');

            $implementation = $mock ? Mock\FTS\CreateAccount::class : FTS\CreateAccount::class;

            return new $implementation($app);
        });
    }

    // phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
    protected function registerFTSRegisterAccount()
    {
        $this->app->bind('fts_register_account', function($app)
        {
            $mock = $app['config']->get('applications.fts.mock');

            $implementation = $mock ? Mock\FTS\RegisterAccount::class : FTS\RegisterAccount::class;

            return new $implementation($app);
        });
    }

    // phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
    protected function registerFTSFundTransfer()
    {
        $this->app->bind('fts_fund_transfer', function($app)
        {
            $mock = $app['config']->get('applications.fts.mock');

            $implementation = $mock ? Mock\FTS\FundTransfer::class : FTS\FundTransfer::class;

            return new $implementation($app);
        });
    }

    protected function registerFreshchatClient()
    {
        $this->app->singleton('freshchat_client', function($app)
        {
            return new FreshchatClient($app);
        });
    }

    protected function registerCareServiceClient()
    {
        $this->app->singleton('care_service', function($app)
        {
            return new CareServiceClient($app);
        });
    }

    protected function registerShippingProviderService()
    {
        $this->app->singleton('shipping_provider_service', function($app)
        {
            return new ShippingProviderService($app);
        });
    }

    protected function registerShippingMethodProviderService()
    {
        $this->app->singleton('shipping_method_provider_service', function($app)
        {
            return new ShippingMethodProviderService($app);
        });
    }

    protected function registerShippingMethodsService()
    {
        $this->app->singleton('shipping_methods_service', function($app)
        {
            return new ShippingMethodsService($app);
        });
    }

    protected function registerShippingServiceClient()
    {
        $this->app->singleton('shipping_service_client', function($app)
        {
            return new ShippingServiceClient($app);
        });
    }

    protected function registerRtoPredictionProviderService()
    {
        $this->app->singleton('rto_prediction_provider_service', function($app)
        {
            return new RtoPredictionProviderService($app);
        });
    }

    protected function registerRtoPredictionServiceClient()
    {
        $this->app->singleton('rto_prediction_service_client', function($app)
        {
            return new RtoPredictionServiceClient($app);
        });
    }

    protected function registerMagicAnalyticsProviderService()
    {
        $this->app->singleton('magic_analytics_provider_service', function($app)
        {
            return new MagicAnalyticsProviderService($app);
        });
    }

    protected function registerIntegrationServiceClient()
    {
        $this->app->singleton('integration_service_client', function($app)
        {
            return new IntegrationServiceClient($app);
        });
    }

    protected function registerMerchantConfigInShippingService()
    {
        $this->app->singleton(ShippingService\MerchantConfig\Service::SHIPPING_SERVICE_MERCHANT_CONFIG, function($app)
        {
            return new ShippingService\MerchantConfig\Service($app);
        });
    }

    protected function registerRtoDashboardService()
    {
        $this->app->singleton('rto_dashboard_service', function($app)
        {
            return new RtoDashboardService($app);
        });
    }

    protected function registerRtoFileUploadAuditService()
    {
        $this->app->singleton('rto_file_upload_audit_service', function($app)
        {
            return new RtoFileUploadAuditService($app);
        });
    }

    protected function registerRtoFeatureReasonProviderService()
    {
        $this->app->singleton('rto_feature_reason_provider_service', function($app)
        {
            return new RtoFeatureReasonProviderService($app);
        });
    }

    protected function registerRtoPredictionMLModelConfigService()
    {
        $this->app->singleton('rto_prediction_mlmodel_configs', function($app)
        {
            return new RtoPredictionConfigs\MLModelConfigs\Service($app);
        });
    }

    protected function registerRtoPredictionMerchantModelConfigService()
    {
        $this->app->singleton('rto_prediction_merchant_model_configs', function($app)
        {
            return new RtoPredictionConfigs\MerchantMLModelConfigs\Service($app);
        });
    }

    protected function registerFulfillmentOrderService()
    {
        $this->app->singleton('fulfillment_order_service', function($app)
        {
            return new FulfillmentOrderService($app);
        });
    }

    protected function registerMagicAddressProviderService()
    {
        $this->app->singleton('magic_address_provider_service', function($app)
        {
            return new MagicAddressProviderService($app);
        });
    }

    protected function registerMagicAddressServiceClient()
    {
        $this->app->singleton('magic_address_service_client', function($app)
        {
            return new MagicAddressServiceClient($app);
        });
    }

    protected function registerMagicPrepayCODProviderService()
    {
        $this->app->singleton('magic_prepay_cod_provider_service', function($app)
        {
            return new MagicPrepayCODProviderService($app);
        });
    }

    protected function registerFreshdeskTicketService()
    {
        $this->app->singleton('freshdesk_client', function($app)
        {
            $ticketMock = $app['config']->get('applications.freshdesk.mock');

            if ($ticketMock === true)
            {
                return new Mock\FreshdeskTicketClient($app);
            }

            return new FreshDeskTicketClient($app);
        });
    }

    protected function registerTerminalsService()
    {
        $this->app->singleton('terminals_service', function ($app)
        {
            $terminalsServiceMock = $app['config']->get('applications.terminals_service.mock');

            if ($terminalsServiceMock === true)
            {
                return new Mock\TerminalsService($app);
            }

            return new TerminalsService($app);
        });

    }

    protected function registerStorkService()
    {
        $this->app->singleton('stork_service', function ($app)
        {
            if ($app['config']->get('stork.mock') === true)
            {
                return new Mock\Stork;
            }

            return new Stork;
        });
    }

    protected function registerPayoutServiceCreate()
    {
        $this->app->singleton(PayoutService\Create::PAYOUT_SERVICE_CREATE, function($app)
        {
            return new PayoutService\Create($app);
        });
    }

    protected function registerPayoutServiceGet()
    {
        $this->app->singleton(PayoutService\Get::PAYOUT_SERVICE_GET, function($app)
        {
            return new PayoutService\Get($app);
        });
    }

    protected function registerPayoutServiceStatusReasonMap()
    {
        $this->app->singleton(PayoutService\StatusReasonMap::PAYOUT_SERVICE_STATUS_REASON_MAP, function($app)
        {
            return new PayoutService\StatusReasonMap($app);
        });
    }

    protected function registerPayoutServiceBulkPayouts()
    {
        $this->app->singleton(PayoutService\BulkPayout::PAYOUT_SERVICE_BULK_PAYOUTS, function($app)
        {
            return new PayoutService\BulkPayout($app);
        });
    }


    protected function registerPayoutServiceStatus()
    {
        $this->app->singleton(PayoutService\Status::PAYOUT_SERVICE_STATUS, function($app)
        {
           return new PayoutService\Status($app);
        });
    }

    protected function registerPayoutServiceDetail()
    {
        $this->app->singleton(PayoutService\Details::PAYOUT_SERVICE_DETAIL, function($app)
        {
            return new PayoutService\Details($app);
        });
    }

    protected function registerCreditTransferPayoutServiceUpdate()
    {
        $this->app->singleton(PayoutService\CreditTransferPayoutUpdate::CREDIT_TRANSFER_PAYOUT_SERVICE_UPDATE, function($app)
        {
            return new PayoutService\CreditTransferPayoutUpdate($app);
        });
    }

    protected function registerPayoutServiceCancel()
    {
        $this->app->singleton(PayoutService\Cancel::PAYOUT_SERVICE_CANCEL, function($app)
        {
            return new PayoutService\Cancel($app);
        });
    }

    protected function registerPayoutServiceRetry()
    {
        $this->app->singleton(PayoutService\Retry::PAYOUT_SERVICE_RETRY, function($app)
        {
            return new PayoutService\Retry($app);
        });
    }

    protected function registerPayoutServiceRedis()
    {
        $this->app->singleton(PayoutService\Redis::PAYOUT_SERVICE_REDIS, function($app)
        {
            return new PayoutService\Redis($app);
        });
    }

    protected function registerPayoutServiceSchedule()
    {
        $this->app->singleton(PayoutService\Schedule::PAYOUT_SERVICE_SCHEDULE, function($app)
        {
            return new PayoutService\Schedule($app);
        });
    }

    protected function registerPayoutServiceDashboardTimeslots()
    {
        $this->app->singleton(PayoutService\DashboardScheduleTimeSlots::PAYOUT_SERVICE_DASHBOARD_TIME_SLOTS, function($app)
        {
            return new PayoutService\DashboardScheduleTimeSlots($app);
        });
    }

    protected function registerOnHoldBeneEventUpdate()
    {
        $this->app->singleton(PayoutService\OnHoldBeneEvent::PAYOUT_SERVICE_BENE_EVENT_UPDATE, function($app)
        {
            return new PayoutService\OnHoldBeneEvent($app);
        });
    }

    protected function registerOnHoldCron()
    {
        $this->app->singleton(PayoutService\OnHoldCron::PAYOUT_SERVICE_ON_HOLD_CRON, function($app)
        {
            return new PayoutService\OnHoldCron($app);
        });
    }

    protected function registerPayoutsCreateFailureProcessingCron()
    {
        $this->app->singleton(PayoutService\PayoutsCreateFailureProcessingCron::PAYOUTS_CREATE_FAILURE_PROCESSING_CRON, function($app)
        {
            return new PayoutService\PayoutsCreateFailureProcessingCron($app);
        });
    }

    protected function registerPayoutsUpdateFailureProcessingCron()
    {
        $this->app->singleton(PayoutService\PayoutsUpdateFailureProcessingCron::PAYOUTS_UPDATE_FAILURE_PROCESSING_CRON, function($app)
        {
            return new PayoutService\PayoutsUpdateFailureProcessingCron($app);
        });
    }

    protected function registerUpdateFreePayout()
    {
        $this->app->singleton(PayoutService\FreePayout::PAYOUT_SERVICE_FREE_PAYOUT, function($app)
        {
            return new PayoutService\FreePayout($app);
        });
    }

    protected function registerUpdateMerchantFeatureInPayoutService()
    {
        $this->app->singleton(PayoutService\MerchantConfig::PAYOUT_SERVICE_MERCHANT_CONFIG, function($app)
        {
            return new PayoutService\MerchantConfig($app);
        });
    }

    protected function registerOnHoldSLAUpdate()
    {
        $this->app->singleton(PayoutService\OnHoldSLAUpdate::PAYOUT_SERVICE_ON_HOLD_SLA_UPDATE, function($app)
        {
            return new PayoutService\OnHoldSLAUpdate($app);
        });
    }

    protected function registerPayoutServiceDataConsistencyChecker()
    {
        $this->app->singleton(PayoutService\DataConsistencyChecker::PAYOUT_SERVICE_DATA_CONSISTENCY_CHECKER, function($app)
        {
            return new PayoutService\DataConsistencyChecker($app);
        });
    }

    protected function registerPayoutServiceQueuedInitiate()
    {
        $this->app->singleton(PayoutService\QueuedInitiate::PAYOUT_SERVICE_QUEUED_INITIATE, function($app)
        {
            return new PayoutService\QueuedInitiate($app);
        });
    }

    protected function registerPayoutServiceFetch()
    {
        $this->app->singleton(PayoutService\Fetch::PAYOUT_SERVICE_FETCH, function($app)
        {
            return new PayoutService\Fetch($app);
        });
    }

    protected function registerPayoutServiceUpdateAttachments()
    {
        $this->app->singleton(PayoutService\UpdateAttachments::PAYOUT_SERVICE_UPDATE_ATTACHMENTS, function($app)
        {
            return new PayoutService\UpdateAttachments($app);
        });
    }

    protected function registerRelayService()
    {
        $this->app->singleton('relay', function($app)
        {
            return new Relay\Config($app);
        });
    }

    protected function registerTokenService()
    {
        $this->app->singleton('token_service', function($app)
        {
            return new TokenService($app);
        });
    }

    protected function registerWorkflowsService()
    {
        $this->app->singleton('workflow_service', function($app)
        {
            $useMock = $app['config']->get('applications.workflows.mock');

            if ($useMock === true)
            {
                return new Mock\WorkflowService($app);
            }

            return new WorkflowService($app);
        });
    }

    protected function registerWalletApi()
    {
        $this->app->singleton('wallet_api', function($app)
        {
            return new Wallet\Api($app);
        });
    }

    protected function registerSettlementsDashboard()
    {
        $this->app->singleton('settlements_dashboard', function($app)
        {
            $mock = $app['config']->get('applications.settlements_service.dashboard.mock');

            $implementation = ($mock === true) ? Mock\Settlements\Dashboard::class : Settlements\Dashboard::class;

            return new $implementation($app);
        });
    }

    /**
     * RSR-1970 register settlement merchant dashboard class for settlements
     * merchant dashboard requests
     */
    protected function registerSettlementsMerchantDashboard()
    {
        $this->app->singleton('settlements_merchant_dashboard', function($app)
        {
            $mock = $app['config']->get('applications.settlements_service.merchant_dashboard.mock');

            $implementation = ($mock === true) ? Mock\Settlements\MerchantDashboard::class : Settlements\MerchantDashboard::class;

            return new $implementation($app);
        });

    }

    protected function registerSettlementsReminder()
    {
        $this->app->singleton('settlements_reminder', function($app)
        {
            return new Settlements\Reminder($app);
        });
    }

    protected function registerSettlementsPayout()
    {
        $this->app->singleton('settlements_payout', function($app)
        {
            return new Settlements\Payout($app);
        });
    }

    protected function registerSettlementApi()
    {
        $this->app->singleton('settlements_api', function($app)
        {
            $mock = $app['config']->get('applications.settlements_service.api.mock');

            $implementation = ($mock === true) ? Mock\Settlements\Api::class : Settlements\Api::class;

            return new $implementation($app);
        });
    }

    protected function registerDCSClient()
    {
        $this->app->singleton('dcs_config_service', function($app)
        {
            return new DcsConfigService($app);
        });
    }

    protected function registerEinvoiceClient()
    {
        $this->app->singleton('einvoice_client', function($app)
        {
            return new EInvoice($app);
        });
    }

    protected function registerXPayrollService(){

        $this->app->singleton('xpayroll', function($app)
        {
            return new XPayrollService($app);
        });
    }

    protected function registerCapitalCollectionsClient()
    {
        $this->app->singleton('capital_collections', function($app)
        {
            return new CapitalCollectionsClient($app);
        });
    }

    protected function registerCapitalEarlySettlementsClient()
    {
        $this->app->singleton('capital_early_settlements', function($app)
        {
            return new CapitalEarlySettlementClient($app);
        });
    }

    protected function registerCapitalCardsClient()
    {
        $this->app->singleton('capital_cards_client', function($app)
        {
            return new CapitalCardsClient($app);
        });
    }

    /**
     * Registers various http clients. E.g. for twirp client sdk's http client, for credcase.
     * It helps replacing http client implementation for tests, helping assert remote request/response.
     * @return void
     */
    protected function registerHttpClients()
    {
        $this->app->singleton('credcase_http_client', function ($app)
        {
            if ($app->runningUnitTests() === true)
            {
                return new Psr18ClientMock;
            }

            $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

            $this->env = $app['env'];

            if($this->env === 'bvt' or $this->env === 'automation' or $this->env === 'func' or $this->env === 'availability' or $this->env === 'perf' or $this->env === 'perf2')
            {
                return new MultiCurl($responseFactory, ['timeout' => 5]);
            }

            $options = ['timeout' => 1];
            $client = new MultiCurl($responseFactory, $options);
            return $client;
        });

        $this->app->singleton('edge_proxy_http_client', function ($app)
        {
            if ($app->runningUnitTests() === true)
            {
                return new Psr18ClientMock; // Returns mock client for unit tests to help make assertions.
            }

            $options = [
                'timeout' => 60, // No specific reason for 60s value. The value is bit larger on api gateway.
            ];

            return new MultiCurl(Psr17FactoryDiscovery::findResponseFactory(), $options);
        });

        $this->app->singleton('throttler_http_client', function ($app)
        {
            if ($app->runningUnitTests() === true)
            {
                return new Psr18ClientMock;
            }

            return new MultiCurl(Psr17FactoryDiscovery::findResponseFactory(), [
                'timeout' => 5,
            ]);
        });
    }

    // phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
    protected function registerPGRouter()
    {
        $this->app->bind('pg_router', function ($app)
        {
            $mock = $app['config']->get('applications.pg_router.mock');

            $implementation = $mock ? Mock\PGRouter::class : PGRouter::class;

            return new $implementation($app);
        });
    }
    /**
     * register bvs http client
     *
     * @return void
     */
    protected function registerBvsHttpClients()
    {
        $this->app->singleton('bvs_http_client', function($app) {
            if ($app->runningUnitTests() === true)
            {
                return new Psr18ClientMock;
            }

            $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
            $options         = ['timeout' => 5];
            $client          = new MultiCurl($responseFactory, $options);

            return $client;
        });
    }

    /**
     * register asv http client
     *
     * @return void
     */
    protected function registerAsvHttpClient()
    {
        $this->app->singleton(AsvConstant::ASV_HTTP_CLIENT, function($app) {
            if ($app->runningUnitTests() === true)
            {
                return new Psr18ClientMock;
            }
            $timeout = $app[AsvConstant::CONFIG][AsvConstant::ACCOUNT_SERVICE][AsvConstant::ASV_HTTP_CLIENT_TIMEOUT];
            $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
            $options         = ['timeout' => intval($timeout)];
            $client          = new MultiCurl($responseFactory, $options);

            return $client;
        });
    }

    /**
     * register ASV SDK Client
     *
     * @return void
     */
    protected function registerAsvSdkClient()
    {
        $this->app->singleton(ASVV2Constant::ASV_SDK_CLIENT, function ($app) {

            $asvConfig = $app->config->get(ASVV2Constant::ASV_CONFIG);

            $username = $asvConfig[ASVV2Constant::USERNAME];
            $password = $asvConfig[ASVV2Constant::PASSWORD];
            $grpcHost = $asvConfig[ASVV2Constant::GRPC_HOST];

            $credentials = new AsvSdkConfig\Credentials($username, $password);
            $logger = $app['trace'];

            $asvSdkConfig = new AsvSdkConfig\Config();
            $asvSdkConfig->setHost($grpcHost)->setCredentials($credentials)->setLogger($logger)->setTraceCodeClass(TraceCode::Class);

            return new AsvSdkClient($asvSdkConfig);
        });
    }

    // phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
    protected function registerFTSChannelNotification()
    {
        $this->app->bind('fts_channel_notification', function($app)
        {
            return new FTS\ChannelNotification($app);
        });
    }

    protected function registerErrorMappingService()
    {
        $this->app->bind('error_mapper', function ($app)
        {
            return new ErrorMappingService($app);
        });
    }

    public function registerBvsLegalDocumentManager()
    {
        $this->app->singleton('bvs_legal_document_manager', function($app)
        {
            return new Merchant\AutoKyc\Bvs\BvsClient\BvsLegalDocumentManagerClient();
        });
    }

    public function registerKafkaProducerClient()
    {
        $this->app->singleton('kafkaProducerClient', function($app)
        {
            if ($app['config']->get('services.kafka.producer.mock') === true)
            {
                return new Mock\KafkaProducerClient;
            }

            return new KafkaProducerClient();
        });
    }

    protected function registerMerchantRiskAlertClient()
    {
        $this->app->singleton('merchant_risk_alerts', function($app)
        {
            if ($app['config']->get('services.merchant_risks_alerts.mock') === true)
            {
                return new Mock\MerchantRiskAlertClient;
            }

            return new MerchantRiskAlertClient();
        });
    }

    protected function registerDisputesClient()
    {
        $this->app->singleton('disputes', function($app)
        {
            if ($app['config']->get('services.disputes.mock') === true)
            {
                return new Mock\DisputesClient();
            }

            return new DisputesClient();
        });
    }

    protected function registerPaymentsCrossBorderClient()
    {
        $this->app->singleton('payments-cross-border', function($app)
        {
            if ($app['config']->get('applications.payments_cross_border_service.mock') === true)
            {
                return new Mock\PaymentsCrossBorderClient();
            }

            return new PaymentsCrossBorderClient();
        });
    }

    protected function registerPhonepeDowntimeService()
    {
        $this->app->singleton('phonepe', function($app)
        {
            $mock = $app['config']->get('applications.gateway_downtime.phonepe.mock');

            if($mock === true)
            {
                return new Mock\Phonepe($app);
            }

            return new Phonepe($app);
        });
    }

    protected function registerDowntimeSlackNotificationService()
    {
        $this->app->singleton("downtimeSlackNotification", function ($app)
        {
            $mock = $app['config']->get('applications.gateway_downtime.slack.mock');

            if ($mock === true)
            {
                return new Mock\DowntimeSlackNotification($app);
            }

            return new DowntimeSlackNotification($app);
        });
    }

    protected function registerBankingAccountService()
    {
        $this->app->singleton('banking_account_service', function ($app) {
            $mock = $app['config']->get('applications.banking_account_service.mock');

            $implementation = $mock ? Mock\BankingAccountService::class : BankingAccountService::class;

            return new $implementation($app);
        });
    }

    protected function registerMasterOnboarding()
    {
        $this->app->singleton('master_onboarding', function ($app)
        {
            $mock = $app['config']->get('applications.master_onboarding.mock');

            if ($mock === true)
            {
                return new RZP\Services\Mock\MasterOnboardingService();
            }
            else
            {
                return new MasterOnboardingService();
            }
        });
    }

    protected function registerLedger()
    {
        $this->app->bind('ledger', function($app)
        {
            $enabled = $app['config']->get('applications.ledger.enabled');

            $implementation = $enabled ? Ledger::class : Mock\Ledger::class;

            return new $implementation($app);
        });
    }

    protected function registerDeveloperConsole()
    {
        $this->app->bind('developer_console', function($app)
        {
            $enabled = $app['config']->get('applications.developer_console.enabled');

            $implementation = $enabled ? DeveloperConsole::class : Mock\DeveloperConsole::class;

            return new $implementation($app);
        });
    }

    protected function registerMediaService()
    {
        $this->app->bind('media_service', function($app)
        {
            $implementation =  Media::class;
            return new $implementation($app);
        });
    }

    protected function registerSplitz()
    {
        $this->app->singleton('splitzService', function ($app) {

            $mock = $app['config']->get('applications.splitz.mock');

            if ($mock === true)
            {
                return new RZP\Services\Mock\SplitzService();
            }

            return new SplitzService();
        });
    }

    protected function registerGrowth()
    {
        $this->app->singleton('growthService', function ($app) {

            $mock = $app['config']->get('applications.growth.mock');

            if ($mock === true)
            {
                return new RZP\Services\Mock\GrowthService();
            }

            return new GrowthService();
        });
    }

    protected function registerPspx()
    {
        $this->app->singleton('pspx', function ($app)
        {
            $mock = $app['config']->get('applications.pspx.mock');

            if ($mock === true)
            {
                return new RZP\Services\Pspx\Mock\Service();
            }

            return new RZP\Services\Pspx\Service();
        });

        $this->app->singleton('pspx_mandate', function ($app)
        {
            $mock = $app['config']->get('applications.pspx.mock');

            if ($mock === true)
            {
                return new RZP\Services\Pspx\Mock\Mandate();
            }

            return new RZP\Services\Pspx\Mandate();
        });
    }

    protected function registerPayoutServiceWorkflow()
    {
        $this->app->singleton(PayoutService\Workflow::PAYOUT_SERVICE_WORKFLOW, function($app)
        {
            return new PayoutService\Workflow($app);
        });
    }

    protected function registerLOSService()
    {
        $this->app->singleton('losService', function ($app) {

            $mock = $app['config']->get('applications.loan_origination_system.mock');

            if ($mock === true)
            {
                return new RZP\Services\Mock\LOSService();
            }

            return new RZP\Services\LOSService();
        });
    }

    protected function registerPartnerships()
    {
        $this->app->singleton('partnerships', function ($app) {

            $mock = $app['config']->get('applications.partnerships.mock');

            if ($mock === true)
            {
                return new RZP\Services\Mock\PartnershipsService();
            }

            return new RZP\Services\Partnerships\PartnershipsService();
        });
    }

    protected function registerSmartCollect()
    {
        $this->app->singleton('smartCollect', function($app)
        {
            $mock = $app['config']->get('applications.smart_collect.mock');

            $implementation = $mock ? Mock\SmartCollect::class : SmartCollect::class;

            return new $implementation($app);
        });
    }
    /**
     * register cds http client
     *
     * @return void
     */
    protected function registerCdsHttpClients()
    {
        $this->app->singleton('cds_http_client', function($app) {
            if ($app->runningUnitTests() === true)
            {
                return new Psr18ClientMock;
            }

            $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
            $options         = ['timeout' => 5];

            return new MultiCurl($responseFactory, $options);
        });
    }

    protected function registerMagicCheckoutServiceClient()
    {
        $this->app->singleton('magic_checkout_service_client', function($app)
        {
            return new MagicCheckoutServiceClient($app);
        });
    }

    protected function registerMagicCheckoutCouponService()
    {
        $this->app->singleton('magic_checkout_coupon_service', function($app)
        {
            return new MagicCheckoutCouponService($app);
        });
    }

    protected function registerMagicCodEngineService()
    {
        $this->app->singleton('magic_checkout_cod_engine_service', function($app)
        {
            return new MagicCheckoutCodEngineService($app);
        });
    }

    protected function registerCheckoutService(): void
    {
        $this->app->singleton('checkout_service', function($app)
        {
            $mock = $app['config']->get('applications.checkout_service.mock');

            $implementation = $mock ? Mock\CheckoutService::class : CheckoutService::class;

            return new $implementation($app);
        });
    }

    protected function registerMagicCheckoutPluginService()
    {
        $this->app->singleton('magic_checkout_plugin_service', function($app)
        {
            return new MagicCheckoutPluginService($app);
        });
    }
}
