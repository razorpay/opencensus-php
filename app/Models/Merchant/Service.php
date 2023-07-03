<?php

namespace RZP\Models\Merchant;


use ApiResponse;
use App;
use DB;
use EmailValidator\Validator as EmailValidator;
use Lib\PhoneBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response;
use Mail;
use Cache;
use Config;
use Request;
use RZP\Models\Merchant\OneClickCheckout\Constants as ShopifyConstants;

use Illuminate\Support\Str;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\OAuth;
use RZP\Http\Controllers\CareProxyController;
use RZP\Http\Controllers\MerchantController;
use RZP\Jobs\CapturePartnershipConsents;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Card\Network;
use RZP\Models\Card\Type;
use RZP\Models\Emi\DebitProvider;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Locale\Core as LocaleCore;
use RZP\Constants\Metric as ConstantMetric;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\RazorxTreatment as Experiment;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Merchant\Consent\Constants as ConsentConstant;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstants;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\User\Core as UserCore;
use RZP\Models\User\Service as UserService;
use Throwable;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Models\User;
use RZP\Models\Offer;
use RZP\Models\Payout;
use RZP\Models\Coupon;
use RZP\Models\Contact;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Address;
use RZP\Models\Pricing;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Models\Promotion;
use RZP\Models\Admin\Org;
use RZP\Models\Settlement;
use RZP\Http\CheckoutView;
use RZP\Base\JitValidator;
use RZP\Http\RequestHeader;
use RZP\Models\Admin\Admin;
use RZP\Constants\Timezone;
use RZP\Models\Application;
use RZP\Models\Admin\Group;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Services\DiagClient;
use RZP\Base\RuntimeManager;
use RZP\Models\Pricing\Plan;
use RZP\Models\Payment\Refund;
use RZP\Services\HubspotClient;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\ConfigKey;
use RZP\Modules\Migrate\Migrate;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Methods;
use RZP\Constants\Mode as Modes;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Admin as MainAdmin;
use RZP\Models\Merchant\Attribute;
use RZP\Jobs\MerchantHoldFundsSync;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Admin\Org\Hostname;
use RZP\Services\SalesForceClient;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Jobs\SubMerchantTaggingJob;
use RZP\Models\Settlement\Ondemand;
use RZP\Error\PublicErrorDescription;
use RZP\Services\CapitalCardsClient;
use RZP\Jobs\CallBackFillReferredApp;
use Razorpay\OAuth\Token as OAuthToken;
use RZP\Mail\Merchant\EsEnabledNotify;
use RZP\Exception\BadRequestException;
use RZP\Models\Partner\RateLimitBatch;
use RZP\Jobs\CallBackFillMerchantApps;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Http\Controllers\LOSController;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\RazorxTreatment;
use Razorpay\Spine\DataTypes\Dictionary;
use Razorpay\OAuth\Client as OAuthClient;
use RZP\Models\Partner\RateLimitConstants;
use RZP\Models\Comment\Core as CommentCore;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Settlement\SettlementTrait;
use RZP\Models\Batch\Header as BatchHeader;
use RZP\Models\Batch\Status as BatchStatus;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Mail\InstrumentRequest\StatusNotify;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Merchant\AutoKyc\Escalations;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Partner\PartnershipsRateLimiter;
use RZP\Models\Payment\Config as PaymentConfig;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\Pricing\Entity as PricingEntity;
use RZP\Models\BulkWorkflowAction as BulkAction;
use RZP\Models\RiskWorkflowAction as RiskAction;
use RZP\Models\Partner\Service as PartnerService;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Models\Settlement\Ondemand\FeatureConfig;
use Razorpay\OAuth\Application as OAuthApplication;
use RZP\Jobs\RemoveSubmerchantDashboardAccessJob;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Models\Merchant\PurposeCode\PurposeCodeList;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\PayoutLink\Service as PayoutLinkService;
use RZP\Services\Pagination\Entity as PaginationEntity;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Models\Merchant\Detail\Status as MerchantStatus;
use RZP\Constants\{HyperTrace, Mode, Product, Entity as CE, Environment};
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;
use RZP\Models\Merchant\Methods\DefaultMethodsForCategory;
use RZP\Notifications\Dashboard\Events as DashboardEvents;
use RZP\Models\Merchant\Balance\Ledger\Core as LedgerCore;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Gateway\Terminal\Service as TerminalService;
use RZP\Models\Merchant\Detail\SmsTemplates as SmsTemplates;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Models\Workflow\Action\Entity as WorkFlowActionEntity;
use RZP\Mail\Merchant\CreateSubMerchant as CreateSubMerchantMail;
use RZP\Models\Batch\Helpers\SubMerchant as SubMerchantBatchHelper;
use RZP\Models\RiskWorkflowAction\Constants as RiskActionConstants;
use RZP\Models\Partner\SubMerchantBatchUtility as SubMerchantBatchUtil;
use RZP\Models\Merchant\Detail\BusinessType as MerchantDetBusinessType;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;
use RZP\Models\Merchant\Balance\BalanceConfig\Service as BalanceConfigService;
use RZP\Mail\Merchant\CreateSubMerchantPartner as CreateSubMerchantPartnerForPG;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForPG;
use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantPartner as CreateSubMerchantPartnerForX;
use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForX;
use RZP\Mail\Merchant\Capital\LineOfCredit\CreateSubMerchantPartner as CreateSubMerchantPartnerForLOC;
use RZP\Mail\Merchant\Capital\CorporateCards\CreateSubMerchantPartner as CreateSubMerchantPartnerForCC;
use RZP\Mail\Merchant\Capital\LineOfCredit\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForLOC;
use RZP\Mail\Merchant\Capital\CorporateCards\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForCC;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\TrustedBadge;
use RZP\Models\Partner\Commission\Core as PartnerCommissionCore;
use RZP\Models\EntityOrigin\Core as EntityOriginCore;
use \RZP\Models\Workflow\Action\Entity as ActionEntity;
use RZP\Models\Merchant\HsCode\HsCodeList;
use RZP\Models\Merchant\Consent as Consent;
use RZP\Models\Merchant\Analytics\DataProcessor;

class Service extends Base\Service
{
    use Notify;
    use SettlementTrait;

    const COUPON_RESPONSE               = 'apply_coupon';
    const OAUTH_MAIL                    = 'oauth_mail';
    const TALLY_AUTH_OTP_MAIL           = 'tally_auth_otp_mail';
    const MERCHANT_MAIL                 = 'merchant_mail';
    const SUPPORT_DETAILS               = 'support_details';
    const ES_ON_DEMAND_ANNOUNCEMENT_TAG = 'es-on-demand.announcement-early-settlement';
    const OFFSET                        = 'offset';

    const DEFAULT_SUBMERCHANT_FETCH_LIMIT = 100;

    const SECOND    = 1;
    const MINUTE    = 60 * self::SECOND;
    const HOUR      = 60 * self::MINUTE;

    const REQUEST_TIMEOUT_MERCHANT_ANALYTICS   = 90;  // in seconds
    const REQUEST_TIMEOUT_GET_DATA_FOR_SEGMENT = 5;  // in seconds

    const MAX_TRANSACTION_RETRY = 2;

    const BOOTSTRAP_ACCESS_MAPS_CACHE_REQUEST_RULES = [
        'source'        => 'array',
        'source.mids'   => 'array|min:1|max:10000',
        'source.mids.*' => 'string|unsigned_id',
    ];

    const IMPERSONATION_ACCESS_MAPS_REQUEST_RULES = [
        'source'       => 'array',
        'source.ids'   => 'array|min:1|max:10000',
        'source.ids.*' => 'string|unsigned_id',
    ];

    const MERCHANT_DATA_NOT_FOUND_ON_DRUID              = 'merchant data not found on druid';
    const MERCHANT_DATA_NOT_FOUND_ON_PINOT              = 'merchant data not found on pinot';
    const SEGMENT_DATA_USER_BUSINESS_CATEGORY           = 'user_business_category';
    const SEGMENT_DATA_ACTIVATION_STATUS                = 'activation_status';
    const SEGMENT_DATA_MCC                              = 'mcc';
    const SEGMENT_DATA_ACTIVATED_AT                     = 'activated_at';
    const SEGMENT_DATA_USER_ROLE                        = 'user_role';
    const SEGMENT_DATA_FIRST_TRANSACTION_TIMESTAMP      = 'first_transaction_timestamp';
    const SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION  = 'user_days_till_last_transaction';
    const SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV           = 'merchant_lifetime_gmv';
    const SEGMENT_DATA_AVERAGE_MONTHLY_GMV              = 'average_monthly_gmv';
    const SEGMENT_DATA_PRIMARY_PRODUCT_USED             = 'primary_product_used';
    const SEGMENT_DATA_PPC                              = 'ppc';
    const SEGMENT_DATA_MTU                              = 'mtu';
    const SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS     = 'average_monthly_transactions';
    const SEGMENT_DATA_PG_ONLY                          = 'pg_only';
    const SEGMENT_DATA_PL_ONLY                          = 'pl_only';
    const SEGMENT_DATA_PP_ONLY                          = 'pp_only';
    const SEGMENT_FREE_CREDITS_AVAILABLE                = 'free_credits_available';

    const DEFAULT_MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION   =   24;
    // Should be decided by marketing team
    const NEOSTONE_UTM_RULES = [
        [User\Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_Owners_2555_MF_All_24082021', User\Constants::UTM_SOURCE => 'Facebook', User\Constants::UTM_MEDIUM => 'CPC'],
        [User\Constants::UTM_CAMPAIGN => '', User\Constants::UTM_SOURCE => 'rx_ca_neostone', User\Constants::UTM_MEDIUM => ''],
        [User\Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_Entrepreneurship_2555_M_All_07092021', User\Constants::UTM_SOURCE => 'Facebook', User\Constants::UTM_MEDIUM => 'CPC'],
        [User\Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_Owners_2555_M_All_30112021', User\Constants::UTM_SOURCE => 'Facebook', User\Constants::UTM_MEDIUM => 'CPC'],
        [User\Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_LA1PG_2555_M_All_30112021', User\Constants::UTM_SOURCE => 'Facebook', User\Constants::UTM_MEDIUM => 'CPC'],
        [User\Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_WCA_2555_M_All_30112021', User\Constants::UTM_SOURCE => 'Facebook', User\Constants::UTM_MEDIUM => 'CPC'],
        [User\Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_Open_2555_M_All_20012022', User\Constants::UTM_SOURCE => 'Facebook', User\Constants::UTM_MEDIUM => 'CPC'],
        [User\Constants::UTM_CAMPAIGN => 'GoogleDisplay_RZPx_SD_CA_30112021', User\Constants::UTM_SOURCE => 'google', User\Constants::UTM_MEDIUM => 'CPC']
    ];

    const LINKED_ACCOUNT_CREATE = 'linked_account_create_%s';
    const MERCHANT_SETTLEMENTS_EVENTS_CRON_LAST_RUN_AT_KEY = 'merchant_settlements_events_cron_last_run_at';

    const LINKED_ACCOUNT_BANK_ACCOUNT_UPDATE = 'linked_account_bank_account_update_%s';

    const RBL_CO_CREATED = 'RBL_CO_CREATED';

    protected $mutex;

    protected $featureService;

    protected PartnerService $partnerService;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->featureService = new Feature\Service();

        $this->partnerService = new PartnerService();
    }

    /**
     * Creates a merchant and saves in database
     *
     * @param  array $input
     * @param  array $merchantDetailInputData
     * @return array
     */
    public function create(array $input, array $merchantDetailInputData = []): array
    {
        if (empty($input[Entity::ADMINS]) === false)
        {
            Admin\Entity::verifyIdAndStripSignMultiple($input[Entity::ADMINS]);
        }

        if (empty($input[Entity::ORG_ID]) === true)
        {
            // If the organization ID is not present,
            // assume the organization is razorpay
            $input[Entity::ORG_ID] = Org\Entity::RAZORPAY_ORG_ID;

            $email = $input[Entity::EMAIL] ?? null;
            $this->trace->info(
                TraceCode::MERCHANT_ORG_NOT_GIVEN,
                [
                    'merchant_email' => $email,
                    'merchant_name'  => $input[Entity::NAME],
                ]);
        }
        else
        {
            Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);
        }

        /** @var Entity $merchant */
        $merchant = $this->core()->create($input, $merchantDetailInputData);

        unset($merchantDetailInputData['token_data']);

        // merchant info
        $merchant_org = $merchant->org;

        if($merchant_org->isDisableDefaultEmailReceipt() === true)
        {
            $merchant->setReceiptEmailEnabledAttribute(false);
        }

        $this->enableBusinessBankingIfApplicable($merchant);

        $merchantData = $this->saveMerchantAndApplyCoupon($merchant, $input);

        $this->setDefaultLateAuthConfigForMerchant($merchant);

        return $merchantData;
    }

    public function getMerchantFromMid($mid)
    {
        return $this->repo->merchant->findOrFailPublic($mid);
    }

    public function fetchAllMerchantEntitiesRelatedInfo(array $merchantList, string $type = "")
    {
        return $this->core()->fetchAllMerchantEntitiesRelatedInfo($merchantList, $type);
    }

    public function syncStakeholderFromMerchant($input)
    {
        $data = $this->core()->syncStakeholderFromMerchant($input);

        return $data;
    }

    /**
     * Create sub-merchants via batch service
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestValidationFailureException
     * @throws IntegrationException
     * @throws Throwable
     */
    public function createSubMerchantViaBatch(array $input): array
    {
        $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID) ?? null;

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        list($inputCopy, $isCapitalSubmerchant) = (new CapitalSubmerchantUtility())->extractInputFromCapitalBatchInvite($input, $merchantId);

        $merchantDetailsInput          = array();
        $createCapitalApplicationInput = array();

        if ($isCapitalSubmerchant === true)
        {
            $merchantDetailsInput          = CapitalSubmerchantUtility::extractMerchantDetailsInput($input);
            $createCapitalApplicationInput = CapitalSubmerchantUtility::extractCapitalApplicationInput($input, $merchant);
        }

        $this->trace->info(
            TraceCode::BATCH_SUBMERCHANT_ACCOUNT_CREATE_REQUEST,
            [
                'merchant_id'            => $merchantId,
                'input'                  => $input,
                'is_capital_submerchant' => $isCapitalSubmerchant,
            ]
        );

        $createSubMerchantResponse = Tracer::inspan(['name' => HyperTrace::PARTNER_SUBMERCHANT_INVITE], function() use ($merchant, $inputCopy) {

            $createSubMerchantResponse = $this->createSubMerchant(
                $inputCopy,
                $merchant,
                PartnerConstants::ADD_MULTIPLE_ACCOUNT
            );

            $this->trace->info(
                TraceCode::BATCH_SUBMERCHANT_ACCOUNT_CREATE_RESPONSE,
                [
                    'account_id'   => $createSubMerchantResponse[Entity::ID] ?? null,
                    'account_name' => $createSubMerchantResponse[Entity::NAME] ?? null,
                    'email'        => $createSubMerchantResponse[Entity::EMAIL] ?? null,
                    'status'       => 'success',
                ]
            );

            return $createSubMerchantResponse;
        });

        $response = [
            'account_id'   => $createSubMerchantResponse['id'] ?? null,
            'account_name' => $createSubMerchantResponse['name'] ?? null,
            'email'        => $createSubMerchantResponse['email'] ?? null,
            'status'       => 'success',
        ];

        if ($isCapitalSubmerchant === true)
        {
            $subMerchant = $this->repo->merchant->findOrFail(
                Account\Entity::verifyIdAndSilentlyStripSign($createSubMerchantResponse[Entity::ID])
            );

            $response = $this->postProcessForCapitalSubmerchant(
                $merchant,
                $subMerchant,
                $merchantDetailsInput,
                $createCapitalApplicationInput,
                $response
            );
        }

        return $response;
    }

    public function bulkOnboardSubMerchantViaBatch(array $input)
    {
        $properties = [
            'id'            => $input["partner_id"],
            'experiment_id' => $this->app['config']->get('app.admin_submerchant_bulk_increase_resources_exp_id'),
        ];

        $isExpEnabled = $this->core()->isSplitzExperimentEnable($properties, 'enable');

        if ($isExpEnabled === true)
        {
            $this->trace->info(TraceCode::ADMIN_SUBMERCHANT_BULK_API_RESOURCES_LIMIT, [
                'message' => "Increasing resources limit",
            ]);
            RuntimeManager::setTimeLimit(600);
            RuntimeManager::setMaxExecTime(600);
            RuntimeManager::setMemoryLimit('1024M');
        }

        $requeststartAt = millitime();

        $tracePayload = [
            BatchHeader::MERCHANT_NAME   => $input[BatchHeader::MERCHANT_NAME],
            BatchHeader::MERCHANT_EMAIL  => $input[BatchHeader::MERCHANT_EMAIL],
            BatchHeader::PARTNER_ID      => $input[BatchHeader::PARTNER_ID],
        ];

        try
        {
            $configs = SubMerchantBatchHelper::getConfigParamsFromEntry($input);

            $data = Tracer::inSpan(['name' => 'submerchant_onboarding_batch.process_sub_merchant'], function() use ($input, $configs)
            {
               return (new SubMerchantBatchUtil())->processSubMerchantEntry($input, $configs);
            });

            $this->trace->count(Metric::BATCH_UPLOAD_BY_ADMIN_TOTAL);
        }
        catch (BaseException $e)
        {
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

            $error = $e->getError();

            $data[BatchHeader::STATUS]            = BatchStatus::FAILURE;
            $data[BatchHeader::ERROR_CODE]        = $error->getPublicErrorCode();
            $data[BatchHeader::ERROR_DESCRIPTION] = $error->getDescription();

            $this->trace->count(Metric::BATCH_UPLOAD_BY_ADMIN_FAILURE_TOTAL);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

            $data[BatchHeader::STATUS]     = BatchStatus::FAILURE;
            $data[BatchHeader::ERROR_CODE] = ErrorCode::SERVER_ERROR;
        }

        $this->trace->histogram(Metric::BATCH_UPLOAD_BY_ADMIN_LATENCY, millitime() - $requeststartAt);

        return $data;
    }


    public function subMOnboardingRateLimitEnabled(Entity $merchant, bool $isLinkedAccount, string $source): bool
    {
        if ($isLinkedAccount === true ||
            in_array($source, RateLimitConstants::SUPPORTED_RATELIMIT_SOURCES) === false)
        {
            return false;
        }

        return true;
    }

    /**
     * We need the merchant param for batch. This can be removed once the code is restructured
     * in a way that batch can call just core class functions.
     * Source param is to track the origin of sub-merchant creation in data lake. Bulk,Single,Admin,etc.
     *
     * @param array       $input
     * @param Entity|null $merchant
     * @param string      $source
     * @param bool        $optimizeCreationFlow
     *
     * @return array
     * @throws BadRequestException
     */
    public function createSubMerchant(array $input, Entity $merchant = null, string $source = PartnerConstants::ADD_ACCOUNT, bool $optimizeCreationFlow = false): array
    {
        $rateLimiterUpdated = false;
        $subMRateLimiter = null;
        $key = null;
        try
        {
            $merchant = $merchant ?? $this->merchant;

            $isLinkedAccount = (bool) ($input['account'] ?? false);

            if ($isLinkedAccount === true)
            {
                // - unblocking individual la creation from dashboard. Ref: https://razorpay.slack.com/archives/C01QG1N4A82/p1672037321513599

                //$this->core()->blockLinkedAccountCreationIfApplicable($merchant);
            }

            $rateLimit = $this->subMOnboardingRateLimitEnabled($merchant, $isLinkedAccount, $source);

            if($rateLimit === true)
            {
                $subMRateLimiter = (new PartnershipsRateLimiter($source));

                $key = $subMRateLimiter->getRateLimitRedisKey($merchant->getId());

                $rateLimiterUpdated = $subMRateLimiter->rateLimit($key);
            }

            $product = $input[Entity::PRODUCT] ?? Product::PRIMARY;

            $isPartner = $merchant->isPartner();

            $hasAggregatorFeature = $merchant->hasAggregatorFeature();

            $input['country_code'] = $merchant->getCountry();

            $this->trace->info(
                TraceCode::SUBMERCHANT_CREATE_REQUEST,
                [
                    'name'              => $input[Entity::NAME] ?? null,
                    'merchant_id'       => $merchant->getId(),
                    'is_linked_account' => $isLinkedAccount,
                    'rateLimiterUpdated'=> $rateLimiterUpdated,
                ]
            );

            //
            // Cannot create sub-merchant for non-linked account if any of the following conditions are met:
            // 1. Is neither a partner nor has an aggregator feature (for BC we allow the feature)
            // 2. Is a partner of type pure-platform
            //
            if ($isLinkedAccount === false)
            {
                if (($isPartner === false) and ($hasAggregatorFeature === false))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT);
                }
                else
                {
                    if ($merchant->isPurePlatformPartner() === true)
                    {
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT);
                    }
                }
            }

            $output = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_AND_SET_RELATIONS], function() use ($merchant, $isLinkedAccount, $input, $optimizeCreationFlow) {

                return $this->createSubMerchantAndSetRelations($merchant, $isLinkedAccount, $input, $optimizeCreationFlow);
            });

            $data = [
                'status'        => 'success',
                'merchant_id'   => $output['id'] ?? null,
                'partner_id'    => $merchant->getId(),
                'source'        => $source,
                'product_group' => $product
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
                                                     $merchant, null,
                                                     $data);

            $this->trace->info(TraceCode::PARTNERSHIP_SUBMERCHANT_SIGNUP, [
                'data' => $data
            ]);

            $this->app->hubspot->trackSubmerchantSignUp($merchant->getEmail());

            $dimension = [
                'partner_type' => $merchant->getPartnerType(),
                'source'       => $source
            ];

            $this->trace->count(PartnerMetric::SUBMERCHANT_CREATE_TOTAL, $dimension);
            $submerchantId = $output['id'] ?? "";
            $this->core()->pushSettleToPartnerSubmerchantMetrics($merchant->getId(), $submerchantId);

            if ($isLinkedAccount === true)
            {
                $this->app->hubspot->trackLinkedAccountCreation($output['email'] ?? null);
            }
            else
            {
                if (isset($output['id']) === true)
                {
                    $partnerLeadData = [
                        MerchantDetail::CONTACT_NAME   => $output['name'] ?? null,
                        Entity::EMAIL                  => $output['email'] ?? null,
                        MerchantDetail::CONTACT_MOBILE => $output['user']['contact_mobile'] ?? null
                    ];

                    $this->core()->sendPartnerLeadInfoToSalesforce($output['id'], $merchant->getId(), $product, $partnerLeadData);
                }
            }

            if ($isLinkedAccount === false)
            {
                $count = $this->repo->merchant_access_map->getSubMerchantCount($data['partner_id']);

                if (Str::startsWith($submerchantId, 'acc_'))
                {
                    $submerchantId = substr($submerchantId, 4);
                }

                $properties = [
                    'partner_id'         => $data['partner_id'],
                    'merchant_id'        => $submerchantId,
                    'count_of_affiliate' => $count,
                    'product_group'      => $data['product_group']
                ];

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $merchant, $properties, SegmentEvent::AFFILIATE_ACCOUNT_ADDED);

                if ($count === 1)
                {
                    $properties = [
                        'partner_id'  => $data['partner_id'],
                        'merchant_id' => $submerchantId
                    ];
                    $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                        $merchant, $properties, SegmentEvent::PARTNER_ADDED_FIRST_SUBMERCHANT);
                }
            }

            if(empty($submerchantId) === false)
            {
                $subMerchant = $this->repo->merchant->findOrFailPublic($submerchantId);

                $this->setDefaultLateAuthConfigForMerchant($subMerchant);
            }

            return $output;
        }
        catch (\Exception $e)
        {
            if ($e->getCode() !== ErrorCode::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED
                && $rateLimiterUpdated === true
                && $subMRateLimiter !== null)
            {
                $this->trace->info(RateLimitConstants::RATELIMIT_CONFIG[$source][RateLimitConstants::TRACE_CODE], ['message' => 'decrementing the counter due to some issue occurred while creating subM']);
                $subMRateLimiter->decrementRateLimitCount($key);
            }
            throw $e;
        }
    }

    public function invalidateAffectedOwnersCache(string $merchantId)
    {
        (new Stork('live'))->invalidateAffectedOwnersCache($merchantId);
        (new Stork('test'))->invalidateAffectedOwnersCache($merchantId);
    }

    public function createLinkedAccount(array $input)
    {
        $this->core()->setModeAndDefaultConnection(Mode::LIVE);

        $this->trace->info(
            TraceCode::LINKED_ACCOUNT_CREATE_REQUEST_VIA_BATCH,
            [
                'parent_merchant_id'    => $this->merchant->getId(),
                'linked_account_name'   => $input[BatchHeader::ACCOUNT_NAME],
            ]
        );
        // - unblocking batch la creation from dashboard. Ref: https://razorpay.slack.com/archives/C01QG1N4A82/p1672037321513599

        //$this->core()->blockLinkedAccountCreationIfApplicable($this->merchant);

        $submerchantInput = $this->extractSubmerchantInput($input);

        $mutexKey = sprintf(self::LINKED_ACCOUNT_CREATE, strtolower($submerchantInput[Entity::EMAIL]));

        $linkedAccountArray = $this->mutex->acquireAndReleaseStrict(
            $mutexKey,
            function() use ($submerchantInput)
            {
                return $this->createSubMerchantAndSetRelations($this->merchant, true, $submerchantInput);
            },
            Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            Constants::MERCHANT_MUTEX_RETRY_COUNT
        );

        if (isset($linkedAccountArray['id']) === false)
        {
            throw new Exception\LogicException(
                'Linked account creation failed.',
                null,
                $linkedAccountArray
            );
        }

        $this->app->hubspot->trackLinkedAccountCreation($linkedAccountArray['email'] ?? null);

        $linkedAccountId = $linkedAccountArray['id'];

        $linkedAccount = $this->repo->merchant->find($linkedAccountId);

        $bankAccountDetails = $this->extractBankAccountDetails($input);

        $merchantDetailCore = new Merchant\Detail\Core();

        $merchantDetailCore->saveMerchantDetails($bankAccountDetails, $linkedAccount);

        $this->repo->reload($linkedAccount);

        $accountStatus = $merchantDetailCore->getCombinedActivationStatusForLinkedAccounts($linkedAccount->merchantDetail);

        $accountStatus = Merchant\Account\Constants::LA_ACTIVATION_STATUS_MAPPING[$accountStatus] ?? Merchant\Account\Constants::NOT_ACTIVATED;

        $input[BatchHeader::ACCOUNT_ID]         = Merchant\Account\Entity::getSignedId($linkedAccountId);
        $input[BatchHeader::ACCOUNT_STATUS]     = $accountStatus;
        $input[BatchHeader::ACTIVATED_AT]       = $linkedAccount->getActivatedAt();

        $this->trace->info(
            TraceCode::LINKED_ACCOUNT_CREATE_VIA_BATCH_SUCCESSFUL,
            [
                'linked_account_id'     => $linkedAccountId,
                'parent_merchant_id'    => $this->merchant->getId(),
                'linked_account_name'   => $input[BatchHeader::ACCOUNT_NAME],
            ]
        );

        return $input;
    }

    public function updateLinkedAccountBankAccount(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::LINKED_ACCOUNT_UPDATE_BANK_ACCOUNT_REQUEST,
            [
                'linked_account_id'     => Account\Entity::verifyIdAndStripSign($id),
                'parent_merchant_id'    => $this->merchant->getId(),
                'initiator'             => 'merchant',
            ]
        );

        if ($this->merchant->isFeatureEnabled(FeatureConstants::LA_BANK_ACCOUNT_UPDATE) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_BANK_ACCOUNT_UPDATE_FEATURE_NOT_ENABLED,
                null,
                [
                    'parent_merchant_id'    => $this->merchant->getId(),
                    'linked_account_id'     => $id,
                    'feature'               => FeatureConstants::LA_BANK_ACCOUNT_UPDATE,
                ]
            );
        }

        $validator = new Validator();

        $validator->validateInput('la_bank_account_update', $input);

        $validator->validateLinkedAccountUpdation($this->merchant);

        return $this->mutex->acquireAndReleaseStrict(
            sprintf(self::LINKED_ACCOUNT_BANK_ACCOUNT_UPDATE, $id),
            function () use ($id, $input)
            {
                return $this->core()->updateLinkedAccountBankAccount($id, $input);
            }
        );
    }

     /**
     * Change 2fa setting of merchant (enable/disable)
     *
     * @param array  $input
     *
     * @return array
     */
    public function change2faSetting(array $input)
    {
        $this->merchant->getValidator()->validateInput('change2faSetting', $input);

        return $this->core()->change2faSetting(
            $this->user,
            $this->merchant,
            $input);

    }

    /**
     * resets the merchant settlement schedule to default
     *
     * @param array $input
     * @return array
     */
    public function resetSettlementSchedule(array $input): array
    {
        (new Validator)->validateInput('reset_settlement_schedule', $input);

        return $this->core()->resetSettlementSchedule($input['merchant_ids']);
    }

    /**
     * We create this mapping only in case of non-linked accounts if
     * 1. Is partner of type fully-managed
     * 2. Is partner of type aggregator and has exception given for optional sub-merchant email
     * (For now all aggregators are allowed for backward compatibility till future phases)
     * 3. Is not a partner but has aggregator feature (for backward compatibility)
     *
     * @param string $ownerId
     * @param Entity $subMerchant
     * @param Entity $aggregatorMerchant
     * @param string|null $product
     */
    protected function attachSubMerchantUserIfApplicable(
        string $ownerId,
        Entity $subMerchant,
        Entity $aggregatorMerchant,
        string $product = null)
    {
        $isPartner = $aggregatorMerchant->isPartner();

        $hasAggregatorFeature = $aggregatorMerchant->hasAggregatorFeature();

        $isOptionalEmailAllowed = $aggregatorMerchant->isOptionalEmailAllowedAggregator();

        $subMerchantEmailIsSame = ($aggregatorMerchant->getEmail() === $subMerchant->getEmail());

        if (($aggregatorMerchant->isFullyManagedPartner() === true) or
            //Remove the following line later as aggregator isn't supposed to
            //have dashboard access eventually. This is for BC.
            ($aggregatorMerchant->isAggregatorPartner() === true) or
            (($isOptionalEmailAllowed === true) and ($subMerchantEmailIsSame === true)) or
            (($isPartner === false) and ($hasAggregatorFeature === true)))
        {
            $role = $subMerchant->getUserOwnerRole();

            if ($product === Product::BANKING)
            {
                $role = User\Role::VIEW_ONLY;
            }

            $this->trace->info(TraceCode::ATTACH_SUBMERCHANT_USER, [
                'ownerId'     => $ownerId,
                'merchant_id' => $subMerchant->getId(),
                'role'        => $role,
                'product'     => $product,
            ]);

            Tracer::inspan(['name' => HyperTrace::ATTACH_SUBMERCHANT_USER], function () use ($ownerId, $subMerchant, $product, $role) {
                $this->core()->attachSubMerchantUser($ownerId, $subMerchant, $product, $role);
            });
        }
    }

    public function saveMerchantAndApplyCoupon(Entity $merchant, array $input)
    {
        $this->repo->saveOrFail($merchant);

        $merchantData = $merchant->toArrayPublic();

        $couponResponse = $this->applyCouponOnSignUp($input, $merchant);

        $merchantData[self::COUPON_RESPONSE] = $couponResponse;

        return $merchantData;
    }

    protected function applyCouponOnSignUp(array $input, Entity $merchant)
    {
        $result = [];

        if (isset($input[Entity::COUPON_CODE]) === true)
        {
            $couponInput = [
                Coupon\Entity::CODE        => $input[Entity::COUPON_CODE],
                Coupon\Entity::MERCHANT_ID => $merchant->getId()
            ];

            try
            {
                $result = (new Coupon\Service)->apply($couponInput);
            }
            catch (\Throwable $e)
            {
                $result = ['message' => $e->getMessage()];
            }
        }

        return $result;
    }

    public function getBillingLabelSuggestions(): array
    {
        return $this->core()->getBillingLabelSuggestions($this->merchant);
    }

    public function patchMerchantBillingLabelAndDba($input)
    {
        $this->core()->editMerchantBillingLabelAndDba($this->merchant, $input);

        return [
            Entity::ID => $this->merchant->getId(),
            Entity::BILLING_LABEL => $this->merchant->getBillingLabel(),
            Merchant\Detail\Entity::BUSINESS_DBA => $this->merchant->getDbaName()
        ];
    }

    public function edit(string $id, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant'    => $merchant,
                'merchant_id' => $id,
                'input'       => $input,
            ]);

       $this->allowEditingOfMerchantForCompliance($merchant, $input);
        // when the funds are released via bulk action then in that scenario
        // just process the data at API side. No settlement service will be called to
        // update Disable feature.
        if(isset($input['hold_funds']) === true)
        {
            $action = $input['hold_funds'] == 1 ? Merchant\Action::HOLD_FUNDS : Merchant\Action::RELEASE_FUNDS;
            (new Validator())->validateRiskPermissionForAction($merchant, $action);
            if ($action === Merchant\Action::RELEASE_FUNDS) {
                   (new MerchantActionNotification())->removeNotificationTag($merchant, $action);
            }
        }

        if (empty($input[Entity::GROUPS]) === false)
        {
            Group\Entity::verifyIdAndStripSignMultiple($input[Entity::GROUPS]);
        }

        if (empty($input[Entity::ADMINS]) === false)
        {
            Admin\Entity::verifyIdAndStripSignMultiple($input[Entity::ADMINS]);
        }

        if (isset($input[Entity::ORG_ID]) === true)
        {
            Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);
        }

        $merchant = $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input) {
            $merchant = $this->core()->edit($merchant, $input);

            if (isset($input[Entity::FEE_BEARER]) === true)
            {
                $merchantId = $merchant->getId();

                // add feebearer tag if fee_bearer field is set to customer
                // else remove feebearer tag
                if ($input[Entity::FEE_BEARER] === 'customer')
                {
                    $this->insertTag($merchantId, 'feebearer');
                }
                else
                {
                    $this->deleteTag($merchantId, 'feebearer');
                }
            }

            return $merchant;
        });
        //
        // syncing Linked Accounts' hold_funds with parent merchant's due to risk concerns. https://docs.google.com/document/d/1ePztfh9GG4ImVzKlnQVaJ0GKCid0nLx_FRAyJGJDyTc/edit?usp=sharing*/
        //
        $linkedAccountCount = $this->repo->merchant->fetchLinkedAccountsCount($merchant->getId());

        if ((isset($input[Entity::HOLD_FUNDS]) === true) and
            ($linkedAccountCount > 0))
        {
            MerchantHoldFundsSync::dispatch($this->mode, $id, $input[Entity::HOLD_FUNDS]);
        }

        return $merchant->toArrayPublic();
    }

    private function allowEditingOfMerchantForCompliance($merchant, $input)
    {

        if ($merchant->org->isFeatureEnabled(Feature\Constants::ORG_PROGRAM_DS_CHECK) === false)
        {
            return;
        }

        // for test cases
        if (isset($merchant->merchantDetail) === false)
        {
            return false;
        }

        $businessDBA = $merchant->merchantDetail->getBusinessDba();

        // temporary check to prevent merchants from editing name for compliance
        if ((isset($input[Entity::NAME]) === true) and
            (isset($businessDBA) === true) and
            ($businessDBA !== ''))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Name cannot be changed'
            );
        }

        // temporary check to prevent merchants from editing billing label for compliance
        if ((isset($input[Entity::BILLING_LABEL]) === true) and
            (isset($businessDBA) === true) and
            ($businessDBA !== ''))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Billing Label cannot be changed'
            );
        }
    }

    public function syncMerchantFundsOnHold(string $parentMid, $holdFunds)
    {
        $linkedAccountMids = $this->repo->merchant->fetchLinkedAccountMids($parentMid);

        $count = count($linkedAccountMids);

        $updatedCount = 0;

        $this->trace->info(
            TraceCode::LINKED_ACCOUNTS_FETCHED_FOR_HOLD_FUNDS_SYNC,
            [
                'count'                 => $count,
                'parent_mid'            => $parentMid
            ]
        );
        foreach ($linkedAccountMids as $linkedAccountMid)
        {
            $input[Entity::HOLD_FUNDS] = $holdFunds;

            try {
                $this->edit($linkedAccountMid, $input);

                $updatedCount += 1;

                $this->trace->info(
                    TraceCode::LINKED_ACCOUNT_HOLD_FUNDS_UPDATED,
                    [
                        'parent_mid'            => $parentMid,
                        'linked_account_mid'    => $linkedAccountMid,
                        'hold_funds_input'      => $input
                    ]
                );
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::LINKED_ACCOUNT_HOLD_FUNDS_UPDATE_FAILED,
                    [
                        'parent_mid'            => $parentMid,
                        'linked_account_mid'    => $linkedAccountMid,
                        'hold_funds_input'      => $input
                    ]
                );
            }
        }
        return $updatedCount;
    }

    public function editRiskAttributes(string $id, array $input): array
    {
        // not adding a lock, as the chances of race condition is extremely rare
        $this->trace->info(
            TraceCode::MERCHANT_EDIT_RISK_ATTRIBUTES,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        (new Validator)->validateRiskAttributes($input);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === false)
        {
            $newMerchant = clone $merchant;

            $newMerchant->edit($input);

            // in case diff is empty,
            // the workflow handle method will throw an undefined index exception as part of redactFields
            // we can introduce a additonal check to get the diff and verify if its empty or not
            (new Validator)->validateRiskAttributesHasDiff($merchant, $newMerchant);

            $this->app['workflow']
                ->setEntity($merchant->getEntity())
                ->handle($merchant, $newMerchant);
        }

        return $this->edit($id, $input);
    }

    protected function communicateSubMerchantCreation(
        Entity      $subMerchant,
        Entity      $aggregator,
        string      $product,
        User\Entity $user = null,
        bool        $createdNewUser = false
    )
    {
        $this->sendSubMerchantCreationMail($subMerchant, $aggregator, $product, $user, $createdNewUser);

        if (empty($user) === false)
        {
            $this->sendSubMerchantCreationSMSForProduct($product, $subMerchant, $aggregator, $user, $createdNewUser);

            $args      = [
                MerchantConstants::MERCHANT => $subMerchant,
                MerchantConstants::PARTNER  => $aggregator,
                MerchantConstants::PARAMS   => [
                    'subMerchantName'  => $subMerchant->getTrimmedName(25, "..."),
                    'subMerchantId'    => $subMerchant->getId(),
                    'subMerchantEmail' => $subMerchant->getEmail()
                ]
            ];
            $eventName = ($product === Product::BANKING) ? OnboardingEvents::PARTNER_ADDED_SUBMERCHANT_FOR_X : OnboardingEvents::PARTNER_ADDED_SUBMERCHANT;

            (new OnboardingNotificationHandler($args))->sendForEvent($eventName);
        }
    }

    /**
     * Old flow: Sends a mail to the sub-merchant email telling them about
     * account creation. Adds aggregator in cc if the sub-merchant
     * email is different.
     *
     * Partners flow: Sends a mail to the partner informing about the account
     * addition. If the sub-merchant email is different then sends an email
     * to the user created with this email with a password reset email.
     *
     * @param Entity $subMerchant
     * @param Entity $aggregator
     * @param string $product
     * @param User\Entity|null $user
     * @param bool $createdNewUser
     * @param bool $retry
     */
    protected function sendSubMerchantCreationMail(
        Entity $subMerchant,
        Entity $aggregator,
        string $product,
        User\Entity $user = null,
        bool $createdNewUser = false,
        bool $retry = false)
    {
        $isPartnerFlow = $aggregator->isPartner();

        $subMerchant = $subMerchant->toArray();

        $aggregator = $aggregator->toArray();

        if ($isPartnerFlow === true)
        {
            $this->sendNewSubMerchantCreationMails($subMerchant, $aggregator, $product, $user, $createdNewUser, $retry);
        }
        else
        {
            $createSubMerchantMail = new CreateSubMerchantMail($subMerchant, $aggregator);

            Mail::queue($createSubMerchantMail);
        }
    }

    /**
     * @param array            $subMerchant
     * @param array            $aggregator
     * @param string           $product
     * @param User\Entity|null $user
     * @param bool             $createdNewUser
     * @param bool             $retry
     */
    protected function sendNewSubMerchantCreationMails(
        array       $subMerchant,
        array       $aggregator,
        string      $product,
        User\Entity $user = null,
        bool        $createdNewUser = false,
        bool        $retry = false)
    {
        // This mail goes to the partner who has added the sub-merchant. If the partner is adding the merchant then mail
        // is sent to both partner and merchant but when partner sends the mail as a reminder to merchant for setting
        // the password, mail is only sent to merchant and not to the partner. In this case, retry is true and partner
        // does not get any mail.
        // Skip sending mail to partner from batch service.

        if ($retry === false and $this->app['basicauth']->isBatchApp() === false)
        {
            switch ($product)
            {
                case Product::PRIMARY:
                    $createSubMerchantPartnerMail = new CreateSubMerchantPartnerForPG($subMerchant, $aggregator);
                    Mail::queue($createSubMerchantPartnerMail);
                    break;

                case Product::BANKING:
                    $createSubMerchantPartnerMail = new CreateSubMerchantPartnerForX($subMerchant, $aggregator);
                    Mail::queue($createSubMerchantPartnerMail);
                    break;

                case Product::CAPITAL:
                    $createSubMerchantPartnerMail = new CreateSubMerchantPartnerForLOC($subMerchant, $aggregator);
                    Mail::queue($createSubMerchantPartnerMail);
                    break;
            }
        }

        if ($subMerchant[Entity::EMAIL] === $aggregator[Entity::EMAIL])
        {
            return;
        }

        $orgId = (isset($subMerchant['org_id']) === true) ? $subMerchant['org_id'] : $subMerchant['org']['id'];

        /** @var Org\Entity $org */
        $org = $this->repo->org->find($orgId);

        $hostname = $org->getPrimaryHostName();

        $org = $org->toArrayPublic();

        $org[Hostname\Entity::HOSTNAME] = $hostname;

        $mailUserData = $createdNewUser ? $user : null;

        // If user was just created then we need to pass the details to the mailer for sending
        // reset password link.
        switch ($product)
        {
            case Product::PRIMARY:
                $createSubMerchantAffiliateMail = new CreateSubMerchantAffiliateForPG($subMerchant, $aggregator, $org, $mailUserData);
                Mail::queue($createSubMerchantAffiliateMail);
                break;

            case Product::BANKING:
                $createSubMerchantAffiliateMail = new CreateSubMerchantAffiliateForX($subMerchant, $aggregator, $org, $mailUserData);
                Mail::queue($createSubMerchantAffiliateMail);
                break;

            case Product::CAPITAL:
                $createSubMerchantAffiliateMail = new CreateSubMerchantAffiliateForLOC($subMerchant, $aggregator, $org, $mailUserData);
                Mail::queue($createSubMerchantAffiliateMail);
                break;
        }
    }

    /**
     * Send an SMS to sub-merchant when added via partner dashboard for X or Capital
     *
     * @param string      $product
     * @param Entity      $subMerchant
     * @param Entity      $merchant
     * @param User\Entity $user
     * @param bool        $isNewUser
     *
     * @return void
     */
    protected function sendSubMerchantCreationSMSForProduct(
        string      $product,
        Entity      $subMerchant,
        Entity      $merchant,
        User\Entity $user,
        bool        $isNewUser
    )
    {
        if ($product === Product::PRIMARY)
        {
            return;
        }

        $subMerchantDetails = (new MerchantDetailCore())->getMerchantDetails($subMerchant);

        $submContactMobile = $subMerchantDetails->getContactMobile();

        // Do not send SMS when any of the below conditions is true
        // 1. The merchant is not a partner
        // 2. A new sub-merchant user was not created
        // 3. Sub-merchant does not have contact mobile
        if (
            ($merchant->isPartner() === false) or
            ($isNewUser === false) or
            (empty($submContactMobile) === true)
        )
        {
            return;
        }

        $user = $this->repo->user->findOrFailPublic($user->getId());

        $token = $user->getPasswordResetToken();

        if (empty($token) === true)
        {
            $token = (new User\Service())->getTokenWithExpiry(
                $user->getId(),
                User\Constants::SUBMERCHANT_ACCOUNT_CREATE_PASSOWRD_TOKEN_EXPIRY_TIME
            );
        }

        $passwordResetLink = 'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)
                             . '/forgot-password#token=' . $token . '&email=' . $subMerchant->getEmail();

        $contentParams = [
            'subMerchantName'   => $subMerchant->getName(),
            'partnerName'       => $merchant->getName(),
            'resetPasswordLink' => $this->app['elfin']->shorten($passwordResetLink)
        ];

        $smsPayload = [
            'language'          => 'english',
            'ownerType'         => 'merchant',
            'templateNamespace' => 'partnerships',
            'destination'       => $submContactMobile,
            'orgId'             => $subMerchant->getOrgId(),
            'ownerId'           => $subMerchant->getId(),
            'contentParams'     => $contentParams,
            'sender'            => 'RZPAYX'
        ];

        $tracePayload = [
            'submerchant_id'      => $subMerchant->getId(),
            'partner_id'          => $merchant->getId(),
            'submerchant_user_id' => $user->getId(),
        ];

        $traceCode      = TraceCode::SEND_SUBMERCHANT_X_ONBOARDING_SMS;
        $errorTraceCode = TraceCode::SUBMERCHANT_X_ONBOARDING_SMS_FAILED;

        if ($product === Product::BANKING)
        {
            $smsPayload['contentParams']['subMerchantEmail'] = $subMerchant->getEmail();
            $smsPayload['templateName']                      = 'sms.onboarding.partner_submerchant_invite_v2';
            $tracePayload['sms_template']                    = 'sms.onboarding.partner_submerchant_invite_v2';
        }
        elseif ($product === Product::CAPITAL)
        {
            $smsPayload['templateName']   = 'sms.partnership.new_LOC';
            $tracePayload['sms_template'] = 'sms.partnership.new_LOC';
            $traceCode                    = TraceCode::SEND_SUBMERCHANT_LOC_ONBOARDING_SMS;
            $errorTraceCode               = TraceCode::SUBMERCHANT_LOC_ONBOARDING_SMS_FAILED;
        }

        try
        {
            $this->app->stork_service->sendSms($this->mode, $smsPayload);

            $this->trace->info($traceCode, $tracePayload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, $errorTraceCode, $tracePayload);
        }
    }

    public function editEmail($id, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $orignalEmail = $merchant->getEmail();

        $merchant = $this->core()->editEmail($merchant, $input);

        $newEmail = $merchant->getEmail();

        // handle user management on PG
        $this->core()->changeMerchantUsersEmail($merchant, $orignalEmail, $newEmail, Product::PRIMARY);

        // handle user management on X
        $this->core()->changeMerchantUsersEmail($merchant, $orignalEmail, $newEmail, Product::BANKING);

        return $merchant->toArrayPublic();
    }

    public function getUserStatusForEmailUpdateSelfServe($input)
    {
        $this->trace->info(TraceCode::EMAIL_USER_STATUS_FOR_EDIT_EMAIL, $input);

        (new Validator())->validateInput('editMerchantEmailSelfServe', $input);

        $input[Entity::EMAIL]   = mb_strtolower($input[Entity::EMAIL]);

        $merchant = $this->app['basicauth']->getMerchant();

        $product = $this->app['basicauth']->getRequestOriginProduct();

        $status = $this->core()->getUserStatusForEmailUpdateSelfServe($input[Entity::EMAIL], $merchant, $product);

        if ($status[Constants::IS_USER_EXIST] === false)
        {
            // this flow is used by owner user only : basic auth user is same as owner user
            $ownerUser = $this->app['basicauth']->getUser();

            $this->saveMerchantEmailUpdateData($ownerUser->getEmail(), $merchant->getId(), $input);

            $this->core()->sendMailForEditMerchantEmailSelfServe($ownerUser, $input[Entity::EMAIL]);

            $this->trace->info(TraceCode::EMAIL_SENT_FOR_EDIT_MERCHANT_EMAIL, []);

            return $status;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_EMAIL_ASSOCIATED_WITH_ANOTHER_ACCOUNT);
    }

    /**
     * Old flow: Used for changing the owner of the team.
     * Transfers the owner role to the email user and makes the current owner as manager
     *
     * Temporarily deprecating the API for mobile signup.
     * Issue - After the ownership transfer, the old owner of merchant 1 whose email has been changed, can also login
     * to merchant 2 account using his mobile contact
     *
     * Merchant 1	  Merchant 2
     * abc@xyz.com    def@xyz.com  (old email)
     * 12345          67890        (mobile contact)
     *
     * If email of Merchant 2 is changed to abc@xyz.com, then login can be done from abc@xyz.com, 12345 and 67890
     */

    public function editMerchantEmailAndTransferOwnershipToEmailUser(array $input): array
    {
        //(new Validator())->validateInput('editMerchantEmailSelfServe', $input);
        //
        //$input[Entity::EMAIL]   = mb_strtolower($input[Entity::EMAIL]);
        //
        //$this->trace->info(TraceCode::MERCHANT_EDIT_EMAIL_REQUEST, $input);
        //
        //$merchant = $this->app['basicauth']->getMerchant();
        //
        //$user = $this->repo->user->getUserFromEmailOrFail($input[Entity::EMAIL]);
        //
        //// user to whom ownership is being transfered should not have any cross org merchant
        //(new Validator())->validateUserDoesNotBelongToMerchantsInMultipleOrgsForEmailUpdate($user);
        //
        //// this flow is used by owner user only : basic auth user is same as owner user
        //$currentOwner = $this->app['basicauth']->getUser();
        //
        //$this->core()->editMerchantEmailAndTransferOwnershipToUser($user, $currentOwner, $merchant, $input);
        //
        //$this->invalidatePreviousRequestForEmailUpdate($merchant, $currentOwner);
        //
        //return  [
        //    Constants::LOGOUT_SESSIONS_FOR_USERS => [$user->getId(), $currentOwner->getId()]
        //];

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ROUTE_DISABLED);

    }

    public function editMerchantEmailCreateNewUserAndTransferOwnerShip($input)
    {
        (new Validator())->validateInput('changeEmailToken', $input);

        $data = $this->getMerchantEmailUpdateData($input[Entity::MERCHANT_ID]);

        // reset token expires before cache data: throw token expire exception on cache expire
        if (is_null($data) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
        }

        $merchantId = $data[Entity::MERCHANT_ID];

        $this->trace->info(TraceCode::MERCHANT_EDIT_EMAIL_REQUEST,[
            Entity::MERCHANT_ID => $merchantId
        ]);

        $input = array_merge($input, $data);

        // using merchant_id from cache
        $input[Entity::MERCHANT_ID] = $merchantId;

        $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $currentOwnerUser = $this->repo->user->getUserFromEmailOrFail($input[Constants::CURRENT_OWNER_EMAIL]);

        (new Validator())->validateUserIsOwnerForMerchant($currentOwnerUser->getId(), $input[Entity::MERCHANT_ID]);

        $this->core()->createNewUserAndTransferOwnerShip($input, $merchant, $currentOwnerUser);

        $this->deleteMerchantEmailUpdateData($input[Entity::MERCHANT_ID]);

        return [
            Constants::LOGOUT_SESSIONS_FOR_USERS => [$currentOwnerUser->getId()]
        ];
    }

    protected function deleteMerchantEmailUpdateData($merchantId)
    {
        $cacheKey = $this->getMerchantEmailUpdateCacheKey($merchantId);

        $this->app->cache->delete($cacheKey);
    }

    /**
     * @param $merchantId
     *
     * @return mixed
     */
    public function getMerchantData($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
            $merchantId, ['methods', Entity::GROUPS, Entity::ADMINS]);

        // Merchant to array public
        $data = $merchant->toArrayPublic();

        // Merchant confirmed details
        $data['confirmed'] = $this->getMerchantConfirmed($merchant);

        $data['balance_configs'] = (new BalanceConfigService)->getMerchantBalanceConfigs();

        // Fetch formatted merchant details.
        $data['merchant_details'] = (new Detail\Service)->getMerchantDetailsForAdmin();

        $data['is_inheritance_parent'] = $merchant->isInheritanceParent();

        $data['tags'] = $merchant->tagNames();

        $merchantAov = $merchant->merchantDetail->avgOrderValue;

        if (empty($merchantAov) === false)
        {
            $data['merchant_details']['avg_order_min'] = $merchantAov->getMinAov();

            $data['merchant_details']['avg_order_max'] = $merchantAov->getMaxAov();
        }

        // Fetch method specific custom_text.Doing only for cred now.
        $methods = $this->merchant->getMethods();

        if (empty($methods) === false)
        {
            (new Methods\Core)->addCustomTextForCredIfApplicable($this->merchant, $methods, $data['methods']);

            (new Methods\Core)->addIntlBankTransferMethodsIfApplicable($methods, $data['methods']);
        }

        return $data;
    }

    protected function invalidatePreviousRequestForEmailUpdate($merchant, $currentOwnerUser)
    {
        // delete cached data to invalidated any previous request : data will be missing if link is accessed
        $this->deleteMerchantEmailUpdateData($merchant->getId());

        // invalidate Token
        (new User\Service())->setAndSaveResetPasswordToken($currentOwnerUser, null);
    }


    protected function saveMerchantEmailUpdateData($userEmail, $merchantId, $input)
    {
        $data = [
            Constants::CURRENT_OWNER_EMAIL    => $userEmail,
            Entity::EMAIL                     => $input[Entity::EMAIL],
            Entity::MERCHANT_ID               => $merchantId,
            Constants::REATTACH_CURRENT_OWNER => (bool) ($input[Constants::REATTACH_CURRENT_OWNER] ?? false),
            Constants::SET_CONTACT_EMAIL      => (bool) ($input[Constants::SET_CONTACT_EMAIL] ?? false)
        ];

        $cacheKey = $this->getMerchantEmailUpdateCacheKey($merchantId);

        $this->app->cache->put($cacheKey, $data, Constants::MERCHANT_EMAIL_UPDATE_CACHE_TTL);
    }

    protected function getMerchantEmailUpdateData($merchantId)
    {
        $cacheKey = $this->getMerchantEmailUpdateCacheKey($merchantId);

        return $this->app->cache->get($cacheKey);
    }

    protected function getMerchantEmailUpdateCacheKey($merchantId)
    {
        return sprintf(Constants::MERCHANT_EMAIL_UPDATE_CACHE_KEY, $merchantId);
    }


    public function correctMerchantOwnerForBanking($id): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $primaryOwner = $merchant->primaryOwner();

        $bankingOwner = $merchant->primaryOwner('banking');

        if ((empty($primaryOwner) === true) or
            (empty($bankingOwner) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_OWNER_NOT_EXISTS,
                null,
                [
                    'primary_owner' => $primaryOwner,
                    'banking_owner' => $bankingOwner
                ]);
        }

        if ($primaryOwner !== $bankingOwner)
        {
            // Demote banking owner to view_only role
            (new User\Core)->detachAndAttachMerchantUser($bankingOwner, $merchant->getId(), 'view_only', 'banking');

            // Promote the owner in PG to owner in banking
            (new User\Core)->detachAndAttachMerchantUser($primaryOwner, $merchant->getId(), 'owner', 'banking');
        }

        return $merchant->toArrayPublic();
    }

    public function editConfig(array $input): array
    {
        if (empty($input[Merchant\Entity::LOGO_URL]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_INPUT_LOGO_URL);
        }

        // Adds uploaded logo's url to the input.
        $this->uploadLogoIfFound($input);

        //remove the email field from payload
        if($this->merchant->org->isFeatureEnabled(Feature\Constants::ORG_EMAIL_UPDATE_2FA_ENABLED) === true)
        {
            unset($input['transaction_report_email']);
        }

        $this->core()->editConfig($this->merchant, $input);

        return $this->merchant->toArrayConfig();
    }

    /**
     * @throws BadRequestException
     */
    public function editEmail2FA(array $input): array
    {
        if($this->merchant->org->isFeatureEnabled(Feature\Constants::ORG_EMAIL_UPDATE_2FA_ENABLED) === false)
        {
            return $this->editConfig($input);
        }
        else if(array_key_exists("otp",$input) === false or array_key_exists("token",$input) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,null,[
                "internal_error_code" =>ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
                "description" => PublicErrorDescription::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
            ]);
        }

        $this->trace->info(TraceCode::INPUT_OTP_TOKEN_CHECK, [
            'otp_exists' => array_key_exists("otp",$input),
            'token_exists'=> array_key_exists("token",$input),
        ]);

        $input[User\Entity::MEDIUM] = "email";
        $input[User\Entity::ACTION] = "verify_contact";

        $data=[];

        $user = $this->auth->getUser();

        $content = [
            "otp"=>$input["otp"],
            "token"=>$input["token"]
        ];

        try {
            $response = (new User\Core)->verifyUserThroughEmail($content, $this->merchant, $user);
        }
        catch (\Exception $e)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,null,[
                "internal_error_code" =>ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
                "description" => PublicErrorDescription::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
            ]);
        }

        $data["transaction_report_email"] = $input["transaction_report_email"];

        $this->core()->editConfig($this->merchant, ["transaction_report_email"=>$input["transaction_report_email"]]);

        return $data;
    }

    public function deleteMerchantLogo(): array
    {
        $this->merchant->setLogoUrl(null);

        $this->repo->saveOrFail($this->merchant);

        return $this->merchant->toArrayConfig();
    }

    protected function uploadLogoIfFound(&$input)
    {
        // if ($input->hasFile('logo') and $input['logo']->isValid())
        if (isset($input['logo']))
        {
            // Store the logos in AWS
            $logoUrl = (new Logo)->setUpMerchantLogo($input);

            $input['logo_url'] = $logoUrl;
            unset($input['logo']);
        }
    }

    // This is on internal auth
    public function fetch(string $id): array
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
            $id, ['methods', Entity::GROUPS, Entity::ADMINS]);

        return $merchant->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $merchants = $this->repo->merchant->fetch($input);

        return $merchants->toArrayPublic();
    }

    public function fetchConfig(bool $isInternal = false): array
    {
        $merchantId = $this->merchant->getId();

        $configList = Entity::CONFIG_LIST;

        if ($isInternal === true)
        {
            $configList = array_merge($configList, Entity::INTERNAL_CONFIG_LIST);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId, $configList);

        $response = $merchant->toArray();

        $response['logo_large_size_url'] = $this->merchant->getFullLogoUrlWithSize(Logo::LARGE_SIZE);

        $response[Refund\Constants::REFUND_STATUS_FILTER] =
            (new Refund\Service)->getRefundStatusFilterFlagForMerchantDashboard($merchantId);

        if ($isInternal === true)
        {
            $response[Entity::MAX_PAYMENT_AMOUNT] =  $this->merchant->getMaxPaymentAmount();

            $response[Entity::MAX_INTERNATIONAL_PAYMENT_AMOUNT] =  $this->merchant->getMaxPaymentAmountTransactionType(true);

            $response['is_suspended'] =  $this->merchant->isSuspended();

            $response['is_live'] = $this->merchant->isLive();

            $response[Entity::ORG_ID] =  $this->merchant->getOrgId();

            $response['org_custom_code'] =  $this->merchant->org->getCustomCode();

            $response['org_name'] =  $this->merchant->org->getDisplayName();

            $response['brand_color'] = $this->merchant->getBrandColorOrOrgPreference();

            $supportDetails = $this->repo->merchant_email->getEmailByType(Merchant\Email\Type::SUPPORT, $merchant->getId());

            $response['branding_logo'] = $this->merchant->org->getPaymentAppLogo();

            $response['login_logo'] = $this->merchant->org->getLoginLogo();

            $response['custom_org_branding'] = $this->merchant->shouldShowCustomOrgBranding();

            $response[Entity::METHODS] = (new Methods\Core)->getUpiMethodForMerchant($merchant);

            $response[self::SEGMENT_DATA_MCC] = $this->merchant->getCategory();

            $response['country_code'] = $this->merchant->getCountry();

            $response['currency_code'] = $this->merchant->getCurrency();

            $response['time_zone'] = $this->merchant->getTimeZone();

            if ($supportDetails !== null)
            {
                $supportDetails = $supportDetails->toArrayPublic();

                $response['support_email']  = $supportDetails[Merchant\Email\Entity::EMAIL];

                $response['support_mobile'] = $supportDetails[Merchant\Email\Entity::PHONE];
            }
        }

        $response += (new CheckoutView())->addOrgInformationInResponse($this->merchant);

        return $response;
    }

    /**
     * The list of Merchant & Org configs required by checkout-service to
     * build the `/preferences` response.
     *
     * @param array $input
     *
     * @return array
     */
    public function fetchConfigForCheckoutInternal(array $input): array
    {
        (new Validator())->validateInput('fetch_config_for_checkout', $input);

        $merchantId = $this->merchant->getId();

        LocaleCore::setLocale($input, $merchantId);

        /** @var Entity $merchant */
        $merchant = $this->repo->merchant->findOrFailPublic(
            $merchantId,
            Entity::CHECKOUT_CONFIG_LIST
        );

        $keyEntity = $this->repo->key->getLatestActiveKeyForMerchant($merchantId);

        $languageCode = App::getLocale();

        $orgId = $merchant->getOrgId();

        $org = null;

        if (!empty($orgId)) {
            $org = $this->repo->org->find($orgId);
        }

        $checkoutExtraFields = [
            'brand_name' => $merchant->getFilteredDba(),
            'checkout_logo_size_image_url' => $merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE),
            'currency' => $merchant->getCurrency(),
            'is_fee_bearer' => $merchant->isFeeBearerCustomerOrDynamic(),
            'key' => optional($keyEntity)->getPublicKey(),
            'language_code' => $languageCode,
            'org_checkout_logo_url' => optional($org)->getCheckoutLogo() ?? '',
            'category_name' => $merchant->getCategory2(),
        ];

        $optionalInputConfig = $merchant->getOptionalInputConfig();

        if (!empty($optionalInputConfig))
        {
            $checkoutExtraFields['optional'] = $optionalInputConfig;
        }

        return array_merge(
            $merchant->toArray(),
            $checkoutExtraFields,
        );
    }

    public function getPaymentFailureAnalysis($input)
    {
        (new Validator())->validateInput('get_payment_failure_analysis', $input);

        (new Validator())->validateRangeForFailureAnalysis($input);

        $merchant = app('basicauth')->getMerchant();

        try
        {
            if($this->app['api.route']->isWDAServiceRoute() === true)
            {
                $this->trace->info(TraceCode::WDA_GET_PAYMENT_FAILURE_ANALYSIS, [
                    'input'         => $input,
                    'route_auth'    => $this->auth->getAuthType(),
                    'route_name'    => $this->app['api.route']->getCurrentRouteName(),
                ]);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_SERVICE_LOGGING_ERROR, [
                'error_message'    => $ex->getMessage(),
                'route_name'       => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }


        $startTime = microtime(true);
        $failureAnalysisData = $this->repo->payment->fetchPaymentsFailureAnalysisData($input['from'], $input['to'], $merchant->getId());

        $this->trace->info(TraceCode::MERCHANT_FAILURE_ANALYSIS_QUERY_TIME, [
            MerchantConstants::QUERY_EXECUTION_TIME            => (microtime(true) - $startTime),
            MerchantConstants::FAILURE_ANALYSIS_FOR_TIME_RANGE => $input['to'] - $input['from'],
        ]);

        $response = [
            MerchantConstants::SUMMARY => [
                MerchantConstants::NUMBER_OF_TOTAL_PAYMENTS      => 0,
                MerchantConstants::NUMBER_OF_SUCCESSFUL_PAYMENTS => 0,
            ],
            MerchantConstants::FAILURE_DETAILS => [
                MerchantConstants::CUSTOMER_DROP_OFF => 0,
                MerchantConstants::BANK_FAILURE      => 0,
                MerchantConstants::BUSINESS_FAILURE  => 0,
                MerchantConstants::OTHER_FAILURE     => 0,
            ],
        ];

        foreach ($failureAnalysisData as $data)
        {
            $this->addPaymentCountInResponseForFailureAnalysis($response, $data);
        }

        return $response;
    }

    protected function addPaymentCountInResponseForFailureAnalysis(&$response, $data)
    {
        $response[MerchantConstants::SUMMARY][MerchantConstants::NUMBER_OF_TOTAL_PAYMENTS] += $data->count;

        $status = $data->status;

        if (in_array($status, [Payment\Status::AUTHORIZED, Payment\Status::CAPTURED, Payment\Status::REFUNDED]) === true)
        {
            $response[MerchantConstants::SUMMARY][MerchantConstants::NUMBER_OF_SUCCESSFUL_PAYMENTS] += $data->count;
        }
        elseif ($status === Payment\Status::FAILED)
        {
            $errorSourceCategory = $this->getErrorSourceCategoryForFailureAnalysis($data->internal_error_code, $data->method);

            $response[MerchantConstants::FAILURE_DETAILS][$errorSourceCategory] += $data->count;
        }
    }

    public function getErrorSourceCategoryForFailureAnalysis($errorCode, $method)
    {
        [$errorCodeJson,] = $this->app['error_mapper']->getErrorMapping($errorCode, $method);

        if ((isset($errorCodeJson['source']) === true) and
            (key_exists($errorCodeJson['source'], MerchantConstants::ERROR_SOURCE_CATEGORY) === true))
        {
            return MerchantConstants::ERROR_SOURCE_CATEGORY[$errorCodeJson['source']];
        }

        return MerchantConstants::OTHER_FAILURE;
    }

    public function getErrorReason($errorCode, $method)
    {
        [$errorCodeJson,] = $this->app['error_mapper']->getErrorMapping($errorCode, $method);

        return $errorCodeJson['error_description'] ?? MerchantConstants::DEFAULT_ERROR_DESCRIPTION;
    }

    public function shouldShowSettlementUxRevamp(): bool
    {
        $variant = $this->app->razorx->getTreatment($this->merchant->getId(),
            Merchant\RazorxTreatment::SETTLEMENT_UX_REVAMP,
            $this->mode
        );

        $result = (strtolower($variant) === 'on');

        return $result;
    }

    public function getPrimaryBalance()
    {
        return $this->repo->balance->getMerchantBalanceByType($this->merchant->getId(),
                Merchant\Balance\Type::PRIMARY)->toArrayPublic();
    }

    public function fetchBalance($merchantId = null)
    {
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->getId();
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        //
        // For non-activated merchants in live mode, simply return 0.
        // For these merchants, balance entity is not yet created so
        // we need to create the exception here.
        //
        if (($this->mode === Mode::LIVE) and
            ($merchant->isActivated() === false) and
            (Account::isNodalAccount($merchantId) === false))
        {
            // TODO need to discuss this
            $balance[Balance\Entity::ID]      = $merchantId;
            $balance[Balance\Entity::BALANCE] = 0;

            return $balance;
        }

        $balance = $this->repo->balance->getMerchantBalance($merchant);

        return $balance->toArray();
    }

    public function fetchAccountBalances(array $input)
    {
        $merchantId = $this->merchant->getId();

        $cached = 'true';

        $this->trace->info(TraceCode::BALANCE_FETCH_REQUEST,
                           [
                               'input' =>  $input,
                               'merchant_id'  =>  $merchantId,
                           ]);

        if (empty($input['cached']) === false)
        {
            $cached = $input['cached'];
            unset($input['cached']);
        }

        $balance = $this->repo->balance->fetch($input, $merchantId)->toArrayPublic();

        foreach ($balance['items'] as &$b)
        {
            // Only call ledger when balance is of type 'banking and account_type 'shared'.
            if (($b[Balance\Entity::TYPE] === Balance\Type::BANKING) &&
                ($b[Balance\Entity::ACCOUNT_TYPE] === Balance\AccountType::SHARED))
            {

                // Only call ledger when "ledger_journal_reads" is enabled on the merchant.
                if($this->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true)
                {

                    $bankingAccount = $this->merchant->sharedBankingBalance->bankingAccount;

                    $ledgerResponse = (new LedgerCore())->fetchBalanceFromLedger($merchantId, $bankingAccount->getPublicId());

                    if ((empty($ledgerResponse) === false) &&
                        (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE]) === false) &&
                        (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE]) === false))
                    {
                        $b[Balance\Entity::BALANCE] = (int) $ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE];
                    }

                    break;
                }
            }
        }

        foreach ($balance['items'] as &$b)
        {
            if(($b[Balance\Entity::TYPE] === Balance\Type::BANKING) &&
               ($b[Balance\Entity::ACCOUNT_TYPE] === Balance\AccountType::DIRECT))
            {
                $variant = $this->app['razorx']->getTreatment($merchantId,
                                                              Experiment::SYNC_CALL_FOR_FRESH_BALANCE, $app['rzp.mode'] ?? Mode::LIVE);

                $this->trace->info(TraceCode::SYNC_CALL_FOR_FRESH_BALANCE_VARIANT_STATUS,
                                    [
                                       'variant_status' => $variant,
                                    ]);

                $dimension = [
                    ConstantMetric::LABEL_RZP_INTERNAL_APP_NAME => app('request.ctx')->getInternalAppName() ?? ConstantMetric::LABEL_NONE_VALUE,
                    Balance\Entity::CHANNEL             => $b[Balance\Entity::CHANNEL],
                    Merchant\Entity::MERCHANT_ID        => $merchantId,
                ];

                // if cached is false and variant is on and balance last fetched is beyond recency threshold (10 sec)
                // then only , we make sync call for balance fetch
                if (($cached === 'false') and ($variant === 'on'))
                {
                    $thresholdTimestamp = Carbon::now(Timezone::IST)->subSeconds(10)->getTimestamp();

                    if (($b[Balance\Entity::LAST_FETCHED_AT] === null) or
                        ($b[Balance\Entity::LAST_FETCHED_AT] <= $thresholdTimestamp))
                    {
                        $inputArray = [
                            Balance\Entity::CHANNEL     => $b[Balance\Entity::CHANNEL],
                            Balance\Entity::MERCHANT_ID => $merchantId,
                        ];

                        $startTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

                        $startTime = millitime();

                        $this->trace->info(TraceCode::BALANCE_FETCH_REQUEST_SYNC_CALL_STARTED,
                                           [
                                               'input'                  => $input,
                                               'thresholdTimestamp'  => $thresholdTimestamp,
                                               'last_fetched_at'     => $b[Balance\Entity::LAST_FETCHED_AT],
                                               'start_time'          => $startTimeStamp,
                                           ]);

                        $basDetails = (new \RZP\Models\BankingAccount\Core())->fetchAndUpdateGatewayBalanceWrapper($inputArray);

                        $timeTaken = millitime() - $startTime;

                        if(empty($basDetails) === false)
                        {
                            $b[Balance\Entity::BALANCE] = $basDetails->getGatewayBalance();
                            $b[Balance\Entity::LAST_FETCHED_AT] = $basDetails->getBalanceLastFetchedAt();

                            if ($basDetails->getBalanceLastFetchedAt() < $startTimeStamp)
                            {
                                $this->trace->info(TraceCode::BALANCE_FETCH_REQUEST_SYNC_CALL_UNSUCCESSFUL,
                                                   [
                                                       'balance'         => $basDetails->getGatewayBalance(),
                                                       'last_fetched_at' => $basDetails->getBalanceLastFetchedAt(),
                                                       'merchant_id'     => $merchantId,
                                                   ]);

                                $b[Balance\Entity::ERROR_INFO] = 'balance_fetch_sync_call_was_not_successful';

                                $this->trace->count(Metric::BALANCE_FETCH_REQUEST_SYNC_CALL_UNSUCCESSFUL_COUNT, $dimension);
                            }

                            else
                            {
                                $this->trace->info(TraceCode::BALANCE_FETCH_REQUEST_SYNC_CALL_SUCCESSFUL,
                                                   [
                                                       'balance'         => $basDetails->getGatewayBalance(),
                                                       'last_fetched_at' => $basDetails->getBalanceLastFetchedAt(),
                                                       'merchant_id'     => $merchantId,
                                                   ]);

                                $this->trace->count(Metric::BALANCE_FETCH_REQUEST_SYNC_CALL_SUCCESSFUL_COUNT, $dimension);
                            }
                        }

                        $this->trace->histogram(Metric::BALANCE_FETCH_REQUEST_SYNC_CALL_LATENCY, $timeTaken, $dimension);
                    }

                    else
                    {
                        $this->trace->info(TraceCode::BALANCE_FETCH_REQUEST_SYNC_CALL_WITHIN_RECENCY_THRESHOLD,
                                           [
                                               'balance'         => $b[Balance\Entity::BALANCE],
                                               'last_fetched_at' => $b[Balance\Entity::LAST_FETCHED_AT],
                                               'threshold'       => $thresholdTimestamp,
                                               'merchant_id'     => $merchantId,
                                           ]);

                        $this->trace->count(Metric::BALANCE_FETCH_REQUEST_SYNC_CALL_WITHIN_RECENCY_THRESHOLD_COUNT, $dimension);
                    }

                }
            }
        }

        $shouldFetchCardDetails = ((isset($input[Balance\Entity::ACCOUNT_TYPE]) === true) and
                                   (is_array($input[Balance\Entity::ACCOUNT_TYPE]) === true) and
                                   (in_array(Balance\AccountType::CORP_CARD, $input[Balance\Entity::ACCOUNT_TYPE], true) === true));

        foreach ($balance['items'] as $index => &$balanceEntity)
        {
            if (($balanceEntity[Balance\Entity::TYPE] === Balance\Type::BANKING) and
                ($balanceEntity[Balance\Entity::ACCOUNT_TYPE] === Balance\AccountType::CORP_CARD))
            {
                // If account_type is corp_card and it is part of input, fetch card details from capital-cards service
                if ($shouldFetchCardDetails === true)
                {
                    $response = $this->app[CapitalCardsClient::CAPITAL_CARDS_CLIENT]->getCorpCardAccountDetails(
                        ['balance_id' => $balanceEntity[Balance\Entity::ID]]);

                    // If no records are found in the capital-cards service , remove corp_card balance from response
                    if (empty($response) === true)
                    {
                        unset($balance[Base\PublicCollection::ITEMS][$index]);
                        $balance[Base\PublicCollection::COUNT]--;
                    }
                    else
                    {
                        $balanceEntity[Balance\Entity::CORP_CARD_DETAILS] = $response;
                    }
                }
                else
                {
                    // return corp_card balance in response only if explicitly asked for
                    unset($balance[Base\PublicCollection::ITEMS][$index]);
                    $balance[Base\PublicCollection::COUNT]--;
                }
            }
        }

        $balance[Base\PublicCollection::ITEMS] = array_values($balance[Base\PublicCollection::ITEMS]);

        if ($this->auth->isStrictPrivateAuth() === true)
        {
            array_walk($balance['items'], function(& $b){
                $b[Balance\Entity::ACCOUNT_NUMBER] = mask_except_last4($b[Balance\Entity::ACCOUNT_NUMBER]);
            });
        }

        // Tracking balance fetch requests - currently for slack app
        $this->trackBalanceEvent( $input);

        return $balance;
    }

    public function updateLockedBalance(array $input, string $balanceId)
    {
        /** @var Balance\Entity $balance */
        $balance = $this->repo->balance->findOrFailById($balanceId);

        $balance->getValidator()->validateInput(Balance\Validator::LOCKED_BALANCE, $input);

        $lockedBalance = $input[Merchant\Balance\Entity::LOCKED_BALANCE];

        $oldLockedBalance = $balance->getLockedBalance();

        $traceData = [
            'input'                     => $input,
            'balance_id'                => $balanceId,
            'balance_type'              => $balance->getType(),
            'balance_account_type'      => $balance->getAccountType(),
            'current_locked_balance'    => $oldLockedBalance,
        ];

        $this->trace->info(TraceCode::LOCKED_BALANCE_UPDATE_REQUEST, $traceData);

        if (($balance->isTypeBanking() === false) or
            ($balance->isAccountTypeShared() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOCKED_BALANCE_UPDATE_NON_BANKING,
                null,
                $traceData);
        }

        $balance->setLockedBalance($lockedBalance);

        $this->repo->saveOrFail($balance);

        $response = [
            'balance_id'            => $balance->getId(),
            'current_balance'       => $balance->getBalance(),
            'old_locked_balance'    => $oldLockedBalance,
            'new_locked_balance'    => $balance->getLockedBalance(),
        ];

        $this->trace->info(
            TraceCode::LOCKED_BALANCE_UPDATE_RESPONSE,
            $response);

        return $response;
    }

    public function editAmountCredits($merchantId, $input)
    {
        (new Validator)->validateInput('edit_credits', $input);

        $amountCredits = $input['credits'];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $balance = $this->repo->balance->editMerchantAmountCredits($merchant, $amountCredits);

        return $balance->toArray();
    }

    public function assignPricingPlan($id, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_PRICING_PLAN_ASSIGN_REQUEST,
            [
                'merchant_id' => $id,
                'input'       => $input
            ]);

        /** @var Entity $merchant */
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if (isset($input['pricing_plan_id']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_ID_REQURED,
                'pricing_plan_id');
        }

        $orgId = $merchant->org->getId();

        /** @var Plan $plan */
        $plan = $this->repo->pricing->getPricingPlanByIdAndOrgId($input['pricing_plan_id'], $orgId);

        // validate if this plan can be set for this merchant.
        // Refer: https://github.com/razorpay/api/issues/324

        $methods = $this->repo->methods->getMethodsForMerchant($merchant);

        (new Methods\Core)->validatePricingPlanForMethods($merchant, $plan, $methods, false);

        $this->validatePricingPlanForFeeBearer($merchant, $plan);

        $originalPricingPlan = null;

        if (empty($merchant->pricing) === false)
        {
            $originalPricingPlan = $merchant->pricing->getPlanName();
        }

        if(isset($input['spr']) && $input['spr']) {
            return $this->handleSPRWorkflowCreate($input, $merchant, $plan);
        }

        [$original, $dirty] = [
            // Current plan
            ['pricing_plan' => $originalPricingPlan],
            // New plan
            ['pricing_plan' => $plan->first()->getPlanName()],
        ];

        if(isset($input[WorkflowService\Builder\Constants::SPR_APPROVED]) === false ||
            $input[WorkflowService\Builder\Constants::SPR_APPROVED] === false)
        {

        $this->app['workflow']
             ->setEntity($merchant->getEntity())
             ->handle($original, $dirty);

        }


        $merchant->setPricingPlan($input['pricing_plan_id']);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, SlackActions::ASSIGN_PRICING, $input);

        return $plan->toArrayPublic();
    }

    private function handleSPRWorkflowCreate(array $input, Merchant\Entity $merchant, Plan $plan) {

        $input = (new WorkflowService\Builder\PricingWorkflow())->buildCreatePricingWorkflowPayload($input, $merchant, $plan);

        $this->trace->info(
            TraceCode::MERCHANT_PRICING_PLAN_ASSIGN_REQUEST_WORKFLOW,
            [
                'merchant_id' => $merchant->getId(),
                'input'       => $input
            ]);

        return (new WorkflowService\Client())->createWorkflowProxy($input);
    }

    private function handleSPRWorkflowProcessed(array $input, Merchant\Entity $merchant) {
        if(isset($input['spr_assigned']) && $input['spr_assigned']) {

            $featureParams = [
                Feature\Entity::ENTITY_ID   => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                Feature\Entity::NAMES       => [FeatureConstants::SPR_DISABLE_METHOD_RESET],
                Feature\Entity::SHOULD_SYNC => true
            ];

            $features = (new Feature\Service)->addFeatures($featureParams);

            $this->trace->info(
                TraceCode::MERCHANT_PRICING_PLAN_ASSIGN_REQUEST,
                [
                    'features_assigned' => $features
                ]);
        } else {
            if($merchant->isFeatureEnabled(Features::SPR_DISABLE_METHOD_RESET)) {
                $features = (new Feature\Service)->deleteEntityFeature(
                    'accounts',
                    $merchant->getId(),
                    FeatureConstants::SPR_DISABLE_METHOD_RESET,
                    [Feature\Entity::SHOULD_SYNC => true]);


                $this->trace->info(
                    TraceCode::MERCHANT_PRICING_PLAN_ASSIGN_REQUEST,
                    [
                        'features_assigned' => $features
                    ]);
            }

        }
    }

    public function isAdminLoggedInAsMerchant()
    {
        $isAdminLoggedInAsMerchant = $this->app['basicauth']->isAdminLoggedInAsMerchantOnDashboard();

        $this->trace->info(TraceCode::IS_ADMIN_LOGGED_IN_AS_MERCHANT, ["isAdminLoggedInAsMerchant" => $isAdminLoggedInAsMerchant]);

        if ( $isAdminLoggedInAsMerchant === true )
        {
            return ["is_admin_as_merchant" => true];
        }
        return ["is_admin_as_merchant" => false];
    }

    public function assignSettlementScheduleIncludingLinkedAccounts($id, $input)
    {
        $this->trace->info(TraceCode::SCHEDULE_ASSIGN_RAZORX_SUCCESS, []);

        $merchantIds = $this->repo->merchant->fetchLinkedAccountMids($id);

        array_unshift($merchantIds, $id);

        $scheduleTaskCore = new ScheduleTask\Core();

        $succeededIds = [];

        $scheduleTask = $this->repo->transaction(function() use($merchantIds, $id, $input, $scheduleTaskCore, & $succeededIds)
        {
            $parentMerchantScheduleTask = null;

            foreach ($merchantIds as $merchantId)
            {
                $merchant = $this->repo->merchant->findByIdAndOrgId($merchantId, $this->auth->getOrgId());

                $input[ScheduleTask\Entity::TYPE] = ScheduleTask\Type::SETTLEMENT;

                $scheduleTaskObj = $scheduleTaskCore->createOrUpdate($merchant, $merchant, $input);

                $parentMerchantScheduleTask = ($merchantId === $id) ? $scheduleTaskObj : $parentMerchantScheduleTask;

                array_push($succeededIds, $merchantId);
            }
            return $parentMerchantScheduleTask;
        });

        $this->trace->info(
            TraceCode::SCHEDULE_ASSIGNED_SUCCESSFULLY,
            [
                "count"        => count($succeededIds),
                "merchant_ids" => $succeededIds
            ]
        );

        return $scheduleTask->toArrayPublic();
    }

    public function assignSettlementSchedule($id, $input)
    {
        $this->trace->info(
            TraceCode::SCHEDULE_ASSIGN_REQUEST,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        return $this->assignSettlementScheduleIncludingLinkedAccounts($id, $input);
    }

    public function bulkAssignSchedule(array $input): array
    {
        $startTime = millitime();

        $this->trace->info(TraceCode::MERCHANT_SCHEDULE_BULK_REQUEST, $input);

        $this->increaseAllowedSystemLimits();

        (new Validator)->validateInput('bulk_assign_schedule', $input);

        $merchantIds = $input['merchant_ids'];
        $schedule    = $input['schedule'];

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($merchantId, $schedule)
                {
                    $this->assignSettlementSchedule($merchantId, $schedule);
                });
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    \Razorpay\Trace\Logger::ERROR,
                    TraceCode::MERCHANT_SCHEDULE_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'input'       => $schedule,
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        $timeTaken = millitime() - $startTime;

        $this->trace->info(
            TraceCode::BULK_ACTION_RESPONSE_TIME,
            [
                'action'          => 'assign_schedule',
                'time_taken'      => $timeTaken,
            ]);

        return [
            'total_count'  => count($merchantIds),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];
    }

    public function bulkAssignPricing(array $input): array
    {
        $startTime = millitime();

        $this->trace->info(TraceCode::MERCHANT_PRICING_BULK_REQUEST, $input);

        $this->increaseAllowedSystemLimits();

        (new Validator)->validateInput('bulk_assign_pricing', $input);

        $merchantIds   = $input['merchant_ids'];
        unset($input['merchant_ids']);

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($merchantId, $input)
                {
                    $this->assignPricingPlan($merchantId, $input);
                });
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    Trace::ERROR,
                    TraceCode::MERCHANT_PRICING_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'input'       => $input,
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        // Tracing all ids together for ease of re-running in case of errors. The dashboard error
        // display is not that convenient and can be lost. Collecting from the previous logs of
        // individual failures is more time consuming.
        $this->trace->error(TraceCode::MERCHANT_PRICING_BULK_ALL_FAILED_IDS, [ 'failed_ids' => $failedIds]);

        $timeTaken = millitime() - $startTime;

        $this->trace->info(
            TraceCode::BULK_ACTION_RESPONSE_TIME,
            [
                'action'          => 'assign_pricing',
                'time_taken'      => $timeTaken,
            ]);

        return [
            'total_count'  => count($merchantIds),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];
    }

    public function bulkSubmerchantAssign($input)
    {
        $validator = (new Validator);

        $submerchantAssignBatchCollection = new Base\PublicCollection;

        $validator->validateBulkSubmerchantAssignCount($input);

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        $validator->validateBatchId($batchId);

        $idempotencyKey = null;

        $this->trace->info(
            TraceCode::BATCH_SERVICE_SUBMERCHANT_ASSIGN_BULK_REQUEST,
            [
                'batch_id'  => $batchId,
                'input'     => $input,
            ]);

        $terminalService = new Terminal\Service;

        foreach($input as $item)
        {
            try
            {
                $this->repo->transaction(function() use (& $item,
                                                         & $submerchantAssignBatchCollection,
                                                         & $batchId,
                                                         & $idempotencyKey,
                                                         $validator,
                                                         $terminalService)
                {
                    $validator->validateInput('bulk_submerchant_assign', $item);

                    $idempotencyKey = $item['idempotency_key'];

                    $data = $this->processEntryForBulkSubmerchantAssign(
                        $item, $batchId, $idempotencyKey, $terminalService);

                    $submerchantAssignBatchCollection->push($data);
                });

            }
            catch(Exception\BaseException $exception)
            {
                $this->trace->traceException($exception,
                    Trace::ERROR,
                    TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST
                );

                $exceptionData = [
                    'batch_id'        => $batchId,
                    'idempotency_key' => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                ];

                $submerchantAssignBatchCollection->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                    Trace::CRITICAL,
                    TraceCode::BATCH_SERVICE_BULK_EXCEPTION
                );

                $exceptionData = [
                    'batch_id'        => $batchId,
                    'idempotency_key' => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                    Error::HTTP_STATUS_CODE => 500,
                ];

                $submerchantAssignBatchCollection->push($exceptionData);
            }
        }

        $this->trace->info(
            TraceCode::BATCH_SERVICE_SUBMERCHANT_ASSIGN_BULK_RESPONSE,
            [
                'batch_id'  => $batchId,
                'output'    => $submerchantAssignBatchCollection->toArrayWithItems(),
            ]);

        return $submerchantAssignBatchCollection->toArrayWithItems();
    }

    protected function processEntryForBulkSubmerchantAssign($item, $batchId, $idempotencyKey, $terminalService)
    {
        $terminalId     = $item['terminal_id'];
        $submerchantId  = $item['submerchant_id'];

        /*
            Idempotency is checked inside Terminal/Core before assigning a
            terminal to a merchant to whom that terminal has been already assigned.
        */
        $terminalService->addMerchantToTerminal($terminalId, $submerchantId);

        return [
            'batch_id'        => $batchId,
            'submerchant_id'  => $submerchantId,
            'idempotency_key' => $idempotencyKey,
            'terminal_id'     => $terminalId,
            'status'          => 'SUCCESS',
            'failure_reason'  => null,
        ];
    }

    public function migrateMerchantToSettlementSchedules($input)
    {
        $this->trace->info(TraceCode::SCHEDULE_MIGRATION_INITIATED);

        if (isset($input['merchant_ids']))
        {
            $merchants = $this->repo->merchant->findMany($input['merchant_ids']);
        }
        else
        {
            $merchants = $this->repo->merchant->getFewMerchantsWithNoCorrespondingScheduleTasks();
        }

        $migrationSummary = [
            'migrated_ids_count' => 0,
            'failed_ids'         => [],
        ];

        foreach ($merchants as $merchant)
        {
            try
            {
                $defaultDelay = Entity::DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

                $schedule = (new Schedule\Core)->getOrCreateDefaultSchedule($defaultDelay);

                $input = [
                    ScheduleTask\Entity::METHOD      => null,
                    ScheduleTask\Entity::TYPE        => ScheduleTask\Type::SETTLEMENT,
                    ScheduleTask\Entity::SCHEDULE_ID => $schedule->getId()
                ];

                (new ScheduleTask\Core)->createOrUpdate($merchant, $merchant, $input);

                $migrationSummary['migrated_ids_count'] += 1;
            }
            catch (\Exception $ex)
            {
                $merchantId = $merchant->getId();

                $this->trace->info(
                    TraceCode::SCHEDULE_MIGRATION_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'schedule_id' => $schedule->getId(),
                        'error'       => $ex->getMessage(),
                    ]);

                $migrationSummary['failed_ids'][] = $merchantId;
            }
        }

        $migrationSummary['fail_count'] = count($migrationSummary['failed_ids']);

        $this->trace->info(TraceCode::SCHEDULE_MIGRATION_COMPLETE, $migrationSummary);

        return $migrationSummary;
    }

    public function getPricingPlan($id)
    {
        $orgId = $this->auth->getOrgId();

        $merchant = $this->repo->merchant->findByIdAndOrgId($id, $orgId);

        $pricingPlanId = $merchant->getPricingPlanId();

        $plan = new Plan;

        if (empty($pricingPlanId) === false)
        {
            Org\Entity::verifyIdAndSilentlyStripSign($orgId);

            $plan = $this->repo->pricing->getPricingPlanByIdAndOrgId($pricingPlanId, $orgId);
        }

        return $plan->toArrayPublic();
    }

    public function proxyGetPricingPlan()
    {
        $merchant = $this->repo->merchant->find($this->merchant->getId());

        $pricingPlanId = $merchant->getPricingPlanId();

        $plan = new Plan;

        if (empty($pricingPlanId) === false)
        {
            $plan = $this->repo->pricing->getPricingPlanByIdWithProductAndFeatureFilter($pricingPlanId);
        }

        return $plan->toArrayProxy();
    }

    public function sendActivationEmail(array $input)
    {
        $act = new Activate($this->app);

        $response = [];

        foreach ($input['ids'] as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                if ($merchant->isActivated())
                {
                    $act->sendActivationEmail($merchant);

                    $response[$merchantId] = 'Queued merchant activation email';
                }
                else
                {
                    $response[$merchantId] = 'Merchant is not activated';
                }
            }
            catch (\Exception $e)
            {
                $response[$merchantId] = $e->getMessage();
            }
        }

        return $response;
    }

    public function liveEnable($id)
    {
        $this->trace->info(
            TraceCode::MERCHANT_LIVE_ENABLE_REQUEST,
            [
                'merchant_id' => $id,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        if ($merchant->isLive())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_LIVE);
        }

        if ($merchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED);
        }

        $oldMerchant = clone $merchant;

        $merchant->liveEnable();

        // Triggering
        $workflow = $this->app['workflow']
                         ->setEntity($merchant->getEntity())
                         ->handle($oldMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, 'enable');

        return $merchant->toArrayPublic();
    }

    public function liveDisable($id)
    {
        $this->trace->info(
            TraceCode::MERCHANT_LIVE_DISABLE_REQUEST,
            [
                'merchant_id' => $id,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        if ($merchant->isLive() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE);
        }

        $oldMerchant = clone $merchant;

        $merchant->liveDisable();

        // Triggering
        $workflow = $this->app['workflow']
                         ->setEntity($merchant->getEntity())
                         ->handle($oldMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, 'disable');

        return $merchant->toArrayPublic();
    }

    public function toggleInternational($input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_INTERNATIONAL_TOGGLE_REQUEST,
            [
                'input'     => $input,
            ]);

        (new Validator)->validateInput('toggleInternational', $input);

        $toggleValue = (bool) ($input['international'] ?? false);

        $merchant = $this->core()->toggleInternational($this->merchant, $toggleValue);

        if ($toggleValue === true)
        {
            [$segmentEventName, $segmentProperties] = $this->core()->pushSelfServeSuccessEventsToSegment();

            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'International Payments Applied';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $this->merchant, $segmentProperties, $segmentEventName
            );
        }

        return $merchant->toArrayPublic();
    }

    public function action($id, array $input, bool $useWorkflows = true)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT_ACTION,
            [
                'merchant_id' => $id,
                'input'       => $input,
                'useWorkflows'=> $useWorkflows
            ]);

        if (isset($input[Constants::USE_WORKFLOWS]))
        {
            unset($input[Constants::USE_WORKFLOWS]);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchant = $this->core()->action($merchant, $input, $useWorkflows);

        return $merchant->toArrayPublic();
    }

    /**
     * Todo : $id is Not used to fetch merchant. Kept to support Backward Compatible.
     * @param $id
     * @param $input
     * @return array
     */
    public function addBankAccount($id, $input)
    {
        $merchant = app('basicauth')->getMerchant();

        if(($this->app['basicauth']->isAdminAuth() === false) and
            (($merchant->org->isFeatureEnabled(Feature\Constants::ORG_POOL_ACCOUNT_SETTLEMENT) === true) or
            $merchant->isFeatureEnabled(Feature\Constants::OPGSP_IMPORT_FLOW) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCOUNT_ACTION_NOT_SUPPORTED);

        }

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $merchant);

        // Using Request::input() since we do not want the file as input to log
        $this->logActionToSlack($merchant, SlackActions::EDIT_BANK_DETAILS, Request::input());

        return $ba->toArray();
    }

    public function editBankAccount($id, $input)
    {
        $bankAccount = $this->repo->bank_account->findOrFailPublic($id);

        $bankAccount = (new BankAccount\Core)->editBankAccount($bankAccount, $input);

        return $bankAccount->toArray();
    }

    public function bankAccountUpdate(array $input)
    {
        $merchant = app('basicauth')->getMerchant();

        return (new BankAccount\Core)->bankAccountUpdate($merchant, $input);
    }

    public function bankAccountFileUpload(array $input)
    {
        $merchant = app('basicauth')->getMerchant();

        return (new BankAccount\Core)->bankAccountFileUpload($merchant, $input);
    }

    public function bankAccountUpdatePostPennyTestingWorkflow(array $input)
    {
        [$merchant, $merchantDetails] = (New Merchant\Detail\Core())->getMerchantAndSetBasicAuth($input[Constants::MERCHANT_ID]);

        $ba =  (new BankAccount\Core)->bankAccountUpdatePostPennyTestingWorkflow($merchant, $input);

        return $ba->toArray();
    }

    public function fundAdditionTPV(array $input)
    {
        $merchantCore = new Merchant\Core;

        $this->trace->info(Tracecode::FUND_ADDITION_REQUEST, $input);

        switch($input['method'])
        {
            case 'online_payment' :
                return $merchantCore->createOrderForFundAddition($input);

            case 'account_transfer' :
                return $merchantCore->getVirtualAccountForFundAddition($input);
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_FUND_ADDITION_METHOD_IS_INVALID,
            null,
            $input);
    }

    public function addFundsViaWebhook($type, $input)
    {
        $merchantCore = new Merchant\Core;

        try
        {
            switch ($type) {
                case 'online_payment' :
                    return $merchantCore->fundAdditionViaOrders($input);

                case 'account_transfer' :
                    return $merchantCore->fundAdditionViaBankTransfer($input);
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ADDITION_METHOD_IS_INVALID,
                null,
                [
                    "type" => $type,
                    "input" => $input
                ]
            );
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FUND_ADDITION_FAILED,
                ["type" => $type]
            );

            return [
                "error_code" => $e->getCode()
            ];
        }
    }

    /**
     * This function returns if there any open workflow actions associated with the current bank account entity of a
     * merchant. @todo: Replace this with a more generic approach based on primary entity
     *
     * @param $id
     *
     * @return bool
     */
    public function getBankAccountChangeStatus($id)
    {
        return (($this->getBankAccountChangeViaWorkflowStatus($id) === true) or
                ($this->getBankAccountChangeViaPennyTestingStatus($id)));
    }

    public function isBankAccountChangeWorkflowOpen($id)
    {
        return ($this->getBankAccountChangeViaWorkflowStatus($id) === true);
    }

    public function getProductInternationalStatus(array $input = []) :array
    {
        $merchantCore = new Merchant\Core;

        $merchant = $this->merchant;

        $response[Constants::DATA] = $merchantCore->getProductInternationalStatus($merchant, $input);

        return $response;
    }

    public function openWorkflowExists(string $workflowType) : bool
    {
        $merchantCore = new Merchant\Core;

        $merchant = $this->merchant;

        [$entityId, $entity] = $merchantCore->fetchWorkflowData($workflowType, $merchant);

        $actions = (new Action\Core())->fetchOpenActionOnEntityOperation(
            $entityId,
            $entity,
            Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION]);

        $actions = $actions->toArray();

        // If there are any action in progress
        return (empty($actions) === false);
    }

    /**
     * @return bool
     */
    public function getWebsiteStatus()
    {
        $type = Constants::ADDITIONAL_WEBSITE;

        return $this->openWorkflowExists($type);
    }

    public function getBankAccount($id,  $type = null)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $ba = $this->repo->bank_account->getBankAccount($merchant,$type);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        return $ba->toArray();
    }

    public function getOwnBankAccount()
    {
        $ba = $this->repo->bank_account->getBankAccount($this->merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        $bankAccount = $ba->toArrayPublic();

        $bankAccount[BankAccount\Entity::UPDATED_AT] = $ba->getUpdatedAtAttribute();

        return $bankAccount;
    }

    public function generateTestBankAccounts()
    {
        $merchants = $this->repo->merchant->fetchMerchantWhereTestBankIsNull();
        $fetched   = $merchants->count();

        $core = new BankAccount\Core;

        $count = 0;

        foreach ($merchants as $merc)
        {
            $core->createTestBankAccount($merc);
            $count++;
        }

        return ['fetched' => $fetched, 'processed' => $count];
    }

    public function getBanks($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $banks = (new Methods\Core)->getEnabledAndDisabledBanks($merchant);

        return $banks;
    }

    public function getEnabledBanks()
    {
        $methods = (new Methods\Core)->getEnabledAndDisabledBanks($this->merchant);

        return $methods['enabled'];
    }

    public function getOrgDetails(string $id): array
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($id, [CE::ORG]);

        $org = $merchant->org->toArrayPublic();

        $org[Org\Entity::PRIMARY_HOST_NAME] = $merchant->org->getPrimaryHostName();

        return $org;
    }

    /**
     * Checks conditions for showing T&C popup to the merchant
     * @return array
     */
    public function getTermsAndConditionPopupStatus(): array
    {
        if($this->merchant->org->isFeatureEnabled(Feature\Constants::ENABLE_TC_DASHBOARD) === false)
        {
            return ['show_tnc_popup' => false ];
        }

        if($this->merchant->isFeatureEnabled(Feature\Constants::DISABLE_TC_DASHBOARD) === true)
        {
            return ['show_tnc_popup' => false ];
        }

        if((new Detail\Service())->checkIfConsentsPresent($this->merchant->getId(), ConsentConstant::VALID_LEGAL_DOC_BANKING_ORG) === true)
        {
            return ['show_tnc_popup' => false ];
        }

        return ['show_tnc_popup' => true];
    }

    public function setPaymentBanks($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $enabledDisabledBanks = (new Methods\Core)->setPaymentBanksForMerchant(
            $merchant, $input);

        $this->logActionToSlack($merchant, SlackActions::ASSIGN_BANKS);

        return $enabledDisabledBanks;
    }

    public function getFeeBearer()
    {
        $feeBearer = $this->merchant->isFeeBearerCustomer();

        return $feeBearer;
    }

    public function getAppScalabilityConfig()
    {
        $merchant                   = $this->merchant;

        $currentUser                = $this->user;

        $currentUserRole            = $this->app['basicauth']->getUserRole();

        $lastMonthTotalTransactions = $this->getMerchantTransactionsInLastMonth();

        $merchantAppSegment         = $this->core()->getMerchantSegment($lastMonthTotalTransactions);

        $this->trace->info(TraceCode::MERCHANT_USER_APP_CONFIG,
                           [
                               'merchant_id' => $merchant->getId(),
                               'user_id'     => $currentUser->getId(),
                               'user_role'   => $currentUserRole,
                               'segment'     => $merchantAppSegment,
                           ]);

        $merchantAppSegmentWidgetData = $this->core()->getCurrentSegmentWidgetData($merchantAppSegment);

        // sort widget based on priority, first product is the hero product
        array_multisort(array_column($merchantAppSegmentWidgetData, Constants::PRIORITY), SORT_ASC,
                        $merchantAppSegmentWidgetData);

        $response = [];

        $response[Constants::SEGMENT_TYPE] = $merchantAppSegment;
        $response[Constants::WIDGETS]      = [];

        foreach ($merchantAppSegmentWidgetData as $widget => $value)
        {
            if (in_array($currentUserRole, $value[Constants::USER_ROLES]) === true)
            {
                $data = [];

                $data[Constants::TYPE] = $widget;

                try
                {
                    $data = array_merge($data, $this->core()->getWidgetProperty($widget, $merchant->getId(), $currentUser->getId()));
                }
                catch (\Throwable $exception)
                {
                    $this->trace->error(TraceCode::MERCHANT_USER_APP_FAILED_TO_WIDGET_PROPS,
                                        [
                                            'merchant_id'   => $merchant->getId(),
                                            'user_id'       => $currentUser->getId(),
                                            'widget'        => $widget,
                                            'error_message' => $exception->getMessage(),
                                        ]);

                    $data[Constants::ERROR] =
                        [
                            Constants::CODE        => $exception->getCode(),
                            Constants::DESCRIPTION => $exception->getMessage()
                        ];
                }

                $response[Constants::WIDGETS][] = $data;
            }
        }

        return $response;
    }

    public function changeAppMerchantUserFTUX($input)
    {
        (new Validator)->validateInput('app_scalability_change_ftux', $input);

        $merchant    = $this->merchant;

        $currentUser = $this->user;

        $this->trace->info(TraceCode::MERCHANT_USER_APP_PRODUCT_CHANGE_FTUX,
                           [
                               'merchant_id' => $merchant->getId(),
                               'user_id'     => $currentUser->getId(),
                               'input'       => $input,
                           ]);

        $this->core()->changeMerchantUserFTUX($input, $merchant->getId(), $currentUser->getId());

        return [];
    }

    public function merchantUserIncrementProductSession()
    {
        $merchant        = $this->merchant;

        $currentUser     = $this->user;

        $currentUserRole = $this->app['basicauth']->getUserRole();

        $this->trace->info(TraceCode::MERCHANT_USER_APP_INCR_SESSION,
                           [
                               'merchant_id' => $merchant->getId(),
                               'user_id'     => $currentUser->getId(),
                               'user_role'   => $currentUserRole,
                           ]);

        if (in_array($currentUserRole, [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]) === true)
        {
            $this->core()->merchantUserIncrementProductSession($merchant->getId(), $currentUser->getId());
        }

        return [];
    }

    public function getMerchantPaymentsWithOrderSource($input)
    {
        $this->trace->info(TraceCode::MERCHANT_USER_APP_PAYMENT_WITH_SOURCE_START,
                           [
                               'input' => $input,
                           ]);

        return $this->core()->merchantPaymentsWithOrderSource($input);
    }

    public function getMerchantDataForSegmentAnalysis()
    {
        $this->trace->info(TraceCode::GET_MERCHANT_DATA_FOR_SEGMENT,[]);

        $merchant = $this->app['basicauth']->getMerchant();

        $merchantDetails = $merchant->merchantDetail;

        /*
         * removing it temporarily
         *
        $firstTransactionTimeStamp = $this->repo->useSlave(function () use ($merchant)
        {
            return $this->repo->payment->getMerchantFirstAuthorizedPaymentTimeStamp($merchant->getId());
        });
        */

        $firstTransactionTimeStamp = null;

        $isDruidMigrationEnabled = (new Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::DRUID_MIGRATION);

        if($isDruidMigrationEnabled === true)
        {
            $data = $this->getDataFromPinot($merchant->getId());
        }
        else
        {
            $data = $this->getDataFromDruid($merchant->getId());
        }

        return [
            self::SEGMENT_DATA_USER_BUSINESS_CATEGORY          => $merchantDetails->getBusinessCategory(),
            self::SEGMENT_DATA_ACTIVATION_STATUS               => $merchantDetails->getActivationStatus(),
            self::SEGMENT_DATA_MCC                             => $merchant->getCategory(),
            self::SEGMENT_DATA_ACTIVATED_AT                    => $merchant->getactivatedAt(),
            self::SEGMENT_DATA_USER_ROLE                       => $this->app['basicauth']->getUserRole(),
            self::SEGMENT_DATA_FIRST_TRANSACTION_TIMESTAMP     => $firstTransactionTimeStamp,
            self::SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION => $data[self::SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION] ?: null,
            self::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV          => (empty($data[self::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV]) === false ) ? (string)$data[self::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV]: null,
            self::SEGMENT_DATA_AVERAGE_MONTHLY_GMV             => $data[self::SEGMENT_DATA_AVERAGE_MONTHLY_GMV] ?: null,
            self::SEGMENT_DATA_PRIMARY_PRODUCT_USED            => $data[ self::SEGMENT_DATA_PRIMARY_PRODUCT_USED] ?: null,
            self::SEGMENT_DATA_PPC                             => $data[self::SEGMENT_DATA_PPC] ?: null,
            self::SEGMENT_DATA_MTU                             => isset($data[self::SEGMENT_DATA_MTU]) ? $data[self::SEGMENT_DATA_MTU] : null,
            self::SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS    => $data[self::SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS] ?: null,
            self::SEGMENT_DATA_PG_ONLY                         => isset($data[self::SEGMENT_DATA_PG_ONLY]) ? $data[self::SEGMENT_DATA_PG_ONLY] : null,
            self::SEGMENT_DATA_PL_ONLY                         => isset($data[self::SEGMENT_DATA_PL_ONLY]) ? $data[self::SEGMENT_DATA_PL_ONLY] : null,
            self::SEGMENT_DATA_PP_ONLY                         => isset($data[self::SEGMENT_DATA_PP_ONLY]) ? $data[self::SEGMENT_DATA_PP_ONLY] : null
        ];
    }

    public function getDataFromDruidForMerchantIds(array $merchantIdList)
    {
        $strMerchantIds = implode(', ', array_map(function ($val) { return sprintf('\'%s\'', $val);}, $merchantIdList));

        $query = 'select *from druid.segment_fact as merchant_data where merchant_data.merchant_details_merchant_id in (%s)';

        $query = sprintf($query, $strMerchantIds);

        $content = [
            'query' => $query
        ];

        $druidService = $this->app['druid.service'];

        [$error, $data] = $druidService->getDataFromDruid($content);

        if (empty($error) === false)
        {
            return [];
        }

        return $data;
    }

    public function getDataFromPinotForMerchantIds(array $merchantIdList)
    {
        $strMerchantIds = implode(', ', array_map(function ($val) { return sprintf('\'%s\'', $val);}, $merchantIdList));

        $query = 'select * from pinot.segment_fact where segment_fact.merchant_details_merchant_id in (%s)';

        $query = sprintf($query, $strMerchantIds);

        $content = [
            'query' => $query
        ];

        $pinotService = $this->app['eventManager'];

        $dataForMerchantIds = $pinotService->getDataFromPinot($content, self::REQUEST_TIMEOUT_GET_DATA_FOR_SEGMENT);

        $data = [];

        foreach ($dataForMerchantIds as $dataForMerchantId)
        {
            $dataFromPinot = $pinotService->parsePinotDefaultType($dataForMerchantId, 'segment_fact');;
            array_push($data, $dataFromPinot);
        }

        return $data;
    }

    public function getDataFromDruid($merchantId)
    {
        $query = 'select * from druid.segment_fact as merchant_data where merchant_data.merchant_details_merchant_id = \'%s\'';

        $query = sprintf($query, $merchantId);

        $content = [
            'query' => $query
        ];

        $druidService = $this->app['druid.service'];

        [$error, $data] = $druidService->getDataFromDruid($content, self::REQUEST_TIMEOUT_GET_DATA_FOR_SEGMENT);

        if (empty($error) === false)
        {
            return null;
        }

        if (isset($data[0]) === false)
        {
            $this->trace->info(TraceCode::DRUID_REQUEST_FAILURE, [
                'message' => self::MERCHANT_DATA_NOT_FOUND_ON_DRUID
            ]);

            return null;
        }

        return $data[0];
    }

    public function getDataFromPinot($merchantId)
    {
        $query = 'select * from pinot.segment_fact where segment_fact.merchant_details_merchant_id = \'%s\'';

        $query = sprintf($query, $merchantId);

        $content = [
            'query' => $query
        ];

        $pinotService = $this->app['eventManager'];

        $data = $pinotService->getDataFromPinot($content, self::REQUEST_TIMEOUT_GET_DATA_FOR_SEGMENT);

        if (empty($data) === true)
        {
            $this->trace->info(TraceCode::HARVESTER_REQUEST_FAILURE, [
                'message' => self::MERCHANT_DATA_NOT_FOUND_ON_PINOT
            ]);

            return null;
        }

        return $pinotService->parsePinotDefaultType($data[0], 'segment_fact');
    }

    public function getPaymentMethods()
    {
        $formattedMethods = (new Methods\Core)->getFormattedMethods($this->merchant);

        // Licious has dependency on this field in their android app
        if ($this->merchant->getId() === '5yZ76HWrvL9g2l')
        {
            $formattedMethods['http_status_code'] = 200;
        }

        return $formattedMethods;
    }

    public function getPaymentMethodsWithOffersForCheckout($input): array
    {
        return (new Checkout())->getPaymentMethodsWithOffersForCheckout($input, $this->merchant);
    }

    /**
     * Get Payment Methods by Merchant Id
     *
     * @param string $merchantId
     *
     * @return array
     */
    public function getPaymentMethodsById($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
            $merchantId, ['methods', \RZP\Models\Merchant\Entity::GROUPS, Entity::ADMINS]);

        $this->merchant = $merchant;

        $this->auth->setMerchant($this->merchant);

        return (new Methods\Core)->getFormattedMethods($this->merchant);
    }

    public function setPaymentMethods($merchantId, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        return (new Methods\Core)->setPaymentMethods($merchant, $input);
    }

    public function editMethods($input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $this->merchant->getId(),
                'input' => $input,
            ]);

        if($this->merchant->isFeatureEnabled(Feature\Constants::EDIT_METHODS) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        (new Validator)->validateInput('edit_methods', $input);

        return (new Methods\Core)->editMethods($input);
    }

    public function editMerchantMethods($mid, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'input'         => $input,
                'merchant_id'   => $mid
            ]);

        (new Validator)->validateInput('edit_merchant_methods', $input);

        $merchant =  $this->repo->merchant->findOrFailPublic($mid);

        return (new Methods\Core)->editMethods($input, $merchant);
    }

    public function patchMerchantBeneficiaryCode()
    {
        $data = (new BankAccount\Core)->updateBeneficiaryCodes();

        return $data;
    }

    /**
     * Send beneficiary registration request for ALL activated merchants
     *
     * @param array  $input
     * @param string $channel
     *
     * @return array
     */
    public function getMerchantBeneficiary(array $input, string $channel): array
    {
        $response = (new BankAccount\Beneficiary)->register($input, $channel);

        return $response;
    }

    /**
     *   Generate and Send the beneficiary file to nodal account's bank
     *   if a new merchant has been activated since
     *   if (monday)  - 3 days
     *   else         - 1 day
     *
     * @param array $input
     * @param string $channel
     *
     * @return array
     */
    public function postMerchantBeneficiary(array $input, string $channel): array
    {
        $response = (new BankAccount\Beneficiary)->registerBetweenTimestamps($input, $channel);

        return $response;
    }

    public function getCheckoutPreferences($input)
    {
        $merchant = $this->merchant;

        (new Validator)->setStrictFalse()->validateInput(Validator::PREFERENCES, $input);

        $preferences = (new Checkout)->getPreferences($merchant, $this->mode, $input);

        return $preferences;
    }

    public function getInternalCheckoutPreferences($merchantId)
    {
       $this->merchant = $this->repo->merchant->findOrFail($merchantId);

       $this->app['basicauth']->setMerchant($this->merchant);

       return $this->getCheckoutPreferences([]);
    }

    public function getAutoDisabledMethods($merchantId)
    {
        $startTime = millitime();

        $data = [];

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $category = $merchant->getCategory();

        $category2 = $merchant->getCategory2();

        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        $timeTakenInDB = millitime() - $startTime;

        $data['auto_disabled_methods']= DefaultMethodsForCategory::getDefaultDisabledMethodsForInstrumentRequestFromMerchantCategories($category, $category2);

        if($merchantDetails['activation_status']!='activated')
        {
            $data['kyc_enabled'] = false;
        }
        else
        {
            $data['kyc_enabled'] = true;
        }

        $timeTakenTotal = millitime() - $startTime;

        $this->trace->info(
            TraceCode::AUTO_DISABLED_METHODS_RESPONSE_TIME,
            [
                'time_taken_db'         => $timeTakenInDB,
                'time_taken_total'      => $timeTakenTotal,
            ]);

        return $data;
    }

    public function getGSTDetails(): array
    {
        return $this->merchant->merchantDetail->toArrayGST();
    }

    public function editGSTDetails(array $input): array
    {
        $merchantDetail = $this->merchant->merchantDetail;

        $merchantDetail->getValidator()->validateIsGSTEditable($input);

        $merchantDetail->edit($input);

        $this->repo->saveOrFail($merchantDetail);

        return $merchantDetail->toArrayGST();
    }

    /**
     * sends daily reports for all merchants that are currently live
     * Returns an array with the keys: `skipped`, and `sent`,
     * each containing the number of merchants in each category
     * @return array debug response
     */
    public function sendDailyReportForAllMerchants($input)
    {
        return (new DailyReport)->sendReportForAllMerchants($input);
    }

    public function notifyMerchantsHoliday($input)
    {
        (new Validator)->validateInput('holiday_notify', $input);

        RuntimeManager::setTimeLimit(300);

        $this->trace->info(TraceCode::MERCHANT_NOTIFY_HOLIDAY);

        $response = (new HolidayNotification)->send($input);

        $this->trace->info(TraceCode::MERCHANT_NOTIFY_HOLIDAY, $response);

        return $response;
    }

    public function updatePaymentMethods($merchantId, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantMethods = (new Methods\Core)->getPaymentMethods($merchant);

        $disabledBanks = $merchantMethods->getDisabledBanks();

        $unsupportedBanks = Netbanking::findUnsupportedBanks($disabledBanks);

        // to remove banks which are now not supported.
        $disabledBanks = array_diff($disabledBanks, $unsupportedBanks);

        if (isset($input[Methods\Entity::DISABLED_BANKS]) === true)
        {
            $inputBanks = $input[Methods\Entity::DISABLED_BANKS];

            $disabledBanks = array_unique(array_merge($disabledBanks, $inputBanks));
        }

        //setting the enabled banks that are not part of disabled banks.
        if (isset($input[Methods\Entity::ENABLED_BANKS]) === true)
        {
            $inputBanks = $input[Methods\Entity::ENABLED_BANKS];

            $disabledBanks = array_unique(array_diff($disabledBanks, $inputBanks));

            unset($input[Methods\Entity::ENABLED_BANKS]);
        }

        $input[Methods\Entity::DISABLED_BANKS] = $disabledBanks;

        return (new Methods\Core)->setPaymentMethods($merchant, $input);
    }

    public function updateHdfcDebitEmiPaymentMethods($input)
    {

        $this->trace->info(
            TraceCode::UPDATE_HDFC_DEBIT_EMI_VALUE_REQUEST,
            $input
        );

        $count = $input['count'] ?? 100;

        $sucessCount = 0;
        $failureCount = 0;
        $totalCount = 0;

        // fetch methods from slave, debit_emi_provider value as null
        $methods = $this->repo->useSlave(function() use ($count)
        {
            return $this->repo->methods->fetchMethodsToUpdateHdfcDebitEmiValue($count);;

        });

        foreach ($methods as $method)
        {
            $totalCount++;

            $merchantId = $method->getMerchantId();
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $debitEmiProvider = 0;

                if ($merchant->isActivated() === true)
                {

                    $category = $merchant->getCategory();

                    $category2 = $merchant->getCategory2();

                    $autoDisabledMethods = DefaultMethodsForCategory::getDefaultDisabledMethodsForInstrumentRequestFromMerchantCategories($category, $category2);

                    if (in_array(Methods\Entity::HDFC_DEBIT_EMI, $autoDisabledMethods) === false)
                    {
                        $debitEmiProvider = 1;
                    }
                }

                $method->setAttribute(Methods\Entity::DEBIT_EMI_PROVIDERS, $debitEmiProvider);

                $this->repo->saveOrFail($method);

                $sucessCount++;
            }
            catch(\Throwable $ex)
            {
                $data = ["merchant_id" => $merchantId];

                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::UPDATE_HDFC_DEBIT_EMI_VALUE_FAILED,
                    $data);

                $failureCount++;
            }
        }

        $res = ["count"=>$count, "success"=> $sucessCount, "failure"=>$failureCount, "total"=>$totalCount];

        $this->trace->info(
            TraceCode::UPDATE_HDFC_DEBIT_EMI_VALUE_RESPONSE,
            $res
        );

        return $res;
    }

    public function updateMethodsForMultipleMerchants($input)
    {
        $startTime = millitime();

        $this->trace->info(
            TraceCode::MERCHANT_METHODS_BULK_UPDATE,
            $input);

        $this->increaseAllowedSystemLimits();

        (new Methods\Validator)->validateInput('bulk_assign_methods', $input);

        $merchantIds = $input['merchants'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($merchantId, $input)
                {
                    $this->updatePaymentMethods($merchantId, $input['methods']);
                });

                $successCount++;
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    Trace::ERROR,
                    TraceCode::MERCHANT_METHODS_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'input'       => $input['methods'],
                    ]);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $timeTaken = millitime() - $startTime;

        $this->trace->info(
            TraceCode::BULK_ACTION_RESPONSE_TIME,
            [
                'action'          => 'update_methods',
                'time_taken'      => $timeTaken,
            ]);

        $response['total']     = count($merchantIds);
        $response['success']   = $successCount;
        $response['failed']    = $failedCount;
        $response['failedIds'] = $failedIds;

        return $response;
    }

    public function updateMerchantsBulk(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_BULK_UPDATE_REQUEST,
            $input
        );

        if ((isset($input['attributes']) === true) and
            (isset($input['action']) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Both Action and Attributes should not be sent.');
        }

        (new Validator)->validateInput('updateMerchantsBulk', $input);

        if (isset($input['action']) === true)
        {
            $action = $input['action'];

            (new Validator)->validateAdminPermissionForAction($action);

            $riskActions = explode(',', RiskAction\Constants::RISK_ACTIONS_CSV);

            if(in_array($action, $riskActions) === true)
            {
                $mode = $this->mode ??  Mode::LIVE ;

                $variant = $this->app->razorx->getTreatment(
                    UniqueIdEntity::generateUniqueId(),
                    BulkAction\Constants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,
                    $mode);

                $this->trace->info(
                    TraceCode::MERCHANT_BULK_RISK_ACTION_RAZORX_VARIANT,
                    [
                        "razorx_variant" => $variant,
                        "mode"           => $mode,
                    ]
                );

                if(strtolower($variant) === 'on')
                {
                    // NOTE: if succesfull will throw early workflow exception
                    // if non succesfull will throw an exception
                    // (due to validation or workflow creation error)
                    (new BulkAction\Core())->handleBulkAction($input);
                }
            }
        }

        $merchantIds = $input['merchant_ids'];

        unset($input['merchant_ids']);

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                if (isset($input['attributes']) === true)
                {
                    $this->edit($merchantId, $input['attributes']);
                }
                else
                {
                    $this->action($merchantId, $input,false);
                }

                (new MerchantActionNotification())->updateNotificationTag($merchantId,$input);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function updateChannelForMultipleMerchants(array $input)
    {
        (new Validator)->validateInput('update_channel', $input);

        $this->trace->info(
            TraceCode::MERCHANT_CHANNEL_BULK_UPDATE_REQUEST,
            $input
        );

        $merchantIds = $input['merchant_ids'];

        $channel = $input['channel'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                // update channel in merchant entity
                $this->edit($merchantId, ['channel' => $channel]);

                $data = (new Transaction\BulkUpdate)->updateMultipleTransactions($merchantId, $channel);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_CHANNEL_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function updateBankAccountForMultipleMerchants(array $input)
    {
        (new Validator)->validateInput('updateBankAccount', $input);

        $this->trace->info(
            TraceCode::MERCHANT_BANK_ACCOUNT_BULK_UPDATE_REQUEST,
            $input
        );

        $merchantIds = $input['merchant_ids'];

        $bankAccount = $input['bank_account'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        $bankAccountCore = new BankAccount\Core;

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $bankAccountCore->createOrChangeBankAccount($bankAccount, $merchant);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_BANK_ACCOUNT_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function getOffers(string $mid)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $offers = (new Offer\Core)->fetchOffers($merchant);

        return $offers->toArrayAdmin();
    }

    public function getMerchantFeatures()
    {
        $roleBasedFeatures = Feature\UserRoleFeatureMap::getFeaturesForRole($this->auth->getUserRole());

        $features = (new Feature\Service)->getFeaturesForMerchantPublic($this->merchant, $roleBasedFeatures);

        if (in_array(Feature\Constants::RX_BLOCK_REPORT_DOWNLOAD_ROLE_CHECK, $roleBasedFeatures) === false)
        {
            array_walk($features['features'], function(& $feature)
            {
                if ($feature['feature'] === Feature\Constants::RX_BLOCK_REPORT_DOWNLOAD)
                {
                    $feature['value'] = false;
                }
            });
        }

        return $features;
    }

    public function getEarlySettlementPricingForMerchant(): array
    {
        $mid = $this->merchant->getId();

        $key1 = $mid . '_on_demand_es_pricing';
        $key2 = $mid . '_scheduled_es_pricing';

        return [
            $key1 => Cache::get('espricing:' . $key1) ?? 0.3,
            $key2 => Cache::get('espricing:' . $key2) ?? 0.2
        ];
    }

    public function getScheduledEarlySettlementPricingForMerchant(): array
    {
        $pricingPlanId = $this->merchant->getPricingPlanId();

        $pricingFeature = PricingFeature::ESAUTOMATIC;

        if ($this->merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND_RESTRICTED) === true)
        {
            $pricingFeature = PricingFeature::ESAUTOMATIC_RESTRICTED;
        }

        $scheduledPricings = $this->repo->pricing
                              ->getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId($pricingPlanId,
                                                                                           $pricingFeature,
                                                                                           false);

        if ($scheduledPricings->isEmpty() === true)
        {
            $pricingPlanId = $this->addDefaultScheduledEarlySettlementPricingForMerchant($pricingFeature, $pricingPlanId);

            $scheduledPricings = $this->repo->pricing
                                  ->getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId($pricingPlanId,
                                                                                               $pricingFeature,
                                                                                               false);
        }

        $finalSchedulePricing = new Pricing\Entity();

        foreach ($scheduledPricings as $scheduledPricing)
        {
            if($scheduledPricing->getPercentRate() >= $finalSchedulePricing->getPercentRate())
            {
                $finalSchedulePricing = $scheduledPricing;
            }
        }

        $this->trace->info(
            TraceCode::ES_PRICING_SHOWN_TO_MERCHANT,
            [
                'id' => $finalSchedulePricing->getId(),
                'percent_rate' => $finalSchedulePricing->getPercentRate()
            ]
        );

        return $finalSchedulePricing->toArrayPublic() + ['fee_bearer' => $this->merchant->getFeeBearer()];
    }

    public function addDefaultScheduledEarlySettlementPricingForMerchant($pricingFeature, $pricingPlanId,
                                                                         $percentRate = FeatureConfig\Service::DEFAULT_ES_PRICING_PERCENT)
    {
        if ($this->merchant->isPostpaid() === false)
        {
            return $this->repo->transactionOnLiveAndTest(function () use($pricingFeature, $pricingPlanId, $percentRate)

            {
                // Replicates plan for this merchant if it was shared
                if ($this->repo->merchant->fetchMerchantsCountWithPricingPlanId($pricingPlanId) !== 1)
                {
                    $newPlan = (new Pricing\Service())->replicatePlanAndAssign($this->merchant,
                                $this->repo->pricing->getPlanByIdOrFailPublic($pricingPlanId));

                    $this->merchant->refresh();

                    $pricingPlanId = $newPlan->getId();
                }

                if($pricingFeature === PricingFeature::ESAUTOMATIC_RESTRICTED)
                {
                    $defaultScheduledEarlySettlementPricing = $this->getDefaultPartialScheduledEarlySettlementPricing($percentRate);
                }
                else
                {
                    $defaultScheduledEarlySettlementPricing = $this->getDefaultScheduledEarlySettlementPricing($pricingPlanId, $percentRate);

                }

                foreach($defaultScheduledEarlySettlementPricing as $scheduledEarlySettlementPricing)
                {
                    $updatedPlanRule = (new Pricing\Service())->addPlanRule($pricingPlanId, $scheduledEarlySettlementPricing);
                }

                return $pricingPlanId;
            });
        }
        else
        {
            throw new Exception\LogicException('ES scheduled Default Pricing cannot be assigned to postpaid merchant.',
                                                ErrorCode::SERVER_ERROR_NO_ES_PRICING_FOR_POSTPAID_MERCHANT);
        }
    }


    public function getDefaultPartialScheduledEarlySettlementPricing($percentRate): array
    {
        $defaultScheduledEarlySettlementPricings = [];
        $pricingRule = [
            'product'             => Product::PRIMARY,
            'feature'             => PricingFeature::ESAUTOMATIC_RESTRICTED,
            'payment_method'      => Payout\Method::FUND_TRANSFER,
            'percent_rate'        => $percentRate,
            'amount_range_active' => 0,
            'amount_range_max'    => 0,
            'amount_range_min'    => 0,
            'fee_bearer'          => $this->merchant->getFeeBearer(),
        ];

        array_push($defaultScheduledEarlySettlementPricings, $pricingRule);
        return $defaultScheduledEarlySettlementPricings;
    }

    public function createOrUpdateScheduledEarlySettlementPricing($pricingPlanId, $percentRate)
    {
        $scheduledPricings = $this->repo->pricing
                            ->getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId($pricingPlanId,
                                                                                        PricingFeature::ESAUTOMATIC,
                                                                                        false);
        if ($scheduledPricings->isEmpty() === true)
        {
            $this->addDefaultScheduledEarlySettlementPricingForMerchant(PricingFeature::ESAUTOMATIC,
                                                                        $pricingPlanId,
                                                                        $percentRate);
        }
        else
        {
            $this->updateScheduledEarlySettlementPricing($scheduledPricings, $percentRate);
        }
    }

    public function updateScheduledEarlySettlementPricing($scheduledPricings, $percentRate)
    {
        $scheduledPricingsArray = $scheduledPricings->toArray();
        $inputArray = [];
        foreach($scheduledPricingsArray as $scheduledPricing)
        {
            if($scheduledPricing['payment_method'] === Payment\Method::WALLET and
                $scheduledPricing['payment_network'] === Payment\Processor\Wallet::PAYPAL)
            {
                continue;
            }
            $scheduledPricing['idempotency_key'] ='random';
            $scheduledPricing['update'] = true;
            $scheduledPricing[Pricing\Entity::MERCHANT_ID] = $this->merchant->getId();
            $scheduledPricing['percent_rate'] = $percentRate;
            array_push($inputArray, $scheduledPricing);
        }
        (new Pricing\Service)->postAddBulkPricingRules($inputArray);
    }

    public function getDefaultScheduledEarlySettlementPricing($pricingPlanId, $percentRate)
    {
        $scheduledEarlySettlementmethods = [
            Payment\Method::AEPS,
            Payment\Method::CARD,
            Payment\Method::CARDLESS_EMI,
            Payment\Method::EMI,
            Payment\Method::NETBANKING,
            Payment\Method::PAYLATER,
            Payment\Method::TRANSFER,
            Payment\Method::UPI,
            Payment\Method::WALLET,
        ];

        $defaultScheduledEarlySettlementPricings = [];

        foreach($scheduledEarlySettlementmethods as $method)
        {
            $pricingRule = [
                'product'             => Product::PRIMARY,
                'feature'             => PricingFeature::ESAUTOMATIC,
                'payment_method'      => $method,
                'percent_rate'        => $percentRate,
                'amount_range_active' => 0,
                'amount_range_max'    => 0,
                'amount_range_min'    => 0,
                'fee_bearer'          => $this->merchant->getFeeBearer(),
            ];

            array_push($defaultScheduledEarlySettlementPricings, $pricingRule);
        }

        $scheduledInternaltionalRules = $this->repo->pricing
                                        ->getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId($pricingPlanId,
                                                                                                    PricingFeature::ESAUTOMATIC,
                                                                                                    true)->toArray();

        $esInternationCardRule = array_filter($scheduledInternaltionalRules, function($scheduledInternaltionalRule)
        {
            return (isset($scheduledInternaltionalRule['payment_method']) === true &&
                    $scheduledInternaltionalRule['payment_method'] === Payment\Method::CARD);
        });

        if(empty($esInternationCardRule) === true)
        {
            $cardInternationalEsRule = [
                'product'             => Product::PRIMARY,
                'feature'             => PricingFeature::ESAUTOMATIC,
                'payment_method'      => Payment\Method::CARD,
                'international'       => 1,
                'percent_rate'        => 0,
                'amount_range_active' => 0,
                'amount_range_max'    => 0,
                'amount_range_min'    => 0,
                'fee_bearer'          => $this->merchant->getFeeBearer(),
            ];

            array_push($defaultScheduledEarlySettlementPricings, $cardInternationalEsRule);
        }

        $paypalWalletEsRule = [
            'product'             => Product::PRIMARY,
            'feature'             => PricingFeature::ESAUTOMATIC,
            'payment_method'      => Payment\Method::WALLET,
            'payment_network'     => Payment\Processor\Wallet::PAYPAL,
            'percent_rate'        => 0,
            'amount_range_active' => 0,
            'amount_range_max'    => 0,
            'amount_range_min'    => 0,
            'fee_bearer'          => $this->merchant->getFeeBearer(),
        ];

        array_push($defaultScheduledEarlySettlementPricings, $paypalWalletEsRule);

        return $defaultScheduledEarlySettlementPricings;
    }

    public function getInstantRefundsPricingForMerchant(): array
    {
        // Merchant's pricing plan id
        $pricingPlanId = $this->merchant->getPricingPlanId();

        //
        // To decide whether to display pricing on the merchant dashboard -
        // There are some pricing plan variations which cannot be displayed the merchant dashboard
        // as per the current design
        // These include :
        // 1. Pricing plan has more than 6 rules for Instant Refunds
        // 2. Instant Refunds mode level pricing plan
        // 3. Pricing is not consistent across methods for Instant Refunds
        // 4. Pricing is defined on percent rate for Instant Refunds
        //
        $isCustomPricingPlan = false;

        $instantRefundsPricingRules = $this->repo->pricing->getPricingRulesByPlanIdProductAndFeatureWithoutOrgId(
            $pricingPlanId,
            Product::PRIMARY,
            PricingFeature::REFUND
        );

        // While fetching default pricing rules
        $pricingMethod = Payment\Method::CARD;

        //
        // Merchant does not have merchant specific pricing for Instant Refunds, default pricing plan is applied and
        // hence we can display default pricing plan
        //
        if ($instantRefundsPricingRules->isEmpty() === true)
        {
            $planId = Pricing\Fee::DEFAULT_INSTANT_REFUNDS_PLAN_ID;

            $merchantId = $this->merchant->getId();

            $variant = $this->app->razorx->getTreatment(
                $merchantId,
                Merchant\RazorxTreatment::INSTANT_REFUNDS_DEFAULT_PRICING_V1,
                $this->mode
            );

            //
            // Instant Refunds v2 pricing is now default - not behind a razorx anymore
            // Instant Refunds v1 Pricing is behind razorx for merchants in transition phase
            //
            if ($variant !== RefundConstants::RAZORX_VARIANT_ON)
            {
                $planId = Pricing\Fee::DEFAULT_INSTANT_REFUNDS_PLAN_V2_ID;
            }

            $instantRefundsDefaultPricingRules = $this->repo->pricing->getInstantRefundsDefaultPricingPlanForMethod(
                PricingFeature::REFUND,
                $pricingMethod,
                Product::PRIMARY,
                $planId
            );

            $finalRulesToBeFormatted = $instantRefundsDefaultPricingRules;
        }
        else
        {
            //
            // Merchant has merchant specific pricing rules -
            // we need to figure out if its a complex pricing plan which cannot be shown on merchant dashboard
            //
            [$isCustomPricingPlan, $pricingMethod] = $this->isComplexInstantRefundsPricing($instantRefundsPricingRules);

            $finalRulesToBeFormatted = $instantRefundsPricingRules->where(Pricing\Entity::PAYMENT_METHOD, $pricingMethod);
        }

        $formattedRules = $isCustomPricingPlan ? [] : $this->getFormattedInstantRefundsPricingPlan($finalRulesToBeFormatted);

        //
        // If rules are more than 6, we cannot display on the merchant dashboard as per current design,
        // hence treating it as complex / custom pricing
        //
        if (count($formattedRules) > Constants::MAX_RULES_TO_BE_DISPLAYED)
        {
            $isCustomPricingPlan = true;

            $formattedRules = [];
        }

        $result = [
            Constants::CUSTOM_PRICING => $isCustomPricingPlan,
            Constants::RULES          => $formattedRules,
        ];

        return $result;
    }

    protected function getFormattedInstantRefundsPricingPlan($instantRefundsPricingRules): array
    {
        $fieldsToExpose = [
            Pricing\Entity::AMOUNT_RANGE_MIN,
            Pricing\Entity::AMOUNT_RANGE_MAX,
            Pricing\Entity::FIXED_RATE,
        ];

        //
        // array_values - to avoid numeric keys being present in the map
        //
        $filtered = array_values($instantRefundsPricingRules->map->only($fieldsToExpose)->toArray());

        if ($this->isInstantRefundsPricingAmountRangeActive($instantRefundsPricingRules) === false)
        {
            $filtered = $this->getInstantRefundsPricingInDefaultSlabs($filtered);
        }

        return array_sort_recursive($filtered);
    }

    protected function isComplexInstantRefundsPricing($instantRefundsPricingRules)
    {
        if ($this->isInstantRefundsModeLevelPricing($instantRefundsPricingRules) === true)
        {
            return [true, null];
        }

        if ($this->isInstantRefundsPercentageRatePricing($instantRefundsPricingRules) === true)
        {
            return [true, null];
        }

        $uniqueMethods = array_unique($instantRefundsPricingRules->pluck(Pricing\Entity::PAYMENT_METHOD)->toArray());

        if ($this->isInstantRefundsDefaultMethodPricing($uniqueMethods) === true)
        {
            // If distinct - pricing is not complex
            if ($this->isInstantRefundsDefaultMethodPricingDistinct($instantRefundsPricingRules) === true)
            {
                return [false, null];
            }

            //
            // If duplicate rules are present - we may not be able to display rules on the merchant dashboard
            // Hence, complex
            //
            return [true, null];
        }

        if ($this->isInstantRefundsPricingConsistentAcrossMethods($instantRefundsPricingRules, $uniqueMethods) === true)
        {
            //
            // Pricing has been defined consistently for all the methods
            // hence picking card
            //
            return [false, Payment\Method::CARD];
        }

        //
        // Pricing is complex and cannot be displayed on the merchant dashboard
        //
        return [true, null];
    }

    /**
     * @param $instantRefundsPricingRules
     * @return bool
     */
    protected function isInstantRefundsModeLevelPricing($instantRefundsPricingRules) : bool
    {
        $modes = $instantRefundsPricingRules->pluck(Pricing\Entity::PAYMENT_METHOD_TYPE)->toArray();

        //
        // If the distinct mode is not null, it is considered as mode level pricing
        //
        if (!((count(array_unique($modes)) === 1) and
            (end($modes) === null)))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $instantRefundsPricingRules
     * @return bool
     */
    protected function isInstantRefundsPercentageRatePricing($instantRefundsPricingRules) : bool
    {
        $percentRateRules = $instantRefundsPricingRules->pluck(Pricing\Entity::PERCENT_RATE)->toArray();

        //
        // If the distinct percent rate is not empty (0), it is considered as percentage rate pricing
        //
        if (!((count(array_unique($percentRateRules)) === 1) and
            (empty(end($percentRateRules)) === true)))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $uniqueMethods
     * @return bool
     */
    protected function isInstantRefundsDefaultMethodPricing($uniqueMethods) : bool
    {
        //
        // If the distinct method is null, it is considered as default method all pricing
        //
        if ((count($uniqueMethods) === 1) and
            (end($uniqueMethods) === null))
        {
            return true;
        }

        return false;
    }

    /**
     * Checks if there are any duplicate rules in the default method instant refunds pricing plan
     *
     * @param $instantRefundsPricingRules
     * @return bool
     */
    protected function isInstantRefundsDefaultMethodPricingDistinct($instantRefundsPricingRules) : bool
    {
        //
        // These are the fields to compare for check for duplicacy
        //
        $fieldsToCompare = [
            Pricing\Entity::PAYMENT_METHOD_TYPE,
            Pricing\Entity::AMOUNT_RANGE_MIN,
            Pricing\Entity::AMOUNT_RANGE_MAX,
        ];

        $rulesToCompare = array_sort_recursive($instantRefundsPricingRules->map->only($fieldsToCompare)->toArray());

        $distinctRules = [];

        // Identifying distinct rules
        foreach ($rulesToCompare as $ruleToCompare)
        {
            if (in_array($ruleToCompare, $distinctRules, true) === false)
            {
                $distinctRules[] = $ruleToCompare;
            }
        }

        // Is distinct
        if (count($distinctRules) === count($rulesToCompare))
        {
            return true;
        }

        // Duplicate rules found
        return false;
    }

    /**
     * If instant refunds pricing is defined method wise, this function checks and validates
     * if pricing has been defined consistently for all the supported instant refunds methods
     *
     * @param $instantRefundsPricingRules
     * @param $uniqueMethods
     * @return bool
     */
    protected function isInstantRefundsPricingConsistentAcrossMethods($instantRefundsPricingRules, $uniqueMethods) : bool
    {
        // Fields to compare for consistency
        $fieldsToCompare = [
            Pricing\Entity::PAYMENT_METHOD_TYPE,
            Pricing\Entity::AMOUNT_RANGE_MIN,
            Pricing\Entity::AMOUNT_RANGE_MAX,
            Pricing\Entity::FIXED_RATE,
        ];

        $instantRefundSupportedMethods = Payment\Method::INSTANT_REFUND_SUPPORTED_METHODS;

        $instantRefundPricingMethods = array_merge(
            $instantRefundSupportedMethods,
            [null]
        );

        $allRules = [];

        //
        // Checking if pricing is defined for all the supported instant refunds pricing methods
        //
        if ((array_diff($uniqueMethods, $instantRefundSupportedMethods) === array_diff($instantRefundSupportedMethods, $uniqueMethods)) or
            (array_diff($uniqueMethods, $instantRefundPricingMethods) === array_diff($instantRefundPricingMethods, $uniqueMethods)))
        {
            $grouped = $instantRefundsPricingRules->groupBy(Pricing\Entity::PAYMENT_METHOD);

            foreach ($grouped as $key => $group)
            {
                $groupRules = array_sort_recursive($group->map->only($fieldsToCompare)->toArray());

                if (in_array($groupRules, array_values($allRules), true) === false)
                {
                    $allRules[$key] = $groupRules;
                }
            }

            // If there is only 1 set of rules - it means pricing has been defined consistently across all the required methods
            if (count($allRules) === 1)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $instantRefundsPricingRules
     * @return bool
     */
    protected function isInstantRefundsPricingAmountRangeActive($instantRefundsPricingRules) : bool
    {
        $amountRangeActive = $instantRefundsPricingRules->pluck(Pricing\Entity::AMOUNT_RANGE_ACTIVE)->toArray();

        if ((count(array_unique($amountRangeActive)) === 1) and
            (end($amountRangeActive) === false))
        {
            return false;
        }

        return true;
    }

    /**
     * @param $filteredRules
     * @return array
     */
    protected function getInstantRefundsPricingInDefaultSlabs($filteredRules)
    {
        $instantRefundsDefaultPricingSlabs = [
            [
                Pricing\Entity::AMOUNT_RANGE_MIN => 0,
                Pricing\Entity::AMOUNT_RANGE_MAX => 100000,
            ],
            [
                Pricing\Entity::AMOUNT_RANGE_MIN => 100000,
                Pricing\Entity::AMOUNT_RANGE_MAX => 2500000,
            ],
            [
                Pricing\Entity::AMOUNT_RANGE_MIN => 2500000,
                Pricing\Entity::AMOUNT_RANGE_MAX => 4294967295,
            ],
        ];

        $fixedRate = $filteredRules[0][Pricing\Entity::FIXED_RATE];

        $filtered = [];

        //
        // Super imposing the fixed rate into the default slabs
        //
        foreach ($instantRefundsDefaultPricingSlabs as $instantRefundsDefaultPricingSlab)
        {
            $instantRefundsDefaultPricingSlab[Pricing\Entity::FIXED_RATE] = $fixedRate;

            $filtered[] = $instantRefundsDefaultPricingSlab;
        }

        return $filtered;
    }

    /**
     * Add a merchant to OnDemandEnabledMailingList mailing lists and remove from OnDemandNotEnabledMailingList.
     *
     * @param string $merchantId
     */
    public function addMerchantToOnDemandEnabledMailingList(string $merchantId)
    {
        $merchantCore = $this->core();

        $merchant =  $this->repo->merchant->findOrFail($merchantId);

        $merchantCore->addMerchantEmailToMailingList($merchant, [Constants::LIVE_SETTLEMENT_ON_DEMAND]);

        $merchantCore->removeMerchantEmailToMailingList($merchant, [Constants::LIVE_SETTLEMENT_DEFAULT]);
    }

    /**
     * Remove a merchant from OnDemandEnabledMailingList mailing lists and add to OnDemandNotEnabledMailingList.
     *
     * @param string $merchantId
     */
    public function removeMerchantFromOnDemandEnabledMailingList(string $merchantId)
    {
        $merchantCore = $this->core();

        $merchant =  $this->repo->merchant->findOrFail($merchantId);

        $merchantCore->removeMerchantEmailToMailingList($merchant, [Constants::LIVE_SETTLEMENT_ON_DEMAND]);

        $merchantCore->addMerchantEmailToMailingList($merchant, [Constants::LIVE_SETTLEMENT_DEFAULT]);
    }

    public function getOnDemandEarlySettlementPricingForMerchant($pricingFeature = PricingFeature::PAYOUT)
    {
        // This is a wrapper over getPricingPlans to fetch payout pricing for given
        // pricingPlanId along with corresponding rules
        $pricingPlanId = $this->merchant->getPricingPlanId();

        $onDemandPricing = $this->repo->pricing
                                    ->getPricingRulesByPlanIdProductFeaturePaymentMethodOrgId($pricingPlanId,
                                                                                         Product::PRIMARY,
                                                                                         $pricingFeature,
                                                                                         Payout\Method::FUND_TRANSFER);

        // We do not expect multiple rows of primary-payout-fund_transfer for a given planId
        return $onDemandPricing;
    }

    public function updateOnDemandPricingForMerchantBeforeEnableSchedule($pricingFeature = PricingFeature::PAYOUT)
    {
        //
        // W.e.f March 2020 Product wants to provide es on demand with under 20 bps if scheduled is enabled too.
        // In case the bps value is already less than 20 then don't change
        // In case it's greater than 20 then
            // Check if the pricing plan is used by more than 1 merchant
                // If yes than replicate plan and assign new plan for the merchant
            // Update pricing plan for the merchant payout (could be old or new duplicated)
            // and update payout pricing to 15bps
        // For merchants who have payout pricing percent rate lesser than 20 already won't get the change
        //
        $onDemandPricing = $this->getOnDemandEarlySettlementPricingForMerchant($pricingFeature);

        if(empty($onDemandPricing) === false)
        {
            $onDemandPricingRuleId = $onDemandPricing->getId();

            if ($onDemandPricing->getPercentRate() < 20)
            {
                return $onDemandPricing->toArrayPublic()['percent_rate'];
            }

            $planId = $this->merchant->getPricingPlanId();

            $plan = $this->repo->pricing->getPlanByIdOrFailPublic($planId);

            // Replicates plan for this merchant if it was shared
            if ($this->repo->merchant->fetchMerchantsCountWithPricingPlanId($planId) !== 1)
            {
                // Replicate also takes care of assigning the plan to the merchant
                $newPlan = (new Pricing\Service())->replicatePlanAndAssign($this->merchant, $plan);

                $this->merchant->refresh();

                $plan = $newPlan;

                // Currently we have just one rule id for on demand
                $onDemandPricingRuleId = $this->getOnDemandEarlySettlementPricingForMerchant($pricingFeature)->getId();
            }

            $updatedPlanRule = (new Pricing\Service())->updatePlanRule($plan->getId(),
                                                    $onDemandPricingRuleId,
                                                    ['percent_rate' => 15]);

            return $updatedPlanRule['percent_rate'];
        }
        else
        {
            return null;
        }
    }

    public function enableScheduledEs($skipRoleCheck = false, $notify = true): array
    {
        if($skipRoleCheck == false)
        {
            $userRole = $this->repo
                             ->merchant
                             ->getMerchantUserMapping(
                                $this->merchant->getId(),
                                $this->user->getId(),
                                null,
                                Product::PRIMARY)
                             ->pivot
                             ->role;

            if (($userRole !== User\Role::ADMIN) and ($userRole !== User\Role::OWNER))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED,
                                                        'role',
                                                        $userRole);
            }
        }

        if ($this->merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND_RESTRICTED) === true)
        {
            return $this->enablePartialScheduledEs();
        }

        $pricingForMerchant = $this->getScheduledEarlySettlementPricingForMerchant();

        $schedule = (new Schedule\Repository)->getScheduleByPeriodIntervalAnchorHourDelayAndType(
                                                Schedule\Period::HOURLY,
                                                1,
                                                null,
                                                0,
                                                0,
                                                ScheduleTask\Type::SETTLEMENT);

        if ($schedule === null)
        {
            throw new Exception\LogicException(
                'Schedule for Scheduled Automatic settlement was not found.',
                ErrorCode::BAD_REQUEST_UNKNOWN_SCHEDULE
            );
        }

        $scheduledTasks = (new ScheduleTask\Core)->getMerchantSettlementScheduleTasks($this->merchant, false);

        $this->repo->transactionOnLiveAndTest(function () use($schedule, $scheduledTasks, &$pricingForMerchant)
        {
            foreach ($scheduledTasks as $scheduledTask)
            {
                $input = [
                    ScheduleTask\Entity::METHOD      => $scheduledTask[ScheduleTask\Entity::METHOD],
                    ScheduleTask\Entity::TYPE        => $scheduledTask[ScheduleTask\Entity::TYPE],
                    ScheduleTask\Entity::SCHEDULE_ID => $schedule->getId()
                ];

                $this->app['workflow']->skipWorkflows(function() use ($input)
                {
                    (new ScheduleTask\Core)->createOrUpdate($this->merchant, $this->merchant, $input);
                });
            }

            // Pricing plan updates, if required, need not be blocked by workflows.
            $this->app['workflow']->skipWorkflows(function() use(&$pricingForMerchant)
            {
                $onDemandPayoutPricing = $this->updateOnDemandPricingForMerchantBeforeEnableSchedule();

                $settlementOndemandPricing = $this->updateOnDemandPricingForMerchantBeforeEnableSchedule(PricingFeature::SETTLEMENT_ONDEMAND);

                $pricingForMerchant['on_demand_percent_rate'] = [$onDemandPayoutPricing, $settlementOndemandPricing] ;
            });

            $this->addOrRemoveMerchantFeatures([
                                                    Entity::FEATURES => [
                                                        Feature\Constants::ES_AUTOMATIC => 1
                                                    ],
                                                    Feature\Entity::SHOULD_SYNC => 1]);

            // Updating the merchant_config for merchants migrated to new settlement service
            if ($this->merchant->isFeatureEnabled(Feature\Constants::NEW_SETTLEMENT_SERVICE) === true)
            {
                (new Settlement\Core)->updateMerchantSchedule($this->merchant->getId(),Mode::LIVE);

                (new Settlement\Core)->updateMerchantSchedule($this->merchant->getId(),Mode::TEST);

            }

        });

        // All the mail sending steps are taken out of the transactionOnLiveAndTest.
        // We want the flow to not get disturbed or reverted for any issues that may happen with mailer.
        if ($notify === true)
        {
            try
            {
                $this->sendMailsPostEnableScheduledEs($pricingForMerchant);
            }

            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::FEATURE_ENABLE_EARLY_SETTLEMENT_MAIL_FAILED,
                    [
                        'merchant_id' => $this->merchant->getId(),
                        'user_id' => $this->user->getId()
                    ]);
            }
        }

        return ['success' => true];
    }

    public function disableScheduledES($initialOndemandPricing, $initialScheduleId)
    {
        $this->repo->transactionOnLiveAndTest(function () use($initialOndemandPricing, $initialScheduleId)
        {

            $scheduledTasks = (new ScheduleTask\Core)->getMerchantSettlementScheduleTasks($this->merchant,
                                                                                            false);
            foreach ($scheduledTasks as $scheduledTask) {
                $input = [
                    ScheduleTask\Entity::METHOD      => $scheduledTask[ScheduleTask\Entity::METHOD],
                    ScheduleTask\Entity::TYPE        => $scheduledTask[ScheduleTask\Entity::TYPE],
                    ScheduleTask\Entity::SCHEDULE_ID => $initialScheduleId
                ];

                $this->app['workflow']->skipWorkflows(function () use ($input) {
                    (new ScheduleTask\Core)->createOrUpdate($this->merchant, $this->merchant, $input);
                });
            }

            if ($this->merchant->isFeatureEnabled(Feature\Constants::NEW_SETTLEMENT_SERVICE) === true) {

                (new Settlement\Core)->updateMerchantSchedule($this->merchant->getId(), Mode::LIVE);

                (new Settlement\Core)->updateMerchantSchedule($this->merchant->getId(), Mode::TEST);
            }

        });
    }

    public function enablePartialScheduledEs($notify = true): array
    {
        $pricingForMerchant = $this->getScheduledEarlySettlementPricingForMerchant();

        $this->repo->transactionOnLiveAndTest(function ()
        {
            // Pricing plan updates, if required, need not be blocked by workflows.
            $this->app['workflow']->skipWorkflows(function () use (&$pricingForMerchant)
            {
                $onDemandPayoutPricing = $this->updateOnDemandPricingForMerchantBeforeEnableSchedule();

                $settlementOndemandPricing = $this->updateOnDemandPricingForMerchantBeforeEnableSchedule(PricingFeature::SETTLEMENT_ONDEMAND);

                $pricingForMerchant['on_demand_percent_rate'] = [$onDemandPayoutPricing, $settlementOndemandPricing];

            });

            $this->addOrRemoveMerchantFeatures(
                [
                    Entity::FEATURES => [
                        Feature\Constants::ES_AUTOMATIC_RESTRICTED => 1
                    ],
                    Feature\Entity::SHOULD_SYNC => 1
                ]
            );
        });

        if ($notify === true)
        {
            try
            {
                $this->sendMailsPostEnableScheduledEs($pricingForMerchant);
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::FEATURE_ENABLE_PARTIAL_ES_MAIL_FAILED,
                    [
                        'merchant_id' => $this->merchant->getId(),
                        'user_id' => $this->user->getId()
                    ]);
            }
        }

        return ['success' => true];
    }

    public function sendMailsPostEnableScheduledEs(array $pricingForMerchant = null)
    {
        // Check merchant corresponding tags to see if it is a key account
        $tags = $this->merchant->tagNames();

        array_walk($tags, function(& $tag)
        {
            $tag = substr($tag, 0, 2);
        });
        if (in_array('KA', $tags) === true)
        {
            // For key accounts, send feature enabled mail to Capital product team
            $kamMailerData[EsEnabledNotify::TO_EMAIL] = EsEnabledNotify::KAM_MAILING_LIST_EMAILS;

            $kamMailerData[EsEnabledNotify::TO_NAME] = EsEnabledNotify::KAM_MAILING_LIST_NAMES;

            $kamMailerData[EsEnabledNotify::SUBJECT] = EsEnabledNotify::KAM_MAILER_SUBJECT;

            $kamMailerData[EsEnabledNotify::VIEW] = EsEnabledNotify::KAM_MAILER_VIEW;

            $kamMailerData[EsEnabledNotify::MERCHANT_DATA] = $this->merchant->toArrayPublic();

            $esNotifyKAMEmail = new EsEnabledNotify($kamMailerData);

            Mail::queue($esNotifyKAMEmail);
        }

        if (isset($pricingForMerchant) === true)
        {
            // Fetch all userIds which belong to the merchant and are either owner, finance or admin type
            $merchantOwnerAdminUsersCollections = (new MerchantUser\Repository)->findByRolesAndMerchantId([User\Entity::OWNER,
                                                                                                           User\Entity::ADMIN,
                                                                                                           User\Role::FINANCE],
                                                                                                           $this->merchant->getId());

            $merchantOwnerAdminUsers = array_unique($merchantOwnerAdminUsersCollections
                                                    ->pluck(User\Entity::USER_ID)
                                                    ->toArray());

            // Fetch their corresponding names and email id's
            $userNamesAndEmailsCollections = (new User\Repository)->findMany($merchantOwnerAdminUsers, [User\Entity::NAME, User\Entity::EMAIL]);

            $userNamesAndEmails = $userNamesAndEmailsCollections->pluck(User\Entity::NAME, User\Entity::EMAIL)->toArray();

            $pricingForMerchantPercentRate = number_format(floatval($pricingForMerchant[Pricing\Entity::PERCENT_RATE]) / 100, 2);

            $merchantMailerData[EsEnabledNotify::TO_EMAIL] = array_keys($userNamesAndEmails);

            // Add capital support to receiver's list
            array_push($merchantMailerData[EsEnabledNotify::TO_EMAIL], MailConstants::MAIL_ADDRESSES[MailConstants::CAPITAL_SUPPORT]);

            $merchantMailerData[EsEnabledNotify::TO_NAME] = array_values($userNamesAndEmails);

            array_push($merchantMailerData[EsEnabledNotify::TO_NAME], MailConstants::HEADERS[MailConstants::CAPITAL_SUPPORT]);

            $merchantMailerData[Pricing\Entity::PERCENT_RATE] = $pricingForMerchantPercentRate;

            $merchantMailerData[EsEnabledNotify::SUBJECT] = EsEnabledNotify::MERCHANT_MAILER_SUBJECT;

            $merchantMailerData[EsEnabledNotify::VIEW] = EsEnabledNotify::MERCHANT_MAILER_VIEW;

            $esNotifyMerchantEmail = new EsEnabledNotify($merchantMailerData);

            Mail::queue($esNotifyMerchantEmail);
        }
    }

    public function addOrRemoveMerchantFeatures(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_UPDATE,
            $input);

        $merchant = $this->merchant;

        $shouldSync = (bool) ($input[Feature\Entity::SHOULD_SYNC] ?? false);

        $EsOnDemandFeature = (new Feature\Repository)->findByEntityTypeEntityIdAndName(
            $merchant->getEntity(),
            $merchant->getId(),
            Feature\Constants::ES_ON_DEMAND);

        $input['es_enabled'] = ($EsOnDemandFeature === null) ? false : true;

        $merchant->validateInput('feature', $input);

        $featuresToAdd = $this->getFeatureNamesToAdd($input['features']);

        if ($featuresToAdd === [Feature\Constants::CARD_MANDATE_SKIP_PAGE])
        {
            $this->sendSelfServeSuccessAnalyticsEventToSegmentForEnablingMandatePageSkip();
        }

        $this->addFeatures($featuresToAdd, $shouldSync);

        $featuresToRemove = $this->getFeatureNamesToRemove($input['features']);

        if ($featuresToRemove === [Feature\Constants::NOFLASHCHECKOUT])
        {
            $this->sendSelfServeSuccessAnalyticsEventToSegmentForEnablingFlashCheckout();
        }

        $this->removeFeatures($featuresToRemove, $shouldSync);

        $data = (new Feature\Service)->getFeaturesForMerchantPublic($merchant);

        return $data;
    }

    /**
     * used for fetching referred merchants of a particular merchant
     */
    public function fetchReferredMerchants()
    {
        $merchantId = $this->merchant->getId();

        return $this->repo->merchant->fetchReferredMerchants($merchantId);
    }

    /**
     * Bulk add or remove tags from a list of merchant_ids
     *
     * Input:
     *
     * name = Tag_Name
     * action = insert/delete
     * merchant_ids = [array, of, ids]
     *
     * @param array $input
     *
     * @return array
     */
    public function bulkTag(array $input): array
    {
        $this->trace->info(TraceCode::MERCHANT_TAGS_BULK_REQUEST, $input);

        (new Validator)->validateInput('bulk_tag', $input);

        $merchantIds = $input['merchant_ids'];
        // Action: 'insert' or 'delete'
        $action  = $input['action'];
        $tagName = $input['name'];

        $tagFunction = $action . 'Tag';

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                //
                // Calls either:
                // $this->insertTag() or $this->deleteTag()
                //
                $this->{$tagFunction}($merchantId, $tagName);
            }
            catch (\Throwable $t)
            {
                $this->trace->error(
                    TraceCode::MERCHANT_TAGS_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'tag_name'    => $tagName
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        $result =  [
            'total_count'  => count($merchantIds),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];

        $this->trace->info(TraceCode::MERCHANT_TAGS_BULK_RESPONSE, $result);

        return $result;
    }

    /**
     * used for getting tags of the merchant
     * @param string $id
     */
    public function getTags($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        return $merchant->tagNames();
    }

    /**
     * used for adding tags to merchant
     * This function uses retag(), which overwrites all previous tags
     * with the ones passed in the $input array
     *
     * @param string $id
     * @param array  $input which contains the tags of the merchant
     * @param bool   $slackNotify
     *
     * @return
     */
    public function addTags($id, $input, $slackNotify = false)
    {
        return $this->core()->addTags($id, $input, $slackNotify);
    }

    /**
     * used for deleting a single tag of a merchant
     * @param string $id
     * @param string $tagName tag which has to be deleted
     */
    public function deleteTag($id, $tagName)
    {
        $tags = $this->core()->deleteTag($id, $tagName);

        $this->unsetFraudTypeIfApplicable($id, $tags);

        return $tags;
    }

    public function bulkTagBatch(array $inputs)
    {
        $result = new Base\PublicCollection;

        foreach ($inputs as $input)
        {
            $this->app['api.mutex']->acquireAndReleaseStrict(
                'add_merchant_tag'.$input[Entity::MERCHANT_ID],
                function() use ($input, $result)
                {
                    $idempotencyKey = $input[\RZP\Models\Batch\Constants::IDEMPOTENCY_KEY] ?? '';

                    unset($input[\RZP\Models\Batch\Constants::IDEMPOTENCY_KEY]);

                    $this->trace->info(TraceCode::MERCHANT_TAGS_BATCH_REQUEST, $input);

                    try
                    {
                        (new Validator)->validateInput('bulk_tag_batch', $input);

                        $tagFunction = $input['action']. 'Tag';

                        $merchantId = $input['merchant_id'];

                        $tagName = $input['tags'];

                        $this->{$tagFunction}($merchantId, $tagName);

                        $result->push([
                            'idempotency_key'   => $idempotencyKey,
                            'success'           => true,
                        ]);
                    }
                    catch(\Throwable $t)
                    {
                        $this->trace->error(
                            TraceCode::MERCHANT_TAGS_BATCH_EXCEPTION,
                            [
                                'merchant_id' => $input['merchant_id'],
                                'tag_name'    => $input['tags'],
                            ]);

                        $result->push([
                            'idempotency_key'   => $idempotencyKey,
                            'success'           => false,
                            'error'             => [
                                Error::DESCRIPTION       => $t->getMessage(),
                                Error::PUBLIC_ERROR_CODE => $t->getCode(),
                            ]
                        ]);
                    }

                });
        }

        $this->trace->info(TraceCode::MERCHANT_TAGS_BATCH_RESPONSE,
            [
            'response' => $result->toArrayWithItems(),
          ]);

        return $result->toArrayWithItems();
    }

    public function getCapitalTags()
    {
        return Constants::$capitalMerchantTags;
    }

    protected function updateFraudTypeIfApplicable($merchant, $fraudType)
    {
        $riskTags= explode(',', RiskActionConstants::RISK_TAGS_CSV);

        if (in_array($fraudType, $riskTags) === true)
        {
            try
            {
                $merchant->merchantDetail->setFraudType(sprintf(Constants::FRAUD_TYPE_TAG_TPL, $fraudType));

                $this->repo->merchant_detail->saveOrFail($merchant->merchantDetail);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::BULK_ASSIGN_TAG_SET_FRAUD_TYPE_FAILED);
            }
        }
    }

    protected function unsetFraudTypeIfApplicable($merchantId, $tagsAfterDeletion)
    {
        $riskTags= explode(',', RiskActionConstants::RISK_TAGS_CSV);

        foreach ($tagsAfterDeletion as $tag)
        {
            if (in_array(strtolower($tag), $riskTags) === true)
            {
                return;
            }
        }

        try
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $merchant->merchantDetail->setFraudType('');

            $this->repo->merchant_detail->saveOrFail($merchant->merchantDetail);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BULK_ASSIGN_TAG_SET_FRAUD_TYPE_FAILED);
        }
    }

    /**
     * Tag a merchant for a single tag
     *
     * @param string $merchantId
     * @param string $tagName
     *
     * @return mixed
     */
    public function insertTag(string $merchantId, string $tagName)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        (new Validator)->validateTagsForOnlyDSMerchants($merchant, [$tagName]);

        $merchant->tag($tagName);

        $this->updateFraudTypeIfApplicable($merchant, $tagName);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        return $merchant->tagNames();
    }

    /**
     * This function is used for updating key access of a merchant
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     */
    public function updateKeyAccess(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchant = $this->core()->updateKeyAccess($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function markGratisTransactionPostpaid($input)
    {
        $this->trace->info(
            TraceCode::GRATIS_TO_POSTPAID_INPUT,
            $input);

        $merchantIds = $input['merchant_ids'];

        $from = $input['from'];

        $successIds = [];

        $failedIds = [];

        $merchantCore = $this->core();

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchantCore->markGratisTransactionPostpaid($merchantId, $from);

                $successIds[] = $merchantId;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'success_ids' => $successIds,
            'failed_ids'  => $failedIds,
        ];

        $this->trace->info(
            TraceCode::GRATIS_TO_POSTPAID_RESPONSE,
            $response);

        return $response;
    }

    public function getMerchantUsers($input)
    {
        $users = $this->getUsersWithFilters($input);

        $filteredUsers =  $this->removePartnerUsers($users);

        return $filteredUsers;
    }

    public function getUsersWithFilters($input)
    {
        if(array_key_exists(Constants::ROLE, $input))
        {
            $data = $this->getUsersByRole($input[Constants::ROLE]);
        }
        else
        {
            $data = $this->getUsers();
        }
        return $data;
    }

    public function getUsers()
    {
        $merchantId = $this->merchant->getId();

        $product = $this->auth->getRequestOriginProduct();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $users = $this->core()->getUsers($merchant, $product);

        return $users;
    }

    private function getUsersByRole($roleId)
    {
        $merchantId = $this->merchant->getId();

        $product = $this->auth->getRequestOriginProduct();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $roleEntity = $this->repo->roles->fetchRole($roleId);

        if(empty($roleEntity) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Role Id" ,
                ['roleId' => $roleId]);
        }

        $roleName = $roleEntity->getName();

        $users =  $this->core()->getUsersByRole($merchant, $roleId, $product);

        return [
            Constants::ROLE_ID        => $roleId,
            Constants::MERCHANT_ID    => $merchantId,
            Constants::ROLE_NAME      => $roleName,
            Constants::USERS          => $users
        ];
    }

    public function getInternalUsers($merchantId, $product)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        if ($product === null)
        {
            $product = Product::PRIMARY;
        }

        $users = $this->core()->getUsers($merchant, $product);

        return $users;
    }

    public function getInternalUsersByRole($merchantId, $product, $roleId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $roleEntity = $this->repo->roles->fetchRole($roleId);

        if(empty($roleEntity) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Role Id" ,
                ['roleId' => $roleId]);
        }

        $users =  $this->core()->getUsersByRole($merchant, $roleId, $product);

        return $users;
    }

    public function createBatches(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $batches = $this->core()->createBatches($merchant, $input);

        return $batches;
    }

    public function sendPayoutMailForMultipleMerchants(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_PAYOUT_NOTIFICATION_REQUEST,
            $input
        );

        (new Validator)->validateInput('payout_mail', $input);

        $merchantsData = $input['content'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantsData as $merchantData)
        {
            try
            {
                $merchantId = $merchantData['merchant_id'];

                $email = $merchantData['email'] ?? null;

                $processed = $this->sendPayoutMail($merchantId, $email);

                if ($processed === true)
                {
                    $successCount++;
                }
                else
                {
                    $failedCount++;

                    $failedIds[] = $merchantId;
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response['total']     = count($merchantsData);
        $response['success']   = $successCount;
        $response['failed']    = $failedCount;
        $response['failedIds'] = $failedIds;

        $this->trace->info(
            TraceCode::MERCHANT_PAYOUT_NOTIFICATION_RESPONSE,
            $response
        );

        return $response;
    }

    /**
     * Return all submerchants of the master merchant (for aggregator model only)
     *
     * 1. We do not want all the aggregator merchant to download the complete report
     *    so its behind aggregator_report feature
     * 2. We will have to write the logic to fetch all its submerchants based on tags
     * 3. Currently feature will be enabled only for e-Mitra, and merchants will be hard coded.
     *
     * @return array
     */
    public function getSubmerchants(): array
    {
        $merchantId = $this->merchant->getId();

        $merchants = $this->fetchReferredMerchants();

        return array_merge([$merchantId], $merchants->pluck('id')->toArray());
    }

    protected function sendPayoutMail(string $merchantId, string $email = null)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        [$from, $to] = $this->getTimestamps();

        $processed = $this->core()->sendPayoutMail($merchant, $from, $to, $email);

        return $processed;
    }

    private function getTimestamps()
    {
        $from = Carbon::today(Timezone::IST)->getTimestamp();
        $to   = Carbon::tomorrow(Timezone::IST)->getTimestamp() - 1;

        return [$from, $to];
    }

    /**
     * Gets the feature names to be added. A feature needs to be added to merchant
     * only if the value in input is equal to the default value of the feature
     *
     * @param  array $features
     *
     * @return array
     */
    private function getFeatureNamesToAdd(array $features): array
    {
        $featureNames = [];

        foreach ($features as $name => $value)
        {
            $value = (bool) $value;

            $defaultValue = Feature\Constants::getFeatureValue(
                    Feature\Constants::$visibleFeaturesMap[$name]['feature']);

            if ($value === $defaultValue)
            {
                $featureNames[] = Feature\Constants::$visibleFeaturesMap[$name]['feature'];
            }
        }

        return $featureNames;
    }

    /**
     * Gets the feature names to be removed. A feature needs to be removed from a
     * merchant only if the value in input is opposite of the default value of the feature
     *
     * @param  array $features
     *
     * @return array
     */
    private function getFeatureNamesToRemove(array $features): array
    {
        $featureNames = [];

        foreach ($features as $name => $value)
        {
            $value = (bool) $value;

            $defaultValue = Feature\Constants::getFeatureValue(
                    Feature\Constants::$visibleFeaturesMap[$name]['feature']);

            if ($value !== $defaultValue)
            {
                $featureNames[] = Feature\Constants::$visibleFeaturesMap[$name]['feature'];
            }
        }

        return $featureNames;
    }

    private function addFeatures(array $featureNames, bool $shouldSync = false)
    {
        $merchant = $this->merchant;

        if (count($featureNames) > 0)
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID    => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE  => 'merchant',
                Feature\Entity::NAMES        => $featureNames,
                Feature\Entity::SHOULD_SYNC  => $shouldSync
            ];

            (new Feature\Service)->addFeatures($featureParams);
        }
    }

    public function addFeatureFlag(array $featureNames, bool $shouldSync = false)
    {
        $this->addFeatures($featureNames,$shouldSync);
    }

    public function removeFeatures($featureNames, bool $shouldSync = false)
    {
        $merchant = $this->merchant;

        $entityId = $merchant->getId();

        foreach ($featureNames as $featureName)
        {
            $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                Feature\Constants::MERCHANT,
                $entityId,
                $featureName);

            if ($feature !== null)
            {
                $this->repo->feature->deleteAndSyncIfApplicableOrFail($feature, $shouldSync);

                (new Feature\Core)->updatePayoutsMicroserviceOnFeatureUpdate($feature,
                                                                             $feature->getEntityType(),
                                                                             $feature->getEntityId(),
                                                                             EntityConstants::DISABLE);
            }
        }
    }

    public function getMerchantDetails()
    {
        $data = [];

        /**
         * Merchant needs to be set using X-Razorpay-account header.
         * Setting Merchant in header validates admin access to
         * that merchant in admin access middleware.
         */
        if (empty($this->merchant) === false)
        {
            $data = $this->getMerchantData($this->merchant->getId());
        }

        return $data;
    }

    public function getSmartDashboardMerchantDetails($merchant = null)
    {
        $this->trace->info(TraceCode::SMART_DASHBOARD_MERCHANT_FETCH);

        if($merchant !== null){
            $this->merchant = $merchant;
            $this->auth->setMerchant($merchant);
        }

        $merchantDetails = $this->getMerchantDetails();

        $smartDashboardMerchantDetailMap = MerchantConstants::SMART_DASHBOARD_MERCHANT_DETAILS_MAP;

        foreach ([MerchantConstants::MERCHANT_DETAILS, MerchantConstants::WEBSITE_DETAILS, MerchantConstants::DOCUMENTS] as $detail)
        {
            $detailMap = &$smartDashboardMerchantDetailMap[$detail];

            foreach ($detailMap as $key => $value)
            {
                $fieldMap = [];

                array_walk($value[MerchantConstants::FIELDS], function ($value) use (&$fieldMap, $merchantDetails)
                {
                    $fieldMap[] = $this->smartDashboardMerchantDetailsField($value, $merchantDetails);
                });

                $detailMap[$key][MerchantConstants::FIELDS] = $fieldMap;
            }
        }

        // beautifying subcategory - is required as subcategories from this api needs to be matched to subcategories returned in get_discrepancy_list api.
        // E.g. sla_ffmc_license will be modified to Sla Ffmc License
        array_walk($smartDashboardMerchantDetailMap[MerchantConstants::DOCUMENTS], function(& $fieldInfo){
            $fieldInfo[MerchantConstants::SUBCATEGORY] = ucwords(str_replace('_', ' ', $fieldInfo[MerchantConstants::SUBCATEGORY]));
        });

        $smartDashboardMerchantDetailMap[MerchantConstants::ADDITIONAL_DETAILS] = MerchantConstants::SMART_DASHBOARD_MERCHANT_DETAILS_MAP_ADDITIONAL_DETAILS;

        return $smartDashboardMerchantDetailMap;
    }

    private function smartDashboardMerchantDetailsField($key, $merchantDetails)
    {
        $value = $merchantDetails;

        foreach (explode("|", $key) as $v)
        {
            if (isset($value) === false)
            {
                break;
            }

            if (in_array($v, [Constants::MERCHANT, 'merchant_business_detail', 'documents']))
            {
                $value = $value['merchant_details'];
            }

            if (is_array($value) === false)
            {
                $value = $value->toArray();
            }

            $value = $value[$v] ?? null;

            if (($v === 'business_type') and (is_null($value) === false))
            {
                $value = MerchantDetBusinessType::getKeyFromIndex($value);
            }


        }

        return [
            'name'     => $key,
            'value'    => $value,
            'editable' => false,
        ];
    }

    /**
     * returns merchant info along with merchant details
     */
    public function internalGetMerchant($merchantId)
    {
        $data = [];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->trace->info(TraceCode::MERCHANT_GET_INTERNAL,
            [
                'merchant_id' => $merchantId,
                'merchant'    => $merchant,
            ]);

        $data[EntityConstants::MERCHANT] = $merchant->toArrayPublic();

        $merchantDetail = $merchant->merchantDetail;

        $documentsResponse = [];

        $documents = $merchant->merchantDocuments;

        foreach ($documents as $document)
        {
            $documentMetaData = [
                DocumentEntity::ID            => $document->getId(),
                DocumentEntity::FILE_STORE_ID => $document->getFileStoreId(),
                DocumentEntity::CREATED_AT    => $document->getCreatedAt()
            ];

            if (isset($documentsResponse[$document->getDocumentType()]) === false)
            {
                $documentsResponse[$document->getDocumentType()] = [];
            }

            array_push($documentsResponse[$document->getDocumentType()], $documentMetaData);
        }

        if( empty($documentsResponse) === false ) {
            $data[EntityConstants::MERCHANT_DOCUMENT] = $documentsResponse;
        }

        $data[EntityConstants::MERCHANT_DETAIL] = $this->getMerchantDetailForInternalGetMerchant($merchantDetail);

        $this->trace->info(TraceCode::MERCHANT_GET_INTERNAL,
            [
                'merchant_id' => $merchantId,
                'merchant_detail'    => $data[EntityConstants::MERCHANT_DETAIL],
            ]);

        $supportInformation = (new PayoutLinkService())->getMerchantSupportSettings($merchant);

        $data[self::SUPPORT_DETAILS] = $supportInformation;

        $this->trace->info(TraceCode::MERCHANT_GET_INTERNAL,
            [
                'merchant_id' => $merchantId,
                'support_details'    => $supportInformation,
            ]);

        $data[EntityConstants::MERCHANT][EntityConstants::FEATURE] = $merchant->getEnabledFeatures();
        if ($merchant->isFeatureEnabled(Feature\Constants::WHITE_LABELLED_ROUTE) === true)
        {
            $data[EntityConstants::MERCHANT][EntityConstants::ORG_FEATURE] = $merchant->org->getEnabledFeatures();
        }

        $data[EntityConstants::MERCHANT][EntityConstants::METHODS] = $this->repo->methods->getMethodsForMerchant($merchant);

        $this->trace->info(TraceCode::MERCHANT_GET_INTERNAL,
            [
                'merchant_id' => $merchantId,
                'methods'    => $data[EntityConstants::MERCHANT][EntityConstants::METHODS],
            ]);

        $businessDetails = (new BusinessDetail\Service())->fetchBusinessDetailsForMerchant($merchantId);

        $this->trace->info(TraceCode::MERCHANT_GET_INTERNAL,
            [
                'merchant_id' => $merchantId,
                'business_details'    => $businessDetails,
            ]);

        $data[BusinessDetail\Entity::WEBSITE_DETAILS] = $businessDetails->getWebsiteDetails();

        $data[EntityConstants::MERCHANT_DETAIL][BusinessDetailConstants::PLAYSTORE_URL] = $businessDetails->getPlaystoreUrl();

        $data[EntityConstants::MERCHANT_DETAIL][BusinessDetailConstants::APPSTORE_URL] = $businessDetails->getAppstoreUrl();

        $data[EntityConstants::MERCHANT_DETAIL][BusinessDetailConstants::PG_USE_CASE] = $businessDetails->getPgUseCase();

        $data[EntityConstants::MERCHANT_DETAIL][Constants::TOTAL_LEAD_SCORE] = optional($merchant->merchantBusinessDetail)->getTotalLeadScore() ?? 0;

        if($merchantDetail != null) {

            $merchantAov = $merchantDetail->avgOrderValue;

            if ($merchantAov != null) {
                $data[EntityConstants::MERCHANT_DETAIL]['min_aov'] = $merchantAov->getMinAov();

                $data[EntityConstants::MERCHANT_DETAIL]['max_aov'] = $merchantAov->getMaxAov();
            }
        }

        $data[EntityConstants::MERCHANT_DETAIL]['authentication_out_of_band'] = $this->getMerchant3DSOnboardingDetails($merchant);

        $isPayoutService = app('basicauth')->isPayoutService();

        if ($isPayoutService === true)
        {
            $data[EntityConstants::MERCHANT][EntityConstants::CREATED_AT] = $merchant->getCreatedAt();
        }

        $defaultOffersBool = (new Offer\Core())->defaultOffersForMerchant($merchantId);

        $data[EntityConstants::MERCHANT]['default_offers'] = $defaultOffersBool;

        return $data;
    }

    protected function getMerchant3DSOnboardingDetails($merchant): array
    {

        $details = [];
        foreach (Merchant\Constants::listOfNetworksSupportedOn3ds2 as $key => $network) {
            $details[$key] = $this->app['card.payments']->get3ds2DetailsForNetwork($network, $merchant, Product::PRIMARY);
            $details[$key]["network"] = $network;
        }

        return $details;

    }

    protected function getMerchantDetailForInternalGetMerchant($merchantDetail)
    {
        $detail = isset($merchantDetail) === true ? $merchantDetail->toArrayPublic() : [];

        if (empty($detail[Detail\Entity::BUSINESS_TYPE]) === false)
        {
            try
            {
                $detail[Detail\Constants::BUSINESS_TYPE_DISPLAY_NAME] = BusinessType::getDisplayNameFromKey(BusinessType::getKeyFromIndex($detail[Detail\Entity::BUSINESS_TYPE]));
                $detail[Detail\Constants::BUSINESS_TYPE_KEY] = BusinessType::getKeyFromIndex($detail[Detail\Entity::BUSINESS_TYPE]);
            }
            catch (Exception\BadRequestValidationFailureException $e)
            {
                $this->trace->error(
                    TraceCode::MERCHANT_BUSINESS_TYPE_DISPLAY_NAME_NOT_FOUND,
                    [
                        Detail\Entity::BUSINESS_TYPE => $detail[Detail\Entity::BUSINESS_TYPE],
                        'error'                     => $e->getMessage()
                    ]);
            }
        }

        return $detail;
    }

    public function externalGetMerchantCompositeDetails($merchantId)
    {
        $orgId = $this->app['basicauth']->getOrgId();

        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $orgFeature = (new Org\Entity)->getFeatureAssignedToOrg($orgId);

        (new Merchant\Validator)->validateMerchantAccessibilityForOrg($merchantId, $orgFeature);

        $data = [];

        $merchantDetails = $this->internalGetMerchant($merchantId);

        $merchantDetails = array_merge($merchantDetails['merchant'], $merchantDetails['merchant_detail']);

        $data['merchant'] = (new Entity)->toArrayAdminRestrictedWithFeature($merchantDetails, null, $orgFeature);

        $data['terminals'] = (new Terminal\Service())->getMerchantTerminalsForGateway($merchantId, $orgId, Terminal\Entity::featureToGatewayMap[$orgFeature]);

        return $data;
    }

    public function fetchEligiblePricingPlansAndUpdateCorporatePricingRule(int $limit)
    {
        $successCount = 0;
        $failedCount = 0;
        $total = 0;
        $failedIds = [];
        $this->increaseAllowedSystemLimits();

        $planIds = (new Pricing\Core)->fetchEligiblePlansWithMissingCorporateRule($limit);

        $this->trace->info(
            TraceCode::PRICING_PLAN_IDS_FETCH,
            [
                'count'   => count($planIds),
                'planIds' => $planIds,
            ]);

        // increase system timeout
        foreach ($planIds as $planId)
        {
            $total++;
            $this->trace->info(
                TraceCode::PRICING_UPDATE_START,
                [
                    'plan_id' => $planId
                ]);
            try
            {
                $this->repo->transactionOnLiveAndTest(function() use ($planId)
                {
                    $plan = $this->repo->pricing->getPricingPlanById($planId);

                    $data = $plan->toArray();

                    $orgId = $data[0]['org_id'];

                    $rule =  [
                        'payment_method'            => 'card',
                        'fixed_rate'                =>  0,
                        'type'                      => 'pricing',
                        'feature'                   => 'payment',
                    ];

                    // for all card rules with type x and subtype null, add a rule with type business
                    // if network level rule exists, add a network rule with type business as well

                    $rulesWithTypeCreditNull = $this->getCardRuleWithTypeAndSubtype($data,"credit", null);
                    // if count of rule with type credit and sub type null is zero, copy
                    if (count($rulesWithTypeCreditNull) !== 0)
                    {
                        $this->addRuleForType($plan, $rule, "credit", $orgId, $rulesWithTypeCreditNull);
                    }

                    $rulesWithTypeDebitNull = $this->getCardRuleWithTypeAndSubtype($data, "debit", null);

                    if (count($rulesWithTypeDebitNull) !== 0)
                    {
                        $this->addRuleForType($plan, $rule, "debit", $orgId, $rulesWithTypeDebitNull);
                    }

                    $rulesWithTypePrepaidNull = $this->getCardRuleWithTypeAndSubtype($data, "prepaid", null);

                    if (count($rulesWithTypePrepaidNull) !== 0)
                    {
                        $this->addRuleForType($plan, $rule, "prepaid", $orgId, $rulesWithTypePrepaidNull);
                    }

                    $rulesWithTypeNullNull = $this->getCardRuleWithTypeAndSubtype($data,null, null);

                    $this->addRuleForType($plan, $rule, null, $orgId, $rulesWithTypeNullNull);

                    $this->trace->info(
                        TraceCode::PRICING_UPDATE_FINISH,
                        [
                            'planId' => $planId
                        ]);
                });

                $successCount++;
            }
            catch (\Throwable $ex)
            {
                $failedIds[] = $planId;
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::PRICING_BULK_UPDATE_EXCEPTION, ['plan_id' => $planId]);
                $failedCount++;
            }
        }

        $this->trace->info(
            TraceCode::PRICING_UPDATE_FINISH_ALL,
            ["success"=> $successCount, "failed"=> $failedCount,  "total" => $total, "failedIds" => $failedIds]);

        return ["success"=> $successCount, "failed"=> $failedCount,  "total" => $total, "failedIds" => $failedIds];
    }

    protected function addRuleForType($plan, $newPricingRule, $type, $orgId, array $existingPricingRulesWithTypeNull = [])
    {
        $newPricingRule['percent_rate'] = 300;

     //   $rule['international'] = $rule['international'] ? '1' : '0';

        $newPricingRule['payment_method_type'] = $type;

        $newPricingRule['payment_method_subtype'] = 'business';

        $addedRules = 0;

        for($i = 0; $i<count($existingPricingRulesWithTypeNull); $i++)
        {
            $paymentNetwork =  $existingPricingRulesWithTypeNull[$i]['payment_network'];

            if($paymentNetwork === null || ($paymentNetwork === 'MC' || $paymentNetwork === 'VISA' || $paymentNetwork === 'RUPAY'))
            {
                $newPricingRule['payment_network'] = $paymentNetwork;

                (new Pricing\Core())->addPlanRule($plan, $newPricingRule, $orgId);

                if($paymentNetwork === null)
                {
                    $addedRules++;
                }
            }
        }

        if($type === null && $addedRules === 0)
        {
            // atleast one card null business null rule is added
            (new Pricing\Core())->addPlanRule($plan, $newPricingRule, $orgId);
        }
    }

    protected function getCardRuleWithTypeAndSubtype(array $rules, $type, $subtype)
    {
        $data = [];
        foreach ($rules as $rule)
        {
            if(($rule['product'] !== 'primary') or ($rule['feature'] !== 'payment') or ($rule['type'] !== 'pricing'))
            {
                continue;
            }

            if($rule['payment_method'] !== 'card'){
                continue;
            }

            if($rule['international'] !== false){
                continue;
            }

            if($rule['payment_method_type'] !== $type){
                continue;
            }

            if($rule['payment_method_subtype'] !== $subtype){
                continue;
            }


            $data[] = $rule;
        }

        return $data;
    }

    /**
     * returns merchant's submissionDate
     */
    public function internalGetMerchantSubmissionDate($merchantId)
    {
        (new Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $detailService = new Detail\Service();

        return ['first_l2_submission_timestamp' => ($detailService)->getFirstL2SubmissionDate()];
    }
    public function getRejectionReasons($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $currentActivationState = $merchant->currentActivationState();

        if ($currentActivationState === null)
        {
            return (new Base\PublicCollection())->toArrayPublic();
        }

        return $currentActivationState->rejectionReasons()->get()->toArrayPublic();
    }

    public function sendMerchantEmail($merchantId, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_SEND_EMAIL_REQUEST,
            $input
        );

        (new Validator)->validateInput(self::MERCHANT_MAIL, $input);

        $emailType = $input["type"];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $data = $input["data"];

        $data['contact_name'] =  $merchant->getName();

        $data['contact_email'] = $merchant->getEmail();

        $this->trace->info(
            TraceCode::MERCHANT_SEND_EMAIL_REQUEST,
            $data
        );

        switch ($emailType)
        {
            case Constants::MERCHANT_INSTRUMENT_STATUS_UPDATE:

                (new Validator)->validateInput(Constants::INSTRUMENT_STATUS_UPDATE_MERCHANT_MAIL, $data);

                $mail = new StatusNotify($data);

                break;

            default:
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_EMAIL_TYPE,
                    null,
                    [
                        'type' => $emailType,
                    ]);
        }

        Mail::queue($mail);

        return ['success' => true];
    }
    /**
     * returns merchant id, name and website
     */
    public function getMerchantBulk($input)
    {
        $ids = $input["ids"];

        $merchants = $this->repo->merchant->findMany($ids, [Entity::ID, Entity::NAME, Entity::WEBSITE, Entity::ORG_ID]);

        $data = $merchants->toArrayPublic();

        return $data;
    }
    /**
     * Will provide if merchant is confirmed or not.
     *
     * @param  $merchant
     * @return bool
     */
    public function getMerchantConfirmed($merchant)
    {
        $parentId = $merchant->getParentId();

        // Market place sub accounts are confirmed.
        if (empty($parentId) === false)
        {
            return true;
        }
        else
        {
            $owner = $this->core()->getMerchantConfirmedOwner($merchant);

            // True if an confirmed owner is present.
            return !empty($owner);
        }
    }

    private function shouldSkipNotificationForClient($clientId)
    {
        $applicationId = (new OAuthClient\Repository)->findOrFail($clientId)[OAuthClient\Entity::APPLICATION_ID];

        $shouldSkipNotification = $this->featureService->checkFeatureEnabled(Feature\Constants::APPLICATION, $applicationId, Feature\Constants::SKIP_OAUTH_NOTIFICATION)['status'];

        $this->trace->info(
            TraceCode::FEATURE_SKIP_OAUTH_NOTIFICATION,
            [
                'should_skip_notification_for_application_id' => $shouldSkipNotification,
                'application_id'                              => $applicationId
            ]);

        return $shouldSkipNotification;
    }

    /**
     * Sends a mail to the merchant when an action is taken
     * on oauth access to his account
     * In case of tally auth application, sends an otp via mail
     *
     * @param array $input
     * @param string $type
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function sendOAuthNotification(array $input, string $type): array
    {
        if ($this->shouldSkipNotificationForClient($input[OAuthToken\Entity::CLIENT_ID]) === true)
        {
            return ['success' => true];
        }

        if($type === 'tally_auth_otp' )
        {
            return $this->sendTallyAuthOTPMail($input, $type);
        }
        else
        {
            return $this->sendOAuthMail($input, $type);
        }
    }

    /**
     * Sends a mail to the merchant when an action is taken
     * on oauth access to his account
     *
     * @param array $input
     * @param string $type
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function sendOAuthMail(array $input, string $type): array
    {
        $this->trace->info(
            TraceCode::SEND_OAUTH_MAIL_REQUEST,
            [
                'type'  => $type,
                'input' => $input
            ]);

        (new Validator)->validateInput(self::OAUTH_MAIL, $input);

        $merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

        $user     = $this->repo->user->findOrFail($input[User\Entity::USER_ID]);

        $client   = (new OAuthClient\Repository)->findOrFail($input[OAuthToken\Entity::CLIENT_ID]);

        $mailer   = $this->getOAuthMailerClassByType($type);

        $data = [
            'merchant'    => $merchant->toArrayPublic(),
            'user'        => $user->toArrayPublic(),
            'application' => $client->application->toArrayPublic(),
        ];

        Mail::queue((new $mailer($data)));

        $this->sendCompetitorAppAuthorizedEmail($merchant, $client);

        return ['success' => true];
    }

    /**
     * Sends an email to support team informing them that a merchant has authorized
     * an application owned by a competitor like Juspay.
     *
     * @param Entity             $merchant
     * @param OAuthClient\Entity $client
     */
    protected function sendCompetitorAppAuthorizedEmail(Entity $merchant, OAuthClient\Entity $client)
    {
        $application = $client->application;

        if ($this->shouldSendCompetitorAppAuthorizedEmail($merchant, $application) === false)
        {
            return;
        }

        $type = 'competitor_app_authorized';

        $mailer = $this->getOAuthMailerClassByType($type);

        $data = [
            'merchant'    => [
                Entity::ID            => $merchant->getId(),
                Entity::NAME          => $merchant->getName(),
                Entity::WEBSITE       => $merchant->getWebsite(),
                Entity::BILLING_LABEL => $merchant->getBillingLabel(),
            ],
            'application' => [
                OAuthApplication\Entity::NAME => $application->getName(),
            ]
        ];

        Mail::queue((new $mailer($data)));
    }

    /**
     * Sends an OTP via mail to the merchant
     * on tally auth integration request to his account
     *
     * @param array $input
     * @param string $type
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function sendTallyAuthOTPMail(array $input, string $type): array
    {
        (new Validator)->validateInput(self::TALLY_AUTH_OTP_MAIL, $input);

        $this->trace->info(
            TraceCode::SEND_TALLY_AUTH_OTP_MAIL_REQUEST,
            [
                'type'          => $type,
                'merchant_id'   => $input[Entity::MERCHANT_ID],
                'user_id'       => $input[User\Entity::USER_ID],
                'client_id'     => $input[OAuthToken\Entity::CLIENT_ID]
            ]);

        $merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

        $user     = $this->repo->user->findOrFail($input[User\Entity::USER_ID]);

        $client   = (new OAuthClient\Repository)->findOrFail($input[OAuthToken\Entity::CLIENT_ID]);

        $mailer   = $this->getOAuthMailerClassByType($type);

        $logoUrl = null;

        if (empty($client->application['logo_url']) === false)
        {
            $cdnName = $this->app->environment() === Environment::PRODUCTION ? 'cdn' : 'betacdn';

            // Constructing the cdn url for logo. We save multiple sizes of logo, using large here by adding the `_large` after the id.
            $logoUrl = 'https://' . $cdnName . '.razorpay.com' . preg_replace('/\.([^\.]+$)/', '_large.$1', $client->application['logo_url']);
        }

        $data = [
            'merchant'    => [
                'name' => $merchant['name'],
                'id'   => $merchant['id']
            ],
            'application' => [
                'name'     => $client->application['name'],
                'logo_url' => $logoUrl
            ],
            'user'        => [
                'name'  => $user['name']
            ],
            'otp'         => $input['otp'],
            'email'       => $input['email']
        ];

        Mail::queue((new $mailer($data)));

        return ['success' => true];
    }

    /**
     * @param Entity                  $merchant
     * @param OAuthApplication\Entity $app
     *
     * @return bool
     */
    protected function shouldSendCompetitorAppAuthorizedEmail(
        Entity $merchant,
        OAuthApplication\Entity $app): bool
    {
        // Do not send the email if the application is not a competitor to us
        if (in_array($app->getId(), Feature\Type::S2S_APPLICATION_IDS) === false)
        {
            return false;
        }

        // Do not send the email if the merchant has already authorized the app before
        $appAuthorized = $this->repo
                              ->merchant_access_map
                              ->findMerchantAccessMapOnEntityId($merchant->getId(),
                                                                $app->getId(),
                                                                AccessMap\Entity::APPLICATION);

        if ($appAuthorized !== null)
        {
            return false;
        }

        return true;
    }

    /**
     * Returns OAuth mailer class name by event type. Also validates that
     * the same exists. If not throws a bad request exception.
     *
     * @param string $type
     *
     * @return string
     *
     * @throws Exception\BadRequestException
     */
    protected function getOAuthMailerClassByType(string $type): string
    {
        $mailer = 'RZP\\Mail\\OAuth\\' . studly_case($type);

        if (class_exists($mailer) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OAUTH_MAIL_TYPE,
                null,
                [
                    'type' => $type,
                ]);
        }

        return $mailer;
    }

    public function fetchAnalytics(array $input): array
    {
        (new Validator())->validateCheckoutQueries($input);

        $input = (new Core())->processMerchantAnalyticsQuery($this->merchant->getId(), $input);

        $this->trace->info(TraceCode::HARVESTER_REQUEST_DETAILS,[
            "data" => $input
        ]);

        $dataProcessor = new DataProcessor();

        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::HARVESTER_SEGREGATE_QUERIES,
            $this->app['basicauth']->getMode() ?? "live"
        );

        if(strcmp($variant, Constants::RAZORX_EXPERIMENT_ON) != 0) {
            $response = $this->app['eventManager']->query($input, self::REQUEST_TIMEOUT_MERCHANT_ANALYTICS);

            return $dataProcessor->processMerchantAnalyticsResponse($response);
        }

        if(isset($input[Constants::AGGREGATIONS]) === false) {
            $response = $this->app['eventManager']->query($input, self::REQUEST_TIMEOUT_MERCHANT_ANALYTICS);

            return $dataProcessor->processMerchantAnalyticsResponse($response);
        }

        $queries = $this->segregateQueries($input);

        $response = [];

        foreach($queries as $query)
        {
            $data = $this->app['eventManager']->query($query, self::REQUEST_TIMEOUT_MERCHANT_ANALYTICS);

            if($data !== null)
            {
                $response = array_merge($response, $data);
            }
        }

        return $dataProcessor->processMerchantAnalyticsResponse($response);
    }

    public function segregateQueries(array $input): array
    {
        $queries = [];

        $aggregations = $input[Constants::AGGREGATIONS];
        $filters = $input[Constants::FILTERS];

        foreach($aggregations as $aggregationKey => $aggregationValue)
        {
            $query = [];

            $query[Constants::AGGREGATIONS][$aggregationKey] =  $aggregationValue;

            $query[Constants::FILTERS] = $filters;

            array_push($queries, $query);
        }

        return $queries;
    }

    /**
     * Creates submerchant User and associates with the submerchant as owner.
     *
     * @param string $merchantId
     * @param array  $input
     *
     * @return array
     */
    public function createSubMerchantUser($merchantId, array $input): array
    {
        /** @var Entity $subMerchant */
        $subMerchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $input[User\Entity::MERCHANT_ID] = $this->merchant->getId();

        $input[User\Entity::EMAIL] = $this->validateAndGetEmailInput($subMerchant, $this->merchant, $input);

        $this->validateAggregatorSubMerchantRelation($subMerchant, $this->merchant);

        (new Validator)->validateInput('createSubMerchantUser', $input);

        unset($input[User\Entity::MERCHANT_ID]);

        [$subMerchantUser, $createdNew] =
            $this->createOrFetchUserAndAttachMerchant($subMerchant, $input[User\Entity::EMAIL]);

        // Sends Account linked communication emails to users.
        (new User\Service)->sendAccountLinkedCommunicationEmail($subMerchantUser, $subMerchant, $createdNew);

        $subMerchantUser = $subMerchantUser->toArrayPublic();

        return $subMerchantUser;
    }

    /**
     * In case of partners, we create the sub-merchant's user in case the email
     * is different from partner's. There might be some rare cases where the user
     * with the provided email already exists, in which case we would want to attach
     * that user to the sub-merchant created as owner. Same can happen when trying to
     * create login for a submerchant in the old aggregator model where we will
     * just attach the user found instead of creating one.
     *
     * @param Entity $subMerchant
     * @param string $email
     * @param string|null $product
     *
     * @return array
     */
    public function createOrFetchUserAndAttachMerchant(Entity $subMerchant, string $email, string $product = null): array
    {
        $created = false;

        /** @var User\Entity $subMerchantUser */
        $subMerchantUser = $this->repo->user->getUserFromEmail($email);

        if (empty($subMerchantUser) === true)
        {
            $subMerchantUser = $this->createUserAndAttachMerchant($subMerchant, $email, $product);

            $created = true;
        }
        else
        {
            $this->core()->attachSubMerchantUser($subMerchantUser->getId(), $subMerchant, $product);
        }

        return [$subMerchantUser, $created];
    }

    protected function createUserAndAttachMerchant(Entity $subMerchant, string $email, string $product = null): User\Entity
    {
        $skipCaptcha = Request::all()[User\Entity::SKIP_CAPTCHA_VALIDATION] ?? false;

        $userData = $this->formatUserCreationData($email, $subMerchant, $skipCaptcha);

        $userData[Merchant\Entity::SIGNUP_SOURCE]=$product;

        $isLinkedAccountUser = ($subMerchant->isLinkedAccount() === true);

        if ($skipCaptcha === true)
        {
            $subMerchantUser = (new User\Core)->create($userData, 'create_without_captcha', $isLinkedAccountUser);
        }
        else
        {
            $subMerchantUser = (new User\Core)->create($userData,'create', $isLinkedAccountUser);
        }

        $this->core()->attachSubMerchantUser($subMerchantUser->getId(), $subMerchant, $product);

        return $subMerchantUser;
    }

    /**
     * In case of `create login` in the old aggregator flow, the email is taken from
     * the sub-merchant for creating an owner for the account.
     * In case of partner type aggregator the email comes in input.
     *
     * @param  Entity $subMerchant
     * @param  Entity $partnerMerchant
     * @param  array $input
     *
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateAndGetEmailInput(Entity $subMerchant, Entity $partnerMerchant, array $input)
    {
        if (empty($input[User\Entity::EMAIL]) === true)
        {
            return $subMerchant->getEmail();
        }

        $isAggregatorPartner = $partnerMerchant->isAggregatorPartner();

        $isFullyManagedPartner = $partnerMerchant->isFullyManagedPartner();

        $subEmailIsSameAsPartner = ($subMerchant->getEmail() === $partnerMerchant->getEmail());

        $subMerchantHasLessThanTwoOwners = ($subMerchant->owners()->count() < 2);

        $inviteEmailSameAsSelf = ($input[User\Entity::EMAIL] === $subMerchant->getEmail());

        //
        // In case of a partner of type `aggregator` or `fully_managed` having created a sub-merchant
        // without providing email explicitly, we want to provide the ability to create
        // an owner for the sub-merchant, with an email, later.
        // In both old aggregator and partners flow, we never expect the total number of
        // owners for a merchant to be greater than 2 (1 for partner and 1 for sub-merchant).
        //
        // If the sub-merchant email is changed later then the invite may still need to be
        // sent for login but that should only be to the merchant email.
        //
        if ((($isAggregatorPartner === true) or ($isFullyManagedPartner === true)) and
            (($subEmailIsSameAsPartner or $inviteEmailSameAsSelf) === true) and
            ($subMerchantHasLessThanTwoOwners === true))
        {
            return $input[User\Entity::EMAIL];
        }

        throw new Exception\BadRequestValidationFailureException(
            PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_MERCHANT_USER);
    }

    public function formatUserCreationData(string $email, Entity $subMerchant, $skipCaptcha = false)
    {
        $dummyPass = bin2hex(random_bytes(20));
        $subMerchantDetails = (new MerchantDetailCore())->getMerchantDetails($subMerchant);

        $userData = [
            User\Entity::NAME                  => $subMerchant->getName(),
            User\Entity::EMAIL                 => $email,
            User\Entity::CONTACT_MOBILE        => $subMerchantDetails->getContactMobile(),
            User\Entity::PASSWORD              => $dummyPass,
            User\Entity::PASSWORD_CONFIRMATION => $dummyPass,
            User\Entity::CAPTCHA_DISABLE       => User\Validator::DISABLE_CAPTCHA_SECRET,
            Merchant\Entity::COUNTRY_CODE      => $subMerchant->getCountry(),
        ];
        if ($skipCaptcha === true)
        {
            unset($userData[User\Entity::CAPTCHA_DISABLE]);
        }
        return $userData;
    }

    public function enableEmiMerchantSubvention(string $id, string $emiPlanId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $emiPlan = $this->repo->emi_plan->handleFindOrFailPublic($emiPlanId);

        return $this->core()->enableEmiMerchantSubvention($merchant, $emiPlan, $input);
    }

    public function getDummyRazorX()
    {
        $variant = $this->app->razorx->getTreatment('123', 'dummy', 'mode');

        return ['variant' => $variant];
    }

    protected function createSubMerchantAndSetRelationsInternal($input,
                                                                $merchant,
                                                                $isLinkedAccount,
                                                                $ownerId,
                                                                $product,
                                                                $optimizeCreationFlow = false)
    {
        $enableDashboardAccess = (bool) ($input['dashboard_access'] ?? false);

        $allowReversals = (bool) ($input['allow_reversals'] ?? false);

        $this->checkDashboardAccessForAllowReversals($enableDashboardAccess, $allowReversals);

        unset($input['dashboard_access']);

        unset($input['allow_reversals']);

        $subMerchant = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_CORE], function () use ($input, $merchant, $isLinkedAccount, $optimizeCreationFlow) {

            /** @var  Core */
            $merchantCore = $this->core();

            /** @var Entity */
            return $merchantCore->createSubMerchant($input, $merchant, $isLinkedAccount, false, $optimizeCreationFlow);
        });

        $newUser = null;

        $createdNewUser = false;

        if ($isLinkedAccount === false)
        {
            SubMerchantTaggingJob::dispatch($this->mode, $merchant->getId(), $subMerchant->getId(), Constants::PARTNER_REFERRAL_TAG_PREFIX);

            Tracer::inspan(['name' => HyperTrace::ATTACH_SUBMERCHANT_USER_IF_APPLICABLE], function () use ($ownerId, $subMerchant, $merchant, $product) {

                $this->attachSubMerchantUserIfApplicable($ownerId, $subMerchant, $merchant, $product);
            });

            Tracer::inspan(['name' => HyperTrace::MAP_SUBMERCHANT_PARTNER_APP_IF_APPLICABLE], function () use ($merchant, $subMerchant) {
                // Partner and sub-merchant are connected via partner's app,
                // this connect is used for multiple validity checks, web-hooks, etc
                $this->mapSubMerchantPartnerAppIfApplicable($merchant, $subMerchant);
            });
        }

        // Users will be created and given access to the account in partners flow, irrespective of enable
        // dashboard access. users will be created and given access in linked accounts case only when enable
        // dashboard access is true.
        if ((($enableDashboardAccess === true) and ($isLinkedAccount === true)) or ($isLinkedAccount === false))
        {
            try
            {
                [$newUser, $createdNewUser] = Tracer::inspan(['name' => HyperTrace::CREATE_ADDITIONAL_USER_OR_FETCH_IF_APPLICABLE], function () use ($subMerchant, $merchant, $product) {

                    return $this->createAdditionalUserOrFetchIfApplicable($subMerchant, $merchant, $product);
                });
            }
            catch (\Illuminate\Database\QueryException $ex)
            {
                // throw 4xx bad request exception instead of 5xx error in case email is duplicate. Issue thread: https://razorpay.slack.com/archives/C01G2BS6JTH/p1662794582254929
                if ($ex->getCode() === "23000" and in_array(1062, $ex->errorInfo) === true and strpos($ex->errorInfo[2], "users_email_unique") !== false)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS);
                }

                throw $ex;
            }
        }

        if ($product === Product::BANKING)
        {
            $this->enableBusinessBankingIfApplicable($subMerchant, true);
        }

        $this->repo->saveOrFail($subMerchant);

        if (($allowReversals === true) and ($isLinkedAccount === true))
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID    => $subMerchant->getId(),
                Feature\Entity::ENTITY_TYPE  => CE::MERCHANT,
                Feature\Entity::NAME         => Feature\Constants::ALLOW_REVERSALS_FROM_LA
            ];
            Tracer::inspan(['name' => HyperTrace::ADD_FEATURE_REQUEST], function () use ($featureParams) {

                (new Feature\Core)->create($featureParams, true);
            });
        }

        $subMerchantAdditionType = ($isLinkedAccount === true) ? Metric::MARKETPLACE : Metric::PARTNER;

        $dimensions = [Metric::SUB_MERCHANT_ADD_TYPE => $subMerchantAdditionType];

        $this->trace->count(Metric::ADD_SUB_MERCHANT, $dimensions);

        $this->trace->count(PartnerMetric::SUBMERCHANT_PRICING_PLAN_ASSIGN_TOTAL, ['partner_type' => $merchant->getPartnerType()]);

        $this->trace->count(PartnerMetric::SUBMERCHANT_USER_CREATE_TOTAL, ['submerchant_user_created' => $createdNewUser]);

        return [$subMerchant, $newUser, $createdNewUser];
    }

    protected function createSubMerchantAndSetRelations(Entity $merchant, bool $isLinkedAccount, array $input, bool $optimizeCreationFlow = false)
    {
        $ownerId = $merchant->primaryOwner()->getId();

        $product = $input[Entity::PRODUCT] ?? Product::PRIMARY;

        $actualProduct = $input['actual_product'] ?? $product;

        unset($input['actual_product']);

       //block P.G sub merchant creation
        if ($isLinkedAccount === false)
        {
            $inputCopy = $input;

            $inputCopy[Entity::SIGNUP_SOURCE] = $product;

            (new UserCore())->validateAccountCreation($inputCopy);
        }

        // TODO: Remove when dashboard stops sending
        unset($input['user_id']);
        unset($input['account']);
        unset($input[Entity::PRODUCT]);

        list($subMerchant, $newUser, $createdNew) = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_AND_SET_RELATIONS_INTERNAL], function () use ($optimizeCreationFlow, $input, $merchant, $isLinkedAccount, $ownerId, $product) {
            if ($optimizeCreationFlow === false) {
                [$subMerchant, $newUser, $createdNew] = $this->repo->transactionOnLiveAndTest(function () use (
                    $input,
                    $merchant,
                    $isLinkedAccount,
                    $ownerId,
                    $product
                ) {
                    return $this->createSubMerchantAndSetRelationsInternal($input, $merchant, $isLinkedAccount, $ownerId, $product, false);
                });
            } else {
                [$subMerchant, $newUser, $createdNew] = $this->createSubMerchantAndSetRelationsInternal($input, $merchant, $isLinkedAccount, $ownerId, $product, true);
            }
            return [$subMerchant, $newUser, $createdNew];
        });

        if ($merchant->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true)
        {
            $this->app->hubspot->skipMerchantOnboardingComm($subMerchant->getEmail());
        }

        Tracer::inspan(['name' => HyperTrace::SEND_MAIL_TO_SUBMERCHANT], function () use ($merchant, $isLinkedAccount, $newUser, $subMerchant, $createdNew, $actualProduct) {

            // Sends email to marketplace LA dashboard enabled users.
            if ((empty($newUser) === false) and (($merchant->isMarketplace() and $isLinkedAccount) === true))
            {
                (new User\Service)->sendAccountLinkedCommunicationEmail($newUser, $subMerchant, $createdNew);
            }
            else if (((($merchant->isMarketplace() === true) and ($isLinkedAccount === true)) === false) and
                     ($merchant->canCommunicateWithSubmerchant() === true))
            {
                $this->communicateSubMerchantCreation($subMerchant, $merchant, $actualProduct, $newUser, $createdNew);
            }
        });

        /**
         *  Slack thread - https://razorpay.slack.com/archives/C021KESTRLH/p1671430073261459
         *  Jira - https://razorpay.atlassian.net/browse/PRTS-2171 and
         *  https://razorpay.atlassian.net/browse/PRTS-1085
         *  Sometime stork calls api for cache even before db parent transaction finished. So sending cache invalidation
         *  request again to stork.
         */
        if ($isLinkedAccount === false)
        {
            \Event::dispatch(new TransactionalClosureEvent(function() use ($subMerchant) {
                Tracer::inspan(['name' => HyperTrace::SUBMERCHANT_STORK_INVALIDATE_CACHE_REQUEST], function() use ($subMerchant) {
                    $this->invalidateAffectedOwnersCache($subMerchant->getId());
                });
            }));
        }

        return $this->getSubMerchantResponseArray($merchant, $subMerchant, $product);
    }

    /**
     * This returns subMerchant entity as it is in case of old-aggregator/marketplace
     * flow and subMerchant with additional partner dashboard details in case of
     * partner flow.
     *
     * @param Entity $merchant
     * @param Entity $subMerchant
     * @param string|null $product
     *
     * @return array
     */
    protected function getSubMerchantResponseArray(Entity $merchant, Entity $subMerchant, string $product = null): array
    {
        if (($merchant->isPartner() === true) and ($subMerchant->isLinkedAccount() === false))
        {
            //
            // This gets submerchant for a partner, with extra details required by partner dashboard.
            // This does not get called for pure platform partners.
            //
            $subMerchant = $this->core()->getSubmerchant($merchant, $subMerchant->getId(), [Entity::PRODUCT => $product]);

            $subMerchant = $subMerchant->toArrayPartner();
        }
        else
        {
            $subMerchant = $subMerchant->toArrayPublic();
        }

        return $subMerchant;
    }

    protected function createAdditionalUserOrFetchIfApplicable(Entity $subMerchant, Entity $merchant, string $product = null)
    {
        $subMerchantUser = null;
        $createdNew      = false;

        if ((($merchant->isPartner() === true) or ($merchant->isMarketplace() === true)) and
            ($subMerchant->getEmail() !== $merchant->getEmail()))
        {
            [$subMerchantUser, $createdNew] =
                $this->createOrFetchUserAndAttachMerchant($subMerchant, $subMerchant->getEmail(), $product);
        }

        return [$subMerchantUser, $createdNew];
    }

    protected function mapSubMerchantPartnerAppIfApplicable(Entity $merchant, Entity $subMerchant)
    {
        $this->trace->info(TraceCode::MAP_PARTNER_SUBMERCHANT_ENTITY);

        if ($merchant->isPartner() === false)
        {
            return;
        }

        $app = $this->core()->fetchPartnerApplication($merchant);

        $appId = $app->getId();

        (new AccessMap\Service)->mapOAuthApplication(
                                                $subMerchant->getId(),
                                                ['application_id' => $appId, 'partner_id' => $merchant->getId()]);
    }

    /**
     * @param  Entity $subMerchant
     * @param  Entity $aggregatorMerchant
     *
     * @throws Exception\BadRequestException
     */
    protected function validateAggregatorSubMerchantRelation(Entity $subMerchant, Entity $aggregatorMerchant)
    {
        if ($subMerchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $referrer = $subMerchant->getReferrer();

        $referrerNotEmptyAndSame = (empty($referrer) === false) and ($referrer === $aggregatorMerchant->getId());

        if ($referrerNotEmptyAndSame === true)
        {
            return;
        }

        $isNonPurePlatformAggregator = $aggregatorMerchant->isNonPurePlatformPartner();

        $isMapped = $this->core()->isMerchantManagedByPartner($subMerchant->getId(), $aggregatorMerchant->getId());

        if (($isNonPurePlatformAggregator === true) and ($isMapped === true))
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
    }

    /**
     * This used to map submerchants to the partner in merchant_access_map entity
     * If the given partnerId is not a partner then it will mark him as a partner then proceed
     *
     * @param array $input
     *
     * @return array
     */
    public function createPartnerSubmerchantMap(array $input)
    {
        (new Validator)->validateInput('partner_submerchant_map', $input);

        $partnerType   = $input[ENTITY::PARTNER_TYPE];
        $submerchantId = $input['submerchant_id'];
        $partnerId     = $input['partner_merchant_id'];

        $partner = $this->markAsPartner($partnerId, $partnerType);

        return $this->mapSubmerchant($partner, $submerchantId);
    }

    public function fetchPartnerIntent(): array
    {
        $response = (new Settings\Service)->get(
            Constants::PARTNER,
            Constants::PARTNER_INTENT);

        $partnerIntent = $response['settings'];

        if ($partnerIntent instanceof Dictionary)
        {
            $partnerIntent = null;
        }
        else
        {
            $partnerIntent = boolVal($partnerIntent);
        }

        return [
            Constants::PARTNER_INTENT       => $partnerIntent,
        ];
    }

    /**
     * Updates partner_intent key in settings table
     * @param array $input
     *
     * @return array
     */
    public function updatePartnerIntent(array $input): array
    {
        (new Validator)->validateInput('update_partner_intent', $input);

        (new Settings\Service)->upsert(
            Constants::PARTNER,
            $input);

        // since Settings/Service->upsert does not return anything hence,
        // returning whatever was passed in input
        return [
            Constants::PARTNER_INTENT   => $input[Constants::PARTNER_INTENT],
        ];
    }

    /**
     * @param string $merchantId
     *
     * @return array
     */
    public function createPartnerAccessMap(string $merchantId): array
    {
        $partner = $this->fetchPartner();

        $submerchant = $this->fetchSubmerchant($merchantId);

        $accessMap = $this->core()->createPartnerSubmerchantAccessMap($partner, $submerchant);

        $data = [
            'status'       => 'success',
            'merchant_id'  => $merchantId,
            'partner_id'   => $partner->getId(),
            'source'       => PartnerConstants::LINKING_ADMIN
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
            $partner, null,
            $data);

        if ($partner->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true)
        {
            $this->app->hubspot->skipMerchantOnboardingComm($submerchant->getEmail());
        }

        $this->app->hubspot->trackSubmerchantSignUp($partner->getEmail());

        $dimension = [
            'partner_type' => $partner->getPartnerType(),
            'source'       => PartnerConstants::LINKING_ADMIN
        ];

        $this->trace->count(PartnerMetric::SUBMERCHANT_CREATE_TOTAL, $dimension);

        return $accessMap;
    }

    /**
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws \Throwable
     */
    public function updatePartnerAccessMap(string $merchantId, array $input)
    {
        $partner = $this->fetchPartner();

        $submerchant = $this->fetchSubmerchant($merchantId);

        $accessMap = $this->core()->updatePartnerAccessMap($input, $partner, $submerchant);

        return $accessMap;
    }

    /**
     * @param Merchant\Entity $partner
     * @param                 $submerchantId
     *
     * @throws BadRequestException
     */
    protected function mapSubmerchant(Merchant\Entity $partner, $submerchantId): array
    {
        // Using findOrFail here will not give a proper error code in the batch output.
        $submerchant = $this->repo->merchant->find($submerchantId);

        if ($submerchant === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                Merchant\Entity::ID,
                [
                    Merchant\Entity::ID => $submerchantId
                ]);
        }

        return $this->core()->createPartnerSubmerchantAccessMap($partner, $submerchant);
    }

    /**
     * @param       $merchantId
     * @param       $partnerType
     *
     * @return Merchant\Entity
     * @throws BadRequestException
     */
    protected function markAsPartner($merchantId, $partnerType): Merchant\Entity
    {
        $partner = $this->repo->merchant->find($merchantId);

        if ($partner === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                Merchant\Entity::ID,
                [
                    Merchant\Entity::ID => $merchantId
                ]);
        }

        // Mark as partner only if the merchant is not a partner
        if ($partner->isPartner() === true)
        {
            return $partner;
        }

        if (empty($partnerType) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER);
        }

        $partner = $this->core()->markAsPartner($partner, $partnerType);

        return $partner;
    }

    public function updatePartnerType(array $input): array
    {

        $validator = new Validator;

        $validator->validateInput('update_partner_type', $input);

        $validator->validateOrgDetails($this->merchant);

        $response = Tracer::inspan(['name' => HyperTrace::UPDATE_PARTNER_TYPE_CORE,
                     'attributes' => array ( Entity::PARTNER_TYPE =>  $input[Entity::PARTNER_TYPE], 'merchantId'=> $this->merchant->getId())], function () use ($input) {

            return $this->core()->updatePartnerType($this->merchant, $input[Entity::PARTNER_TYPE]);
        });

        if( empty($input[DEConstants::CONSENT]) === false)
        {
            $mode = ($this->app['env'] === Environment::TESTING) ? Mode::TEST : Mode::LIVE;
            $input[DEConstants::IP_ADDRESS ] = $this->app['request']->ip();
            $input[DEConstants::USER_ID]     = $this->app['request']->header(RequestHeader::X_DASHBOARD_USER_ID);
            $input[DEConstants::DOCUMENTS_DETAIL] = [
                [
                    DEConstants::TYPE => Constants::TERMS,
                    DEConstants::URL  => Constants::RAZORPAY_PARTNERSHIP_TERMS,
                ]
            ];

            CapturePartnershipConsents::dispatch($mode, $input, $this->merchant->getId(), Constants::PARTNERSHIP);
        }

        return $response;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function makeMerchantAsBankCaOnboardingPartnerType(array $input): array
    {
        $merchantId = array_pull($input, Base\PublicEntity::MERCHANT_ID);

        $this->merchant = $this->repo->merchant->findorFailPublic($merchantId);

        // This tag is to make sure this merchant can invite bank role users to join.
        $this->core()->appendTag($this->merchant, Constants::ENABLE_RBL_LMS_DASHBOARD);

        return Tracer::inspan(['name'       => HyperTrace::UPDATE_PARTNER_TYPE_CORE,
                               'attributes' => array(Entity::PARTNER_TYPE => $input[Entity::PARTNER_TYPE], 'merchantId' => $this->merchant->getId())],
            function() use ($input) {
                return $this->core()->updatePartnerTypeToBankCaOnboarding($this->merchant, $input[Entity::PARTNER_TYPE]);
            });
    }

    public function backFillMerchantApplications(array $input)
    {
        $limit = $input['limit'];

        $merchantIds = $input['merchant_ids'];

        $afterId = $input['afterId'];

        return CallBackFillMerchantApps::dispatch($this->mode, $merchantIds, $limit, $afterId);
    }

    public function backFillReferredApplication(array $input)
    {
        $limit = $input['limit'];

        $merchantIds = $input['merchant_ids'];

        $afterId = $input['afterId'];

        return CallBackFillReferredApp::dispatch($this->mode, $merchantIds, $limit, $afterId);
    }

    public function getSubmerchant(string $submerchantId, array $input): array
    {
        Account\Entity::verifyIdAndSilentlyStripSign($submerchantId);

        $partner = $this->fetchPartner();

        $submerchant = $this->core()->getSubmerchant($partner, $submerchantId, $input);

        return $submerchant->toArrayPartner();
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function listSubmerchants(array $input): array
    {
        $partner = $this->fetchPartner();

        $validator = new Validator();

        $validator->validateInput('list_submerchants', $input);
        $validator->validateIsPartner($partner);

        $input['skip'] = $input['skip'] ?? 0;
        $input['count'] = $input['count'] ?? self::DEFAULT_SUBMERCHANT_FETCH_LIMIT;

        if (empty($input[Detail\Entity::ACTIVATION_STATUS]) === false and $input[Detail\Entity::ACTIVATION_STATUS] === 'not_submitted')
        {
            $input[Detail\Entity::ACTIVATION_STATUS] = null;
        }
        // if contact info is present check if it is email or contact no
        if (empty($input[Constants::CONTACT_INFO]) === false ) {
            if((new EmailValidator)->isEmail($input[Constants::CONTACT_INFO]))
            {
                $input[Entity::EMAIL] = $input[Constants::CONTACT_INFO] ;
            }
            else
            {
                $input[Detail\Entity::CONTACT_MOBILE] = $input[Constants::CONTACT_INFO] ;
            }
            unset($input[Constants::CONTACT_INFO]);
        }
        // format contact mobile to country format
        if (empty($input[Detail\Entity::CONTACT_MOBILE]) === false)
        {
            $input[Detail\Entity::CONTACT_MOBILE] = $this->normalizeContactNo($input[Detail\Entity::CONTACT_MOBILE], $partner);
        }
        $startTime = millitime();
        $isExpEnabled = $this->isSubmerchantFetchMultipleOptimisationExpEnabled($partner->getId());

        $result = Tracer::inspan(['name' => HyperTrace::LIST_SUBMERCHANTS_CORE], function () use ($partner, $input, $isExpEnabled) {
            if ($isExpEnabled)
            {
                return $this->core()->listSubmerchantsV2($partner, $input, $isExpEnabled);
            }

            return $this->core()->listSubmerchants($partner, $input);
        });

        $response = $isExpEnabled ? $result[0]->toListSubmerchantsArray() : $result[0]->toArrayPartner();
        if (array_key_exists(self::OFFSET, $result) === true)
        {
            $response[self::OFFSET] = $result[self::OFFSET];
        }

        $this->trace->histogram(Metric::FETCH_ALL_SUBMERCHANTS_LATENCY, millitime()-$startTime);

        return $response;
    }

    /**
     * normalizes contact no based on country
     * exmaple - converts 9999999999 to +919999999999
     * @param string $contactNo
     * @param Entity $partner
     *
     * @return array|mixed|string|string[]
     * @throws \libphonenumber\NumberParseException
     */
    private function normalizeContactNo(string $contactNo, Entity $partner)
    {
        $number = new PhoneBook($contactNo, true, $partner->getCountry());
        if ($number->isValidNumber() === true)
        {
            return $number->format();
        }
        $normalizedNumber = $number->getRawInput();
        return $normalizedNumber;
    }
    private function isSubmerchantFetchMultipleOptimisationExpEnabled(string $partnerId) : bool
    {
        $authType = $this->app['basicauth']->getAuthType();
        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $this->app['config']->get('app.submerchant_fetch_multiple_optimisation_exp_id'),
            'request_data'  => json_encode([
                'mid'       => $partnerId,
                'auth_type' => $authType,
            ]),
        ];

        return $this->core()->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * @param string $merchantId
     *
     * @throws Exception\BadRequestValidationFailureException
     * @throws \Throwable
     */
    public function deletePartnerAccessMap(string $merchantId)
    {
        $partner = $this->fetchPartner();

        $submerchant = $this->fetchSubmerchant($merchantId);

        $this->core()->deletePartnerAccessMap($partner, $submerchant);
    }

    /**
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function fetchPartner(): Entity
    {
        //
        // In the context of partners and submerchants -
        //
        // $merchant_id here corresponds to the submerchant's id. This is because the merchant_access_map entity maps
        // the submerchant id to the application entity (entity_type = application and entity_id = application_id),
        // which makes the submerchant as the primary entity in the merchant_access_map
        //
        $partner = $this->merchant;

        if ($partner === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_CONTEXT_NOT_SET,
                Entity::PARTNER_TYPE);
        }

        return $partner;
    }

    /**
     * @param string $submerchantId
     *
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function fetchSubmerchant(string $submerchantId): Entity
    {
        // The submerchant should belong to the same org as of the admin
        /** @var Entity $submerchant */
        $submerchant = $this->repo->merchant->findByIdAndOrgId($submerchantId, $this->auth->getOrgId());

        /** @var Admin\Entity $admin */
        $admin = $this->auth->getAdmin();

        // The current admin should have access to the submerchant before the mapping can be created/deleted
        $hasSubmerchantAccess = (new Group\Core)->groupCheck($admin, $submerchant);

        if ($hasSubmerchantAccess === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                Entity::MERCHANT_ID,
                [
                    'admin_id'       => $admin->getId(),
                    'partner_id'     => $submerchantId,
                    'submerchant_id' => $submerchant->getId(),
                ]);
        }

        return $submerchant;
    }

    /**
     * Edits linked account email.
     *
     * @param array $input
     *
     * @return array
     */
    public function editLinkedAccountEmail(array $input): array
    {
        $merchant = $this->merchant;

        $validator = new Validator;

        $validator->validateLinkedAccount($merchant);

        $parentMerchant = $merchant->parent;

        $validator-> validateLinkedAccountUpdation($parentMerchant);

        if (empty($input['email']) === false)
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        $merchant = $this->core()->editEmail($merchant, $input);

        $product = $this->auth->getRequestOriginProduct();

        $this->core()->handleLinkedAccountMerchantsUsers($merchant, $product);

        return $merchant->toArrayPublic();
    }

    public function registerBeneficiariesThroughApi(array $input, string $channel): array
    {
        $response = (new BankAccount\Beneficiary)->registerBeneficiariesThroughApi($input, $channel);

        return $response;
    }

    /**
     * Function to provide dashboard access and allow refunds access to linked accounts.
     * @param array $input
     *
     * @return array
     *
     */
    public function updateLinkedAccountConfig(array $input): array
    {
        $merchant = $this->auth->getMerchant();

        $validator = new Validator;

        $validator->validateLinkedAccount($merchant);

        $parentMerchant = $merchant->parent;

        $validator->validateLinkedAccountUpdation($parentMerchant);

        if (isset($input['dashboard_access']) === true)
        {
            $this->updateLinkedAccountDashboardAccess($input, $merchant);
        }

        if (isset($input['allow_reversals']) === true)
        {
            $this->updateLinkedAccountAllowReversals($input, $merchant);
        }

        return ['success' => true];
    }

    protected function updateLinkedAccountDashboardAccess(array &$input, Merchant\Entity $merchant)
    {
        $dashboardAccess = (bool) ($input['dashboard_access'] ?? false);

        (new Validator)->validateLinkedAccountDashboardAccess($dashboardAccess, $merchant);

        $parentMerchant = $merchant->parent;

        if (($dashboardAccess === true) and ($parentMerchant->isMarketplace() === true))
        {
            if ($parentMerchant->getEmail() === $merchant->getEmail())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_NO_EMAIL_LINKED_ACCOUNT_DASHBOARD_ACCESS);
            }

            [$newUser, $createdNew] = $this->createAdditionalUserOrFetchIfApplicable($merchant, $parentMerchant);

            if (empty($newUser) === false)
            {
                (new User\Service)->sendAccountLinkedCommunicationEmail($newUser, $merchant, $createdNew);
            }
        }
        else
        {
            // Remove allow reversals capability as well if dashboard access is revoked
            $allowReversals = $merchant->isFeatureEnabled(Feature\Constants::ALLOW_REVERSALS_FROM_LA);

            if ($allowReversals === true)
            {
                $input['allow_reversals'] = false;

                $this->updateLinkedAccountAllowReversals($input, $merchant);

                unset($input['allow_reversals']);
            }

            $this->repo->sync($merchant,  'users', []);
        }
    }

    public function updateLinkedAccountAllowReversals(array $input, Merchant\Entity $merchant)
    {
        $allowReversals = (bool) ($input['allow_reversals'] ?? false);

        (new Validator)->validateLinkedAccountReversals($allowReversals, $merchant);

        $feature = [Feature\Constants::ALLOW_REVERSALS_FROM_LA];

        if ($allowReversals === true)
        {
            $this->addFeatures($feature, true);
        }
        else
        {
            $this->removeFeatures($feature, true);
        }
    }

    public function fetchSubMerchantIds(Entity $merchant): array
    {
        $associatedAccounts = [];

        if ($merchant->isPartner() === true)
        {
            // submerchant accounts
            $submerchants = ($this->core()->listSubmerchants($merchant, []))[0];

            $associatedAccounts = $submerchants->getIds();
        }
        return $associatedAccounts;
    }

    /**
     * Fetches submerchant / linked / referred accounts for parent account.
     */
    public function fetchAssociatedAccounts(string $merchantId)
    {
        $associatedAccounts = [];

        $merchant = $this->repo->merchant->findorFailPublic($merchantId);

        if ($merchant->isMarketplace() === true)
        {
            // linked accounts
            $associatedAccounts = $merchant->accounts()->get()->getIds();
            $this->trace->info(TraceCode::ASSOCIATED_ACCOUNTS_FOR_MARKET_PLACE_FEATURE_MERCHANT,
                [
                    'partner_id'           => $merchantId,
                    'associated_accounts'  => $associatedAccounts
                ]
            );
        }
        else if ($merchant->isPartner() === true)
        {
            // submerchant accounts
            $submerchants = ($this->core()->listSubmerchants($merchant, []))[0];
            $associatedAccounts = $submerchants->getIds();

            $this->trace->info(TraceCode::ASSOCIATED_MERCHANT_DATA_FOR_PARTNER_MERCHANTS,
                [
                    'partner_id'            => $merchantId,
                    'associated_accounts'   => $associatedAccounts
                ]
            );
        }
        else if ($merchant->hasAggregatorFeature() === true)
        {
            // referred accounts
            $associatedAccounts = $this->repo->merchant->fetchReferredMerchants($merchantId)->getIds();
        }

        return ['associated_accounts' => array_unique($associatedAccounts)];
    }

    /**
     * Fetch the list of all merchants the submerchant is associated with
     *
     * @param string $merchantId
     *
     * @return array
     */
    public function fetchAffiliatedPartners(string $merchantId): array
    {
        $startTime = millitime();

        $partners = $this->core()->fetchAffiliatedPartners($merchantId);

        $this->trace->histogram(Metric::AFFILIATED_PARTNERS_FETCH_LATENCY,millitime()-$startTime);

        return $partners->toArrayPublic();
    }

    /**
     * Takes Merchant from auth context and sends it to razorx.
     *
     * @param string $featureFlag
     *
     * @return array
     */
    public function getRazorxTreatment(string $featureFlag)
    {
        $merchantId = $this->merchant->getId();

        $mode = $this->mode ?? 'live';

        $result = $this->app['razorx']->getTreatment($merchantId, $featureFlag, $mode);

        $response = ['result' => $result];

        return $response;
    }

    /**
     * Takes Merchant from auth context and sends it to razorx in Bulk
     *
     * @param array $featureFlag
     *
     * @return array
     */
    public function getRazorxTreatmentUsingBulkEvaluate(array $featureFlag)
    {
        $merchantId = $this->merchant->getId();

        $mode = $this->mode ?? 'live';

        $result = $this->app['razorx']->getTreatmentBulk($merchantId, $featureFlag, $mode);

        return $result;
    }

    public function getRazorxTreatmentInBulk(array $input)
    {
        $response = [];

        $featureFlags = $input['features'] ?? "";

        if (empty($featureFlags) === false)
        {
            $timeStarted = microtime(true);

            $featureFlagArray = explode(',', $featureFlags);

            $chunkFeatureArray = array_chunk($featureFlagArray, 10);

            $resultArray = [];

            foreach ($chunkFeatureArray as $batchFeatureArray)
            {
                $trimmed_array = array_map('trim', $batchFeatureArray);

                $result = $this->getRazorxTreatmentUsingBulkEvaluate($trimmed_array);

                $resultArray = array_merge($resultArray, $result);
            }

            foreach ($resultArray as $resultValue)
            {
                $response[$resultValue['feature_flag']] = ['result' => $resultValue['result']];
            }

            $timeTaken = get_diff_in_millisecond($timeStarted);

            $this->trace->histogram(Merchant\Metric::RAZORX_BULK_EVALUATE_TIME_MS, $timeTaken);
        }

        return $response;
    }

    public function submitSupportCallRequest(array $input): array
    {
        $validator = new Validator;
        $validator->validateNowIsWorkingHour();
        $validator->validateInput(__FUNCTION__, $input);

        // Dashboard also does treatment check hence happening this is a invalid request.
        if ($this->canSubmitSupportCallRequest() === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid request.');
        }

        return $this->app->myoperator->submitSupportCallRequest($input);
    }

    public function canSubmitSupportCallRequest() : bool
    {
        $allowCallRequest = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::SUPPORT_CALL,
            $this->mode ?? 'live');

        if ($allowCallRequest !== 'on')
        {
            return false;
        }

        $isActivated = $this->merchant->isActivated();

        $product = $this->app['basicauth']->getProduct();

        $this->trace->info(
        TraceCode::SUBMIT_SUPPORT_CALL_REQUEST,
            compact('input', 'allowCallRequest', 'isActivated'));

        if ($product === Product::BANKING)
        {
            return ($isActivated === true);
        }

        if ($product === Product::PRIMARY)
        {
            return (($isActivated === false) or
                    ($this->merchant->isFundsOnHold() === true));
        }

        return false;
    }

    public function syncMerchantsToEs(array $input)
    {
        return $this->core()->syncMerchantsToEs($input);
    }

    public function bulkRegenerateBalanceIds(array $input)
    {
        $limit = (int) ($input['limit'] ?? 1000);

        $balances = $this->repo->balance->getBalances($limit);

        $failed = 0;
        $failedIds = [];
        $success = 0;
        $total = count($balances);

        $this->trace->info(
            TraceCode::MERCHANT_BALANCE_BACKFILL_REQUEST,
            [
                'merchant_ids' => $balances->pluck(Entity::MERCHANT_ID)->toArray(),
                'total'        => $total,
            ]);

        foreach ($balances as $balance)
        {
            try
            {
                $id = $balance->generateUniqueIdFromTimestamp($balance->getCreatedAt());

                $balance->setAttribute(Entity::ID, $id);

                $balance->saveOrFail();

                $success++;
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::MERCHANT_BALANCE_BACKFILL_ERROR,
                    [
                        'id' => $balance->getMerchantId(),
                    ]);

                $failed++;

                $failedIds[] = $balance->getMerchantId();
            }
        }

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'failed_ids' => $failedIds,
        ];
    }

    /**
     * Checks if sbi emi is enabled on checkout for a merchant.
     *
     * Fetches the terminal for a merchant with gateway:`emi_sbi`
     * If null is returned
     *      There is no SBI MID stored for this merchant.
     *      This merchant has not been onboarded yet. return false
     *
     * Else if there's a emi_sbi terminal which is enabled. return true.
     *
     * @return bool
     */
    public function isSbiEmiEnabled()
    {
        $merchantId = $this->merchant->getId();

        $terminal = $this->repo->terminal->getByMerchantIdAndGateway($merchantId, Payment\Gateway::EMI_SBI);

        if ((empty($terminal) === false) and
            ($terminal->isEnabled() === true))
        {
            return true;
        }
        return false;
    }

    public function isAllowedForBusinessBanking(string $merchantId)
    {
        $org = $this->repo->merchant->getMerchantOrg($merchantId);

        $businessType = $this->repo->merchant_detail->getByMerchantId($merchantId)->getBusinessType();

        return (in_array($org, \RZP\Models\Admin\Org\Constants:: ALLOW_TO_BUSINESS_BANKING, true) and
            (Merchant\Detail\BusinessType::isUnregisteredBusiness($businessType) === false));
    }

    public function switchProductMerchant($product = null, $afterEmailVerified = false)
    {
        // TODO: remove this once Yesbank issue is resolved
        $merchant = $this->auth->getMerchant();

        $isProductBanking = (($product === Product::BANKING) or
                             ($this->auth->isProductBanking()));

        $isMerchantBankingEnabled = $merchant->isBusinessBankingEnabled();

        $merchantId = $merchant->getMerchantId();

        if (($isMerchantBankingEnabled === false) and
            ($isProductBanking === true))
        {
            // Commenting this call since YesBank Moratorium is done.
            // $this->isXRegistrationBlocked(false, true);

            if ($this->isAllowedForBusinessBanking($merchantId) === false)
            {
                return;
            }
        }

        $wasBankingEnabledNow = false;
        $wasSwitchToPG = false;

        $currentAttempt = 1;

        while ($currentAttempt <= self::MAX_TRANSACTION_RETRY)
        {
            $isolationLevelIssue = false;

            $this->repo->transactionOnLiveAndTest(function() use
            ($product, &$wasBankingEnabledNow, &$wasSwitchToPG, $merchant, &$afterEmailVerified, &$isolationLevelIssue, &$currentAttempt)
            {
                try
                {
                    $this->addProductSwitchRole($product);
                }
                catch (\Illuminate\Database\QueryException $ex)
                {
                    //The INSERT query failed due to a unique constraint violation.
                    if ($ex->errorInfo[1] == 1062 &&
                        $currentAttempt < self::MAX_TRANSACTION_RETRY)
                    {
                        // Adding this catch block because in case of product-switch, even though read is happening from
                        // master, due to default isolation level of REPEATABLE READ in mysql, we get the stale value.
                        $this->trace->traceException(
                            $ex,
                            Trace::ERROR,
                            TraceCode::ERROR_DUE_TO_ISOLATION_LEVEL);

                        $isolationLevelIssue = true;

                        // This will return from the transaction, but the loop while loop continues
                        return;
                    }

                    throw $ex;
                }

                $wasSwitchToPG = $this->auth->getRequestOriginProduct() === Product::PRIMARY;

                $merchant = $this->auth->getMerchant();

                $currentlyEnabled = $merchant->isBusinessBankingEnabled();

                $wasBankingEnabledNow = $this->enableBusinessBankingIfApplicable($merchant);

                $this->repo->saveOrFail($merchant);

                if (($wasBankingEnabledNow === true) or
                    ($afterEmailVerified === true))
                {
                    Tracer::inSpan(['name' => 'product_switch.captureEventOfInterestOfPrimaryMerchantInBanking'], function() use($merchant) {
                        $this->captureEventOfInterestOfPrimaryMerchantInBanking($merchant);
                    });

                    Tracer::inSpan(['name' => 'product_switch.addNewBankingErrorFeature'], function() use($merchant) {
                        $this->addNewBankingErrorFeature($merchant);
                    });
                }

                // Commenting this call since YesBank Moratorium is done.

                // $isXRegistrationBlocked = $this->isXRegistrationBlocked($currentlyEnabled, false);

                // if ($isXRegistrationBlocked === true)
                // {
                //     return;
                // }

                $this->activateBusinessBankingAndApplyPromotion($merchant, $wasBankingEnabledNow, $product);
            });

            if ($currentAttempt > 1)
            {
                $this->trace->info(
                    TraceCode::SUCCESSFUL_READ_ON_TRANSACTION_RETRY,
                    [
                        'merchant_id'    => $merchant->getId(),
                        'currentAttempt' => $currentAttempt
                    ]);
            }

            // In case product-switch works without isolation level issue in the first go,
            // no need to start the transaction again, hence we break.
            if ($isolationLevelIssue === false)
            {
                break;
            }

            $currentAttempt++;
        }
        // At this point the product switch has happened, and if there were exceptions it
        // wouldn't have come till here


        $this->trace->info(TraceCode::PRODUCT_SWITCH, [
            'merchant' => $merchant,
            'wasSwitchToPg' => $wasSwitchToPG,
            'wasBankingEnabledNow' => $wasBankingEnabledNow
        ]);

        if ($wasBankingEnabledNow or
            $wasSwitchToPG or
            $afterEmailVerified)
        {
            Tracer::inSpan(['name' => 'product_switch.postProductSwitchActions'] , function() use($merchant, $wasSwitchToPG, $wasBankingEnabledNow) {
                $this->postProductSwitchActions($merchant, $wasBankingEnabledNow);
            });
        }

    }

    public function activateBusinessBankingAndApplyPromotion($merchant, $wasBankingEnabledNow, $product)
    {
        Tracer::inSpan(['name' => 'product_switch.activateBusinessBankingIfApplicable'] , function() use($merchant, $wasBankingEnabledNow) {
            (new Activate)->activateBusinessBankingIfApplicable($merchant);
        });


        // creating a user mapping for a merchant on X is equivalent to him signing up on X
        // platform, so we will check if sign up has any promotion running and will assign rewards
        Tracer::inSpan(['name' => 'product_switch.applyPromotion'], function() use($merchant, $product) {
            (new Promotion\Core)->applyPromotion($merchant, $product, Promotion\Event\Constants::SIGN_UP);
        });
    }

    /**
     * @param Entity $merchant
     * @param array  $utmParams
     */
    protected function storeIfVisitedCaStaticPage(Entity $merchant, array $utmParams): void
    {
        $attributeCore = new Attribute\Core;

        $product = Product::BANKING;
        $group   = Attribute\Group::X_SIGNUP;
        $type    = Attribute\Type::CA_PAGE_VISITED;

        try
        {
            $caPageVisitedAttr = $attributeCore->fetch($merchant, $product, $group, $type);
        }
        catch (\Throwable $e)
        {
            $caPageVisitedAttr = null;
        }

        // don't want to rewrite in case product switch happens again.
        if ($caPageVisitedAttr === null)
        {
            $caPageVisited = ((isset($utmParams['first_page']) and ($utmParams['first_page'] === User\Constants::CA_STATIC_PAGE))
                              or (isset($utmParams['final_page']) and ($utmParams['final_page'] === User\Constants::CA_STATIC_PAGE))
                              or (isset($utmParams['website']) and ($utmParams['website'] === User\Constants::CA_STATIC_PAGE)));
            $attributeCore->create(
                [
                    Attribute\Entity::PRODUCT => $product,
                    Attribute\Entity::GROUP   => $group,
                    Attribute\Entity::TYPE    => $type,
                    Attribute\Entity::VALUE   => strval((int) ($caPageVisited)) // saving as 1/0
                ],
                $merchant
            );
        }
    }


    private function storeCampaignType(Entity $merchant, array $utmParams)
    {
        $attributeCore = new Attribute\Core;

        $product = Product::BANKING;
        $group   = Attribute\Group::X_SIGNUP;
        $type    = Attribute\Type::CAMPAIGN_TYPE;

        try
        {
            $campaignTypeAttr = $attributeCore->fetch($merchant, $product, $group, $type);
        }
        catch (\Throwable $e)
        {
            $campaignTypeAttr = null;
        }

        // don't want to rewrite in case product switch happens again.
        if ($campaignTypeAttr === null)
        {
            $campaignType = $this->findCampaignType($utmParams);

            if ($campaignType === null)
            {
                return;
            }

            $attributeCore->create(
                [
                    Attribute\Entity::PRODUCT => $product,
                    Attribute\Entity::GROUP   => $group,
                    Attribute\Entity::TYPE    => $type,
                    Attribute\Entity::VALUE   => $campaignType
                ],
                $merchant
            );
        }
    }

    public function storeMerchantCaOnboardingFlow(Entity $merchant, string $caOnboardingFlow)
    {
        $attributeCore = new Attribute\Core;

        $product = Product::BANKING;
        $group   = Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS;
        $type    = Attribute\Type::CA_ONBOARDING_FLOW;

        try
        {
            $campaignTypeAttr = $attributeCore->fetch($merchant, $product, $group, $type);
        }
        catch (\Throwable $e)
        {
            $campaignTypeAttr = null;
        }

        // don't want to rewrite in case product switch happens again.
        if ($campaignTypeAttr === null)
        {
            $attributeCore->create(
                [
                    Attribute\Entity::PRODUCT => $product,
                    Attribute\Entity::GROUP   => $group,
                    Attribute\Entity::TYPE    => $type,
                    Attribute\Entity::VALUE   => $caOnboardingFlow
                ],
                $merchant
            );

            /** @var  $salesforceClient SalesForceClient*/
            $salesforceClient = $this->app->salesforce;

            $salesforceClient->sendCaOnboardingToSalesforce([
                'merchant_id' => $merchant->getId(),
                'ca_onboarding_flow' => $caOnboardingFlow
            ]);
        }
    }

    protected function getLastRunAtKeyForSettlementsEventsCron(): string
    {
        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $lastRunAtKey = self::MERCHANT_SETTLEMENTS_EVENTS_CRON_LAST_RUN_AT_KEY . '_' . $mode;

        return $lastRunAtKey;
    }

    private function isXRegistrationBlocked(bool $currentlyEnabled = false, bool $trace = false) :bool
    {
        $config = (new MainAdmin\Service)->getConfigKey(['key' => MainAdmin\ConfigKey::BLOCK_X_REGISTRATION]) ?? false;


        if (boolval($config) === true)
        {
            if ($trace === true)
            {
                $this->trace->info(
                    TraceCode::BLOCKING_RX_PRODUCT_SWITCH_TEMPORARILY,
                    [
                        'product'           => Product::BANKING,
                        'business_banking'  => false,
                        'config'            => $config
                    ]);
            }
            return $currentlyEnabled;
        }

        return false;
    }

    public function postProductSwitchActions(Merchant\Entity $merchant, bool  $wasBankingEnabledNow = false){

        // Keeping this for only PG -> X for now, since the current event dashboard are built with that assumption
        // need to change this once the expectation is clear.

        if ($wasBankingEnabledNow) {
            //1. Capture this Product Switch Event in the Datalake
            /** @var $diagClient DiagClient */
            $diagClient = $this->app['diag'];
            $utmParams = [];
            (new User\Service)->addUtmParameters($utmParams);

            $diagClient->trackOnboardingEvent(EventCode::PRODUCT_SWITCH, $merchant, null, $utmParams);

            // 2. store signup source information
            $this->storeRelevantPreSignUpSourceInfoForBanking($utmParams, $merchant);

            $xChannelDefinitionService = new XChannelDefinition\Service;
            $xChannelDefinitionService->addChannelDetailsInPreSignupSFPayload($merchant, $utmParams);

            $this->app->salesforce->sendProductSwitchDetails($utmParams, $merchant);
        }

        // for users signed up with mobile number and have not added an email,
        // we cannot trigger a hubspot event since hubspot works with email as its primary source.
        // Ref: https://razorpay.slack.com/archives/C021KESTRLH/p1638519054298100
        if(empty($merchant->getEmail()) === false)
        {
            //3. Send this Event to Hubspot
            /** @var HubspotClient $hubspotClient */
            $hubspotClient = $this->app->hubspot;
            $hubspotClient->trackHubspotEvent($merchant->getEmail(), [
                'product_switch' => true
            ]);

            if(($this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING) or $this->mode === 'test')
            {
                $this->app['x-segment']->sendEventToSegment(SegmentEvent::X_SIGNUP_SUCCESS, $merchant);
            }
        }
    }

    public function storeRelevantPreSignUpSourceInfoForBanking(array $utmParams, Merchant\Entity $merchant)
    {
        $this->trace->info(TraceCode::UTM_PARAMS, [
            'merchant'   => $merchant->getId(),
            'utm_params' => $utmParams
        ]);

        // if the merchant visited the CA static page (first or last) (razorpay.com/x/current-accounts/)
        // we want to show the new CA self-serve flow on dashboard. Hence, saving this information
        $this->storeIfVisitedCaStaticPage($merchant, $utmParams);

        $this->storeCampaignType($merchant, $utmParams);

        $xChannelDefinitionService = new XChannelDefinition\Service;
        $xChannelDefinitionService->storeChannelDetails($merchant, $utmParams);
    }

    public function migrationBankingVAs(array $input)
    {
        $merchantIds = $input['merchant_ids'] ?? [];
        $mode        = $input['mode'] ?? Mode::LIVE;

        $processedCount = 0;

        $illegal = [];

        $failed = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                /** @var Merchant\Entity $merchant */
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->trace->info(
                    TraceCode::MERCHANT_RAZORPAYX_VA_MIGRATION,
                    [
                        'merchant_id'        => $merchantId,
                        'category'           => $merchant->getCategory(),
                        'category2'          => $merchant->getCategory2(),
                        'billing_label'      => $merchant->getBillingLabel(),
                    ]);

                if ($merchant->isBusinessBankingEnabled() === false)
                {
                    $illegal[] = $merchantId;

                    continue;
                }

                (new Activate)->createBankingEntitiesForMode($merchant, $mode);

                $processedCount++;
            }
            catch (\Throwable $e)
            {
                $failed[] = $merchantId;

                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::MERCHANT_RAZORPAYX_VA_MIGRATION_FAILED);
            }
        }

        return [
            'total'     => count($merchantIds),
            'processed' => $processedCount,
            'illegal'   => $illegal,
            'failed'    => $failed
        ];
    }

    /**
     * Checks if a merchant exists with the input email
     * and if it is marked as a partner
     *
     * @param  array $input
     * @return array
     */
    public function fetchMerchantPartnerStatus(array $input)
    {
        $partnerExists = $merchantExists = false;

        (new Validator)->validateInput('merchant_partner_status', $input);

        $this->auth->setModeAndDbConnection(Mode::LIVE);

        /** @var Base\PublicCollection $merchants */
        $merchants = $this->repo->merchant->fetchByEmailAndOrgId($input[Entity::EMAIL]);

        if ($merchants->count() > 0)
        {
            $merchantExists = true;

            //
            // First entry should be partner if there is a partner
            // as we order by created_at asc.
            //

            /** @var Entity $first */
            $first = $merchants->first();

            if ($first->getPartnerType() !== null)
            {
                $partnerExists = true;
            }
        }

        $result = [CE::MERCHANT => $merchantExists, Constants::PARTNER => $partnerExists];

        $this->trace->info(
            TraceCode::MERCHANT_PARTNER_STATUS_RESPONSE,
            [
                'input'  => $input,
                'result' => $result
            ]
        );

        return $result;
    }

    public function getMerchantActivationEligibility($merchantId)
    {
        $response = [];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $detailCore = new MerchantDetailCore();

        $response['is_eligible_for_activation'] = !($detailCore->blockMerchantActivations($merchant));

        return $response;
    }

    protected function captureEventOfInterestOfPrimaryMerchantInBanking($merchant)
    {
        // Merchant has switched from primary product to banking product for the first time,
        // so, sending details to salesforce.

        // Putting in a try catch block so that any error here does not disrupt
        // the main flow.
        try
        {
            /** @var  $salesforceClient SalesForceClient */
            $salesforceClient = $this->app->salesforce;

            $salesforceClient->captureInterestOfPrimaryMerchantInBanking($merchant);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SALESFORCE_FAILED_TO_DISPATCH_JOB);
        }
    }

    /**
     * @param Entity $merchant
     * @param bool   $partnerFlow - is true when merchant was onboarded via partner
     *
     * @return bool
     */
    protected function enableBusinessBankingIfApplicable(Entity $merchant, bool $partnerFlow = false): bool
    {
        $isBanking = $this->auth->isProductBanking();

        if (($isBanking === true or $partnerFlow === true) and $merchant->isBusinessBankingEnabled() === false)
        {
            $this->trace->info(
                TraceCode::MERCHANT_EDIT,
                [
                    'business_banking' => $isBanking,
                ]
            );

            $merchant->setBusinessBanking(true);

            return true;
        }

        return false;
    }

    protected function addNewBankingErrorFeature(Entity $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::NEW_BANKING_ERROR) === true)
        {
            return;
        }

        $featureParams = [
            Feature\Entity::ENTITY_ID   => $merchant->getId(),
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAMES       => [Feature\Constants::NEW_BANKING_ERROR],
            Feature\Entity::SHOULD_SYNC => true
        ];

        (new Feature\Service)->addFeatures($featureParams);
    }

    /**
     * Used when partner sends a reminder mail to sub merchant for creation of password.
     * Mail is sent only to sub merchant and partner does not get any mail.
     *
     * @param string $id submerchant id.
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function sendSubmerchantPasswordResetLink(string $id, array $input)
    {
        $merchant = $this->auth->getMerchant();

        (new Validator)->validateIsPartner($merchant);

        (new Validator)->validateInput('send_submerchant_product', $input);

        /** @var Entity $subMerchant */
        $subMerchant = $this->repo->merchant->findOrFailPublic($id);

        $isMapped = $this->core()->isMerchantMappedToNonPurePlatformPartner($subMerchant->getId(), $merchant->getId());

        if ($isMapped === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER);
        }

        if (strtolower($subMerchant->getEmail()) === strtolower($merchant->getEmail()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
                Merchant\Entity::EMAIL,
                $merchant->getEmail()
            );
        }

        $subMerchantUser = $this->repo->user->getUserFromEmail($subMerchant->getEmail());

        $product = $this->auth->getRequestOriginProduct();

        $mapping = null;

        if(empty($input['product']) === false)
        {
            $product = $input['product'];

            unset($input['product']);
        }

        // For capital submerchants, we create merchants users with 'banking' as product.
        $merchantUserProduct = ($product === Product::CAPITAL) ? Product::BANKING : $product;

        if (empty($subMerchantUser) === false)
        {
            $mapping = $this->repo->merchant->getMerchantUserMapping($subMerchant->getId(),
                                                                     $subMerchantUser->getId(), null, $merchantUserProduct);
        }

        if ((empty($subMerchantUser) === true) or (empty($mapping) === true))
        {
            [$subMerchantUser, $createdNew] = $this->createAdditionalUserOrFetchIfApplicable($subMerchant,
                                                                                                 $merchant, $merchantUserProduct);
        }

        //
        // If user already exists, we do not send mail to the user and createNewUser (4th param in following function)
        // is false in that case. Here, for resending the mail to the user, we are passing createdNewUser as true always
        // so that user always get a mail.
        //
        $this->sendSubMerchantCreationMail($subMerchant, $merchant, $product, $subMerchantUser, true, true);

        return ['success' => true];
    }

    public function onboardMerchant(string $id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->trace->info(
            TraceCode::INITIATE_TERMINAL_ONBOARDING_REQUEST_ADMIN_ROUTE,
            [
                'merchant_id'    => $id,
                'input'          => $input,
            ]);

        (new Validator)->validateInput('onboard_merchant_input', $input);

        (new Validator)->validateInput('onboard_merchant_input_' . $input['gateway'], $input); //validate input based on gateway

        (new Validator)->validateOrgForOnboarding($input);

        if ($input['gateway'] === Payment\Gateway::HITACHI)
        {
            return (new TerminalService)->onboardMerchant($merchant, $input, false)
            ->toArrayAdmin();
        }

        $currency = isset($input['currency_code']) ? [$input['currency_code']] : [];

        $identifiers = isset($input['identifiers']) ? $input['identifiers'] : null;

        $response = $this->app['terminals_service']->initiateOnboarding($id, $input['gateway'], $identifiers, null, $currency, $input);

        return $response;
    }

    public function applyRestrictedSettings(array $input): array
    {
        (new Validator)->validateInput('restrict_settings_merchant', $input);

        $merchantId = $input[Entity::MERCHANT_ID];

        $action = $input[Entity::ACTION];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        return $this->core()->applyRestrictedSettings($merchant, $action);
    }

    public function removeSuspendedMerchantsFromMailingList(array $input)
    {
        (new Validator)->validateInput('suspended_merchant_remove', $input);

        $merchants = $this->repo->merchant
                                ->fetchAllSuspendedMerchants($input);

        $i = 0;

        foreach ($merchants as $merchant)
        {
            $this->core()->removeMerchantEmailToMailingList($merchant, [], [], $i);

            $i++;
        }
    }

    /**
     * @param Entity $merchant
     * @param Plan $plan
     * @throws Exception\BadRequestValidationFailureException
     *
     * Ensures that all pricing rules in plan have the same feeBearer value as the merchant
     * the plan is being assigned to.
     *
     * This is not applicable in case of dynamic fee bearer.
     */
    public function validatePricingPlanForFeeBearer(Merchant\Entity $merchant, Plan $plan)
    {
        if ($merchant->isFeeBearerDynamic() === true)
        {
            return;
        }

        $merchantFeeBearer = $merchant->getFeeBearer();

        foreach ($plan as $pricing)
        {
            $pricingFeeBearer = $pricing->getFeeBearer();

            if ($pricingFeeBearer !== $merchantFeeBearer)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_PRICING_RULE_FEE_BEARER_MISMATCH,
                    'fee_bearer',
                    'The merchant is ' . $merchantFeeBearer . ' fee bearer. Cannot assign ' . $pricingFeeBearer . ' fee bearer pricing rule to merchant'
                );
            }
        }
    }

    /**
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fetchReferral(): array
    {
        $merchant = $this->auth->getMerchant();

        $referrals = Tracer::inspan(['name' => HyperTrace::FETCH_MERCHANT_REFERRAL_CORE], function () use ($merchant) {

            return (new Referral\Core)->fetchMerchantReferral($merchant);
        });

        return $this->formatReferralResponse($referrals);
    }

    /**
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function createReferral()
    {
        $merchant = $this->auth->getMerchant();

        $partner = $this->fetchPartner();

        (new Referral\Validator)->validateForReferral($partner);

        $referrals = Tracer::inspan(['name' => HyperTrace::CREATE_OR_FETCH_REFERRAL_CORE], function () use($merchant) {

            return (new Referral\Core)->createOrFetch($merchant);
        });

        $result = $referrals[Product::PRIMARY];

        $result['referrals'] = $referrals;

        return $result;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fetchPartnerReferralViaBatch(array $input): array
    {
        $merchantId = $input[Entity::MERCHANT_ID];
        $product = $input[Entity::PRODUCT];

        $product = empty($product) ? Product::PRIMARY : $product;
        $product = strtolower($product);

        $this->trace->info(TraceCode::BATCH_PARTNER_REFERRAL_FETCH_REQUEST, $input);

        try
        {
            Entity::verifyUniqueId($merchantId, true);

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $referral = (new Referral\Core())->fetchPartnerReferral($merchant, $product);

            $response = [
                BatchHeader::MERCHANT_ID        => $merchantId,
                BatchHeader::REFERRAL_ID        => $referral[BatchHeader::ID],
                BatchHeader::REF_CODE           => $referral[BatchHeader::REF_CODE],
                BatchHeader::URL                => $referral[BatchHeader::URL],
                BatchHeader::REFERRAL_PRODUCT   => $referral[BatchHeader::REFERRAL_PRODUCT],
                BatchHeader::STATUS             => 'Success',
            ];

            $this->trace->info(TraceCode::BATCH_PARTNER_REFERRAL_FETCH_RESPONSE, $response);
        }
        catch(\Exception $e)
        {
            $error = $e->getError();

            $response = [
                BatchHeader::MERCHANT_ID                 => $merchantId,
                BatchHeader::REFERRAL_PRODUCT            => $product,
                BatchHeader::STATUS                      => 'Failure',
                BatchHeader::ERROR_CODE                  => $error->getPublicErrorCode(),
                BatchHeader::ERROR_DESCRIPTION           => $error->getDescription(),
            ];

            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $response);
        }
        finally
        {
            return $response;
        }
    }

    /**
     * @param array $record
     */
    public function actionPerform(array $record)
    {
        $attribute = [];
        $settings  = [];

        $this->segregateInputFieldsAndSettings($record, $attribute, $settings);

        (new Validator)->validateInput('entity_batch_action', $settings);

        $core = CE::getEntityCoreClass($settings[Constants::ENTITY]);

        $batch_action = $settings[Constants::BATCH_ACTION];

        $function = camel_case($batch_action);

        $core->$function($settings[Entity::ID], $attribute);
    }


    /**
     * @param array $input
     *
     * @return Base\PublicCollection
     */
    public function merchantsBulkUpdate(array $input)
    {
        $response = new Base\PublicCollection();

        foreach ($input as $record)
        {
            try
            {
                $this->actionPerform($record);

                $response->push($record);
            }
            catch (Exception\BaseException $exception)
            {
                $this->setErrorAttributesToResponse($record, $exception, $response);
            }
        }

        return $response->toArrayWithItems();
    }

    /**
     * @param array                   $record
     * @param Exception\BaseException $exception
     * @param Base\PublicCollection   $response
     */
    public function setErrorAttributesToResponse(array $record, Exception\BaseException $exception, Base\PublicCollection $response)
    {
        $this->trace->traceException($exception,
                                     Trace::INFO,
                                     TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST);
        $exceptionData = [
            'error'                 => [
                Error::DESCRIPTION       => $exception->getError()->getDescription(),
                Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
            ],
            Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
        ];

        $response->push(array_merge($record, $exceptionData));
    }

    /**
     * @param array $record
     * @param array $attribute
     * @param array $settings
     */
    public function segregateInputFieldsAndSettings(array $record, array & $attribute, array & $settings)
    {
        $settings = array_only($record, Constants::$EntityBatchActionSettingParams);

        $attribute = array_diff($record, $settings);
    }

    /**
     * @return array
     */
    public function getBatchActionEntities(): array
    {
        $batchAction = (new Core())->getBatchActionEntities();

        return $batchAction;
    }

    /**
     * @return array
     */
    public function getBatchActions(): array
    {
        $batchAction = (new Core())->getBatchActions();

        return $batchAction;
    }

    public function requestInternationalProduct(array $input, bool $draft = false): array
    {
        $validator = (new Validator);

        if ($draft === false)
        {
            $validator->validateInput('request_international_product', $input);
        }

        $validator->validateMerchantForProductInternational($this->merchant);

        if ($draft === true)
        {
            return [];
        }

        $merchant = $this->core()->requestInternationalProduct($input);

        return $merchant->toArrayPublic();
    }

    /**
     * getGlobalMerchantConfigs: used to return global configs to the settlement service which are not stored in the
     * settlement service e.g. ActivationStatus, PartnerSettlementConfig, ParentDetails(for Aggregate settlement)
     *
     * @param string $mid
     * @return array
     */
    public function getGlobalMerchantConfigs(string $mid)
    {
        $merchant = $this->repo->merchant->findOrFail($mid);

        $merchantSettleToPartner = $this->core()->getPartnerBankAccountIdsForSubmerchants([$mid]);

        $email = "";

        if ($merchant->isLinkedAccount() === true)
        {
            $email = $merchant->parent->getEmail();
        }
        else
        {
            $email = $merchant->getEmail();
        }

        $partnerCommissionConfig = Tracer::inspan(['name' => HyperTrace::GET_PARTNER_COMMISSION_CONFIG], function() use ($merchant) {

            return $this->getPartnerCommissionConfig($merchant);
        });

        // RSR-2002; global_hold_status & global_hold_reason will be provided to new settlement service as Global config.
        return [
            "active"                           => $merchant->isActivated(),
            "parent"                           => $this->settlementToPartner($mid),
            "partner_bank_account"             => isset($merchantSettleToPartner[$mid]) ? $merchantSettleToPartner[$mid] : null,
            "pan_details"                      => $this->getMerchantPANDetails($merchant),
            "purpose_code"                     => $merchant->getPurposeCode(),
            "iec_code"                         => $merchant->getIecCode(),
            "business_address"                 => ($merchant->merchantDetail !== null) ? $merchant->merchantDetail->getBusinessRegisteredAddressAsText(', ') : null,
            "global_hold_status"               => $merchant->getHoldFunds(),
            "global_hold_reason"               => ($merchant->getHoldFunds() === false) ? '' : ($merchant->getHoldFundsReason() ?? 'merchant funds are on hold'),
            "settle_to_org"                    => $this->getMerchantOrgSettleValue($merchant),
            "org_id"                           => $merchant->getOrgId(),
            "merchant_email"                   => $email,
            "partner_commissions_config"       => $partnerCommissionConfig,
            "pg_ledger_reverse_shadow_enabled" => $this->isMerchantOnPGReverseShadow($merchant),
            "country_code"                     => $merchant->getCountry()
        ];
    }

    private function getPartnerCommissionConfig($merchant)
    {
        $globalOnHold           = $merchant->getHoldFunds();
        $globalOnHoldReason     = ($merchant->getHoldFunds() === false) ? '' : ($merchant->getHoldFundsReason() ?? 'merchant funds are on hold');
        $partnerType            = $merchant->getPartnerType();

        $partnerCommissionConfig = [
            'hold_status'          => $globalOnHold,
            'hold_reason'          => $globalOnHoldReason,
            'enabled'              => false,
        ];

        /*
        Do not add custom configs for reseller partners for malaysia
        We collect and store custom configurations if we think partner business is not trustable
        In case of Malaysia, as we do manual onboard and offline verification so these are not required
        */
        if ($merchant->getCountry() === 'MY')
        {
            return $partnerCommissionConfig;
        }

        $properties = [
            'id'                   => $merchant->getId(),
            'experiment_id'        => $this->app['config']->get('app.partner_independent_kyc_exp_id'),
        ];

        $isExpEnable = (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');

        if($isExpEnable === false )
        {
            return $partnerCommissionConfig;
        }

        if ($partnerType === Constants::RESELLER)
        {
            $activationStatus       = ($merchant->merchantDetail !== null) ? $merchant->merchantDetail->getActivationStatus() : null;

            if (empty($activationStatus) === true)
            {
                $partnerActivation = $merchant->partnerActivation;

                $partnerCommissionConfig ['hold_status'] = $partnerActivation->getFundsOnHold();
                $partnerCommissionConfig ['hold_reason'] = ($partnerActivation->getFundsOnHold() === false) ? '' : 'Partner funds are on hold';
                $partnerCommissionConfig ['enabled']     = true;
            }
        }

        return $partnerCommissionConfig;
    }

    private function getMerchantPANDetails($merchant) {
        $PANDetails = null;
        if($merchant->merchantDetail !== null) {
            $PANDetails = $merchant->merchantDetail->getPan();
            // we need pan_details for OPGSP settlements preferably company pan
            // in case there is a merchant who doesn't have company pan we check if the business type is PROPRIETORSHIP
            // then we send promoter_pan details
            // else null
            if(empty($PANDetails) === true) {
                if($merchant->merchantDetail->getBusinessType() === BusinessType::PROPRIETORSHIP ) {
                    $PANDetails = $merchant->merchantDetail->getPromoterPan();
                }
            }
        }
        return $PANDetails;
    }

    private function getMerchantOrgSettleValue($merchant)
    {
        return (($merchant->isFeatureEnabled(Feature\Constants::CANCEL_SETTLE_TO_BANK) === false) and
            ($merchant->org->isFeatureEnabled(Feature\Constants::ORG_SETTLE_TO_BANK) === true) and ($merchant->isFeatureEnabled(Feature\Constants::OLD_CUSTOM_SETTL_FLOW) === false));
    }

    private function isMerchantOnPGReverseShadow($merchant)
    {
        return $merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true;
    }

    public function getPersonalisedMethods($input)
    {
        $merchant = $this->merchant;

        (new Validator)->setStrictFalse()->validateInput(Validator::PERSONALISATION, $input);

        $preferredMethods = [];

        $data = (new Checkout)->getPersonalisedMethods($merchant, $this->mode, $input);

        if (isset($data['preferred_methods']) === true) {
            $preferredMethods['preferred_methods'] = $data['preferred_methods'];
        }

        $isRTBLive = (new TrustedBadge\Core())->isTrustedBadgeLiveForMerchant($merchant->getId());

        if($isRTBLive === true)
        {
            $contact = $input['contact'] ?? '';

            $preferredMethods['rtb_experiment'] = (new TrustedBadge\Core())->getRTBExperimentDetails($merchant->getId(), $contact);
        }

        return $preferredMethods;
    }

    /**
     * Fix merchant data with leading and trailing spaces
     *
     * @param PaginationEntity $paginationEntity
     * @return array
     */
    public function fixData(PaginationEntity $paginationEntity): array
    {
        (new Payout\Core)->trimPayoutPurpose($paginationEntity);

        (new FundAccount\Core)->trimBeneficiaryName($paginationEntity);

        (new FundAccount\Core)->trimAccountNumber($paginationEntity);

        (new Contact\Core)->trimContactType($paginationEntity);

        (new Contact\Core)->trimContactName($paginationEntity);

        return [
            'status'        => 'updated'
        ];
    }

    public function setDefaultLateAuthConfigForMerchant($merchant)
    {
        $defaultConfig = array(
            'capture' => "automatic",
            'capture_options' => [
                'automatic_expiry_period' => 7200,
                'refund_speed' => 'normal'
            ]
        );

        $configInput = array(
            'config' => $defaultConfig,
            'is_default' => true,
            'name' => 'late_auth_' . $merchant->getId(),
            'type' => Payment\Config\Type::LATE_AUTH,
        );

        //
        // Reset the connection to the requests original mode
        //
        $originalMode = $this->app['basicauth']->getMode();

        $this->repo->transactionOnLiveAndTest(function () use($configInput, $merchant)
        {
            try
            {
                $this->createConfigWithMode(Mode::LIVE, $configInput, $merchant);
                $this->createConfigWithMode(Mode::TEST, $configInput, $merchant);
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException($t);

                throw $t;
            }
        });

        $this->app['basicauth']->setModeAndDbConnection($originalMode);
    }

    private function createConfigWithMode(string $mode, $configInput, $merchant)
    {
        $config = (new Payment\Config\Entity())->build($configInput, 'create');

        $config['merchant_id'] = $merchant->getId();;

        (new PaymentConfig\Core())->withMerchant($merchant)->trackLateAuthConfigEvent(EventCode::PAYMENT_CONFIG_CREATION_INITIATED, $configInput, 'default');

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $config->setConnection($mode);

        $config->refresh();

        $this->repo->config->save($config);
    }

    /**
     * Bootstrap stork's mid<>oauth-app-ids cache using api's access map table as source.
     *
     * @param  array $input Holds opts for source and target.
     * @return array
     */
    public function bootstrapAccessMapsCacheOfStork(array $input): array
    {
        $this->trace->info(TraceCode::BOOTSTRAP_ACCESS_MAPS_CACHE_REQUEST, $input);

        (new JitValidator)->rules(self::BOOTSTRAP_ACCESS_MAPS_CACHE_REQUEST_RULES)
            ->caller($this)->input($input)->validate();

        $source  = new AccessMap\MigrateSource;
        $target  = new AccessMap\MigrateStorkTarget;
        $migrate = new Migrate($source, $target);

        $sourceOpts = $input['source'] ?? [];
        $targetOpts = $input['target'] ?? [];

        return $migrate->migrateAsync($sourceOpts, $targetOpts, false);
    }

    public function migrateImpersonationGrants(array $input): array
    {
        $this->trace->info(TraceCode::IMPERSONATION_MIGRATE_REQUEST, $input);

        (new JitValidator)->rules(self::IMPERSONATION_ACCESS_MAPS_REQUEST_RULES)
            ->caller($this)->input($input)->validate();

        $source  = new AccessMap\MigrateImpersonationSource;
        $target  = new AccessMap\MigrateKongTarget;
        $migrate = new Migrate($source, $target);

        $sourceOpts = $input['source'] ?? [];
        $targetOpts = $input['target'] ?? [];

        return $migrate->migrateAsync($sourceOpts, $targetOpts, false);
    }

    public function partnerAccessMapBulkUpsert(array $input)
    {
        $response = new Base\PublicCollection();

        foreach ($input as $record)
        {
            $attribute = [];
            $settings = [];

            $this->segregateInputFieldsAndSettings($record, $attribute, $settings);

            (new Validator)->validateInput('access_map_batch', $settings);

            $batch_action = camel_case($settings[Constants::BATCH_ACTION]);

            try
            {
                $this->actionOnAccessMap($record);

                $response->push($record);

                $dimension = [
                    'action' => $batch_action,
                ];

                $this->trace->count(Metric::SUBMERCHANT_BATCH_ACTION_SUCCESS_TOTAL,$dimension);
            }
            catch (BaseException $exception)
            {
                $dimension = [
                    'action' => $batch_action,
                ];

                $this->trace->count(Metric::SUBMERCHANT_BATCH_ACTION_FAILURE_TOTAL,$dimension);

                $this->setErrorAttributesToResponse($record, $exception, $response);
            }
        }

        return $response->toArrayWithItems();
    }

    /**
     * @param array $record
     *
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function actionOnAccessMap(array $record)
    {
        $attribute = [];

        $settings = [];

        $this->segregateInputFieldsAndSettings($record, $attribute, $settings);

        (new Validator)->validateInput('access_map_batch', $settings);

        $core = CE::getEntityCoreClass($settings[Constants::ENTITY]);

        $batch_action = $settings[Constants::BATCH_ACTION];

        $function = camel_case($batch_action);

        $partner = $this->repo->merchant->find($attribute[Constants::PARTNER_ID]);

        if (empty($partner) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_ID_DOES_NOT_EXIST);
        }

        $subMerchant = $this->repo->merchant->find($attribute[Constants::MERCHANT_ID]);

        if (empty($subMerchant) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_DOES_NOT_EXIST);
        }

        return $core->$function($partner, $subMerchant);
    }

    protected function getBankAccountChangeViaWorkflowStatus($id): bool
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        if (empty($oldBankAccount) === true) {
            return false;
        }


        $actions = (new Action\Core())->fetchOpenActionOnEntityOperation(
            $oldBankAccount->getId(), $oldBankAccount->getEntity(), Permission::EDIT_MERCHANT_BANK_DETAIL);

        $actions = $actions->toArray();

        // If there are any action in progress
        if (empty($actions) === false) {
            return true;
        }

        return false;
    }

    protected function getBankAccountChangeViaPennyTestingStatus($id)
    {
        $bankAccountCore = (new BankAccount\Core);

        $merchant = $this->repo->merchant->findOrFail($id);

        return $bankAccountCore->isBankAccountUpdatePennyTestingInProgress($merchant);
    }

    public function triggerMerchantBankingAccountsWebhook($id)
    {
        $merchant = $this->repo->merchant->findOrFail($id);

        return $this->core()->triggerMerchantBankingAccountsWebhook($merchant);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(300);
    }

    public function handleSoftLimitBreachOnAutoKYC()
    {

        // since a number of queries are fired ensure enough time is provided to complete them.
        $this->increaseAllowedSystemLimits();

        try
        {
            (new Merchant\Escalations\Core())->pushWebAttributionDetailsToSegmentCron();
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'pushWebAttributionFirstTouchDetailsToSegmentCron',
                'error' => $e->getMessage()
            ]);
        }

        (new Escalations\Core())->handleSoftLimitBreach();
    }

    public function handleHardLimitBreachOnAutoKYC()
    {
        return (new Escalations\Core())->handleHardLimitBreach();
    }

    public function handleAutoKycEscalationCron()
    {
        return (new Escalations\Core())->handleEscalationsCron();
    }

    public function handleCron($cronType, $input)
    {
        return (New Cron\Core())->handleCron($cronType, $input);
    }

    public function updateMerchantStore(array $input)
    {
        return (new Store\Core())->updateMerchantStore($this->merchant->getId(), $input);
    }

    public function fetchMerchantStore(array $input)
    {
        return (new Store\Core())->fetchMerchantStore($this->merchant->getId(), $input);
    }

    public function handleReport(array $input)
    {
        return (new Detail\Report\Core)->sendReport($input);
    }

    public function installAppOnAppStoreForMerchant(array $input)
    {
        //Validate input
        $merchant = $this->app['basicauth']->getMerchant();

        return (new \RZP\Models\AppStore\Core())->installAppOnAppStoreForMerchant($input, $merchant);
    }

    public function getInstalledAppsOnAppStore(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        return (new \RZP\Models\AppStore\Core())->getInstallAppsForMerchant($merchant);
    }

    protected function extractSubmerchantInput(array $input)
    {
        return [
            Entity::NAME                    => $input[BatchHeader::ACCOUNT_NAME],
            Entity::EMAIL                   => $input[BatchHeader::ACCOUNT_EMAIL],
            Entity::DASHBOARD_ACCESS        => (bool) $input[BatchHeader::DASHBOARD_ACCESS],
            Entity::ALLOW_REVERSALS         => (bool) $input[BatchHeader::CUSTOMER_REFUNDS],
        ];
    }

    protected function extractBankAccountDetails(array $input)
    {
        return [
            MerchantDetail::BANK_ACCOUNT_NAME       => $input[BatchHeader::BENEFICIARY_NAME],
            MerchantDetail::BANK_ACCOUNT_NUMBER     => $input[BatchHeader::ACCOUNT_NUMBER],
            MerchantDetail::BANK_BRANCH_IFSC        => $input[BatchHeader::IFSC_CODE],
            MerchantDetail::BUSINESS_NAME           => $input[BatchHeader::BUSINESS_NAME],
            MerchantDetail::BUSINESS_TYPE           => Detail\BusinessType::getIndexFromKey($input[BatchHeader::BUSINESS_TYPE]),
            MerchantDetail::SUBMIT                  => '1',
        ];
    }

    protected function checkDashboardAccessForAllowReversals(bool $dashboardAccess, bool $allowReversals)
    {
        if (($allowReversals === true) and
            ($dashboardAccess === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_DASHBOARD_ACCESS_REQUIRED_TO_ALLOW_REVERSALS,
                null,
                null,
                PublicErrorDescription::BAD_REQUEST_DASHBOARD_ACCESS_REQUIRED_TO_ALLOW_REVERSALS
            );
        }
    }

    public function getRewardsForCheckout()
    {
        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::M2M_REWARDS_AB_TESTING,
            $this->mode
        );

        $rewards = $this->repo->merchant_reward->fetchLiveRewardByMerchantId($this->merchant->getId());

        if(empty($rewards) === true)
        {
            $response[] = ['variant' => false];

            return $response;
        }

        $response[] = ['variant' => true];

        if($variant === 'on' || $variant === 'off')
        {
            if($variant === 'off')
            {
                $response[0]['variant'] = false;
            }

            $rewardKey = array_rand($rewards);

            if(isset($rewardKey) === true)
            {
                $reward = $rewards[$rewardKey];

                $response[0]['reward_id'] = "reward_".$reward['id'];
                $response[0]['logo'] = $reward['logo'];
                $response[0]['name'] = $reward['name'];
                $response[0]['brand_name'] = $reward['brand_name'];
            }

        }

        return $response;
    }

    /**
     *
     * Returns the ids for account whose data was updated in the specified time range
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\ServerErrorException
     */
    public function getUpdatedAccountsForAccountService(array $input): array
    {

        $this->trace->info(TraceCode::ASV_FETCH_UPDATED_ACCOUNT_IDS_REQUEST, ['query' => $input]);

        (new Validator)->validateInput('getUpdatedAccountsForAccountService', $input);

        $fromTimestamp = intval($input['from']);
        $toTimestamp = $fromTimestamp + intval($input['duration']);

        $uniqueUpdatedMerchantIds = $this->getUpdatedMerchantsInTimerange($fromTimestamp, $toTimestamp);

        if(isset($input['limit'])) {
            $uniqueUpdatedMerchantIds = array_slice($uniqueUpdatedMerchantIds , 0, intval($input['limit']));
        }

        $count = count($uniqueUpdatedMerchantIds);

        $response =  [
            "count" => $count ,
            "account_ids" => $uniqueUpdatedMerchantIds
        ];

        $this->trace->info(TraceCode::ASV_FETCH_UPDATED_ACCOUNT_IDS_RESPONSE, ['count' => $count]);
        $this->trace->debug(TraceCode::ASV_FETCH_UPDATED_ACCOUNT_IDS_RESPONSE, $response);

        return $response;
    }

    /**
     * @throws Exception\ServerErrorException
     */
    public function getUpdatedMerchantsInTimerange(int $fromTimestamp, int $toTimestamp): array
    {
        // get Merchants whose email was updated between the query range
        $merchantWithEmailUpdates = $this->repo->merchant_email
            ->getEmailsUpdatedBetween($fromTimestamp, $toTimestamp)
            ->pluck('merchant_id')->toArray();

        // get Merchants whose details were updated between the query range
        $merchantWithDetailsUpdates = $this->repo->merchant_detail
            ->getIfUpdatedBetween($fromTimestamp, $toTimestamp)
            ->pluck('merchant_id')->toArray();

        // get Merchants which were updated between the query range
        $merchantsUpdated = $this->repo->merchant
            ->getIfUpdatedBetween($fromTimestamp, $toTimestamp)
            ->pluck('id')->toArray();

        // get Merchants whose documents were updated between the query range
        $merchantsWithDocumentsUpdates = $this->repo->merchant_document
            ->getIfUpdatedBetween($fromTimestamp, $toTimestamp)
            ->pluck('merchant_id')->toArray();

        // get Merchants whose stakeholders were updated between the query range
        $merchantsWithStakeholdersUpdates = $this->repo->stakeholder
            ->getIfUpdatedBetween($fromTimestamp, $toTimestamp)
            ->pluck('merchant_id')->toArray();

        // get Merchants whose website details were updated between the query range
        $merchantsWithWebsiteUpdates = $this->repo->merchant_website
            ->getIfUpdatedBetween($fromTimestamp, $toTimestamp)
            ->pluck('merchant_id')->toArray();

        // get Merchants whose business details were updated between the query range
        $merchantsWithBusinessUpdates = $this->repo->merchant_business_detail
            ->getIfUpdatedBetween($fromTimestamp, $toTimestamp)
            ->pluck('merchant_id')->toArray();

        $updatedMerchantIds = array_merge(
            $merchantWithDetailsUpdates,
            $merchantWithEmailUpdates,
            $merchantsUpdated,
            $merchantsWithDocumentsUpdates,
            $merchantsWithStakeholdersUpdates,
            $merchantsWithWebsiteUpdates,
            $merchantsWithBusinessUpdates);

        return array_values(array_unique($updatedMerchantIds));
    }

    /**
     * @throws BadRequestException
     */
    public function getMerchantDetailsForAccountService(string $accountId): array
    {
        $this->trace->info(TraceCode::ACS_FETCH_ACCOUNT_DETAILS, ['id' => $accountId]);

        $data = [];

        try {
            $merchant = $this->repo->merchant->findOrFailPublic($accountId);
            $merchantDetails = $this->repo->merchant_detail->findOrFailPublic($accountId);
        } catch (BadRequestException $ex){

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ACS_FETCH_ACCOUNT_DETAILS_EXCEPTION,
                [
                    "id" => $accountId,
                ]
            );

            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ID_DOES_NOT_EXIST, "id");
        }

        $stakeholders = $this->repo->stakeholder->findManyByMerchantIds([$accountId]);
        $documents = $this->repo->merchant_document->findManyByMerchantIds([$accountId]);
        $merchantEmails = $this->repo->merchant_email->getEmailByMerchantId($accountId);

        $merchantWebsite = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($accountId);
        $merchantBusinessDetails = $this->repo->merchant_business_detail->getBusinessDetailsForMerchantId($accountId);

        $stakeholderArray = [];
        foreach($stakeholders as $stakeholder){
            $stakeholderArray[] = $stakeholder->toArrayWithRawValuesForAccountService();
        }

        $isStakeHolderPresent = count($stakeholderArray) > 0;

        $merchantDocs = new Base\PublicCollection;
        $stakeholderDocs = new Base\PublicCollection;
        foreach ($documents as $document)
        {
            if ($document->getEntityType() === EntityConstants::STAKEHOLDER)
            {
                $stakeholderDocs->add($document);
            }
            else
            {
                $docType = $document->getDocumentType();
                $proofType = Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$docType];

                if (Document\Type::PROOF_TYPE_ENTITY_MAPPING[$proofType] === EntityConstants::STAKEHOLDER and
                    ($isStakeHolderPresent === true))
                {
                    $stakeholderDocs->add($document);
                }
                else
                {
                    $merchantDocs->add($document);
                }
            }
        }

        $data['merchant'] = $merchant->toArrayWithRawValuesForAccountService();
        $data['merchant_details'] = $merchantDetails->toArray();
        $data['stakeholders'] = $stakeholderArray;

        foreach ($stakeholders as $index => $stakeholder)
        {
            $address = $this->repo->address->fetchPrimaryAddressOfEntityOfType($stakeholder, Address\Type::RESIDENTIAL);
            if (empty($address) === false)
            {
                $data['stakeholders'][$index]['addresses']['residential'] = $address->toArray();
            }
        }

        $data['stakeholder_documents'] = $stakeholderDocs->toArray();
        $data['merchant_documents'] = $merchantDocs->toArray();
        $data['merchant_emails'] = $merchantEmails->toArray();
        $data['merchant_business_detail'] = $merchantBusinessDetails == null ? null : $merchantBusinessDetails->toArray();
        $data['merchant_website'] = $merchantWebsite == null ? null : $merchantWebsite->toArray();

        return $data;
    }

    /**
     * validate the product name received from input and
     * fetches the product used by a merchant for given merchant ids and product
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function fetchProductUsedByMerchants(array $input): array
    {
        $merchantIds = $input['merchant_ids'];

        $product = $input['product'] ?? null;

        $limit = $input['limit'] ?? null;

        // validate the product name
        (new Validator())->validateMerchantProduct($product);

        $merchantProducts = $this->core()->fetchProductForMerchants($merchantIds, $product, true, $limit);

        return $merchantProducts;
    }

    public function getMerchantSupportOptionFlags() : array
    {
        $isActivated = $this->merchant->isActivated();

        $showCreateTicketPopup = false;

        if ($isActivated === false)
        {
            $showCreateTicketPopup = true;
        }

        $response = [
            'show_chat'                                 =>      $this->canChatOnDashboard($isActivated),
            "show_create_ticket_popup"                  =>      $showCreateTicketPopup,
        ];

        $variant  = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::SHOW_CREATE_TICKET_POPUP,
            $this->app['rzp.mode'] ?? Mode::LIVE);

        if ($variant === 'control')
        {
            return $response;
        }

        $createTicketPopup = $this->getCreateTicketPopupOptions();

        $response = array_merge($response, $createTicketPopup);

        return $response;
    }

    /**
     * @return bool
     * @throws Exception\BadRequestException
     */
    protected function canChatOnDashboard($isActivated) : bool
    {
        if ((new Freshchat\Service)->isChatEnabledNow() === false)
        {
            return false;
        }

        $merchantDetailsCore = new MerchantDetailCore;

        $merchantDetail = $merchantDetailsCore->getMerchantDetails($this->merchant);

        $isUnregistered = $merchantDetail->isUnregisteredBusiness();

        $activationFlow = null;

        if($merchantDetail->canDetermineActivationFlow())
        {
            if($isUnregistered === false)
            {
                // get partners if any
                $partners = (new Merchant\Core())->fetchAffiliatedPartners($this->merchant->getId());

                $partner = $partners->filter(function(Merchant\Entity $partner) {
                    return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
                })->first();

                $activationFlow = $merchantDetailsCore->getActivationFlow($this->merchant, $merchantDetail, $partner, false);
            }
        }

        $isWhitelisted = $activationFlow === ActivationFlow::WHITELIST;

        if ($isActivated === true)
        {
            return true;
        }
        else if ($isUnregistered === false && $isWhitelisted === true)
        {
            return true;
        }

        return  false;
    }

    protected function getCreateTicketPopupOptions() : array
    {
        $merchantDetails = $this->merchant->merchantDetail;

        if ($this->merchant->isActivated() === true)
        {
            return [
                "show_create_ticket_popup" => false,
                "cta_list"                 => [],
                "message_body"             => "",
            ];
        }

        $activationStatus = $merchantDetails->getActivationStatus();

        $activationProgress = $merchantDetails->getActivationProgress();

        $formSubmissionDate = $merchantDetails->getSubmittedAt();

        $isSubmitted = $this->merchant->merchantDetail->isSubmitted();

        $dataForPopup = $this->getDataForCreateTicketPopup($activationStatus, $activationProgress, $isSubmitted);

        $message    = ($dataForPopup[Constants::MESSAGE]) ?
            __($dataForPopup[Constants::MESSAGE],['submission_at' => date("F j, Y",$formSubmissionDate)])
            : "";

        $response = [
            "show_create_ticket_popup"  => $dataForPopup[Constants::SHOW_POPUP] ? $dataForPopup[Constants::SHOW_POPUP] : false,
            "cta_list"                  => $dataForPopup[Constants::CTA_LIST] ? $dataForPopup[Constants::CTA_LIST] : [],
            "message_body"              => $message
        ];

        return $response;
    }

    /**
     * @param array $referrals
     * @return array|mixed
     */
    public function formatReferralResponse(array $referrals)
    {
        // In the current format, a single referral for the pg product
        // is returned in response, going forward, we will be returning all
        // the referrals for a merchant (pg, banking, etc.).
        // To support backward compatibility, we are stuffing referral details of
        // the pg at root level

        if (array_key_exists(Product::PRIMARY, $referrals) === true) {
            $result = $referrals[Product::PRIMARY];

            $result['referrals'] = $referrals;

            return $result;
        }

        return $referrals;
    }

    public function getRZPTrustedBadgeDetails()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::RZP_TRUSTED_BADGE)) {

            return [
                'rtb_details' => true,
            ];
        }
        else
        {
            return [
                'rtb_details' => false,
            ];
        }
    }

    protected function getDataForCreateTicketPopup($activationStatus, $activationProgress, bool $isSubmitted= false): array
    {
        $dataForPopup = [];

        if (in_array($activationStatus,[MerchantStatus::UNDER_REVIEW, MerchantStatus::NEEDS_CLARIFICATION, MerchantStatus::REJECTED]) === true )
        {
            $functionName = "getPopupDataFor".studly_case($activationStatus);

            $dataForPopup = $this->$functionName();
        }
        else if ($isSubmitted === false)
        {
            $activationProgressRanges = Constants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_PROGRESS_RANGES;

            foreach ($activationProgressRanges as $activationProgressRange)
            {
                $minPercent = is_numeric($activationProgressRange[Constants::MIN_ACTIVATION_PROGRESS]) === true ? $activationProgressRange[Constants::MIN_ACTIVATION_PROGRESS] :
                    ($this->getActivationProgressRequired($activationProgressRange[Constants::MIN_ACTIVATION_PROGRESS]) + 1);

                $maxPercent = is_numeric($activationProgressRange[Constants::MAX_ACTIVATION_PROGRESS]) === true ? $activationProgressRange[Constants::MAX_ACTIVATION_PROGRESS] :
                    $this->getActivationProgressRequired($activationProgressRange[Constants::MAX_ACTIVATION_PROGRESS]);

                $this->trace->info(
                    TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
                    [
                        'minimum Percent' => $minPercent,
                        'maximum Percent' => $maxPercent,
                        'activation Progress'   => $activationProgress,
                    ]);

                if ($activationProgress >= $minPercent &&
                    $activationProgress <= $maxPercent)
                {
                    $dataForPopup = $activationProgressRange;
                }
            }
        }

        return $dataForPopup;
    }

    protected function getActivationProgressRequired($key) : int
    {
        $maxActivationProgressForFirstRange = (int)((new AdminService)->getConfigKey(['key' => $key]));

        if (empty($maxActivationProgressForFirstRange) === true)
        {
            return 0;
        }

        return $maxActivationProgressForFirstRange;
    }

    /**
     * @return int
     */
    protected function getMinTimeDiffToAllowCreateTicket(): int
    {
        $minTimeDiff = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION]);

        if (empty($minTimeDiff) === true)
        {
            $minTimeDiff = self::DEFAULT_MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION;
        }

        return $minTimeDiff;
    }

    protected function getPopupDataForUnderReview(): array
    {
        $l2FirstSubmissionDateTime = (new Detail\Service())->getFirstL2SubmissionDate();

        $dataForUnderReview = Constants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_STATUS[MerchantStatus::UNDER_REVIEW];

        $minTimeDiff = $this->getMinTimeDiffToAllowCreateTicket();

        $differenceInSeconds = self::HOUR * $minTimeDiff;

        $currentTimestamp = Carbon::now()->getTimestamp();

        $this->trace->info(
            TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
            [
                'minimum_difference_in_seconds'              => $differenceInSeconds,
                'current_time_difference_in_seconds'         => $currentTimestamp,
            ]);

        if ($differenceInSeconds < $currentTimestamp - $l2FirstSubmissionDateTime)
        {
            $dataForPopup = $dataForUnderReview[Constants::X_HOURS_AFTER_ACTIVATION_FORM_SUBMISSION];
        }
        else
        {
            $dataForPopup = $dataForUnderReview[Constants::X_HOURS_WITHIN_ACTIVATION_FORM_SUBMISSION];
        }

        return $dataForPopup;
    }

    protected function getPopupDataForNeedsClarification(): array
    {
        $isDedupe = (new Detail\DeDupe\Core)->isMerchantImpersonated($this->merchant);

        $this->trace->info(
            TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
            [
                'is Dedupe ' => $isDedupe,
            ]);

        $dataForNeedsClarification = Constants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_STATUS[MerchantStatus::NEEDS_CLARIFICATION];

        if ($isDedupe === true)
        {
            $dataForPopup = $dataForNeedsClarification[Constants::DEDUPE_MERCHANT];
        }
        else
        {
            $dataForPopup = $dataForNeedsClarification[Constants::NON_DEDUPE_MERCHANT];
        }

        return $dataForPopup;
    }

    protected function getPopupDataForRejected(): array
    {
        return Constants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_STATUS[MerchantStatus::REJECTED][Constants::DEFAULT];
    }

    public function getMerchantRiskData(string $merchantId): array
    {
        return $this->core()->getMerchantRiskData($merchantId);
    }

    /**
     * @throws BadRequestException
     */
    public function fireHubspotEventFromDashboard(array $input): array
    {
        $merchantEmail = array_pull($input, 'merchant_email');

        $merchant = $this->merchant;

        if ($merchantEmail !== $merchant->getEmail())
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'The provided Email Id does not belongs to the merchant');
        }

        if (empty($merchantEmail) === false)
        {
            $this->app->hubspot->trackHubspotEvent($merchantEmail, $input);
        }

        $this->trace->info(
            TraceCode::PUSHED_EVENT_TO_HUBSPOT,
            [
                'merchant_email' => $merchantEmail,
                'payload'        => $input
            ]);

        return ['success' => true];
    }

    public function handleMerchantActionNotificationCron(): array
    {
        (new MerchantActionNotification())->handleMerchantActionNotificationCron();

        return ['success' => true];
    }

    public function completeSubmerchantOnboarding($submerchantId, $input)
    {
        $input['submerchant_id'] = $submerchantId;

        (new Validator)->validateInput('complete_submerchant_onboarding', $input);

        $partnerId     = $input['partner_merchant_id'];

        $submerchant = $this->repo->merchant->findOrFailPublic($submerchantId);
        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        $this->validateAggregatorSubMerchantRelation($submerchant, $partner);

        $this->core()->addMerchantSupportingEntitiesAsync($submerchant, $partner);

        return ['success' => true];
    }

    /**
     * @throws BadRequestException
     */
    public function createSalesforceLeadFromDashboard(array $input): array
    {
        $merchant_id = $input['merchant_id'];

        $merchant = $this->merchant;

        $contact = $merchant->merchantDetail->getContactMobile();

        if (empty($contact) === false)
        {
            $input['contact_mobile'] = $contact;
        }

        if ($merchant->getId() !== $merchant_id)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'The id provided does not exist');
        }


        // Fetch and add current value of X channel and sub-channel in SF payload
        $xChannelDefinitionService = new XChannelDefinition\Service();
        $channelDetails = $xChannelDefinitionService->getCurrentChannelDetails($merchant);

        if (!empty($channelDetails[XChannelDefinition\Constants::CHANNEL]))
        {
            $input['X_Channel'] = $channelDetails[XChannelDefinition\Constants::CHANNEL];
        }

        if (!empty($channelDetails[XChannelDefinition\Constants::SUBCHANNEL]))
        {
            $input['X_Subchannel'] = $channelDetails[XChannelDefinition\Constants::SUBCHANNEL];
        }

        $this->trace->info(TraceCode::X_CHANNEL_DEFINITION_SF_LEAD_EVENT_CHANNEL_DETAILS, [
            'channel'    => $input['X_Channel'] ?? '',
            'subchannel' => $input['X_Subchannel'] ?? '',
        ]);

        try
        {
            $input = $this->app->salesforce->sendCaOnboardingToSalesforce($input);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SALESFORCE_FAILED_TO_DISPATCH_JOB);

            return ['success' => false];
        }

        // added lumberjack integration to match data pulled from SF with product data
        $this->app['diag']->trackOnboardingEvent(EventCode::X_CA_ONBOARDING_LEAD_UPSERT, $merchant, null, $input);

        $this->trace->info(
            TraceCode::CREATED_LEAD_ON_SALESFORCE,
            [
                'payload'        => $input
            ]);

        return ['success' => true];
    }

    public function getMerchantDetailsForSFConverge(string $mid): array
    {
        //$this->app['rzp.mode'] = Mode::LIVE;
        //$this->core()->setModeAndDefaultConnection(Mode::LIVE);
        $merchant = $this->repo->merchant->findOrFailPublic($mid);
        if($merchant !== null){
            $this->merchant = $merchant;
            $this->auth->setMerchant($merchant);
        }

        $data = $this->getMerchantDetails();
        $data['pricing_details'] = $this->getPricingPlan($mid);
        $data['documents'] = (new Document\Service())->fetchActivationFilesFromDocument($mid);

        return $data;
    }

    public function getTerminalDetailsForSFConverge(string $mid): array
    {
        $input= [
            'merchant_ids' => [$mid]
        ];

        $path = "v1/merchants/terminals";

        $data['terminals'] = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::POST,
            $path,[],
            ['X-Truncate-Terminal-Response' => 'salesforce']
        );

        return $data;
    }

    public function getPurposeCodeDetails(): array
    {
        $data = [];

        $data = PurposeCodeList::getPurposeCode();

        return $data;
    }

    public function getHsCodeDetails(): array
    {
        $data = [];

        $data = HsCodeList::getHsCode();

        return $data;
    }

    /**
     * Updates merchant purpose code in merchants table along with its iec code in merchant_details table
     * @param array $input
     * @return bool[]
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function patchMerchantPurposeCode(array $input)
    {
        $merchantFields[Merchant\Entity::PURPOSE_CODE] = $input['purpose_code'];
        $merchantDetailsFields[Merchant\Detail\Entity::IEC_CODE] = $this->getIecCode($input);

        (new Validator)->validateIecCode($merchantFields[Merchant\Entity::PURPOSE_CODE],
            $merchantDetailsFields[Merchant\Detail\Entity::IEC_CODE], $this->merchant->getBankIfsc());

        if(!empty($merchantFields[Merchant\Entity::PURPOSE_CODE])) {
            $this->merchant->edit($merchantFields);
            $this->repo->merchant->saveOrFail($this->merchant);
        }

        if($this->merchant->merchantDetail !== NULL and
            !empty($merchantDetailsFields[Merchant\Detail\Entity::IEC_CODE])) {
            $this->merchant->merchantDetail->edit($merchantDetailsFields);
            $this->repo->merchant_detail->saveOrFail($this->merchant->merchantDetail);
        }

        $this->trace->info(
            TraceCode::MERCHANT_EDIT, [
            Entity::ID => $this->merchant->getId(),
            Entity::PURPOSE_CODE => $this->merchant->getPurposeCode(),
            Merchant\Detail\Entity::IEC_CODE => $this->merchant->getIecCode(),
        ]);
        return ['success' => true];
    }

    private function getIecCode(array $input)
    {
        if (isset($input['iec_code']) === true)
        {
            return $input['iec_code'];
        }

        return null;
    }

    public function saveMerchantCheckoutDetail(array $input)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ONE_CC_MERCHANT_DASHBOARD) === true)
        {
            return (new Merchant\CheckoutDetail\Core())->createOrEditCheckoutDetail($this->merchant->merchantDetail, $input);
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ELIGIBLE_FOR_1CC
            );
        }
    }

    public function fetchMerchantCheckoutDetail()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ONE_CC_MERCHANT_DASHBOARD) === true)
        {
            return $this->repo->merchant_checkout_detail->getByMerchantId($this->merchant->getId());
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ELIGIBLE_FOR_1CC
            );
        }
    }

    private function getWebsiteSelfServeWorkflowAction($entityId, $entity, $orgId = null)
    {
        $action = (new Action\Core())->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Constants::MERCHANT_WORKFLOWS[Constants::ADDITIONAL_WEBSITE][Constants::PERMISSION],
             Constants::MERCHANT_WORKFLOWS[Constants::UPDATE_BUSINESS_WEBSITE][Constants::PERMISSION]],
            $orgId);

        return $action;
    }

    public function getWebsiteSelfServeWorkflowDetails()
    {
        $action = $this->getActionForMerchantWorkflow(Constants::ADDITIONAL_WEBSITE);

        return (new WorkflowService)->getWorkflowDetailsWithRejectionMessage($action);
    }

    private function findCampaignType(array $utmParams): ?string
    {
        $isNeostoneCampaign = false;

        foreach (self::NEOSTONE_UTM_RULES as $neostoneUtmRule)
        {
            $ruleSatisfied = true;

            foreach (User\Constants::$utmDecider as $utmType)
            {
                $ruleSatisfied = ($ruleSatisfied and array_key_exists(('final_' . $utmType), $utmParams));

                if (array_key_exists(('final_' . $utmType), $utmParams)) // We check only last click utm parameters
                {
                    $ruleSatisfied = ($ruleSatisfied and (strtolower($neostoneUtmRule[$utmType]) === strtolower($utmParams['final_' . $utmType])));
                }
            }

            if ($ruleSatisfied === true)
            {
                $isNeostoneCampaign = true;

                break;
            }
        }

        return $isNeostoneCampaign ? 'ca_neostone' : null;
    }

    public function toggleFeeBearer(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_TOGGLE_FEE_BEARER,
        [
            Constants::INPUT   => $input
        ]);
        $merchant = $this->merchant;

        $merchant->validateInput('toggle_fee_bearer', $input);

        (new Validator())->validateIsActivated($merchant);

        $planId = $this->repo->transactionOnLiveAndTest(function () use($merchant, $input)
        {
            $oldPlanId = $merchant->getPricingPlanId();

            $merchant->setFeeBearer($input[Entity::FEE_BEARER]);

            $plan = $this->repo->pricing->getPlanByIdOrFailPublic($merchant->getPricingPlanId());

            $newPlanId = $this->createNewPricingPlan($merchant, $plan, $input);

            $merchant->setPricingPlan($newPlanId);

            $this->repo->merchant->saveOrFail($merchant);

            $this->trace->info(TraceCode::PRICING_PLAN_ASSIGN_SUCCESS,
                [
                    Entity::FEE_BEARER              => $merchant->getFeeBearer(),
                    Constants::OLD_PRICING_PLAN_ID  => $oldPlanId,
                    Constants::NEW_PRICING_PLAN_ID  => $newPlanId
                ]);

            return $newPlanId;
        });

        return ['plan id' => $planId];
    }

    public function createNewPricingPlan($merchant, $plan, $input)
    {
        $merchantId = $merchant->getMerchantId();

        $ruleOrgId = $plan->getOrgId();

        $oldRules = $plan->toArray();

        $planName = Constants::SELF_SERVE_FOR_FEE_BEARER . $merchantId . time();

        $newRules = [];

        foreach ($oldRules as $rule)
        {
            $rule = array_except ($rule,
                [
                    PricingEntity::ID,
                    PricingEntity::PLAN_ID,
                    PricingEntity::ORG_ID,
                    PricingEntity::CREATED_AT,
                    PricingEntity::UPDATED_AT,
                    PricingEntity::DELETED_AT,
                    PricingEntity::EXPIRED_AT
                ]);

            if ($rule[PricingEntity::FEATURE] === PricingFeature::REFUND)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'This action cannot be performed for your account. Please reach out to support team to perform this action'
                );
            }
            $rule[PricingEntity::FEE_BEARER] = $input[PricingEntity::FEE_BEARER];

            $rule[PricingEntity::INTERNATIONAL] = $rule[PricingEntity::INTERNATIONAL] === true ? '1' : '0';

            if ($rule[PricingEntity::PRODUCT] !== Product::BANKING)
            {
                unset($rule[PricingEntity::ACCOUNT_TYPE]);
            }

            if ((isset($rule[PricingEntity::ACCOUNT_TYPE]) === false) or
                ($rule[PricingEntity::ACCOUNT_TYPE] !== Merchant\Balance\AccountType::DIRECT))
            {
                unset($rule[PricingEntity::CHANNEL]);
            }

            array_push($newRules, $rule);
        }

        $newPlan = (new Pricing\Core)->create([PricingEntity::PLAN_NAME => $planName, PricingEntity::RULES => $newRules], $ruleOrgId);

        return $newPlan[0][PricingEntity::PLAN_ID];
    }

    public function postIncreaseTransactionLimitSelfServe(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_INCREASE_TRANSACTION_LIMIT_INPUT,[
           Constants::INPUT => $input
        ]);

        $merchant = $this->merchant;

        $merchantId = $merchant->getMerchantId();

        $merchantDetails = $merchant->merchantDetail;

        $businessType = $merchantDetails->getBusinessType();

        $isBusinessRegistered = in_array($businessType, [Merchant\Detail\BusinessType::INDIVIDUAL, Merchant\Detail\BusinessType::NOT_YET_REGISTERED]) ? false : true;

        $merchantInfo = $this->app['datalake.presto']->getDataFromDataLake(sprintf(Constants::PRESTO_QUERY_FIND_MERCHANT_TYPE, $merchant->getId()));

        $isMerchantKamOrDirectSales = $this->isMerchantKamOrDirectSales($merchantInfo);

        (new Validator)->validateIncreaseTransactionLimitConditions($merchant, $input, $isBusinessRegistered, $isMerchantKamOrDirectSales);

        if ((isset($input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]) === true) and
            (is_object($input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]) === true))
        {
            $input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL] = (new Core())->uploadInvoiceForIncreaseTransactionLimit($merchantDetails, $input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]);
        }

        $oldMerchantData = $merchant;

        $newMerchantData = clone $oldMerchantData;

        $data = [Entity::MERCHANT_ID => $merchantId];

        if ((isset($input[Constants::TRANSACTION_TYPE]) === true) and
            ($input[Constants::TRANSACTION_TYPE]) === Constants::TRANSACTION_TYPE_INTERNATIONAL)
        {
            $newMerchantData->setMaxInternationalPaymentAmount($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT]);
            $data[Entity::MAX_INTERNATIONAL_PAYMENT_AMOUNT] = $input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT];
            $workflowPermission = Permission::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT;
        }else{
            $newMerchantData->setMaxPaymentAmount($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT]);
            $data[Entity::MAX_PAYMENT_AMOUNT] = $input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT];
            $workflowPermission = Permission::INCREASE_TRANSACTION_LIMIT;
        }

        $this->app['workflow']
            ->setPermission($workflowPermission)
            ->setEntityAndId($merchant->getEntity(), $merchant->getId())
            ->setInput($data)
            ->setController(Constants::INCREASE_TRANSACTION_LIMIT_POST_WORKFLOW_APPROVE)
            ->handle($oldMerchantData, $newMerchantData, true);

        $this->addCommentForIncreaseTransactionLimitPostWorkflowCreation($merchant, $input, $workflowPermission);

        if ((isset($input[Constants::TRANSACTION_TYPE]) === true) and
            ($input[Constants::TRANSACTION_TYPE]) === "international")
        {
            return [Entity::MAX_INTERNATIONAL_PAYMENT_AMOUNT => $merchant->getMaxPaymentAmountTransactionType(true)];
        } else{
            return [Entity::MAX_PAYMENT_AMOUNT => $merchant->getMaxPaymentAmountTransactionType(false)];
        }
    }

    protected function addCommentForIncreaseTransactionLimitPostWorkflowCreation(Entity $merchant, array $input, string $workflowPermission)
    {
        $workFlowAction = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperation($merchant->getMerchantId(),
            $merchant->getEntity(),
            $workflowPermission,
            $merchant->getOrgId()
        )->first();

        if (is_null($workFlowAction) === true)
        {
            throw new Exception\ServerErrorException('Workflow Action Not Found',
                ErrorCode::SERVER_ERROR_WORKFLOW_ACTION_CREATE_FAILED);
        }

        $this->addCommentForTransactionLimitIncreaseReason($workFlowAction, $input);

        if (empty($input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]) === false)
        {
            $this->addCommentForTransactionLimitInvoiceUrl($workFlowAction, $input);
        }
    }

    protected function addCommentForTransactionLimitIncreaseReason(WorkFlowActionEntity $workFlowAction, array $input)
    {
        $comment = sprintf(Constants::TRANSACTION_LIMIT_INCREASE_REASON_COMMENT,
            $input[Constants::TRANSACTION_LIMIT_INCREASE_REASON]
        );

        $commentEntity = (new CommentCore())->create([
            Constants::COMMENT => $comment,
        ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    protected function addCommentForTransactionLimitInvoiceUrl(WorkFlowActionEntity $workFlowAction, array $input)
    {
        $comment = sprintf(Constants::TRANSACTION_LIMIT_INCREASE_SUPPORT_DOCUMENT_URL_COMMENT,
            $this->app->config->get('applications.dashboard.url'),
            $input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]
        );

        $commentEntity = (new CommentCore())->create([
            Constants::COMMENT => $comment,
        ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    public function postTransactionLimitWorkflowApprove(array $input)
    {
        $merchantId = $input[Constants::MERCHANT_ID];

        $merchant = $this->repo->merchant->findorFailPublic($merchantId);

        $isInternationalLimit = array_key_exists('max_international_payment_amount', $input);

        if($isInternationalLimit){
            $newTransactionLimit = (new Merchant\Detail\Service())->getAgentApprovedInternationalTransactionLimit($merchant);

            $this->merchant->setMaxInternationalPaymentAmount($newTransactionLimit);
        }
        else {
            $newTransactionLimit = (new Merchant\Detail\Service())->getAgentApprovedTransactionLimit($merchant);

            $this->merchant->setMaxPaymentAmount($newTransactionLimit);
        }

        $this->repo->merchant->saveOrFail($this->merchant);

        $this->trace->info(TraceCode::MERCHANT_TRANSACTION_LIMIT_UPDATE_SUCCESS,
            [
               Constants::UPDATED_TRANSACTION_LIMIT => $newTransactionLimit
            ]
        );

        // $newTransactionLimit is in paise. We have to show it in INR in the notification, hence dividing by 100
        // to get $newTransactionLimit in INR.
        $args = [
            Constants::MERCHANT         => $merchant,
            DashboardEvents::EVENT      => DashboardEvents::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE,
            Constants::PARAMS           => [
                Constants::UPDATED_TRANSACTION_LIMIT  => $newTransactionLimit/100,
                'merchant_name'                       => $merchant[Entity::NAME],
            ]
        ];

        (new DashboardNotificationHandler($args))->send();
    }

    public function getMerchantWorkflowDetails(string $workflowType, string $merchantId = null)
    {
        $action = $this->getActionForMerchantWorkflow($workflowType, $merchantId);

        $response = (new WorkflowService)->getWorkflowDetailsWithRejectionMessage($action);

        $permission = empty($action) ? null : $action->permission->name;

        $needsClarification =  (new WorkFlowActionCore())->getNeedsClarificationBodyFromWorkflowComment($action);

        $this->addCustomKeysInWorkflowDetailsResponse($response, $workflowType, $permission);

        return array_merge($response, [
            'needs_clarification'      => $needsClarification,
            'permission'               => $permission,
            'request_under_validation' => $this->isRequestUnderValidationForMerchantWorkflow($workflowType, $merchantId),
            'tags'                     => $this->getWorkflowTags($action),
        ]);
    }

    protected function addCustomKeysInWorkflowDetailsResponse(array & $response, string $workflowType, $permission)
    {
        if (($workflowType === Constants::BANK_DETAIL_UPDATE) and
            ($permission === Permission::EDIT_MERCHANT_BANK_DETAIL))
        {
            $careResponse = $this->app['care_service']->dashboardProxyRequest(CareProxyController::GET_BANK_ACCOUNT_UPDATE_RECORD, []);

            if ((array_key_exists(Constants::BANK_ACCOUNT_ID, $careResponse) === true) and
                ($careResponse[Constants::BANK_ACCOUNT_ID] !== ""))
            {
                $response[Constants::BANK_ACCOUNT_ID] = $careResponse[Constants::BANK_ACCOUNT_ID];
            }
        }
    }

    public function getMerchantWorfklowDetailsBulk(string $merchantId, array $input) {

        $response = array();

        foreach( $input["workflow_type"] as $workflowType ) {

            array_push($response, [
                'workflow_name'      => $workflowType,
                'workflow_details'   => $this->getMerchantWorkflowDetails($workflowType,$merchantId),
            ]);

        }

        return $response;
    }

    protected function getWorkflowTags($action)
    {
        if (empty($action) === true)
        {
            return [];
        }

        $tags = $action->getTagsAttribute();

        $tags =  $tags->map(function ($tag)
        {
            return $tag->slug;
        });

        return $tags;
    }

    protected function isRequestUnderValidationForMerchantWorkflow(string $workflowType, $merchantId = null)
    {
        switch ($workflowType)
        {
            case Constants::GSTIN_UPDATE_SELF_SERVE :
                return $this->isGstinUpdateUnderBvsValidation($merchantId);
                break;
            case Constants::BANK_DETAIL_UPDATE :
                return $this->isBankAccountUpdateUnderBvsValidation();
            default:
                return false;
        }
    }

    protected function isGstinUpdateUnderBvsValidation($merchantId = null)
    {
        $data = (new Detail\Service())->getGstinSelfServeInputFromCache($merchantId);

        return is_null($data) === false;
    }

    protected function isBankAccountUpdateUnderBvsValidation()
    {
        $bankAccountCore = (new BankAccount\Core);

        return $bankAccountCore->isBankAccountUpdatePennyTestingInProgress($this->merchant);
    }

    protected function getActionForMerchantWorkflow($workflowType, $merchantId = null)
    {
        $orgId = null;

        if ($merchantId !== null)
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);
            $orgId = $merchant->getOrgId();
        }
        else
        {
            $merchant = $this->merchant;
        }

        $merchantCore = new Merchant\Core;

        [$entityId, $entity] = $merchantCore->fetchWorkflowData($workflowType, $merchant);

        $this->trace->info(
            TraceCode::GET_MERCHANT_WORKFLOW_DETAILS,
            [
                'entity_id'  => $entityId,
                'entity'     => $entity,
            ]);

        switch ($workflowType)
        {
            case Constants::GSTIN_UPDATE_SELF_SERVE :
                return $this->getWorkflowActionForGstinUpdateSelfServe($entityId, $entity, $orgId);

            case Constants::ADDITIONAL_WEBSITE :
                return $this->getWebsiteSelfServeWorkflowAction($entityId, $entity, $orgId);

            default:
                return  (new Action\Core())->fetchLastUpdatedWorkflowActionInPermissionList(
                    $entityId,
                    $entity,
                    [Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION]],$orgId
                );
        }
    }

    protected function getWorkflowActionForGstinUpdateSelfServe($entityId, $entity, $orgId = null)
    {
        $action = (new Action\Core())->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Permission::UPDATE_MERCHANT_GSTIN_DETAIL, Permission::EDIT_MERCHANT_GSTIN_DETAIL], $orgId
        );

        return $action;
    }

    public function postMerchantWorkflowClarification(string $workflowType, array $input)
    {
        $this->trace->info(TraceCode::POST_MERCHANT_WORKFLOW_CLARIFICATION, $input);

        (new Validator)->validateInput('merchant_workflow_clarification', $input);

        $merchant = $this->merchant;

        $merchantCore = new Merchant\Core;

        [$entityId, $entity] = $merchantCore->fetchWorkflowData($workflowType, $merchant);

        $this->trace->info(
            TraceCode::GET_MERCHANT_WORKFLOW_DETAILS,
            [
                'entity_id'  => $entityId,
                'entity'     => $entity
            ]);

        $action = $this->getActionForMerchantWorkflow($workflowType);

        if (empty($action) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_OPEN_WORKFLOW_NOT_FOUND);
        }

        return (new Core)->postMerchantWorkflowClarification($action, $input);
    }

    private function isMerchantKamOrDirectSales($merchantInfo): bool
    {
        if ((isset($merchantInfo) === true) and
            (isset($merchantInfo[0]['owner_role__c']) === true) and
            (in_array($merchantInfo[0]['owner_role__c'], [Constants::MERCHANT_TYPE_KAM , Constants::MERCHANT_TYPE_DIRECT_SALES])) === true)
        {
            return true;
        }

        return false;
    }

    /**
     * Add/Update Merchant Fetch Coupons URL
     * @param array $input
     * @return void
     */
    public function updateFetchCouponsUrl(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_FETCH_COUPONS_REQUEST, $input);

        if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }
        else
        {
            (new Validator)->validateInput('couponCodeUrlUpdateRequest', $input);
        }

        (new Merchant\Core)->associateMerchant1ccConfig(
            Merchant1ccConfig\Type::FETCH_COUPONS_URL,
            $input['url']
        );
    }

    /**
     * Add/Update Merchant Coupon Validity URL
     * @param array $input
     * @return void
     */
    public function updateApplyCouponUrl(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_CHECK_COUPON_VALIDITY_REQUEST, $input);

        if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }
        else
        {
            (new Validator)->validateInput('couponCodeUrlUpdateRequest', $input);
        }

        (new Merchant\Core)->associateMerchant1ccConfig(
            Merchant1ccConfig\Type::APPLY_COUPON_URL,
            $input['url']
        );
    }

     /**
     * Add/Update Merchant Serviceability and COD Serviceability URL
     * @param array $input
     * @return void
     * @throws \Throwable
     */
    public function updateShippingInfoUrl(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_ADDRESS_SERVICEABILITY_REQUEST, $input);

        if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }
        else
        {
            (new Validator)->validateInput('serviceabilityUrlUpdateRequest', $input);
        }

        (new Merchant\Core)->associateMerchant1ccConfig(
            Merchant1ccConfig\Type::SHIPPING_INFO_URL,
            $input['url']
        );
    }

    /**
     * Add/Update Merchant Shipping Method Config
     * @param array $input
     * @throws \Throwable
     */
    public function updateShippingMethodProviderConfig(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_SHIPPING_METHOD_PROVIDER_REQUEST, $input);

        return (new Merchant\Core)->associateMerchant1ccConfig(
            Merchant1ccConfig\Type::SHIPPING_METHOD_PROVIDER,
            "",
            $input
        );
    }

    /**
    * Add/Update Merchant Platform Type
    * @param array $input
    * @return void
    * @throws \Throwable
    */
   public function updateMerchantPlatform(array $input)
   {
       if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
       {
           $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

           $this->app['basicauth']->setMerchant($this->merchant);
       }
       else
       {
         (new Validator)->validateInput('merchantPlatformUpdateRequest', $input);
       }

       (new Merchant\Core)->associateMerchant1ccConfig(
           Merchant1ccConfig\Type::PLATFORM,
           $input['platform']
       );
   }

    /**
     * Add/Update Merchant 1cc Config from dark
     * @param array $input
     * @return void
     * @throws \Throwable
     */
    public function updateMerchant1ccConfigDark(array $input)
    {
        if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }

        if($input['config'] === ShopifyConstants::ONE_CC_GUPSHUP_CREDENTIALS) {

            if(isset($input['value_json']) === true) {
                $index = 0;
                foreach ($input['value_json'] as $credential) {
                    $input['value_json'][$index]['password'] = $this->app['encrypter']->encrypt($credential['password']);
                    $index++;
                }
            }
        }

        (new Merchant\Core)->associateMerchant1ccConfig(
            $input['config'],
            $input['value']??'',
            $input['value_json'] ?? []
        );
    }

    /**
     * Adds/Updates COD Slabs for the merchant (1CC)
     * @param array $input
     * @return void
     * @throws Throwable
    */
    public function updateCodSlabs(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_COD_SLABS_UPDATE_REQUEST, $input);

        if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }

        if(!isset($input['slabs'])) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $slabs = $this->validateAndSortSlabs($input['slabs']);

        (new Core())->associateCodSlab($slabs);
    }

    /**
     * @throws Throwable
     * @throws BadRequestException
     */
    public function updateShippingSlabs(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_SHIPPING_SLABS_UPDATE_REQUEST, $input);

        if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }

        if(!isset($input['slabs'])) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $slabs = $this->validateAndSortSlabs($input['slabs']);

        (new Core())->associateShippingSlab($slabs);
    }

    /**
     * @throws Throwable
     * @throws BadRequestException
     */
    public function updateCodServiceabilitySlabDark(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_SHIPPING_SLABS_UPDATE_REQUEST, $input);

        if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }

        if(!isset($input['slabs'])) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        (new Core())->associateCodServiceabilitySlab($input['slabs']);
    }

    /**
     * @throws BadRequestException
     */
    public function validateAndSortSlabs(array $slabs): array
    {
        $validator = (new Validator);

        if (empty($slabs)===true) {
            return [];
        }

        foreach ($slabs as $slab) {
            $validator->validateInput('updateSlabRequest', $slab);
        }

        usort(
            $slabs,
            function ($s1, $s2)
            {
                if ($s1['amount'] == $s2['amount'])
                {
                    throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
                }
                return ($s1['amount'] > $s2['amount']) ? 1 : -1;
            }
        );

        if($slabs[0]['amount'] != 0){
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        return $slabs;
    }

    public function fetchMerchantsByParams(array $input)
    {
        (new Validator)->validateInput('fetch_merchants_by_params', $input);

        $this->trace->info(TraceCode::FETCH_MERCHANTS_BY_PARAMS_REQUEST, $input);

        return $this->repo->merchant->fetchMerchantsByParams($input)->toArrayAdmin();
    }

    public function addProductSwitchRole(string $product = null)
    {
        // Add Banking Role for the current merchant User.
        return Tracer::inSpan(['name' => 'product_switch.addProductSwitchRole'], function () use ($product) {
            return (new User\Service)->addProductSwitchRole($product);
        });
    }

    public function getMerchantUserMappingForProduct(string $product = null,
                                                     string $merchantId = null,
                                                     string $userId = null,
                                                     bool $useWritePdo = false)
    {
        return (new User\Service)->getMerchantUserMappingForProduct($product, null, null, $useWritePdo);
    }

    public function updateWhitelistedDomain($input): array
    {
        $status = 'success';

        $errorMsg = '';

        $comment = '';

        try
        {
            switch (strtolower($input['action']))
            {
                case 'insert':
                    $comment = $this->core()->addWhitelistedDomainForUrl($input['merchant_id'], $input['url']);
                    break;
                case 'delete':
                    $this->core()->removeWhitelistedDomainForUrl($input['merchant_id'], $input['url']);
                    break;
                default:
                    throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ACTION);
            }
        }
        catch (\Throwable $e)
        {
            $status = 'failure';

            $errorMsg = sprintf('ERROR: %s', $e->getMessage());
        }

        $input['status'] = $status;

        $input['comment'] = $comment;

        $input['error_message'] = $errorMsg;

        return $input;
    }

    public function getFUXDetailsForPartner() : array
    {
        (new Merchant\Validator)->validateIsPartner($this->merchant);

        $partnerId = $this->merchant->getId();

        $response = [];

        $response['first_submerchant_added'] = $this->repo->merchant_access_map->isSubmerchantPresentForPartner($partnerId);

        $response['first_earning_generated'] = $this->repo->commission->isEarningsPresentForPartner($partnerId);

        $response['first_commission_payout'] = $this->repo->commission_invoice->isProcessedInvoicePresentForPartner($partnerId);

        $entityOrigins = $this->repo->entity_origin->fetchOriginApplicationsForPartner($partnerId, 2);

        $response['api_integration'] = count($entityOrigins) > 0;

        $response['first_submerchant_accept_payments'] = $this->repo->merchant_access_map
            ->isLiveSubmerchantPresentForPartner($partnerId);

        if ($this->partnerService->isPartnerTypeSwitchExpEnabled($partnerId) === true)
        {
            try
            {
                $partnerMigrationResponse = $this->app->partnerships->getLastPartnerMigration(['partner_id' => $partnerId]);

                $this->trace->info(TraceCode::GET_PARTNER_MIGRATION_AUDIT_RESPONSE, $partnerMigrationResponse);

                $response['partner_migration_enabled'] = ($partnerMigrationResponse['status_code'] == 200 and empty($partnerMigrationResponse['response']['partner_migration']));
            }
            catch (Throwable $e)
            {
                $this->trace->error(TraceCode::PRTS_GET_PARTNER_MIGRATION_AUDIT_ERROR, ['partner_id'=>$partnerId, 'error'=> $e->getMessage()]);

                $response['partner_migration_enabled'] = false;
            }
        }


        return $response;
    }

    public function bulkMigrateAggregatorToResellerPartner(array $input)
    {
        if ($this->isPartnerTypeBulkMigrationExperimentEnabled() === false)
        {
            return ['success' => true, 'errorMessage' => "Merchant is not allowed for migration"];
        }

        return $this->core()->bulkMigrateAggregatorToResellerPartner($input);
    }

    public function migrateAggregatorToResellerPartner(array $input)
    {
        $merchantId = $input['merchant_id'];

        if ($this->partnerService->isPartnerTypeSwitchExpEnabled($merchantId) === false)
        {
            return ['success' => true, 'errorMessage' => "Merchant is not allowed for migration"];
        }

        $this->trace->info(TraceCode::MIGRATE_AGGREGATOR_TO_RESELLER_REQUEST, $input);

        (new Validator())->validateInput('aggregatorToResellerMigration', $input);

        $result = null;

        try
        {
            $result = $this->core()->migrateAggregatorToResellerPartner($merchantId);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_ERROR, $input);
            throw $e;
        }

        return ['success' => $result, 'errorMessage' => $result !== true ? "Invalid merchant for migration" :null];
    }


    public function trackBalanceEvent($input)
    {
        $merchantId = $this->merchant->getId();
        $user   = $this->auth->getUser();
        $role   = $this->auth->getUserRole();;

        $userId = null;
        //For outh user will be there for normal private auth user won't be there
        if (isset($user) === true )
        {
            $userId        = $user->getId();
        }

        $eventAttribute = [
            'merchant_id' => $merchantId,
            'request'     => 'balance_fetch_multiple',
            'user_id'     => $userId,
            'user_role'   => $role,
            'channel'     => $this->auth->getSourceChannel(),
            'filters'     => $input
        ];

        $this->app['diag']->trackBalanceEvents(EventCode::BALANCE_FETCH_REQUESTS,
                                               null,
                                               null,
                                               $eventAttribute);
    }

    public function isPluginMerchant($merchantId)
    {
        $isDruidMigrationEnabled = (new Core())->isRazorxExperimentEnable($merchantId,
                                                                          RazorxTreatment::DRUID_MIGRATION);

        if($isDruidMigrationEnabled === true)
        {
            $data = $this->getPluginAndTotalPaymentCountsFromPinot($merchantId);
        }
        else
        {
            $data = $this->getPluginAndTotalPaymentCountsFromDruid($merchantId);
        }

        $this->trace->info(
            TraceCode::DRUID_DATA_PLUGIN_MERCHANT,
            [
                'data'          => $data,
            ]);

        if (empty($data) === false &&
            array_key_exists('plugin_transactions',$data[0]) === true &&
            array_key_exists('total_transactions',$data[0]) === true &&
            $data[0]['plugin_transactions'] >= $data[0]['total_transactions'] * Constants::RATIO_OF_TOTAL_TRANSACTION_FOR_PLUGIN)
        {
            return true;
        }

        return false;
    }

    public function getPluginAndTotalPaymentCountsFromDruid($merchantId)
    {
        $query = 'select payment_analytics_total_plugin_payments as plugin_transactions,payments_total_payments as total_transactions from druid.plugin_merchant_fact where plugin_merchant_fact.payments_merchant_id=\'%s\'';

        $query = sprintf($query, $merchantId);

        $content = [
            'query' => $query
        ];

        $druidService = $this->app['druid.service'];

        [$error, $data] = $druidService->getDataFromDruid($content, self::REQUEST_TIMEOUT_GET_DATA_FOR_SEGMENT);

        if (empty($error) === false)
        {
            return [];
        }

        return $data;
    }

    public function getPluginAndTotalPaymentCountsFromPinot($merchantId): array
    {
        $query = 'select payment_analytics_total_plugin_payments as plugin_transactions,payments_total_payments as total_transactions from pinot.plugin_merchant_fact where plugin_merchant_fact.payments_merchant_id=\'%s\'';

        $query = sprintf($query, $merchantId);

        $content = [
            'query' => $query
        ];

        $pinotService = $this->app['eventManager'];

        $data = $pinotService->getDataFromPinot($content, self::REQUEST_TIMEOUT_GET_DATA_FOR_SEGMENT);

        $parsedResults = [];

        foreach ($data as $key => $value)
        {
            array_push($parsedResults, $pinotService->parsePinotDefaultType($value, 'plugin_merchant_fact'));
        }

        return $parsedResults;
    }

    public function handleMerchantPopularProductsCron()
    {
        $cronLastRunAt = $this->getMerchantPopularProductsCronLastRunAt();

        $this->app['trace']->info(TraceCode::MERCHANT_POPULAR_PRODUCTS_CRON_STARTED, [
            'last_run_at' => $cronLastRunAt,
        ]);

        $this->checkIfCronLastRanInCurrentQuarter($cronLastRunAt);

        $products = $this->getProductsListFromDataLake();

        $productListInString = implode(',', $products);

        $this->app->cache->put(Constants::MERCHANT_POPULAR_PRODUCTS_CACHE_KEY, $productListInString);

        $newLastRunAt = Carbon::now()->getTimestamp();

        $this->updateMerchantPopularProductsCronLastRunAt($newLastRunAt);

        $this->app['trace']->info(TraceCode::MERCHANT_POPULAR_PRODUCTS_CRON_RESULT, [
            'results'         => $productListInString,
            'new_last_run_at' => $newLastRunAt,
        ]);

        return ['success' => true];
    }

    protected function checkIfCronLastRanInCurrentQuarter($cronLastRunAt)
    {
        $currentDate = Carbon::now();

        $currentQuarter = $currentDate->quarter;

        if (is_null($cronLastRunAt) === false)
        {
            $cronLastRunAtDate = Carbon::parse($cronLastRunAt);

            $cronLastRunAtQuarter = $cronLastRunAtDate->quarter;

            if ($currentQuarter === $cronLastRunAtQuarter)
            {
                return;
            }
        }
    }

    protected function getProductsListFromDataLake()
    {
        $threeMonthsAgoDate = Carbon::now()->subMonths(3);

        $previousQuarterStartTimestamp = $threeMonthsAgoDate->firstOfQuarter()->getTimestamp();

        $previousQuarterEndTimestamp = $threeMonthsAgoDate->lastOfQuarter()->getTimestamp();

        $dataLakeQuery = sprintf(Constants::DATA_LAKE_FETCH_POPULAR_PRODUCTS_ACRROSS_MERCHANT_QUERY, $previousQuarterStartTimestamp, $previousQuarterEndTimestamp);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $products = array_column($lakeData, "product");

        return $products;
    }

    protected function getMerchantPopularProductsCronLastRunAt()
    {
        $lastRunAt = $this->app['cache']->get(Constants::MERCHANT_POPULAR_PRODUCTS_CRON_LAST_RUN_AT_KEY);

        if (is_null($lastRunAt) === false)
        {
            return $lastRunAt;
        }
    }

    protected function updateMerchantPopularProductsCronLastRunAt($lastRunAt)
    {
        $previousLastRunAt = $this->getMerchantPopularProductsCronLastRunAt();

        if ($lastRunAt <= $previousLastRunAt)
        {
            return;
        }

        $this->app['cache']->put(Constants::MERCHANT_POPULAR_PRODUCTS_CRON_LAST_RUN_AT_KEY, $lastRunAt);
    }

    public function settlementsEventsCron($input)
    {

        $cronLastRunAt = $this->getSettlementsEventsCronLastRunAt($input);

        $this->app['trace']->info(TraceCode::SETTLEMENTS_EVENTS_CRON_STARTED, [
            'last_run_at' => $cronLastRunAt,
        ]);

        (new Validator)->validateSettlementsEventsCronInput($input, $cronLastRunAt);

        $updatedAtTo = ($input[Constants::END_TIMESTAMP] ?? Carbon::now()->getTimestamp());

        $merchantsCollection = $this->repo->merchant->getMerchantsForSettlementsEventsCron($cronLastRunAt, $updatedAtTo);

        $merchantsCollection = $merchantsCollection->filter(function ($merchant)
        {
           return $merchant->isFeatureEnabled(Feature\Constants::NEW_SETTLEMENT_SERVICE) === true;
        });

        $this->app['trace']->info(TraceCode::SETTLEMENTS_EVENTS_CRON_SELECTED_MERCHANT_IDS, [
            'merchant_ids' => $merchantsCollection->pluck('id')->toArray(),
        ]);

        $resultsTrace = [];

        foreach ($merchantsCollection->chunk(10) as $merchants)
        {
            // going with hardcoding the output structure as the number of attribute is expected to be
            // at 3-4 max.
            $result = [
                [
                    'merchant_ids' => $merchants->filter(function ($merchant)
                    {
                        return $merchant->getHoldFunds() === true;
                    })->pluck('id')->toArray(),
                    'attribute'   => [
                        Entity::HOLD_FUNDS => true,
                    ],
                ],
                [
                    'merchant_ids' => $merchants->filter(function ($merchant)
                    {
                        return $merchant->getHoldFunds() === false;
                    })->pluck('id')->toArray(),
                    'attribute'   => [
                        Entity::HOLD_FUNDS => false,
                    ],
                ],
            ];

            $result = array_values(array_filter($result, function ($resultElement)
            {
               return count($resultElement['merchant_ids']) > 0;
            }));

            $this->app['sns']->publish(json_encode($result), 'settlements_merchants_events');

            $resultsTrace[] = $result;
        }

        if (count($merchantsCollection) > 0)
        {
            $newLastRunAt = max($merchantsCollection->pluck(Entity::UPDATED_AT)->toArray());

            $this->updateSettlementsEventsCronLastRunAt($newLastRunAt);

            $this->app['trace']->info(TraceCode::SETTLEMENTS_EVENTS_CRON_RESULT, [
                'results'         => $resultsTrace,
                'new_last_run_at' => $newLastRunAt,
            ]);
        }

        return ['success' => true];
    }

    protected function getSettlementsEventsCronLastRunAt($input = []): int
    {
        $lastRunAtKey = $this->getLastRunAtKeyForSettlementsEventsCron();

        $lastRunAt = $this->app['cache']->get($lastRunAtKey);

        if (is_null($lastRunAt) === false)
        {
            return $lastRunAt;
        }

        return Carbon::now()->getTimestamp() - ($input['lookback_seconds'] ?? 600);
    }

    protected function updateSettlementsEventsCronLastRunAt($lastRunAt)
    {
        $previousLastRunAt = $this->getSettlementsEventsCronLastRunAt();

        if ($lastRunAt <= $previousLastRunAt)
        {
            $this->trace->count(Metric::NSS_CRON_LAST_RUN_AT_SAME_VALUE);
            return;
        }

        $lastRunAtKey = $this->getLastRunAtKeyForSettlementsEventsCron();

        $this->app['cache']->set($lastRunAtKey, $lastRunAt);
    }

    /**
     * Updates merchant purpose code in merchants table along with its iec code in merchant_details table via admin action
     * @param array $input
     * @return bool[]
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function patchAdminPurposeCode(array $input)
    {

        $merchantFields[Merchant\Entity::PURPOSE_CODE] = $input['purpose_code'];
        $merchantDetailsFields[Merchant\Detail\Entity::IEC_CODE] = $this->getIecCode($input);
        $merchantId = $input['merchant_id'];

        $merchant =  $this->repo->merchant->fetchMerchantFromId($merchantId);

        (new Validator)->validateIecCode(
            $merchantFields[Merchant\Entity::PURPOSE_CODE],
            $merchantDetailsFields[Merchant\Detail\Entity::IEC_CODE],
            $merchant->getBankIfsc()
        );

        if (!empty($merchantFields[Merchant\Entity::PURPOSE_CODE])) {
            $merchant->edit($merchantFields);
            $this->repo->merchant->saveOrFail($merchant);
        }

        if ($merchant->merchantDetail !== NULL and
            !empty($merchantDetailsFields[Merchant\Detail\Entity::IEC_CODE])
        ) {
            $merchant->merchantDetail->edit($merchantDetailsFields);
            $this->repo->merchant_detail->saveOrFail($merchant->merchantDetail);
        }

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                Entity::ID => $merchant->getId(),
                Entity::PURPOSE_CODE => $merchant->getPurposeCode(),
                Merchant\Detail\Entity::IEC_CODE => $merchant->getIecCode(),
            ]
        );

        return ['success' => true];
    }

    private function isPartnerTypeBulkMigrationExperimentEnabled(string $merchantId = null): bool
    {
        if ($this->auth->isAdminAuth() === false)
        {
            $merchantId = $this->auth->isPartnerAuth() ? $this->auth->getPartnerMerchantId() : $this->auth->getMerchantId();
        }

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.partner_type_bulk_migration_exp_id'),
        ];

        return $this->core()->isSplitzExperimentEnable($properties, 'enable');
    }


    private function getMerchantTransactionsInLastMonth()
    {
        $merchant = $this->merchant;

        $totalTransactions = Cache::get($this->getMerchantTransactionsInLastMonthKey($merchant->getMerchantId()));

        if (empty($totalTransactions) === true)
        {
            return 0;
        }
        return  $totalTransactions;
    }

    public function getMerchantTransactionsInLastMonthKey($merchantId)
    {
        return Constants::MERCHANT_SEGMENT_TRANSACTION_COUNT . ':' . $merchantId;
    }

    public function get1ccMerchantPreferences(): array
    {
        $merchant = $this->merchant;

        $preferences = (new Core)->get1ccMerchantPreferences($merchant);

        return $preferences;
    }

    public function removeSubmerchantDashboardAccessOfPartner(array $input)
    {
        $partnerIds = $input['partner_ids'];

        RemoveSubmerchantDashboardAccessJob::dispatch($partnerIds);
    }

    public function addOrRemoveFeaturesForMerchant(array $input)
    {
        $merchant = $this->merchant;

        $shouldSync = true;

        $failedStatus = "FAILED";

        $featuresToAdd = $this->getFeatureNamesFromFeatureFlag($input['enable']);

        $featuresToRemove = $this->getFeatureNamesFromFeatureFlag($input['disable']);

        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_UPDATE,
            [
                'purpose'             => "Add/update feature flags for the merchant via merchant dashboard",
                'merchant'            =>  $merchant->getId(),
                'featuresToAdd'       =>  $featuresToAdd,
                'featuresToRemove'    =>  $featuresToRemove,
            ]
        );

        if (in_array($failedStatus,$featuresToAdd, true) || in_array($failedStatus,$featuresToRemove, true)){
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                null,
                null,
                "The requested feature is unavailable."
            );
        }

        $this->addFeatures($featuresToAdd, $shouldSync);

        $this->removeFeatures($featuresToRemove, $shouldSync);

        $this->sendMerchantNotifications($merchant, $featuresToAdd);

        return ['success' => true];
    }

    // validate and obtain the feature flags from the supported list of feature flags for self serve
    private function getFeatureNamesFromFeatureFlag(array $features): array
    {
        $featureNames = [];

        foreach ($features as $name)
        {
            try
            {
                $featureName = Feature\Constants::$visibleFeaturesMap[$name]['feature'];

                if(isset($featureName) === false || empty($featureName) === true)
                {
                    $this->trace->info(
                        TraceCode::MERCHANT_FEATURE_NOT_EXIST,
                        [
                            'purpose'         => "Feature sent doesn't exist",
                            'features'        =>  $features,
                        ]
                    );

                    return ['FAILED'];
                }

                $featureNames[] = $featureName;
            }
            catch (\Throwable $e)
            {
                $this->trace->info(
                    TraceCode::MERCHANT_FEATURE_NOT_EXIST,
                    [
                        'purpose'         => "Feature sent doesn't exist",
                        'features'        =>  $features,
                    ]
                );

                return ['FAILED'];
            }
        }

        return $featureNames;
    }

    //email notifications for different feature flags
    private function sendMerchantNotifications($merchant, $features)
    {
        foreach ($features as $name)
        {
            switch(Feature\Constants::$visibleFeaturesMap[$name]['feature'])
            {
                case FeatureConstants::ACCEPT_ONLY_3DS_PAYMENTS:
                    $args = [
                        MerchantConstants::MERCHANT        => $merchant,
                        DashboardEvents::EVENT             => DashboardEvents::DISABLE_NON_3DS_ALERT,
                        MerchantConstants::PARAMS          => []
                    ];
                    (new DashboardNotificationHandler($args))->send();
            }
        }
    }

    // Enabling non-3ds card processing for merchants
    public function postEnableNon3dsSelfServe()
    {
        $merchant = $this->merchant;

        $merchantId = $merchant->getMerchantId();

        $data = [Entity::MERCHANT_ID => $merchantId];

        (new Validator)->validateEnableNon3dsConditions($merchant);

        $workflowPermission = Permission::ENABLE_NON_3DS_PROCESSING;

        $oldFlag = [
            'feature' => 'accept_only_3ds_payments'
        ];

        $newFlag = [
            'feature' => null
        ];

        $this->app['workflow']
            ->setPermission($workflowPermission)
            ->setEntityAndId($merchant->getEntity(), $merchantId)
            ->setInput($data)
            ->setController(Constants::ENABLE_NON_3DS_WORKFLOW_APPROVE)
            ->handle($oldFlag, $newFlag);

        $this->trace->info(
            TraceCode::ENABLE_NON_3DS_WORKFLOW_CREATED,
            [
                'merchant_id' => $merchantId
            ]
        );

        return ['success' => true];
    }

    public function postEnableNon3dsWorkflowApprove(array $input)
    {
        $merchantId = $input[Constants::MERCHANT_ID];

        $merchant = $this->repo->merchant->findorFailPublic($merchantId);

        $featuresToRemove = [];

        $featuresToRemove[] = Feature\Constants::$visibleFeaturesMap[FeatureConstants::ACCEPT_ONLY_3DS_PAYMENTS]['feature'];

        $shouldSync = true;

        $this->removeFeatures($featuresToRemove, $shouldSync);

        $this->trace->info(TraceCode::ENABLE_NON_3DS_WORKFLOW_APPROVED,
            [
               'merchant_id' => $merchantId
            ]
        );

        $args = [
            MerchantConstants::MERCHANT        => $merchant,
            DashboardEvents::EVENT             => DashboardEvents::ENABLE_NON_3DS_ALERT,
            MerchantConstants::PARAMS          => []
        ];

        (new DashboardNotificationHandler($args))->send();
    }

    public function getEnableNon3dsDetails()
    {
        $action = $this->getActionForMerchantWorkflow(Constants::ENABLE_NON_3DS_PROCESSING);

        $updatedDate = null;

        if ($action !== null) {
            $updatedTime = $action->getAttribute(ActionEntity::UPDATED_AT);

            $updatedDate = date('d-m-Y', $updatedTime);
        }

        $response = (new WorkflowService)->getWorkflowDetailsWithRejectionMessage($action);

        return array_merge($response, [
            'allow_only_3ds'      => $this->merchant->isFeatureEnabled(FeatureConstants::ACCEPT_ONLY_3DS_PAYMENTS),
            'updated_at'          => $updatedDate
        ]);
    }

    public function retryStoreLegalDocuments()
    {
        (new Consent\Core())->retryStoreLegalDocuments();

        return ['success' => true];
    }

    public function getMerchantConsents($merchantId)
    {
        return (new Consent\Core())->getMerchantConsents($merchantId);
    }

    public function saveMerchantConsents($input)
    {
        (new Consent\Core())->saveMerchantConsents($input);

        return ['success' => true];
    }

    /**
     * Runs Public Key & Keyless Auth over Internal Auth & returns the MerchantId,
     * Mode & MerchantKey on success.
     *
     * Currently used by checkout-service to authenticate preferences requests
     * and cache the auth response.
     *
     * This method exists because key based auth is in shadow mode on edge & raw
     * KeyLess Auth isn't supported  by edge.
     *
     * @param HttpRequest $request Laravel Request Instance
     *
     * @return Response|JsonResponse
     */
    public function validatePublicAuthOverInternalAuth(HttpRequest $request): Response|JsonResponse
    {
        (new Validator)->validateInput('publicAuthOverInternalAuth', $request->all());

        $request->merge(['key_id' => $request->input('merchant_public_key', '')]);

        // Remove User Header & Password Set for Internal/App Auth.
        $request->headers->remove('PHP_AUTH_USER');
        $request->headers->remove('PHP_AUTH_PW');

        /** @var BasicAuth $ba */
        $ba = $this->app['basicauth'];

        $oauth = (new OAuth());

        if ($oauth->hasOAuthPublicToken() === true)
        {
            $response = $oauth->resolvePublicToken();

            // Any not null $response (e.g. 401, 403 etc) means the request was not authenticated.
            return $response ?? ApiResponse::json([
                'merchant_id' => optional($ba->getMerchant())->getId() ?? '',
                'merchant_key' => $ba->getPublicKey() ?? '',
                'mode' => $ba->getMode() ?? '',
            ]);
        }

        $request->merge(['account_id' => $request->input('merchant_account_id', '')]);

        $response = $ba->publicAuth();

        // Any not null $response (e.g. 401, 403 etc) means the request was not authenticated.
        return $response ?? ApiResponse::json([
            'merchant_id' => optional($ba->getMerchant())->getId() ?? '',
            'merchant_key' => optional($ba->getKeyEntity())->getPublicKey() ?? '',
            'mode' => $ba->getMode() ?? '',
        ]);
    }

    private function sendSelfServeSuccessAnalyticsEventToSegmentForEnablingFlashCheckout()
    {
        [$segmentEventName, $segmentProperties] = (new Merchant\Core())->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Flash Checkout Enabled';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    private function sendSelfServeSuccessAnalyticsEventToSegmentForEnablingMandatePageSkip()
    {
        [$segmentEventName, $segmentProperties] = (new Merchant\Core())->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Mandate Page Skipped';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    /**
     * Add/Update Merchant 1cc Config from dark
     * @param array $input
     * @return void
     * @throws \Throwable
     */
    public function updateMerchant1ccCouponConfig(array $input)
    {
        (new Validator)->validateInput('merchantCouponConfig', $input);

        if ($this->merchant === null and $this->app['basicauth']->isPrivilegeAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }

        $config = [];

        foreach ($input['value_json'] as $value)
        {
            (new Validator)->validateInput('couponConfigData', $value);

            if (isset($value['visibility']) === false)
            {
                $value['visibility'] = 1;
            }

            $config[$value['reference_id']] = $value;
        }

        return (new Merchant\Core)->associateMerchant1ccConfig(
            $input['config'],
            "coupon visibility and disable payment method configs",
            $config
        );
    }

    public function fetchMerchantIpConfig()
    {
        return (new Core)->fetchMerchantIpConfig();
    }

    public function fetchMerchantIpConfigForAdmin(string $merchantId)
    {
        return (new Core)->fetchMerchantIpConfigForAdmin($merchantId);
    }

    public function createOrEditMerchantIpConfig(array $input)
    {
        (new Validator)->validateInput('ipConfigCreateOrEdit', $input);

        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            // if proxy auth then only use this.
            (new User\Core)->verifyOtp($input + ['action' => 'ip_whitelist'],
                $this->merchant,
                $this->user,
                $this->mode === Modes::TEST);
        }

        return (new Core)->createOrEditMerchantIpConfig($input);
    }

    public function editOptStatusForMerchantIPConfig(array $input)
    {
        return (new Core)->editOptStatusForMerchantIPConfig($input);
    }

    public function getMerchantNcCount($merchantId)
    {
        $merchantDetails = $this->repo->merchant_detail->findByPublicId($merchantId);

        $statusChangeLogs = (new Merchant\Core)->getActivationStatusChangeLog($merchantDetails->merchant);

        $ncCount = (new MerchantDetailCore())->getStatusChangeCount($statusChangeLogs, Merchant\Detail\Status::NEEDS_CLARIFICATION);

        $this->trace->info(TraceCode::MERCHANT_NC_COUNT,
            [
                'merchant_id'           => $merchantId,
                'nc_count'              => $ncCount,
            ]);

        return ['nc_count' => $ncCount];
    }

    private function removePartnerUsers($users)
    {
        $merchantId = $this->merchant->getId();

        if ($this->auth->getRequestOriginProduct() !== Product::PRIMARY)
        {
            return $users;
        }

        $affiliatedPartnerIds = $this->repo->merchant_access_map->fetchAffiliatedPartnersForSubmerchant($merchantId)->pluck(AccessMap\Entity::ENTITY_OWNER_ID)->toArray();

        if (empty($affiliatedPartnerIds) === true)
        {
            return $users;
        }

        $isSplitzExperimentEnabled = $this->isRemovePartnerUserExperimentEnabled($affiliatedPartnerIds);

        if ($isSplitzExperimentEnabled === true)
        {
            $submerchantUserIds = array_column($users, User\Entity::ID);
            $partnerMerchantsUserIds = $this->repo->merchant_user->fetchMerchantUsersIdsByMerchantIds($affiliatedPartnerIds);

            $filteredUserIds = array_values(array_diff($submerchantUserIds, $partnerMerchantsUserIds));

            $users = array_values(array_filter($users, function($user) use ($filteredUserIds) {
                return (in_array($user[User\Entity::ID], $filteredUserIds) === true);
            }));
        }

        return $users;
    }


    private function isRemovePartnerUserExperimentEnabled($partnerIds)
    {
        $isSplitzExperimentEnabled = false;

        foreach ($partnerIds as $partnerId)
        {
            $properties = [
                'id'            => $partnerId,
                'experiment_id' => $this->app['config']->get('app.remove_partner_user_from_merchant_manage_team_experiment_id'),
            ];

            $isSplitzExperimentEnabled = $this->core()->isSplitzExperimentEnable($properties, 'enable');
            if ($isSplitzExperimentEnabled === true) {
                break;
            }
        }

        return $isSplitzExperimentEnabled;
    }

    public function isPartnershipMerchant(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        // merchant is not a partner
        if ($merchant->isPartner() === false) {

            $isSubMerchant = (new AccessMap\Core())->isSubMerchant($merchantId);

            if ($isSubMerchant === false)
            {

                return ['is_partnership' => false];
            }
        }

        $this->trace->info(
            TraceCode::IS_PARTNERSHIP_MERCHANT,
            [
                'merchantId'     => $merchantId,
                'is_partnership' => "true",
            ]);

        return ['is_partnership' => true];
    }

    public function internalGetMerchantDetails($merchantId)
    {

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        if($merchant !== null){
            $this->merchant = $merchant;
            $this->auth->setMerchant($merchant);
        }

        return  $this->getMerchantData($merchantId);
    }

    /**
     * @param Entity $partner
     * @param Entity $subMerchant
     * @param array  $merchantDetailsInput
     * @param array  $createCapitalApplicationInput
     * @param array  $response
     *
     * @return array
     * @throws BadRequestValidationFailureException
     * @throws IntegrationException
     * @throws Throwable
     */
    public function postProcessForCapitalSubmerchant(
        Entity $partner,
        Entity $subMerchant,
        array $merchantDetailsInput,
        array $createCapitalApplicationInput,
        array $response
    ): array
    {
        $this->trace->info(
            TraceCode::CAPITAL_SUBMERCHANT_POST_PROCESS,
            [
                'submerchant_id'           => $subMerchant->getId(),
                'merchant_detail_input'    => $merchantDetailsInput,
                'create_application_input' => $createCapitalApplicationInput
            ]
        );

        CapitalSubmerchantUtility::addTagAndAttributeForCapitalSubmerchant($partner->getId(), $subMerchant);

        (new Detail\Service)->saveMerchantDetails($merchantDetailsInput, $subMerchant);

        CapitalSubmerchantUtility::createCapitalApplicationForSubmerchant($subMerchant, $partner, $createCapitalApplicationInput, PartnerConstants::ADD_MULTIPLE_ACCOUNT);

        $response[BatchHeader::CONTACT_MOBILE] = $merchantDetailsInput[BatchHeader::CONTACT_MOBILE];

        return $response;
    }

    /**
     * Fetches the capital applications for a given product for sub-merchants of a partner
     *
     * @param $input
     *
     * @return JsonResponse|Response
     * @throws BadRequestValidationFailureException
     * @throws IntegrationException
     * @throws Throwable
     */
    public function getCapitalApplicationsForSubmerchants($input): JsonResponse|Response
    {
        $validator = new Validator();

        $validator->validateInput('capital_submerchant_application_fetch_request', $input);

        $partner = $this->fetchPartner();

        if((new CapitalSubmerchantUtility())->isCapitalPartnershipEnabledForPartner($partner->getId()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        return $this->core()->fetchCapitalApplicationsForSubmerchants($partner, $input);
    }

    public function savePGOSDataToAPI($sqlBinLogData)
    {
        /*
        $validator = new Validator();

        $validator->validateInput('sql_bin_log', $sqlBinLogData);

        if ($sqlBinLogData["database"] === "pgos-prod" and $sqlBinLogData["commit"] === false)
        {
            return;
        }
        */

        $transformers = (new \RZP\Base\Transformer())->getTransformers($sqlBinLogData["table"]);

        foreach ($transformers as $transformer)
        {
            $transformer->transform($sqlBinLogData["data"]);
        }

    }


    public function saveMerchantAuthorizationToPartner(string $merchantId, array $input)
    {
        return $this->core()->saveMerchantAuthorizationToPartner($merchantId, $input);
    }

    public function getMerchantAuthorizationForPartner(string $merchantId, array $input)
    {
        $partnerId = $input[Merchant\Constants::PARTNER_ID];

        return $this->core()->getMerchantAuthorizationForPartner($merchantId, $partnerId);
    }

    /**
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    public function isFeatureEnabledForPartnerOfSubmerchant(string $featureName, string $merchantId = null): array
    {
        (new Feature\Validator())->validateFeatureName($featureName);

        $merchantId = $merchantId ?? $this->merchant->getId();

        $partners = (new Merchant\Core())->fetchAffiliatedPartners($merchantId);

        $partnerService = new \RZP\Models\Partner\Service();

        $merchantApplicationsCore = new MerchantApplications\Core();

        $partner = $partners->filter(function (Merchant\Entity $partner) use ($featureName, $partnerService, $merchantApplicationsCore) {
            if ($partner->isPurePlatformPartner() === true)
            {
                $oauthAppIds = $merchantApplicationsCore->getMerchantAppIds($partner->getId(), ['oauth']);

                foreach ($oauthAppIds as $oauthAppId)
                {
                    if ($partnerService->isFeatureEnabledForPartner($featureName, $partner, $oauthAppId) === true)
                    {
                        return true;
                    }
                }
            }

            return ($partnerService->isFeatureEnabledForPartner($featureName, $partner) === true);
        })->first();

        if (empty($partner) === false)
        {
            return [
                'feature_enabled'               => true,
                MerchantConstants::PARTNER_ID   => $partner->getId()
            ];
        }

        return ['feature_enabled' => false];
    }
}
