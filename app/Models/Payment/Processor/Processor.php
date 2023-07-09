<?php

namespace RZP\Models\Payment\Processor;

use App;
use Request;

use RZP\Services\Shield;
use Neves\Events\TransactionalClosureEvent;
use Route;
use Config;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use RZP\Base\Luhn;
use RZP\Base\Repository;
use RZP\Constants\Country;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Http\RequestHeader;
use RZP\Jobs\TerminalDisable;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Network;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Entity;
use RZP\Jobs\Order\OrderUpdate;
use RZP\Models\Merchant\Merchant1ccConfig\Type;
use RZP\Models\Order\ProductType;
use RZP\Models\Pricing\Fee;
use RZP\Models\RateLimiter\FixedWindowLimiter;
use RZP\Models\Risk;
use RZP\Models\Admin;
use RZP\Models\Order;
use RZP\Models\Offer;
use RZP\Trace\Tracer;
use RZP\Models\Gateway;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Currency;
use RZP\Models\Terminal;
use RZP\Models\Customer\Token;
use RZP\Services\Doppler;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\UpiMandate;
use RZP\Models\CardMandate;
use RZP\Models\BankAccount;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Models\EntityOrigin;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Flow;
use RZP\Jobs\TransferProcess;
use RZP\Constants\Environment;
use RZP\Models\Payment\Metric;
use RZP\Services\KafkaProducer;
use RZP\Models\Payment\Status;
use RZP\Models\UpiMandate\Core;
use RZP\Models\Payment\AuthType;
use RZP\Services\CredcaseSigner;
use RZP\Services\NbPlus\Service;
use RZP\Constants\Entity as E;
use RZP\Base\RepositoryManager;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Methods;
use RZP\Models\Plan\Subscription;
use RZP\Models\Payment\UpiMetadata;
use RZP\Models\Payment\Refund\Speed;
use RZP\Gateway\Base\CardCacheTrait;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Base\PublicCollection;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Transfer\PaymentTransfer;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Locale\Core as LocaleCore;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Transfer\Core as TransferCore;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Payment\OtpPaymentTest;
use CodeOrange\RedisCountingSemaphore\Semaphore;
use RZP\Models\Transfer\Metric as TransferMetric;
use RZP\Models\CardMandate\CardMandateNotification;
use RZP\Models\Transfer\Constant as TransferConstant;
use RZP\Models\UpiMandate\Status as UpiMandateStatus;
use RZP\Models\EMandate\Constants as EmandateConstants;
use RZP\Models\Customer\Token\Constants as TokenConstants;
use RZP\Models\UpiMandate\Frequency as UPIMandateFrequency;
use RZP\Models\UpiMandate\RecurringType as UPIMandateRecurringType;
use RZP\Models\Ledger\ReverseShadow\Payments\Core as ReverseShadowPaymentsCore;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Models\Payment\Method;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Customer\Token\Core as TokenCore;

class Processor
{
    use Authorize;
    use Callback;
    use Capture;
    use Refund;
    use Verify;
    use OtpResend;
    use Topup;
    use FraudDetector;
    use HeadlessOtp;
    use Omnichannel;
    use Payout;
    use Reversal;
    use Transfer;
    use Vpa;
    use AuthorizePush;
    use CardCacheTrait;
    use UpiRecurring;
    use CardPaymentService;
    use NbPlusService;
    use UpiTrait;
    use Card\InputDecryptionTrait;
    use EmandateRecurring;



    const RAZORPAY_ORG_ID = '100000Razorpay';

    /**
     * Callback urls can be hit multiple times by customers.
     * Within certain duration x minutes, we will return payment
     * success or failed when the url is hit again.
     * After that duration, we will simply throw
     * BAD_REQUEST_PAYMENT_ALREADY_PROCESSED payment_processed error.
     */
    const CALLBACK_PROCESS_AGAIN_DURATION = 20;

    /**
     * If payment fails on gateway then we may retry it with a different terminal/gateway.
     */
    const MAX_RETRY_ATTEMPTS = 5;

    /**
     * If a payment gets converted to authorized from failed after 15 minutes of creation of payment,
     * we do not send a notification to the customer.
     */
    const FAILED_TO_AUTHORIZED_NOTIFY_DURATION = 900;

    /**
     * Payment can be cancelled in multiple ways, one of which being
     * by closing the payment pop-up that.
     * However, we only allow payment to be cancelled within a certain duration.
     * A payment created today can only be cancelled within few minutes and
     * not on next day.
     */
    const PAYMENT_CANCEL_TIME_DURATION   = 1800;  // 30 min * 60 sec

    /**
     * We only allow payment to fallback within a certain duration.
     * A payment can fallback only within few minutes
     */
    const PAYMENT_FALLBACK_TIME_DURATION = 600;  // 10 min * 60 sec

    /**
     * We only allow payment to fallback within a certain duration.
     * A payment can fallback only within few minutes
     */
    const PAYMENT_REDIRECT_TO_AUTHORIZE_TIME_DURATION = 1200;  // 20 min * 60 sec

    /**
     * If a payment is async, it can receive a callback for 5 mins after which it is converted to a
     * failed payment
     */
    const ASYNC_PAYMENT_TIMEOUT = 300;

    /**
     * Default UPI collect request expiry time in minutes.
     * Revised: We are changing the default time from 5 minutes to 10.
     */
    const UPI_COLLECT_EXPIRY = 10;

    /**
     * UPI Subscription's mandate expiry extension period.
     */
    const UPI_SUBSCRIPTION_MANDATE_EXPIRY_EXTENSION = 604800;

    /**
     * Minimum payment amount for which mdr should be calculated
     */
    const MIN_MDR_PAYMENT_AMOUNT = 200000;

    /**
     * Timeout to store card details for fallback auth type
     */
    const CARD_CACHE_TTL = 10;

    /**
     * Timeout to store card details for redirect to authorize (in mins)
     */
    const REDIRECT_CACHE_TTL = 20;

    /**
     * Timeout to store redirect authorize response cache
     * Multiplying by 60 since cache put() expect ttl in seconds
     */
    const REDIRECT_CACHE_RESPONSE_TTL = 2 * 60;

    const CACHE_KEY = 'fallback_%s_card_details';

    /**
     * Delayed adding to capture queue,
     * Initiating capture via queue after 15 mins except paysecure
     */

    const CAPTURE_QUEUE_DELAY = 900; // In seconds

    const PAYSECURE_CAPTURE_QUEUE_DELAY = 300; // In seconds

    /**
     * The number of payment transfers to process per merchant in sync via API per
     * hour
     */
    const PAYMENT_TRANSFERS_SYNC_PROCESSING_HOURLY_RATE_LIMIT = 7000;

    /**
     * The semaphore config parameters to use for payment transfers sync processing
     *
     * limit => The number of payment transfers to process per merchant in parallel
     *          using semaphore. This limit is applied to the counting semaphore.
     * retry_interval => The semaphore retry interval in seconds
     * retries => The number of retries
     */
    const PAYMENT_TRANSFERS_SYNC_PROCESSING_DEFAULT_CONFIG = [
        'limit'          => 3,
        'retry_interval' => 0,
        'retries'        => 0,
    ];

    /**
     * Core payment service feature flag
     */
    const CPS_FEATURE_FLAG_PREFIX               = 'cps_gateway_routing';
    const CARD_PAYMENTS_PREFIX                  = 'card_payments_gateway_routing';
    const NB_PLUS_PAYMENTS_PREFIX               = 'nb_plus_payments_gateway_routing';
    const CARD_PAYMENTS_AUTHORIZE_ALL_TERMINALS = 'card_payments_authorize_all_terminals';

    /**
     * Those error codes for which the payment can fallback to 3ds flow if the merchant gets
     * a failure on otp generate flow during with the json v2 payment create request
     */
    const AUTHORIZE_JSON_V2_3DS_FALLBACK_ERRORS = [
        ErrorCode::GATEWAY_ERROR_OTPELF_FAILURE,
        ErrorCode::GATEWAY_ERROR_IVR_UNAVAILABLE,
        ErrorCode::GATEWAY_ERROR_IVR_AUTHENTICATION_NOT_AVAILABLE,
    ];

    /**
     * Card payment service feature flag
     */
    const CARD_PAYMENT_SERVICE_VARIANT_PREFIX          = 'cardps';
    /**
     * 3D Secure international feature flag
     */
    const SECURE_3D_INTERNATIONAL = 'secure_3d_international';

    const FINGERPRINT_MIGRATION_CACHE_KEY = 'fingerprint_migration';

    /**
     * Razorx flag to indicate if a payment should go via PG Router and CPS or just via API service, during Payment creation
     */
    const CARD_PAYMENTS_VIA_PGROUTER = 'card_payments_via_pg_router_v2';

    /**
     * Razorx flag to indicate if a payment should go via PG Router and NB+ Service or just via API service, during Payment creation
     */
    const NETBANKING_PAYMENTS_VIA_PGROUTER = 'netbanking_payments_via_pg_router';

    /**
     * Razorx flag to decide if payments should go via pg-router to UPS.
     */
    const UPS_PAYMENTS_VIA_PGROUTER = 'ups_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a payment should go via PG Router and CPS or just via API service for headless or Rupay, during Payment creation
     */
    const HEADLESS_CARD_PAYMENTS_VIA_PGROUTER = 'headless_card_payments_via_pg_router_v2';

    /**
     * Razorx flag to indicate if a payment should go via PG Router and CPS or just via API service for IVR or OTP during Payment creation
     */
    const IVR_OTP_CARD_PAYMENTS_VIA_PGROUTER = 'ivr_otp_card_payments_via_pg_router_v2';

    /**
     * Razorx flag to indicate if a raas payment should go via PG Router and CPS or just via API service
     */
    const RAAS_CARD_PAYMENTS_VIA_PGROUTER = 'raas_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a oAuth payment should go via PG Router and CPS or just via API service
     */
    const OAUTH_CARD_PAYMENTS_VIA_PGROUTER = 'oauth_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a partner auth payment should go via PG Router and CPS or just via API service
     */
    const PARTNER_AUTH_CARD_PAYMENTS_VIA_PGROUTER = 'partner_auth_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a Diners and AMex payment should go via PG Router and CPS or just via API service
     */
    const DINERS_OR_AMEX_CARD_PAYMENTS_VIA_PGROUTER = 'diners_or_amex_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a JSON V2 should go via PG Router and CPS or just via API service
     */
    const JSON_V2_CARD_PAYMENTS_VIA_PGROUTER = 'json_v2_card_payments_via_pg_router';

     /**
     * Razorx flag to indicate if a marketplace should go via PG Router and CPS or just via API service
     */
    const MARKETPLACE_CARD_PAYMENTS_VIA_PGROUTER = 'marketplace_v2_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a non saved card tokenised Payment should go via PG Router and CPS or just via API service
     */
    const NON_SAVED_TOKENISED_CARD_PAYMENTS_VIA_PGROUTER = 'non_saved_tokenised_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a Fee Bearer Payment should go via PG Router and CPS or just via API service
     */
    const FEE_BEARER_CARD_PAYMENTS_VIA_PGROUTER = 'fee_bearer_card_payments_via_pg_router';

    /**
    * Razorx flag to indicate if a Dynamic Convenience Fee Payment should go via PG Router and CPS or just via API service
    */
    const DYNAMIC_CONVENIENCE_PAYMENTS_VIA_PGROUTER = 'dynamic_convenience_payments_via_pg_router';

    /**
     * Razorx flag to indicate if open wallet Payment should go via PG Router and CPS or just via API service
     */
    const OPEN_WALLET_CARD_PAYMENTS_VIA_PGROUTER = 'open_wallet_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if  Payment links should go via PG Router and CPS or just via API service
     */
    const PAYMENT_LINKS_CARD_PAYMENTS_VIA_PGROUTER = 'payment_links_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a saved card token payment should go via PG Router and CPS or just via API service
     */
    const SAVED_CARD_TOKEN_PAYMENTS_VIA_PGROUTER = 'saved_card_token_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a payment with save option should go via PG Router and CPS or just via API service
     */
    const SAVED_CARD_PAYMENTS_VIA_PGROUTER = 'saved_card_payments_via_pg_router';

    /**
     * Razorx flag to indicate if a payment with save option on custom/razorpayjs should go via PG Router and CPS or just via API service
     */
    const SAVED_CARD_PAYMENTS_VIA_PGROUTER_V2 = 'saved_card_payments_via_pg_router_v2';

    /**
     * Razorx flag to block merchants from re-arch flow
     */
    const BLOCK_MERCHANTS_ON_REARCH_UPS = 'block_merchants_on_rearch_ups';

    /**
     * Razorx flag to block merchants from re-arch flow
     */
    const ALLOW_MERCHANTS_ON_REARCH_UPS_V2 = 'allow_merchants_on_rearch_ups_v2';

    /**
     * Razorx flag to indicate which method and gateway are supported by barricade service
     */
    const BARRICADE_PAYMENT_METHOD = 'barricade_payment_method';
    const BARRICADE_SQS_PUSH       = 'barricade_sqs_push';
    const BARRICADE_PAYMENT_GATEWAY = 'barricade_supported_gateway';
    const BARRICADE_AUTHORIZE_VERIFY_CARD_GATEWAY = 'barricade_authorize_verify_card_gateway';
    const BARRICADE_QR_PAYMENT_VERIFY = 'barricade_qr_payment_verify';
    const BARRICADE_UPI_PAYMENT_VERIFY ='barricade_upi_transfer_payment_verify';
    const BARRICADE_BANK_PAYMENT_VERIFY = 'barricade_bank_transfer_payment_verify';
    const PUSH_PAYMENT_VERIFY = "push_payment_verify";

    /**
     * User consent flag indicates whether the user has given consent to tokenise
     * the card or not.
     */
    const USER_CONSENT_FOR_TOKENISATION = 'user_consent_for_tokenisation';

    /**
     * save flag indicates whether new card needs to be saved
     */
    const SAVE = 'save';

    /**
     * Razorx flag to indicate if a s2s payment should go via PG Router and CPS or just via API service, during Payment creation
     */
    const S2S_CARD_PAYMENTS_VIA_PGROUTER = 's2s_card_payments_via_pg_router_v2';

    /**
     * Razorx flag to indicate if a s2s payment should go via PG Router and CPS or just via API service for headless and rupay, during Payment creation
     */
    const HEADLESS_S2S_CARD_PAYMENTS_VIA_PGROUTER = 'headless_s2s_card_payments_via_pg_router_v2';

    /**
     * Razorx flag to indicate if a s2s payment should go via PG Router and CPS or just via API service for IVR or OTP during Payment creation
     */
    const S2S_IVR_OTP_CARD_PAYMENTS_VIA_PGROUTER = 'ivr_otp_s2s_card_payments_via_pg_router_v2';

    /**
     * Razorx flag to block merchant on re-arch flow for payments card
     */
    const BLOCK_MERCHANTS_ON_REARCH_CPS = 'block_merchant_on_rearch_cps';

    const CAPTURE_VERIFY_METRO_TOPIC        = 'rearch-capture-verify';

    const SODEXO = 'sodexo';

    const FORCE_AUTHORIZE_FAILED_ALLOW_GATEWAYS = [
        Payment\Gateway::KOTAK_DEBIT_EMI,
        Payment\Gateway::FULCRUM,
    ];

    /**
     * @var Merchant\Entity
     */
    protected $merchant;
    protected $trace;

    /**
     * @var Payment\Entity
     */
    protected $payment;

    /**
     * @var Terminal\Entity
     */
    protected $terminal;

    /**
     * This should be an array and not a collection
     * @var array
     */
    protected $selectedTerminals;
    protected $mode;
    /**
     * @var RepositoryManager
     */
    protected $repo;
    protected $orderRepo;
    protected $paymentRepo;
    protected $app;
    protected $mutex;
    protected $request;
    protected $methods;
    /**
     * @var Payment\Refund\Entity
     */
    protected $refund;
    /**
     * @var Order\Entity
     */
    protected $order;
    /**
     * @var Offer\Entity
     */
    protected $offer;

    /**
     * @var UpiMandate\Entity
     */
    protected $upiMandate;

    /**
     * @var Subscription\Entity
     */
    protected $subscription;

    protected $receiver;
    protected $segment;

    protected $verifyRefundStatus;

    protected $emandateDescError;

    /**
     * Api Route instance
     *
     * @var \RZP\Http\Route
     */
    protected $route;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $ba;

    protected $cache;

    protected $secureCacheDriver;

    protected $sendDopplerFeedback = true;

    /**
     * Array of error codes upon which paypal maybe suggested as a backup option
     */

    protected static $errorCodesToAllowPaypal = array(
        ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_GATEWAY,
        ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK,
        ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED,
        ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_NOT_PERMITTED_TXN,
        ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED,
        ErrorCode::BAD_REQUEST_NON_3DS_INTERNATIONAL_NOT_ALLOWED
    );

    /**
     * Array of backup methods for retry of failed international card payments
     */
    protected static $retryMethodsForIntl = array(
        Methods\Entity::PAYPAL
    );


    protected static $upiRearchRoutes = [
        'payment_create_upi',
        'payment_create_ajax'
    ];

    public function __construct(Merchant\Entity $merchant)
    {
        $this->app  = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->mode = $this->app['rzp.mode'];
        $this->repo = $this->app['repo'];

        $this->merchant = $merchant;
        $this->methods = $merchant->getMethods();

        $this->checkMerchantPermissions();

        $this->paymentRepo = $this->repo->payment;

        $this->orderRepo = $this->repo->order;

        $this->request = $this->app['request'];

        $this->mutex = $this->app['api.mutex'];

        $this->cache = $this->app['cache'];

        $this->route = $this->app['api.route'];

        $this->segment = $this->app['segment'];

        $this->ba = $this->app['basicauth'];

        // Only used in hdfc verify refund flow
        $this->verifyRefundStatus = null;

        $this->secureCacheDriver = $this->getDriver();
    }

    public function flushPaymentObjects()
    {
        $this->order        = null;
        $this->offer        = null;
        $this->payment      = null;
        $this->refund       = null;
        $this->type         = null;
        $this->subscription = null;
    }


    /*
     * | Method | Flow   | Network          | Experimental Dependency
     * <--------------------------------------------------->
     * | Card   | API    | Visa/MC          | If experiment is enabled
     * |        |        |                  | then api flow selected
     * | Card   | rearch | Visa/MC/UnionPay | If experiment is disabled
     * |        |        |                  | and network is UnionPay
     */

    /**
     * @return bool
     * Controls rearch flow proxy for Malaysia
     */
    private function canRouteThroughRearchFlowForMY(array $input)
    {
        // Always true for current product state except for test mode in production
        if (app()->isEnvironmentProduction() === true  and ($this->mode === Mode::TEST))
        {
            return false;
        }

        if (isset($input[Payment\Entity::SUBSCRIPTION_ID]) === true or (isset($input['recurring']) and $input['recurring'] == '1'))
        {
            return false;
        }

        /*
         * Using this experiment we are controlling the flow whether we need to pass the Visa/MC payments via
         * API or PG-Router.
         */
        $properties = [
            'id'            => $this->merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.redirect_malaysia_card_payments_via_api'),
        ];

        $isExpEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');


        /*
         * For Union Pay as a network: we are expected to send payments traffic to Umobile gateway which has been integrated
         * in Pg router.
         * For MC,Visa etc. as a network: This is supposed to go through 3DS 2.0 Integration which is not integrated in rearch flow yet, hence
         * it need to go through via Api Monolith
         * For request where card number is not present: In those cases, currently it is failing at validation step in pg router, we
         * will direct that to pg router and not changing the default behaviour of the MY Payments flow.
         */
        if (($isExpEnabled === true) &&
            ($this->isNetworkUnionPay($input) === false))
        {
           return false;
        }

        return true;
    }

    private function isOpgspImportMerchant(): bool
    {
        return $this->merchant->isOpgspImportEnabled();
    }

    private function canRouteThroughRearchFlow(array & $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();
        try
        {
            $result = '';
            $currentRouteName = $this->route->getCurrentRouteName();
            $merchant = $this->app['basicauth']->getMerchant();

            if ($merchant->getCountry() === 'MY')
            {
                return $this->canRouteThroughRearchFlowForMY($input);
            }

            if($this->merchant->isFeatureEnabled(Feature::UPIQR_V1_HDFC) === true)
            {
                return false;
            }

            /*
             * Rearch criteria
             * 1. Route should be payment/create/ajax
             * 2. Method should be card
             * 3. Non recurring payment
             * 4. Regular card payment
             * 5. Merchant shouldn't be fee bearer
             * 6. Capture queue should be implemented in the second ramp
             */
            if ((app()->isEnvironmentProduction() === true) and
                ($this->mode === Mode::TEST))
            {
                return false;
            }

            if ((app()->isEnvironmentQA() === true) and
                ($this->mode === Mode::LIVE))
            {
                return false;
            }

            if ($this->isRearchBVTRequest() === true)
            {
                if(empty($input[Payment\Entity::TOKEN]) === false)
                {
                    $this->preProcessTokenisedPaymentRequestForRearch($input, $merchant);
                }
                return true;
            }

            if (($this->route->isRearchRoute($currentRouteName) == false) or
                (empty($input[Payment\Entity::METHOD]) === true) or
                ($input[Payment\Entity::METHOD] !== Payment\METHOD::CARD) or
                (empty($input[Payment\Entity::RECURRING]) === false) or
                (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === false) or
                (empty($input[Payment\Entity::INVOICE_ID]) === false) or
                (empty($input[Payment\Entity::TOKEN_ID]) === false) or
                (empty($input[Payment\Entity::OFFER_ID]) === false) or
                (empty($input[Payment\Entity::CHARGE_ACCOUNT]) === false) or
                ((empty($input['reward_ids']) === false) and ($merchant->getId() !== '2aTeFCKTYWwfrF')) or
                ($merchant->isRazorpayOrgId() === false) or
                ((empty($input[Payment\Entity::CARD][Card\Entity::TOKENISED]) === false) and
                    empty($input[Payment\Entity::CARD][Card\Entity::CRYPTOGRAM_VALUE]) === true) or
                (empty($input['application']) === false && $input['application'] === 'visasafeclick') or
                ((isset($input[Payment\Method::CARD][Card\Entity::CVV]) === false) and
                    ($merchant->isFeatureEnabled('vsc_authorization') === true)))
            {
                if (($this->route->isRearchRoute($currentRouteName) === true) and
                    (empty($input[Payment\Entity::METHOD]) === false and
                    $input[Payment\Entity::METHOD] === Payment\METHOD::CARD))
                {
                    $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                        'reason' => "input",
                        'merchant_id' => $merchant->getId(),
                    ]);
                }
                return false;
            }

            $order = null;
            if (empty($input[Payment\Entity::ORDER_ID]) === false)
            {
                $order = $this->fetchOrderFromInput($input);

                $orderTransfers = $this->repo->transfer->fetchBySourceTypeAndIdAndMerchant(E::ORDER,
                    $order->getId(), $this->merchant);

                // offers are not supported in initial ramp
                if ((empty($order) === false) and
                    (($order->hasOffers() === true) or
                        ($order->isDiscountApplicable() === true) or
                        ($order->getProductId() !== null and $order->getProductType() !== ProductType::PAYMENT_LINK_V2) or
//                        ($order->getFeeConfigId() !== null) or
                        ($order->invoice !== null)))
                {
                    $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                        'reason' => "offers",
                        'merchant_id' => $merchant->getId(),
                    ]);

                    return false;
                }

                if ((empty($orderTransfers) === false) and
                    (count($orderTransfers) > 0))
                {
                    $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                        'reason' => "order_transfers",
                        'merchant_id' => $merchant->getId(),
                    ]);

                    return false;
                }
            }

            if (($input[Payment\Entity::METHOD] == Payment\METHOD::CARD) and
                ($merchant->isFeatureEnabled('skip_cvv') === true))
            {
                $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                    'reason' => "merchant_feature",
                    "feature_name" => "skip_cvv",
                    'merchant_id' => $merchant->getId(),
                ]);
                return false;
            }

            if (($input[Payment\Entity::METHOD] == Payment\METHOD::CARD) and
                ($input[Payment\Entity::PROVIDER] == self::SODEXO))
            {
                return true;
            }

            if ((empty($input['currency']) === false) and
                ($input['currency'] !== Currency\Currency::INR))
            {
                $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                    'reason' => "non_inr_currency",
                    'merchant_id' => $merchant->getId(),
                ]);
                return false;
            }

            //Ultimate flag to stop re-arch traffic, merchants added in this flag will be blocked from CPS re-arch traffic
            $result = $this->app->razorx->getTreatment($merchant->getId(), self::BLOCK_MERCHANTS_ON_REARCH_CPS, $this->mode);
            if ($result === 'on') {
                $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                    'reason' => "blocked_merchant",
                    'merchant_id' => $merchant->getId(),
                ]);
                return false;
            }

            //checking it here since we don't have to call the exp. twice
            $feeBearerResult = $this->app->razorx->getTreatment($merchant->getId(), self::FEE_BEARER_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);
            $isMerchantCustomerOrDynamicFeeBearer = $merchant->isFeeBearerCustomerOrDynamic();

            if ($merchant->isFeeBearerDynamic() === true && $feeBearerResult === 'on') {
                // Re-calculates fees on the amount, using a dummy payment creation flow.
                // This sets re-calculated fee and amount value (in paise) in $input.
                // Hence, saving original amount as amount.
                $feesArray = $this->processAndReturnFees($input);
                $input['amount'] = $feesArray['original_amount'];

                if (isset($feesArray['customer_fee']) === true) {
                    $input['convenience_fee'] = $feesArray['customer_fee'];

                    $input['convenience_fee_gst'] = $feesArray['customer_fee_gst'];
                }
            }


            //Check for saved card token payments
            if(empty($input[Payment\Entity::TOKEN]) === false)
            {
                $tokenId = $input[Payment\Entity::TOKEN];
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::SAVED_CARD_TOKEN_PAYMENTS_VIA_PGROUTER, $this->mode);

                if ($result === 'on' && ($feeBearerResult === "on" || $isMerchantCustomerOrDynamicFeeBearer === false))
                {
                    try {
                        // First fetch the relevant customer (global or local)
                        list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp(
                            $input, $merchant, false);
                        if ($customer !== null)
                        {
                            $token = (new Token\Core)->getByTokenIdAndCustomer($tokenId, $customer);
                        }
                        else
                        {
                            $token = (new Token\Core)->getByTokenIdAndMerchant($tokenId, $merchant);
                        }

                        if ($token !== null && $token->isLocal() && $token->isRecurring() === false)
                        {
                            $this->trace->info(
                                TraceCode::PAYMENT_PROCESS_FROM_SAVED_LOCAL,
                                [
                                    'token_id' => $input[Payment\Entity::TOKEN]
                                ]);

                            $card = $this->repo->card->fetchForToken($token);

                            //check if card is not null
                            if(empty($card) === true)
                            {
                                $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                                    'reason' => "token_card_empty",
                                    'merchant_id' => $merchant->getId(),
                                ]);

                                return false;
                            }
                            if ($card->getVault() === Card\Vault::PROVIDERS || $card->getVault() === Card\Vault::AXIS)
                            {
                                $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                                    'reason' => "vault_providers_or_axis",
                                    'merchant_id' => $merchant->getId(),
                                ]);

                                return false;
                            }
                            if ($card->isNetworkTokenisedCard() === true)
                            {
                                $this->trace->info(TraceCode::TOKENISED_CARD_PAYMENT_ROUTING_INFO, [
                                    'tokenId'       => $token->getId(),
                                    'isGlobal'      => $token->isGlobal(),
                                    'routedThrough' => 'tokenisedCard',
                                    'cardInfo'      => [
                                        'issuer'    => $card->getIssuer(),
                                        'network'   => $card->getNetworkCode(),
                                        'type'      => $card->getType(),
                                    ],
                                ]);
                                $cryptogram = (new Card\CardVault)->fetchCryptogramForPayment($card->getVaultToken(), $merchant);
                                $cardInput = $this->getCardInputForRearch($cryptogram, $card, $input, $token);
                                //modify input for cards
                                $input[Payment\Entity::CARD] = $cardInput;
                                $input[Payment\Entity::TOKEN] = $token->getId();
                                return true;
                            }
                        } else {
                            $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                                'reason' => "saved_card_not_network_tokenized",
                                'merchant_id' => $merchant->getId(),
                            ]);
                            return false;
                        }
                    }
                    catch (\Throwable $e)
                    {
                        //If anything fails while using saved card token then fallback to api flow
                        $this->trace->traceException(
                            $e,
                            Trace::CRITICAL,
                            TraceCode::REARCH_CRITIERIA_SAVE_CARD_CHECK_FAILED,
                            []);

                        return false;
                    }
                }
                return false;
            }

            //transaction from cryptogram value
            $input[Payment\Entity::CARD][Card\Entity::NUMBER] = str_replace(' ', '', $input[Payment\Entity::CARD][Card\Entity::NUMBER]);
            $iinId = substr($input[Payment\Entity::CARD][Card\Entity::NUMBER], 0, 6);
            if ($this->isPaymentViaTokenisedCard($input))
            {
                $tokenIin = substr($input[Payment\Entity::CARD][Card\Entity::NUMBER], 0, 9);
                $iinId = Card\IIN\IIN::getTransactingIinforRange($tokenIin) ?? $iinId;
            }

            $iin = $this->repo->iin->find($iinId);

            // IIN not available
            if (empty($iin) === true)
            {
                $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                    'reason' => "iin_not_available",
                    'merchant_id' => $merchant->getId(),
                ]);
                return false;
            }

            $supportedNetworks = [
                Card\Network::MC,
                Card\Network::VISA,
                Card\Network::RUPAY,
                Card\Network::DICL,
                Card\Network::AMEX,
            ];

            if ((($iin->isAmex() === false) and
                    IIN\IIN::isInternational($iin->getCountry(), $merchant->getCountry()) === true) or
                (in_array($iin->getNetworkCode(), $supportedNetworks, true) === false))
            {
                return false;
            }

            $supportedFlows = [
                Card\IIN\Flow::_3DS,
                Card\IIN\Flow::HEADLESS_OTP,
                Card\IIN\Flow::IVR,
                Card\IIN\Flow::OTP,
                // magic is mainly used for checkout flows and has no impact on payment flows
                Card\IIN\Flow::MAGIC,
                // Pin is depricated
                Card\IIN\Flow::PIN,
                // Ifram is mainly used for checkout flows and has no impact on payment flows
                Card\IIN\Flow::IFRAME,
            ];

            $enabledFlows = Card\IIN\Flow::getEnabledFlows($iin->getFlows());

            foreach ($enabledFlows as $flow)
            {
                if (in_array($flow, $supportedFlows, true) === false)
                {
                    return false;
                }
            }

            if ($merchant->isFeeBearerCustomerOrDynamic() === true )
            {
                $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                    'reason' => "check_for_customer_or_dynamic_fee_bearer_rearch",
                    'merchant_id' => $merchant->getId(),
                    '$feeBearerResult' => $feeBearerResult,
                ]);
                return ($feeBearerResult === 'on');
            }

            if ((app()->runningUnitTests() === true) and
                ((bool) Admin\ConfigKey::get(Admin\ConfigKey::PG_ROUTER_SERVICE_ENABLED, false) === false))
            {
                return false;
            }

            if (empty($input[Payment\Entity::SAVE]) === false)
            {
                if ($iin->getNetworkCode() === Card\Network::DICL)
                {
                    return false;
                }

                $library = null;
                if((isset($input['_']) === true) and
                    (isset($input['_']['library']) === true))
                {
                    $library = $input['_']['library'];
                }

                if ($library !== null && $library !== Payment\Analytics\Metadata::CHECKOUTJS)
                {
                    if ($library === Payment\Analytics\Metadata::CUSTOM || $library === Payment\Analytics\Metadata::RAZORPAYJS)
                    {
                        $result = $this->app->razorx->getTreatment($merchant->getId(), self::SAVED_CARD_PAYMENTS_VIA_PGROUTER_V2, $this->mode);

                        return ($result === 'on');
                    }

                    return false;
                }

                $result = $this->app->razorx->getTreatment($merchant->getId(), self::SAVED_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }

            if ($merchant->isFeatureEnabled('openwallet') === true)
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::OPEN_WALLET_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }

            if (empty($order) === false and $order->getProductType() === ProductType::PAYMENT_LINK_V2)
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::PAYMENT_LINKS_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }

            if ($merchant->isFeatureEnabled('raas') === true)
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::RAAS_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }

            if ($this->isPaymentViaTokenisedCard($input))
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::NON_SAVED_TOKENISED_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }

            if (($merchant->isFeatureEnabled(Feature::JSON_V2) === true))
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::JSON_V2_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }


            if ($merchant->isFeatureEnabled(Feature::MARKETPLACE) === true)
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::MARKETPLACE_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }


            if ($this->ba->getOAuthClientId() !== null)
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::OAUTH_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }

            if ($this->ba->isPartnerAuth() === true)
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::PARTNER_AUTH_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);

                return ($result === 'on');
            }

            if ($this->app['basicauth']->isPrivateAuth() === false)
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::CARD_PAYMENTS_VIA_PGROUTER, $this->mode);
            }
            else
            {
                $result = $this->app->razorx->getTreatment($merchant->getId(), self::S2S_CARD_PAYMENTS_VIA_PGROUTER, $this->mode);
            }

            return ($result === 'on');

        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REARCH_CRITIERIA_CHECK_FAILED,
                []);
        }

        return false;
    }

    protected function getCardInputForRearch($cryptogram, $card, $input,$token)
    {
        $input = [
            Card\Entity::NUMBER                 => $cryptogram['token_number'] ?? $cryptogram['card']['number'],
            Card\Entity::NAME                   => $card->getName(),
            Card\Entity::EXPIRY_MONTH           => $cryptogram['token_expiry_month'] ?? null,
            Card\Entity::EXPIRY_YEAR            => $cryptogram['token_expiry_year'] ?? null,
            Card\Entity::LAST4                  => $card->getLast4(),
            Card\Entity::CRYPTOGRAM_VALUE       => $cryptogram['cryptogram_value'] ?? null,
            Card\Entity::TOKENISED              => true,
            Card\Entity::VAULT                  => "rzpvault",
            Card\Entity::CVV                    => $input['card']['cvv'] ?? null,
            Card\Entity::TOKEN_PROVIDER         => 'Razorpay'
        ];

        // override empty cvv with dummy cvv for cvvless
        if (Card\Network::getFullName(Network::VISA) === $card->getNetwork() &&
            $input[Card\Entity::TOKENISED] === true &&
            empty($input[Card\Entity::CVV]) === true) {

            $input[Card\Entity::CVV ] = "123";

            $this->trace->info(
                TraceCode::CVV_OPTIONAL,
                [
                    'message'       => 'Setting cvv to dummy value',
                ]
            );
        }

        if ( $card->getVault() === Card\Vault::HDFC)
        {
            $input[Card\Entity::EXPIRY_MONTH ] = $cryptogram['card']['expiry_month'] ?? null;
            $input[Card\Entity::EXPIRY_YEAR ] =  $cryptogram['card']['expiry_year'] ?? null;
        }

        if ($card->getVault() === Card\Vault::AXIS) {
            $input[Card\Entity::NUMBER] = Card\Entity::DUMMY_AXIS_TOKENHQ_CARD;
        }

        if(isset($cryptogram["cvv"]) === true && Card\Network::getFullName(Network::AMEX) === $card->getNetwork())
        {
            $input["cvv"] = $cryptogram["cvv"];
        }

        if (($this->merchant->isFeatureEnabled(Feature::RAAS)) === true)
        {
            $input = $this->getAdditionalOptimizerCardInputForRearch($token,$input);
        }

        return $input;
    }

    /* Optimizer gateways required additional network token details, like
     * PAR, TRN, TRID for payment processing
     */
    protected function getAdditionalOptimizerCardInputForRearch($token,$input)
    {
        if ($input[E::TOKENISED] === false)
        {
            return $input;
        }

        if (empty($this->app) === true)
        {
            $this->app = App::getFacadeRoot();
        }

        $cardInput = $input[E::CARD];

        // fetch network token associated with payment
        $networkToken = (new TokenCore())->fetchToken($token, false);

        assertTrue(empty($networkToken) === false);

        $tokenisedTerminalId = $networkToken[0][E::TOKENISED_TERMINAL_ID] ?? '';
        $tokenisedTerminal = $this->app['terminals_service']->fetchTerminalById($tokenisedTerminalId);

        $trid = '';

        assertTrue(empty($tokenisedTerminal) === false);

        if (empty($tokenisedTerminal) === false)
        {
            switch ($cardInput[E::NETWORK_CODE])
            {
                case Card\Network::MC:
                    $trid = $tokenisedTerminal[E::GATEWAY_MERCHANT_ID];
                    break;

                case Card\Network::RUPAY:
                    $trid = $tokenisedTerminal[E::GATEWAY_MERCHANT_ID2];
                    break;

                case Card\Network::AMEX:
                case Card\Network::VISA:
                    $trid = $tokenisedTerminal[E::GATEWAY_TERMINAL_ID];
                    break;

                default:
                    break;
            }
        }
        $par = $networkToken[0][E::PROVIDER_DATA][E::PAYMENT_ACCOUNT_REFERENCE] ?? '';
        $trn = $networkToken[0][E::PROVIDER_DATA][E::TOKEN_REFERENCE_NUMBER] ?? '';
        $nri = $networkToken[0][E::PROVIDER_DATA][E::NETWORK_REFERENCE_ID] ?? '';

        $input[E::PAYMENT_ACCOUNT_REFERENCE ]= $par;
        $input[E::TOKEN_REFERENCE_NUMBER ]=  $trn;
        $input[E::TOKEN_REFERENCE_ID ]= $trid;
        $input[E::NETWORK_REFERENCE_ID ]=  $nri;

        return $input;
    }

    private function preProcessTokenisedPaymentRequestForRearch(&$input, $merchant) : bool
    {
        $tokenId = $input[Payment\Entity::TOKEN];

        try {
            // First fetch the relevant customer (global or local)
            list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp(
                $input, $merchant, false);
            if ($customer !== null)
            {
                $token = (new Token\Core)->getByTokenIdAndCustomer($tokenId, $customer);
            }
            else
            {
                $token = (new Token\Core)->getByTokenIdAndMerchant($tokenId, $merchant);
            }

            if ($token !== null && $token->isLocal() && $token->isRecurring() === false)
            {
                $this->trace->info(
                    TraceCode::PAYMENT_PROCESS_FROM_SAVED_LOCAL,
                    [
                        'token_id' => $input[Payment\Entity::TOKEN]
                    ]);

                $card = $this->repo->card->fetchForToken($token);

                //check if card is not null
                if(empty($card) === true)
                {
                    $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                        'reason' => "token_card_empty",
                        'merchant_id' => $merchant->getId(),
                    ]);

                    return false;
                }
                if ($card->getVault() === Card\Vault::PROVIDERS || $card->getVault() === Card\Vault::AXIS)
                {
                    $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                        'reason' => "vault_providers_or_axis",
                        'merchant_id' => $merchant->getId(),
                    ]);

                    return false;
                }
                if ($card->isNetworkTokenisedCard() === true)
                {
                    $this->trace->info(TraceCode::TOKENISED_CARD_PAYMENT_ROUTING_INFO, [
                        'tokenId'       => $token->getId(),
                        'isGlobal'      => $token->isGlobal(),
                        'routedThrough' => 'tokenisedCard',
                        'cardInfo'      => [
                            'issuer'    => $card->getIssuer(),
                            'network'   => $card->getNetworkCode(),
                            'type'      => $card->getType(),
                        ],
                    ]);
                    $cryptogram = (new Card\CardVault)->fetchCryptogramForPayment($card->getVaultToken(), $merchant);
                    $cardInput = $this->getCardInputForRearch($cryptogram, $card, $input, $token);
                    //modify input for cards
                    $input[Payment\Entity::CARD] = $cardInput;
                    $input[Payment\Entity::TOKEN] = $token->getId();
                    return true;
                }
            } else {
                $this->trace->info(TraceCode::REARCH_ROUTING_CRITERIA_FAILED_REASON, [
                    'reason' => "saved_card_not_network_tokenized",
                    'merchant_id' => $merchant->getId(),
                ]);
                return false;
            }
        }
        catch (\Throwable $e)
        {
            //If anything fails while using saved card token then fallback to api flow
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REARCH_CRITIERIA_SAVE_CARD_CHECK_FAILED,
                []);

            return false;
        }

        return false;
    }


    private function canRouteThroughNbPlusRearchFlow($input): bool
    {
        $shouldRoute = false;

        $currentRouteName = $this->route->getCurrentRouteName();
        /**
         * @var Merchant\Entity $merchant
         */
        $merchant = $this->app['basicauth']->getMerchant();


        if ((app()->isEnvironmentProduction() === true) and
            ($this->mode === Mode::TEST))
        {
            return false;
        }

        if (($this->route->isNbRearchRoute($currentRouteName) === true) and
            ($merchant->isRazorpayOrgId() === true) and
            ($merchant->isFeeBearerPlatform() === true) and
            (empty($input[Payment\Entity::METHOD]) === false) and
            ($input[Payment\Entity::METHOD] === Payment\METHOD::NETBANKING) and
            (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === true) and
            (empty($input[Payment\Entity::INVOICE_ID]) === true) and
            (empty($input[Payment\Entity::PAYMENT_LINK_ID]) === true) and
            (empty($input['reward_ids']) === true) and
            ($merchant->isFeatureEnabled('raas') === false) and
            ($merchant->isFeatureEnabled('openwallet') === false) and
            (empty($input[Payment\Entity::META]) === true) and
            (empty($input['signature']) === true) and
            (empty($input[Payment\Entity::BILLING_ADDRESS]) === true) and
            (empty($input[Payment\Entity::BANK]) === false))
        {
            if (empty($input[Payment\Entity::ORDER_ID]) === true)
            {
                $shouldRoute = false; // disabling for now till pg-router raise the fix
            }
            else
            {
                $order = $this->fetchOrderFromInput($input);

                // offers are not supported in initial ramp
                if ((empty($order) === false) and
                    (($order->hasOffers() === false) and
                        ($order->isDiscountApplicable() === false) and
                        ($order->getProductId() === null) and
                        ($order->getFeeConfigId() === null))
                )
                {
                    $shouldRoute = true;

                    if ($this->ba->isPartnerAuth() === true)
                    {
                        $shouldRoute = false;
                        $featureFlag = self::NETBANKING_PAYMENTS_VIA_PGROUTER . '_partner';
                        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(), $featureFlag, $this->mode);

                        $this->trace->info(TraceCode::PAYMENTS_REARCH_RAZORX_EVALUATION, [
                            'variant'      => $variant,
                            'feature_flag' => $featureFlag,
                        ]);

                        if ($variant === 'on')
                        {
                            $shouldRoute = true;
                        }
                    }

                    if ($this->ba->getOAuthClientId() !== null)
                    {
                        $shouldRoute = false;
                        $featureFlag = self::NETBANKING_PAYMENTS_VIA_PGROUTER . '_oauth';
                        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(), $featureFlag, $this->mode);

                        $this->trace->info(TraceCode::PAYMENTS_REARCH_RAZORX_EVALUATION, [
                            'variant'      => $variant,
                            'feature_flag' => $featureFlag,
                        ]);

                        if ($variant === 'on')
                        {
                            $shouldRoute = true;
                        }
                    }

                    if ($this->route->getCurrentRouteName() === "payment_create_private_json")
                    {
                        // experiment for payment_create_private_json route on the basis of merchant ID

                        $shouldRoute = false;
                        $featureFlag = self::NETBANKING_PAYMENTS_VIA_PGROUTER . '_create_json';
                        $variant = $this->app->razorx->getTreatment($merchant->getId(), $featureFlag, $this->mode);

                        $this->trace->info(TraceCode::PAYMENTS_REARCH_RAZORX_EVALUATION, [
                            'variant'      => $variant,
                            'feature_flag' => $featureFlag,
                        ]);

                        if ($variant === 'on')
                        {
                            $shouldRoute = true;
                        }
                    }
                }
            }
        }

        if ($shouldRoute === false)
        {
            return false;
        }

        $featureFlag = self::NETBANKING_PAYMENTS_VIA_PGROUTER . '_disable_mid';
        $variant = $this->app->razorx->getTreatment($merchant->getId(), $featureFlag, $this->mode);

        $this->trace->info(TraceCode::PAYMENTS_REARCH_RAZORX_EVALUATION, [
            'variant'      => $variant,
            'feature_flag' => $featureFlag,
        ]);

        if ($variant === 'disable')
        {
            return false;
        }

        if ($merchant->isMarketplace() === true)
        {
            $featureFlag = self::NETBANKING_PAYMENTS_VIA_PGROUTER . '_marketplace';
            $variant = $this->app->razorx->getTreatment($merchant->getId(), $featureFlag, $this->mode);

            $this->trace->info(TraceCode::PAYMENTS_REARCH_RAZORX_EVALUATION, [
                'variant'      => $variant,
                'feature_flag' => $featureFlag,
            ]);

            if ($variant !== 'on')
            {
                return false;
            }
        }

        if ((app()->runningUnitTests() === true) and
            ((bool) Admin\ConfigKey::get(Admin\ConfigKey::PG_ROUTER_SERVICE_ENABLED, false) === false))
        {
            return false;
        }

        if (Netbanking::banksRoutedAlwaysThroughNbRearch($input[Payment\Entity::BANK]) === true)
        {
            return true;
        }

        $featureFlag = self::NETBANKING_PAYMENTS_VIA_PGROUTER;
        if ($this->isDarkRequest() === true)
        {
            $featureFlag .= '_dark';
        }

        $this->trace->info(TraceCode::PAYMENT_CREATE_ON_PUBLIC, [
            'flag' => $featureFlag,
        ]);

        $result = $this->app->razorx->getTreatment($input[Payment\Entity::BANK], $featureFlag, $this->mode);

        return ($result === 'on');
    }

    private function canRouteThroughUpsRearchFlow($input): bool
    {
        try
        {
            $currentRouteName = $this->route->getCurrentRouteName();
            $merchant = $this->app['basicauth']->getMerchant();

            /*
             * Rearch criteria
             * 1. Route should be payment/create/upi
             * 2. Method should be upi
             * 3. Non recurring payment
             * 4. Non TPV payment
             * 5. Merchant shouldn't be fee bearer
             * 6. Capture queue should be implemented in the second ramp
             * 7. Non BQR / UPIQR / OTM / GPayCard / International / Upi transfer
             */

            // test mode payments are not supported
            if ((app()->isEnvironmentProduction() === true) and
                ($this->mode === Mode::TEST))
            {
                return false;
            }

            if ($this->isUpiPaymentReArchBVTRequest() === true)
            {
                return true;
            }

            if ((app()->isEnvironmentQA() === true) and
                ($this->mode === Mode::LIVE))
            {
                return false;
            }

            $customCheckResults = $this->performCustomChecksToRouteViaUpsRearchFlow($input, $merchant, $currentRouteName);

            if ($customCheckResults['route_via_ups'] === false)
            {
                $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_ROUTING_CRITERIA_FAILED_REASON,
                    [
                        'merchant_id'   => $merchant->getId(),
                        'reason'        => $customCheckResults['reason'],
                    ]
                );

                return false;
            }

            if ((app()->runningUnitTests() === true) and
                ((bool) Admin\ConfigKey::get(Admin\ConfigKey::PG_ROUTER_SERVICE_ENABLED, false) === false))
            {
                return false;
            }

            /*
            //Ultimate flag to stop re-arch traffic, merchants added in this flag will be blocked from UPS re-arch traffic
            $result = $this->app->razorx->getTreatment($merchant->getId(), self::BLOCK_MERCHANTS_ON_REARCH_UPS,
                $this->mode);
            if ($result === 'on') {
                return false;
            }
            */

            $library = $input['_']['library'] ?? '';

            $library = strtolower($library);

            $featureFlag = self::ALLOW_MERCHANTS_ON_REARCH_UPS_V2 . '_' . $library;

            // Allow re-arch traffic, merchants added in this flag will be routes via UPS re-arch
            $result = $this->app->razorx->getTreatment($merchant->getId(), $featureFlag,
            $this->mode);

            $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_PAYMENTS_RAZORX_VARIANT,
            [
                'merchant_id'           => $merchant->getId(),
                'feature_flag'          => $featureFlag,
                'merchant_ramp_variant' => $result,
            ]);

            if (str_starts_with($result, 'on') === true) {
                return true;
            }

            /*
            // Allow certain percentage of overall UPI re-arch traffic
            $result = $this->app->razorx->getTreatment($this->app['request']->getTaskId(),
                self::UPS_PAYMENTS_VIA_PGROUTER, $this->mode);
            if ($result === 'ups')
            {
                return true;
            }
            */

            return false;
        }
        catch(\Throwable $e)
        {
             $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::REARCH_CRITIERIA_CHECK_FAILED,
                    []);
        }

        return false;
    }

    /**
     * Performs Custom checks to avoid routing traffic to UPS Rearch Flow
     *
     * @param array $input
     * @param Entity $merchant
     * @param string $currentRouteName
     * @return array
     */
    private function performCustomChecksToRouteViaUpsRearchFlow(array $input, Merchant\Entity $merchant, string $currentRouteName): array
    {
        $response = [
            'route_via_ups' => false,
            'reason' => ''
        ];

        if ($this->isUpiRearchRoute($currentRouteName) == false)
        {
            $response['reason'] = 'Condition: isUpiRearchRoute() is false';
            return $response;
        }

        if (empty($input[Payment\Entity::METHOD]) === true)
        {
            $response['reason'] = 'Condition: input[METHOD] is empty';
            return $response;
        }

        if ($input[Payment\Entity::METHOD] !== Payment\METHOD::UPI)
        {
            $response['reason'] = 'Condition: input[METHOD] is not UPI';
            return $response;
        }

        if (empty($input[Payment\Entity::RECURRING]) === false)
        {
            $response['reason'] = 'Condition: input[RECURRING] is not empty';
            return $response;
        }

        if (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === false)
        {
            $response['reason'] = 'Condition: input[SUBSCRIPTION_ID] is not empty';
            return $response;
        }

        if (empty($input[Payment\Entity::INVOICE_ID]) === false)
        {
            $response['reason'] = 'Condition: input[INVOICE_ID] is not empty';
            return $response;
        }

        if (empty($input[Payment\Entity::PAYMENT_LINK_ID]) === false)
        {
            $response['reason'] = 'Condition: input[PAYMENT_LINK_ID] is not empty';
            return $response;
        }

        if (empty($input[Payment\Entity::TOKEN_ID]) === false)
        {
            $response['reason'] = 'Condition: input[TOKEN_ID] is not empty';
            return $response;
        }

        if (empty($input[Payment\Entity::TOKEN]) === false)
        {
            $response['reason'] = 'Condition: input[TOKEN] is not empty';
            return $response;
        }

        if (empty($input[Payment\Entity::SAVE]) === false)
        {
            $response['reason'] = 'Condition: input[SAVE] is not empty';
            return $response;
        }

        if (empty($input[Payment\Entity::OFFER_ID]) === false)
        {
            $response['reason'] = 'Condition: input[OFFER_ID] is not empty';
            return $response;
        }

        if (empty($input[Payment\Entity::CHARGE_ACCOUNT]) === false)
        {
            $response['reason'] = 'Condition: input[CHARGE_ACCOUNT] is not empty';
            return $response;
        }

        if (empty($input['reward_ids']) === false)
        {
            $response['reason'] = 'Condition: input[reward_ids] is not empty';
            return $response;
        }

        if ($merchant->isFeeBearerPlatform() === false)
        {
            $response['reason'] = 'Condition: isFeeBearerPlatform() is false';
            return $response;
        }

        if ($merchant->isRazorpayOrgId() === false)
        {
            $response['reason'] = 'Condition: isRazorpayOrgId() is false';
            return $response;
        }

        if ($this->isOtmPayment($input) === true)
        {
            $response['reason'] = 'Condition: isOtmPayment() is true';
            return $response;
        }

        if (isset($input[Payment\Method::UPI][Payment\UpiMetadata\Entity::MODE]) === true)
        {
            $response['reason'] = 'Condition: input[UPI][MODE] is set';
            return $response;
        }

        if (isset($input[Payment\Method::UPI][Payment\UpiMetadata\Entity::PROVIDER]) === true)
        {
            $response['reason'] = 'Condition: input[UPI][PROVIDER] is set';
            return $response;
        }

        if ((isset($input[Payment\Method::UPI][Payment\UpiMetadata\Entity::TYPE]) === true) and
            ($input[Payment\Method::UPI][Payment\UpiMetadata\Entity::TYPE] !== Payment\UpiMetadata\Type::DEFAULT))
        {
            $response['reason'] = 'Condition: input[UPI][TYPE] is set and not DEFAULT';
            return $response;
        }

        if (isset($input[Payment\Entity::RECEIVER]) === true)
        {
            $response['reason'] = 'Condition: input[RECEIVER] is set';
            return $response;
        }

        if (isset($input[Payment\Entity::UPI_PROVIDER]) === true)
        {
            $response['reason'] = 'Condition: input[UPI_PROVIDER] is set';
            return $response;
        }

        if (isset($input[Payment\Entity::CHARGE_ACCOUNT]) === true)
        {
            $response['reason'] = 'Condition: input[CHARGE_ACCOUNT] is set';
            return $response;
        }

        if (isset($input['application']) === true)
        {
            $response['reason'] = 'Condition: input[application] is set';
            return $response;
        }

        if (isset($input[Payment\Entity::BILLING_ADDRESS]) === true)
        {
            $response['reason'] = 'Condition: input[BILLING_ADDRESS] is set';
            return $response;
        }

        if (empty($input[Payment\Entity::ORDER_ID]) === false)
        {
            $order = $this->fetchOrderFromInput($input);

            $orderMeta = (new Order\Core)->getFormattedOrderMeta($order);

            if (empty($order) === false)
            {
                // Check if offers exist in the order
                if ($order->hasOffers() === true)
                {
                    $response['reason'] = 'Condition: Offers exist in the order';
                    return $response;
                }

                // Check if discounts are applicable to the order
                if ($order->isDiscountApplicable() === true)
                {
                    $response['reason'] = 'Condition: Discounts are applicable to the order';
                    return $response;
                }

                // Check if product ID exists in the order
                if ($order->getProductId() !== null)
                {
                    $response['reason'] = 'Condition: Product ID exists in the order';
                    return $response;
                }

                // Check if fee config ID exists in the order
                if ($order->getFeeConfigId() !== null)
                {
                    $response['reason'] = 'Condition: Fee config ID exists in the order';
                    return $response;
                }

                // Check if invoice exists in the order
                if ($order->invoice !== null)
                {
                    $response['reason'] = 'Condition: Invoice exists in the order';
                    return $response;
                }

                // Check if tax invoice meta exists in the order meta
                if (isset($orderMeta[Order\OrderMeta\Type::TAX_INVOICE]) === true)
                {
                    $response['reason'] = 'Condition: Tax invoice meta exists in the order meta';
                    return $response;
                }
            }

            $orderTransfers = $this->repo->transfer->fetchBySourceTypeAndIdAndMerchant(E::ORDER, $order->getId(), $this->merchant);

            // Check if there are any order transfers
            if ((empty($orderTransfers) === false) and
                (count($orderTransfers) > 0))
            {
                $response['reason'] = 'Condition: Order transfers exist';
                return $response;
            }
        }

        // Check if currency is INR
        if ((empty($input['currency']) === false) and
            ($input['currency'] !== Currency\Currency::INR))
        {
            $response['reason'] = 'Condition: Currency is not INR';
            return $response;
        }

        // if none of the condition evaluated as true, the request can be routed via UPS
        // after checking the razorx variant
        $response['route_via_ups'] = true;
        return $response;
    }

    private function canRouteFpxThroughRearchFlow($input): bool
    {
        // fpx always through nbplus rearch except test mode in production
        if ($input[Payment\Entity::METHOD] === Payment\METHOD::FPX)
        {
            if ((app()->isEnvironmentProduction() === true) and ($this->mode === Mode::TEST))
            {
                return false;
            }
            return true;
        }
        else
        {
            return false;
        }
    }

    private function canRouteWalletThroughRearchFlow($input): bool
    {
        // wallet mentioned in array => $supportedWalletsForRearch, always process through nbplus rearch except test mode in production
        if ($input[Payment\Entity::METHOD] === Payment\METHOD::WALLET)
        {
            if ((app()->isEnvironmentProduction() === true) and ($this->mode === Mode::TEST))
            {
                return false;
            }

            if ((isset($input[Payment\Entity::WALLET]) === true) &&
                (in_array($input[Payment\Entity::WALLET], Wallet::$supportedWalletsForRearch)))
            {
                return true;
            }
        }

        return false;
    }

    private function processPaymentViaPGRouter(array $input, $startTime)
    {
        (new Payment\Metric)->pushCreateMetricsViaPGRouter($input);

        $input[Payment\Entity::MERCHANT_ID] = $this->merchant->getId();

        if (empty($input[Payment\Entity::ORDER_ID]) === false)
        {
            $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($this->order->getId());

            $input[Payment\Entity::ORDER] = $this->order;
        }

        if (empty($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            $this->checkAndFillSavedAppToken($input);
        }

        list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp(
            $input, $this->merchant, false);

        if ($customer != null)
        {
            if ($customer->isGlobal())
            {
                $input[Payment\Entity::GLOBAL_CUSTOMER_ID] = $customer->getId();
            }
            else
            {
                $input[Payment\Entity::CUSTOMER_ID] = $customer->getId();
            }
        }

        $paymentData = $this->callPGRouterPaymentCreateBasedOnRoute($input);

        $paymentId = $this->getPaymentIdFromRearchResponse($paymentData);

        $this->trace->info(TraceCode::FINDING_PAYMENT_ID_FROM_REARCH_RESPONSE, [
            'paymentId' => $paymentId
        ]);

        if ($paymentId !== null)
        {
            $payment = $this->repo->payment->findByPublicId($paymentId);

            (new EntityOrigin\Core)->createEntityOrigin($payment);
        }

        $this->logPGRouterRequestTime($input, $startTime);

        $paymentData['processed_via_pg_router'] = true;

        return $paymentData;
    }

    protected function callPGRouterPaymentCreateBasedOnRoute($input)
    {
        $route = $this->app['api.route']->getCurrentRouteName();

        switch ($route)
        {
            case "payment_create_ajax":
                return $this->app['pg_router']->validateAndCreatePayment($input, true);
            case "payment_create_private_old":
                $input['route_auth'] = $this->app['basicauth']->getAuthType();
                return $this->app['pg_router']->validateAndCreatePaymentRedirect($input, true);
            case "payment_create_private_json":
                $input['route_auth'] = $this->app['basicauth']->getAuthType();
                return $this->app['pg_router']->validateAndCreatePaymentJson($input, true);
            case "payment_create_checkout":
                return $this->app['pg_router']->validateAndCreatePaymentCheckout($input, true);
            case "payment_create_upi":
                return $this->app['pg_router']->validateAndCreatePaymentUpi($input, true);
        }
        return null;
    }

    private function getPaymentIdFromRearchResponse($paymentData)
    {
        if (isset($paymentData['payment_id']) === true)
        {
            return $paymentData['payment_id'];
        }
        if (isset($paymentData['razorpay_payment_id']) === true)
        {
            return $paymentData['razorpay_payment_id'];
        }
        if (isset($paymentData['html']) === true)
        {
            $matches = array();

            preg_match('/pay_[a-zA-Z0-9]{14}/', $paymentData['html'], $matches);

            if (sizeof($matches) > 0)
            {
                return $matches[0];
            }
        }
        return null;
    }

    protected function getDummyCardDetails($first6,$last4)
    {
        $card = (new Card\Entity)->getDummyCardArray();

        $card[Card\Entity::NUMBER] = $this->getLuhnValidCardNumber($first6,$last4);

        return $card;
    }

    protected function getLuhnValidCardNumber($firstSix,$lastFour): string
    {
        $part1 = $firstSix . '00000';

        $part2 = $lastFour;

        $checksum = Luhn::computeCheckDigitWithPart($part1, $part2);

        return $part1 . $checksum . $part2;
    }

    protected function validatePaymentForOptimizerOnlyMerchants(){
        $merchant=$this->merchant;
        if($merchant!=null && $merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::OPTIMIZER_ONLY_MERCHANT) && !$merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::RAAS)){
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_OPTIMIZER_ONLY_MERCHANT_HAS_RAAS_DISABLED,
                "merchant_id",
                $merchant->getId(),
                "Optimizer only merchant should have raas feature enabled to make the payments");
        }
    }

    protected function validateCardRecurringAutoPayment($input)
    {
        if ((isset($input[Payment\Entity::METHOD])) and
            ($input[Payment\Entity::METHOD] === Payment\Method::CARD))
        {
            $variant = $this->app->razorx->getTreatment(
                $this->merchant->getId(),
                Merchant\RazorxTreatment::CARD_MANDATE_ENABLE_MULTIPLE_FREQUENCIES,
                $this->mode
            );

            if (($variant === 'on') and
                (isset($input[Payment\Entity::TOKEN])) and
                (isset($input[Payment\Entity::CUSTOMER_ID])))
            {
                $customerId = Customer\Entity::verifyIdAndStripSign($input['customer_id']);

                $token = (new Customer\Token\Core)->getByTokenIdAndCustomerId($input['token'], $customerId);

                if (($token !== null) and
                    ($token->isRecurring() === true) and
                    ($token->getMethod() === Payment\Method::CARD) and
                    ($token->getFrequency() !== null) and
                    ($token->getFrequency() !== SubscriptionRegistration\Entity::AS_PRESENTED))
                {
                    date_default_timezone_set('Asia/Kolkata');

                    switch ($token->getFrequency()) {
                        case SubscriptionRegistration\Entity::WEEKLY:
                            $start = strtotime("sunday -1 week");
                            break;

                        case SubscriptionRegistration\Entity::MONTHLY:
                            $start = strtotime(date('Y-m-01 00:00:00'));
                            break;

                        case SubscriptionRegistration\Entity::YEARLY:
                            $start = strtotime(date('Y-01-01 00:00:00'));
                            break;
                    }

                    $noOfAutoPayments = $this->repo->payment->fetchPaymentCountByTokenForCardInRange($token->getId(), $start, Carbon::now()->getTimestamp());

                    if ($noOfAutoPayments > 0) {
                        throw new Exception\BadRequestValidationFailureException("Debit is not as per the defined frequency of the Mandate.");
                    }
                }
            }
        }
    }

    protected function preProcessPosPaymentRequest(&$input)
    {
        if(isset($input['receiver_type']) === false or $input['receiver_type'] !== Receiver::POS)
            return;

        unset($input['receiver_type'] , $input['status'],
            $input['reference1'],$input['reference2']);

        if($input['method'] === 'card')
        {
            $first6 = implode(explode("-",substr($input['card']['number'],0,7)));

            $last4  = substr($input['card']['number'],-4);

            $card = $this->getDummyCardDetails($first6,$last4);

            $input['card'] = $card;
        }

        if($input['method'] === 'upi' and isset($input['vpa']) === false) {
            $input['vpa'] = Payment\Entity::DUMMY_VPA;
        }

        $input['receiver'] = array(
            'type'=> 'pos'
        );
    }


    public function process(array $input, $gatewayInput = []): array
    {
        $meta = [
            'metadata' => [
                'trackId' => $this->app['req.context']->getTrackId()

            ],
            'read_key' => array('trackId'),
            'write_key' => '',
        ];

        try
        {
            $startTime = microtime(true);

            $this->validatePaymentForOptimizerOnlyMerchants();

            $this->preProcessPosPaymentRequest($input);

            $this->setMethodForInput($input);

            $this->validateTokenisedPayment($input);

            $this->validateCardRecurringAutoPayment($input);

            $this->setMethodForSubscription($input);

            $this->appendMetadataForPayment($input);

            $this->fetchAndSetOrdertoCurrentContext($input);

            $this->preProcessForUpiIfApplicable($input);

            $this->validate1CCFlow($input);

            $this->validateLavbBankPayments($input);

            $this->validateAndDecryptEncryptedCardInput($input);

            if (($this->isOpgspImportMerchant() === false) and
                (($this->canRouteWalletThroughRearchFlow($input) === true) or
                ($this->canRouteThroughRearchFlow($input) === true) or
                ($this->canRouteThroughNbPlusRearchFlow($input) === true) or
                ($this->canRouteThroughUpsRearchFlow($input) === true) or
                ($this->canRouteFpxThroughRearchFlow($input) === true)))
            {
                $this->app['diag']->trackPaymentEventV2(EventCode::REARCH_PAYMENT_CREATION_INITIATED,  null, null, $meta);

                $paymentData = $this->processPaymentViaPGRouter($input, $startTime);

                $this->app['diag']->trackPaymentEventV2(EventCode::REARCH_PAYMENT_CREATE_REQUEST_PROCESSED,  null, null, $meta);
            }
            else
            {
                $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_INPUT_VALIDATIONS_INITIATED, null, null, $meta);

                $payment = $this->buildPaymentEntity($input);

                $this->preProcessForSubscriptionsIfApplicable($input, $payment);

                // adding this here instead of inside createPaymentEntity.
                // Since that is inside a transaction, not possible to move certain validation query of payment pages
                // to slave. so moving out that particular validation here.
                // Rest of the validations will continue inside createPaymentEntity
                $this->preProcessAndValidateForPaymentPagesIfApplicable($input);

                $ret = $this->preProcessPaymentInputs($input, $payment);

                if ($ret !== null)
                {
                    $this->logPaymentRespawnEvent($input, $ret);

                    return $ret;
                }

                $this->repo->transaction(function() use ($input, $payment)
                {
                    $this->createPaymentEntity($input, $payment);
                });

                $payment = $this->payment;

                $this->preProcessPaymentMeta($input, $payment);

                // This flow is being used for only hosted (Shopify).
                $this->checkSignature($input, $payment);

                // Creates an origin entity for the payment based on the auth used to initiate the payment.
                $entityOrigin = (new EntityOrigin\Core)->createEntityOrigin($payment);

                if (empty($entityOrigin) === false)
                {
                    $payment->setRelation('entityOrigin', $entityOrigin);
                }

                $paymentData = $this->authorize($payment, $input, $gatewayInput);

                $this->logRequestTime($payment, $startTime);

                $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_REQUEST_PROCESSED, $payment);
            }

            $this->saveUserConsentInRedis($input, $paymentData);

            return $paymentData;
        }
        catch (\Throwable $e)
        {
            $payment = $payment ?? null;

            $this->logUPIPaymentFailure($e, $payment, $input);

            $dimensions[Metric::LABEL_PAYMENT_IS_CREATED] = false;

            if ($payment instanceof Payment\Entity === true)
            {
                $dimensions[Metric::LABEL_PAYMENT_IS_CREATED] = $payment->wasRecentlyCreated;
            }

            (new Payment\Metric)->pushExceptionMetrics($e, Metric::PAYMENT_PROCESS_FAILED, $dimensions, $payment);

            $properties = [];

            if ($payment === null)
            {
                $properties['merchant'] = $this->merchant->getMerchantProperties();
            }

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_REQUEST_PROCESSED, $payment, $e, $meta, $properties);

            throw $e;
        }
    }

    /**
     * Logs upi payment failure
     */
    private function logUPIPaymentFailure(\Throwable $e, $payment, $input)
    {
        if (($input['method'] !== Payment\Method::UPI) || ($e === null))
        {
            return;
        }

        $logData = [
            Payment\Entity::MERCHANT_ID         => $this->merchant->getId(),
            payment\Entity::METHOD              => Payment\Method::UPI,
        ];

        if ($payment instanceof Payment\Entity === true)
        {
            $logData[Payment\Entity::CPS_ROUTE] = $payment->getCpsRoute();
            $logData[Payment\Entity::GATEWAY]   = $payment->getGateway();
        }

        $errorAttributes = [];

        if ($e instanceof Exception\BaseException)
        {
            if (($e->getError() !== null) and ($e->getError() instanceof Error))
            {
                $errorAttributes = $e->getError()->getAttributes();
            }
        }
        else
        {
            $errorAttributes = [
                Metric::LABEL_TRACE_CODE         => $e->getCode(),
            ];
        }


        $logData[Metric::LABEL_TRACE_CODE]              = array_get($errorAttributes, Error::INTERNAL_ERROR_CODE);
        $logData[Metric::LABEL_TRACE_EXCEPTION_CLASS]   = get_class($e);

        $this->trace->info(TraceCode::UPI_PAYMENT_INITIATE_FAILURE_LOG, $logData);
    }

    protected function validateAndDecryptEncryptedCardInput(& $input)
    {
        if  (($this->app['basicauth']->isPrivateAuth() === false) or
            (empty($input['card']['encrypted_number']) === true))
        {
            return;
        }

        $this->decryptCardNumberIfApplicable($input['card']);
    }

    /**
     * @param $input
     * @param $paymentData
     */
    protected function autoSaveUserConsentForRecurring(& $input, $paymentId): void
    {
        // If payment is done via recurring flow and call is from S2S,
        // we are automatically collect the user's consent for card
        try
        {
            $library = $input['_']['library'] ?? '';

            $paymentObject = null;

            try
            {
                $paymentObject = $this->repo->payment->findOrFail(Payment\Entity::stripDefaultSign($paymentId));
            }
            catch (\Throwable $exception){}

            $isRecurringInitialPayment = ($paymentObject !== null and
                                          $paymentObject->isRecurring() and
                                          $paymentObject->isRecurringTypeInitial());

            $isRecurringFlow = isset($input['recurring']) and
            (($input["recurring"] === '1') or ($input['recurring'] === 'preferred'));

            if($isRecurringInitialPayment and
                ($isRecurringFlow or isset($input[Payment\Entity::SUBSCRIPTION_ID])) and
                $library === Payment\Analytics\Metadata::S2S)
            {

                $this->trace->info(
                    TraceCode::EXPLICIT_CONSENT_COLLECTED_RECURRING,
                    [
                        'merchant_id' => $this->app['basicauth']->getMerchantId(),
                        'library' => $library
                    ]
                );

                $input['save'] = "1";
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::EXPLICIT_CONSENT_RECURRING_ERROR,
                []);
        }
    }

    /**
     * @param $input
     * @param $paymentData
     */
    protected function saveUserConsentInRedis($input, $paymentData): void
    {
        try
        {
            $paymentId = $paymentData['payment_id'] ?? $paymentData['razorpay_payment_id'] ?? '';
            $paymentMethod = $input[Payment\Entity::METHOD] ?? '';
            $paymentMethodsUsingCards = [Payment\Entity::CARD, Payment\Entity::EMI];
            $library = $input['_']['library'] ?? '';
            $allowedLibraries = [Payment\Analytics\Metadata::CHECKOUTJS, Payment\Analytics\Metadata::HOSTED, Payment\Analytics\Metadata::RAZORPAYJS, Payment\Analytics\Metadata::CUSTOM, Payment\Analytics\Metadata::S2S];

            $this->autoSaveUserConsentForRecurring($input, $paymentId);

            $this->trace->info(
                TraceCode::TOKENISATION_CONSENT_LOG,
                [
                    'paymentId'     => $paymentId,
                    'library'       => $library,
                    'method'        => $paymentMethod,
                    'user_consent'  => $input[self::USER_CONSENT_FOR_TOKENISATION] ?? '',
                    'consent_to_save_card' => $input['consent_to_save_card'] ?? '',
                    'save'          => $input[self::SAVE] ?? '',
                ]);

            if ((empty($paymentId) === true) or
                (in_array($paymentMethod, $paymentMethodsUsingCards, true) === false) or
                (in_array($library, $allowedLibraries, true) === false))
            {
                return;
            }

            $userConsentGiven = ((empty($input[self::USER_CONSENT_FOR_TOKENISATION]) === false) ||
                                 (empty($input[self::SAVE]) === false));

            // For CAW saved card flow, we clone the original local token and make another new local token
            // we are saving the acknowledged_at value of the original card token in redis for the saved card flow in CAW
            // this value will be set to the new token's acknowledged_at after payment authorization

            $paymentObject = null;

            try
            {
                $paymentObject = $this->repo->payment->findOrFail(Payment\Entity::stripDefaultSign($paymentId));
            }
            catch (\Throwable $exception){}

            $recurringInitialPaymentDataChecksForSavedCardFlow = ($paymentObject !== null and
                                                                  $paymentObject->isRecurring() and
                                                                  $paymentObject->isRecurringTypeInitial());

            $recurringInitialInputPayloadChecksForSavedCardFlow = (((isset($input['subscription_id'])) or
                                                                    ((isset($input['recurring'])) and
                                                                        (($input['recurring'] === '1') or
                                                                            ((bool) $input['recurring'] === true) or
                                                                            ($input['recurring']=== 'preferred')))) and
                                                                    (isset($input['token'])));

            $isRecurringSavedCardFlow = ($recurringInitialPaymentDataChecksForSavedCardFlow and
                                         $recurringInitialInputPayloadChecksForSavedCardFlow);


            if ($userConsentGiven === false and
                $isRecurringSavedCardFlow === false)
            {
                return;
            }

            $redisKey = $paymentId . '_saved_card_consent';

            $ttl = 60 * 60; // 1 hr in seconds

            if($isRecurringSavedCardFlow === true and
               $userConsentGiven === false)
            {
                $recurringSavedCardToken = (new Customer\Token\Repository)->getByTokenAndMerchant($input['token'], $this->merchant);

                $this->trace->info(
                    TraceCode::EXPLICIT_CONSENT_COLLECTED_RECURRING_SAVED_CARD,
                    [
                        'merchant_id' => $this->app['basicauth']->getMerchantId(),
                        'library' => $library
                    ]
                );

                $this->app['cache']->put($redisKey, $recurringSavedCardToken->getAcknowledgedAt(), $ttl);
            }
            else
            {
                $this->app['cache']->put($redisKey, true, $ttl);
            }

            if (isset($input['token']))
            {
                $this->app['cache']->put($redisKey . '_token', $input['token'], $ttl);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::TOKENISATION_CONSENT_REDIS_ERROR,
                []);
        }
    }

    protected function validateLavbBankPayments($input)
    {
        // If there is no method in input, do nothing
        if (isset($input['method']) === false)
        {
            return;
        }

        $data = [];

        switch ($input['method'])
        {
            case Payment\Method::CARD:
                // No card number for card payment
                if (isset($input[Payment\Entity::CARD][Card\Entity::NUMBER]) === false)
                {
                    return;
                }

                $iinId = substr($input[Payment\Entity::CARD][Card\Entity::NUMBER], 0, 6);

                $iin = $this->repo->iin->find($iinId);

                // IIN not available
                if (empty($iin) === true)
                {
                    return;
                }

                if (($iin->getIssuer() !== Card\Issuer::LAVB) or
                    ($iin->isEnabled() === true))
                {
                    return;
                }

                $data['iin'] = $iinId;

                break;

            default:
                return;
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_LAXMI_VILAS_BANK_PAYMENT_DISABLED,
            null,
            $data);
    }

    public function eventPaymentCreated()
    {
        // the scenario where same payment id gets generated in live and test mode is not handled currently.
        $cacheKey = 'EVENT_PAYMENT_CREATED_FIRED_'.$this->payment->getPublicId();

        if (($this->cache->get($cacheKey) === null) or
            ($this->cache->get($cacheKey) === false))
        {
            $eventPayload = [
                ApiEventSubscriber::MAIN => $this->payment,
            ];

            $this->app['events']->dispatch('api.payment.created', $eventPayload);

            $this->cache->put($cacheKey, true, 1200);
        }
    }

    protected function appendMetadataForPayment(array & $input)
    {
        if ($this->app['basicauth']->isPrivateAuth() === true)
        {
            (new Payment\Analytics\Service)->setMetadataForS2SPayment($input);
        }
        else if ($this->app['basicauth']->isPublicAuth() === true)
        {
            (new Payment\Analytics\Service)->setMetadataForPublicAuthPayment($input);
        }
        else if ($this->app['basicauth']->isAppAuth() === true)
        {
            (new Payment\Analytics\Service)->setMetadataForAppAuthPayment($input);
        }
    }

    protected function logPaymentRespawnEvent(array $request, array $data)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $properties = [
            'payment' => $request,
            'reason'  => $data['missing'] ?? 'Unknown',
            'merchant'     => [
                'id'        => $merchant->getId(),
                'name'      => $merchant->getBillingLabel(),
                'mcc'       => $merchant->getCategory(),
                'category'  => $merchant->getCategory2(),
            ],
        ];

        $metaDetails = [
            'metadata'  => $properties,
            'read_key'  => array(),
            'write_key' => 'trackId',
        ];

        $metaDetails['metadata']['trackId'] = $this->app['req.context']->getTrackId();

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATION_RESPAWN, null, null, $metaDetails, $properties);
    }

    public function getPayment(): Payment\Entity
    {
        $this->payment->reload();

        return $this->payment;
    }

    protected function preProcessForSubscriptionsIfApplicable(array & $input, Payment\Entity $payment)
    {
        if (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === true)
        {
            return;
        }

        $appTokenPresent = $this->isAppTokenPresent();

        $this->subscription = $this->app['module']
                                   ->subscription
                                   ->fetchSubscriptionInfo($input, $payment->merchant, false, $appTokenPresent);

        if ($this->subscription->isExternal() === true)
        {
            $payment->setSubscriptionId($this->subscription->getId());

            $subscriptionPaymentRecurringType = $this->subscription->getRecurringType();

            if((isset($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE]) === true) and
                ($input[Payment\Entity::METHOD] === Payment\Method::UPI) and
                $subscriptionPaymentRecurringType === 'card_change')
            {
                $subscriptionPaymentRecurringType = 'initial';
            }

            else if((isset($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE]) === true) and
                ($input[Payment\Entity::METHOD] === Payment\Method::EMANDATE) and
                $subscriptionPaymentRecurringType === 'card_change')
            {
                $subscriptionPaymentRecurringType = 'initial';
            }

            $payment->setRecurringType($subscriptionPaymentRecurringType);

            $this->addOrderIdToInputForExternalSubscription($input);
        }

        $shouldCheckForDCC = $this->shouldApplyDCConSubscriptionPayments($payment);

        if ($shouldCheckForDCC === true and $payment->isRecurringTypeAuto() === true and $payment->getMethod() === Method::CARD and $payment->isRecurring() === true)
        {
            $tokenId = '';

            if ((isset($input['token_id']) === true) and empty($input['token_id']) === false)
            {
                $tokenId = $input['token_id'];
            }

            $subscriptionsPayments = $this->repo->payment->fetchSubscriptionIdAndRecurringType($input[Payment\Entity::SUBSCRIPTION_ID], $tokenId, ['initial', 'card_change'], ['captured', 'refunded']);

            $targetPaymentId = '';

            foreach ($subscriptionsPayments as $subscriptionsPayment)
            {
                if ($subscriptionsPayment->getRecurringType() === Payment\RecurringType::CARD_CHANGE)
                {
                    $targetPaymentId = $subscriptionsPayment->getId();

                    break;
                }
                elseif ($subscriptionsPayment->getTokenId() === $this->subscription->getTokenId() or $subscriptionsPayment->getGlobalTokenId() === $this->subscription->getTokenId())
                {
                    if ($subscriptionsPayment->getStatus() === Status::CAPTURED)
                    {
                        $targetPaymentId = $subscriptionsPayment->getId();

                        break;
                    }
                    else
                    {
                        $targetPaymentId = $subscriptionsPayment->getId();
                    }
                }
            }

            if (empty($targetPaymentId) === false)
            {
                $paymentMeta = $this->repo->payment_meta->findByPaymentId($targetPaymentId);

                if (empty($paymentMeta) === false)
                {
                    $input['dcc_currency'] = $paymentMeta->getGatewayCurrency();

                    $dccInfo = (new Payment\Service)->getDCCInfo($payment->getAmount(), $payment->getCurrency(), $payment->merchant->getDccRecurringMarkupPercentage());

                    $input['currency_request_id'] = $dccInfo['currency_request_id'];
                }
            }
        }
    }

    protected function addOrderIdToInputForExternalSubscription(array & $input)
    {
        assert($this->subscription->isExternal() === true); // nosemgrep :assert-fix-false-positives

        $cardChange = boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false);

        // For subscription card change, we donot need to add order id
        // for the following subscription states. (For these states, we will be
        // using default auth amount as card change amount)
        if (($this->subscription->isActive() === true) or
            ($this->subscription->isHalted() === true) or
            ($this->subscription->isAuthenticated() === true) or
            ($cardChange === true && $input['method'] === Constants::EMANDATE))
        {
            if ($cardChange === true)
            {
                // Adding this to support UPI card change
                if($input['method'] === Constants::UPI)
                {
                    $orderPayLoad = [
                        Order\Entity::AMOUNT          => $input['amount'],
                        Order\Entity::CURRENCY        => $input['currency'],
                        Order\Entity::PAYMENT_CAPTURE => true,
                        Order\Entity::PRODUCT_ID      => $this->subscription->getId(),
                        Order\Entity::PRODUCT_TYPE    => Constants::SUBSCRIPTION
                    ];

                    $order = (new Order\Core)->create($orderPayLoad, $this->merchant);

                    $this->order = $order;

                    $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($order->getId());
                }

                // Adding this to support Card to emandate method change
                else if($input['method'] === Constants::EMANDATE)
                {
                    $orderPayLoad = [
                        Order\Entity::AMOUNT          => $input['amount'],
                        Order\Entity::CURRENCY        => $input['currency'],
                        Order\Entity::METHOD          => Payment\Method::EMANDATE,
                        Order\Entity::PAYMENT_CAPTURE => true,
                        Order\Entity::PRODUCT_ID      => $this->subscription->getId(),
                        Order\Entity::PRODUCT_TYPE    => Constants::SUBSCRIPTION
                    ];

                    $order = (new Order\Core)->create($orderPayLoad, $this->merchant);

                    $this->order = $order;

                    $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($order->getId());
                }

                return;
            }
        }

        if($this->subscription !== null and
           $this->subscription->getStatus() === Constants::CREATED and
           $input[Payment\Entity::METHOD] === Payment\Method::EMANDATE and
           isset($input[Payment\Entity::TOKEN]) === false)
        {
            $orderPayLoad = [
                Order\Entity::AMOUNT          => 0,
                Order\Entity::CURRENCY        => $input['currency'],
                Order\Entity::METHOD          => Payment\Method::EMANDATE,
                Order\Entity::PAYMENT_CAPTURE => true,
                Order\Entity::PRODUCT_ID      => $this->subscription->getId(),
                Order\Entity::PRODUCT_TYPE    => Constants::SUBSCRIPTION
            ];

            $order = (new Order\Core)->create($orderPayLoad, $this->merchant);

            $this->order = $order;

            $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($order->getId());
        }

        else if (($this->subscription->hasCurrentInvoice() === true) and
            (isset($input[Payment\Entity::ORDER_ID]) === false))
        {
            $currentInvoiceId = $this->subscription->getCurrentInvoiceId();

            $invoice = $this->repo->invoice->findOrFailPublic($currentInvoiceId);

            $this->order = $this->repo->order->findByPublicId(Order\Entity::getSignedId($invoice->getOrderId()));

            $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($invoice->getOrderId());
        }
        else if(isset($input['subscription_id']) and
            ($this->subscription->getStatus() === Constants::CREATED) and
            $input['method'] === Constants::UPI)
        {
            // for subscriptions with trial period we don't create invoice and order, for UPI recurring we need Order to create UPI mandate
            $orderPayLoad = [
              "amount"           =>  $input['amount'],
               "currency"        => $input['currency'],
               "payment_capture" => true,
               "product_id"      => $this->subscription->getId(),
               "product_type"    => Constants::SUBSCRIPTION
            ];

            $order = (new Order\Core)->create($orderPayLoad, $this->merchant);

            $this->order = $order;

            $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($order->getId());
        }
    }

    protected function addCustomerIdToInputForExternalSubscription(array & $input)
    {
        assert($this->subscription->isExternal() === true); // nosemgrep : assert-fix-false-positives
        //
        // If a subscription_id is sent in the input, the customer_id should
        // never be sent. It's either associated with the subscription (local customer)
        // or we use the global customer and associate that later.
        //
        if (isset($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CUSTOMER_ID_SENT_IN_INPUT,
                null,
                [
                    'subscription_id'   => $this->subscription->getId(),
                ]);
        }

        if ($this->subscription->hasCustomer() === true)
        {
            $input[Payment\Entity::CUSTOMER_ID] = Customer\Entity::getSignedId($this->subscription->getCustomerId());
        }
    }

    protected function preProcessPaymentMeta(array $input, Payment\Entity $payment)
    {
        if (empty($input[Payment\Entity::META]) === true)
        {
            return;
        }

        if($payment->isPos() === true and $payment->getMethod() === Payment\Method::UPI and $payment->isCaptured() === false)
        {
            return;
        }

        if($payment->isPos() === true and $payment->getMethod() === Payment\Method::CARD and $payment->isAuthorized() === false)
        {
            return;
        }

        (new Payment\PaymentMeta\Core)->addMetaInformation($payment, $input[Payment\Entity::META]);
    }

    protected function preProcessPaymentInputs(array & $input, Payment\Entity $payment)
    {
        $coproto = null;

        switch ($payment->getMethod())
        {
            case Payment\Method::EMANDATE:
                $coproto = $this->preProcessPaymentInputsForEmandate($input, $payment);
                break;

            case Payment\Method::WALLET:
                $coproto = $this->preProcessPaymentInputsForWallet($input, $payment);
                break;

            case Payment\Method::UPI:
                $coproto = $this->preProcessPaymentInputsForUpi($input, $payment);
                break;

            case Payment\Method::CARDLESS_EMI:
                $coproto = $this->preProcessPaymentInputsForCardlessEmi($input, $payment);
                (new Payment\Service())->cachePaylaterCardlessEmiResponseIfApplicable($payment, $coproto);
                break;

            case Payment\Method::PAYLATER:
                $coproto = $this->preProcessPaymentInputsForPayLater($input, $payment);
                (new Payment\Service())->cachePaylaterCardlessEmiResponseIfApplicable($payment, $coproto);


        break;
        }


        return $coproto;
    }

    protected function preProcessPaymentInputsForCardlessEmi($input, $payment)
    {
        $this->verifyCardlessEmiEnabled();

        if ((empty($input['ott']) === false) and
            (Payment\Gateway::isCardlessEmiProviderAndRedirectFlowProvider($input['provider']) === false))
        {
            return;
        }

        if (($payment->merchant->isPhoneOptional() === true) and
            ($payment->getContact() === Payment\Entity::DUMMY_PHONE))
        {
            $coproto = [
                'type'    => 'respawn',
                'request' => [
                    'url'     => $this->route->getUrlWithPublicAuthInQueryParam('payment_create'),
                    'method'  => 'POST',
                    'content' => array_assoc_flatten($input, '%s[%s]'),
                ],
                'method' => 'cardless_emi',
                'version' => '1',
                'provider' => $input['provider'],
            ];

            $coproto['missing'][] = 'contact';

            unset($coproto['request']['content']['contact']);

            return $coproto;
        }

        $payment = $this->repo->transaction(function() use ($input, $payment)
        {
            $payment = $this->createPaymentEntity($input, $payment);

            $payment->setBaseAmount($payment->getAmount());

            $this->setAnalyticsLog($payment);

            $this->repo->saveOrFail($payment);

            return $payment;
        });

        switch ($input['provider'])
        {
            case CardlessEmi::ZESTMONEY:
                $input['contact'] = $payment['contact'];
                break;
            case CardlessEmi::FLEXMONEY:
                $input['contact'] = $payment['contact'];
                break;
            case CardlessEmi::WALNUT369:
                $input['contact'] = $payment['contact'];
                break;
            case CardlessEmi::SEZZLE:
                $input['contact'] = $payment['contact'];
                break;
            case CardlessEmi::EARLYSALARY:
                $input['contact'] = $payment['contact'];
                break;
            default;
                break;
        }

        $input['payment_id'] = $payment->getPublicId();

        if($payment->getWallet() === CardlessEmi::ZESTMONEY and $this->mode === Mode::TEST)
        {
            return;
        }

        //adding this to redirecting to gateway via nbplus
        if ($payment->getWallet() === CardlessEmi::ZESTMONEY)
        {
            $terminals = (new TerminalProcessor)->getTerminalsForPayment($payment);

            $payment->associateTerminal($terminals[0]);

            $this->setPaymentRoutedThroughCpsIfApplicable($payment, []);

            if($payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE)
            {
                return;
            }

        }

        if (((empty($input['emi_duration']) === false) and
           (Payment\Gateway::isCardlessEmiProviderAndRedirectFlowProvider($input['provider']) === true)) or
            (Payment\Gateway::isCardlessEmiSkipCheckAccountProvider($input['provider']) === true))
        {
            return;
        }

        $gateway = Payment\Gateway::CARDLESS_EMI;

        $merchant = $payment->merchant;

        $terminals = (new TerminalProcessor)->getTerminalsForPayment($payment);

        try
        {
            if ($payment->hasOrder() === true)
            {
                $input['order'] = $payment->order->toArray();
            }

            if($merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::REDIRECT_TO_EARLYSALARY))
            {
                $input['callbackUrl']   = $this->getCallbackUrl();
            }

            // merchant id is required to fetch details from cache
            $input['merchant_id']      = $merchant[Merchant\Entity::ID];
            $input['merchant_website'] = $merchant[Merchant\Entity::WEBSITE];
            $input['merchant_mcc']     = $merchant[Merchant\Entity::CATEGORY];
            $input['merchant_name']    = $merchant[Merchant\Entity::NAME];
            $input['merchant_features'] = $merchant->getEnabledFeatures();


            $checkAccountData = $this->app['gateway']->call($gateway, 'check_account', $input, $this->mode, $terminals[0]);

            unset($input['merchant_id']);
            unset($input['merchant_website']);
            unset($input['merchant_mcc']);
            unset($input['merchant_name']);
            unset($input['callbackUrl']);
            unset($input['merchant_features']);
            unset($input['order']);

        }
        catch (Exception\GatewayErrorException $exception)
        {
            $this->updatePaymentFailed($exception, TraceCode::PAYMENT_STATUS_FAILED);

            throw $exception;
        }

        $coproto = [
            'type' => 'respawn',
            'method' => 'cardless_emi',
            'request' => [
                'url'     => $this->route->getUrlWithPublicAuth('otp_verify', [
                    'method'     => 'cardless_emi',
                    'provider'   => $input['provider'],
                    'payment_id' => $input['payment_id'],
                ]),
                'method'  => 'POST',
                'content' => $input,
            ],
            'image'      => $payment->merchant->getFullLogoUrlWithSize(Merchant\Logo::MEDIUM_SIZE),
            'theme'      => $payment->merchant->getBrandColorElseDefault(),
            'merchant'   => $merchant->getDbaName(),
            'gateway'    => $this->getEncryptedGatewayText($gateway),
            'key_id'     => $this->ba->getPublicKey(),
            'version'    => '1',
            'payment_create_url' => $this->route->getUrlWithPublicAuth('payment_create'),
        ];

        if (Payment\Gateway::isCardlessEmiProviderAndRedirectFlowProvider($input['provider']) === true)
        {
            $coproto['emi_plans'] = [
                $input['provider'] => $checkAccountData['emi_plans']
            ];
            $coproto['lender_branding_url'] = $checkAccountData['lender_branding_url'];
        }
        elseif (($input['provider'] === CardlessEmi::EARLYSALARY) and
                (in_array(\RZP\Models\Feature\Constants::REDIRECT_TO_EARLYSALARY,$merchant->getEnabledFeatures())))
        {
            return null;
        }
        else
        {
            (new Customer\Raven)->sendOtp($input, $merchant);

            $coproto['resend_url'] = $this->route->getUrlWithPublicAuth('otp_post');
        }

        $coproto['payment_id'] = $payment->getPublicId();

        return $coproto;
    }

    protected function addIpAndUserAgent(& $input, $payment)
    {
        $analytics = $payment->getMetadata('payment_analytics');

        if($analytics['library'] === Payment\Analytics\Metadata::CHECKOUTJS)
        {
            $input['device']['ip_address'] = $analytics['ip'];
            $input['device']['user_agent'] =  $analytics['user_agent'];
        }

    }

    protected function preProcessPaymentInputsForPayLater($input, $payment)
    {
        $this->verifyPayLaterEnabled($payment);

        if (empty($input['ott']) === false)
        {
            return;
        }

        $merchant = $payment->merchant;

        switch ($input['provider'])
        {
            case Payment\Gateway::GETSIMPL:

                $gateway = Payment\Gateway::GETSIMPL;

                $payment = $this->repo->transaction(function() use ($input, $payment)
                                            {
                                                $payment = $this->createPaymentEntity($input, $payment);
                                                $payment->setBaseAmount($payment->getAmount());
                                                $this->setAnalyticsLog($payment);
                                                return $payment;
                                            });

                $input['payment'] = $payment->toArray();

                $variantFlag = $this->app->razorx->getTreatment($this->merchant->getId(),RazorxTreatment::SEND_USER_DETAILS_TO_GETSIMPL,  $this->mode);

                if($variantFlag === 'on')
                {
                    $this->addIpAndUserAgent($input,$payment);
                }

                $input['contact'] = $payment['contact'];
                break;

            case PayLater::ICICI:
                $gateway = Payment\Gateway::PAYLATER_ICICI;

                $payment = $this->repo->transaction(function() use ($input, $payment)
                {
                    $payment = $this->createPaymentEntity($input, $payment);
                    $payment->setBaseAmount($payment->getAmount());
                    $this->setAnalyticsLog($payment);
                    return $payment;
                });

                $input['payment'] = $payment->toArray();


                break;

            default:
                $gateway = Payment\Gateway::PAYLATER;
                break;
        }

        if (($payment->merchant->isPhoneOptional() === true) and
            ($payment->getContact() === Payment\Entity::DUMMY_PHONE))
        {
            unset($input['payment']);

            $coproto = [
                'type'    => 'respawn',
                'request' => [
                    'url'     => $this->route->getUrlWithPublicAuthInQueryParam('payment_create'),
                    'method'  => 'POST',
                    'content' => array_assoc_flatten($input, '%s[%s]'),
                ],
                'method' => 'paylater',
                'version' => '1',
                'provider' => $input['provider'],
            ];

            $coproto['missing'][] = 'contact';

            unset($coproto['request']['content']['contact']);

            return $coproto;
        }

        $terminals = (new TerminalProcessor)->getTerminalsForPayment($payment);

        // merchant id is required to fetch details from cache
        $input['merchant_id'] = $merchant[Merchant\Entity::ID];

        if (Payment\Gateway::isNbPlusServiceGateway($gateway, $payment))
        {
            $payment->associateTerminal($terminals[0]);

            $this->setPaymentRoutedThroughCpsIfApplicable($payment, []);
        }

        if($payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE)
        {
            $gatewayInput = $input;

            $gatewayInput['payment'] = $payment;

            $gatewayInput['contact'] = $payment['contact'];

            $gatewayInput['payment']['created_at'] = Carbon::now(Timezone::IST)->getTimestamp();

            $response = $this->callGatewayFunction('check_account', $gatewayInput);
        }
        else
        {
            $response = $this->app['gateway']->call($gateway, 'check_account', $input, $this->mode, $terminals[0]);
        }

        unset ($input['merchant_id']);

        $coproto  = $this->preProcesspaylaterResponseHandler($response, $payment, $input, $merchant, $terminals[0]);

        return $coproto;
    }

    protected function preProcessPaymentInputsForEmandate(array & $input, Payment\Entity $payment)
    {
        //
        // We don't want to do this coproto
        // stuff for second recurring payments.
        //
        // We don't want to ask the merchant to send bank_account
        // details and auth_type for second recurring payments.
        // bank_account details are filled into the payment create
        // input automatically using the token.
        // Ideally, even the bank_account details are not really needed
        // to be filled in the input. But, we are filling it anyway.
        // auth_type cannot be filled using the token or any other details
        // in the payment create input. But, we don't need auth_type for
        // second recurring payments. So, it's okay.
        //

        $currentRouteName = $this->route->getCurrentRouteName();

        // Adding subscription_registration_charge_token to enable
        // token charging via dashboard.
        if (($currentRouteName === 'payment_create_recurring') or
            ($currentRouteName === 'subscription_registration_charge_token') or
            ($currentRouteName === 'subscription_registration_charge_token_bulk') or
            ($currentRouteName === 'payment_create_subscriptions'))
        {
            return null;
        }

        // for batch charging of tokens
        if ($this->app->runningInQueue() === true)
        {
            return null;
        }

        if ($payment->isEmandate() === false)
        {
            return null;
        }

        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                Payment\Entity::ORDER_ID,
                [
                    'method' => $payment->getMethod()
                ]);
        }

        $order = $this->fetchOrderFromInput($input);

        $tokenRegistration = $order->getTokenRegistration();

        if ($tokenRegistration !== null)
        {
            if (empty($input[Payment\Entity::BANK_ACCOUNT]) === true)
            {
                $input[Payment\Entity::BANK_ACCOUNT] = [];
            }

            $tokenRegistrationBankAccount = $tokenRegistration->entity;

            if ($tokenRegistrationBankAccount !== null)
            {
                $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::NAME] =
                    $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::NAME] ?? $tokenRegistrationBankAccount->getBeneficiaryName();

                $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::ACCOUNT_NUMBER] = $tokenRegistrationBankAccount->getAccountNumber();

                $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::IFSC] =
                    $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::IFSC] ?? $tokenRegistrationBankAccount->getIfscCode();

                $input[Payment\Entity::BANK_ACCOUNT][Customer\Token\Entity::ACCOUNT_TYPE] =
                    $input[Payment\Entity::BANK_ACCOUNT][Customer\Token\Entity::ACCOUNT_TYPE] ?? $tokenRegistrationBankAccount->getAccountType();
            }
        }
        //
        // We need this flow only if either:
        //   - bank_account details are missing - name , account number, ifsc code, account type
        //   - auth_type is missing
        //

        $skipCoprotoFlow = ((empty($input[Payment\Entity::BANK_ACCOUNT]) === false) and
                            (empty($input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::NAME]) === false) and
                            (empty($input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::ACCOUNT_NUMBER]) === false) and
                            (empty($input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::IFSC]) === false) and
                            (empty($input[Payment\Entity::BANK_ACCOUNT][Customer\Token\Entity::ACCOUNT_TYPE]) === false) and
                            (empty($payment->getAuthType()) === false));

        if ($skipCoprotoFlow === true)
        {
            return null;
        }

        $emandateMethods = [];

        (new Methods\Core)->addRecurringEmandateToMethodsIfApplicable(
                                $this->merchant, $this->methods, $emandateMethods);

        //
        // This can happen when the required features are not enabled
        // or when there's not a single bank for any auth type.
        //
        if (empty($emandateMethods) === true)
        {
            return null;
        }

        $bank = $input[Payment\Entity::BANK];

        //
        // This case should ideally never come up because the bank
        // passed by the client would be based on the methods API only.
        // Even here, we are using the methods API. If the bank did
        // not come up in the methods API now, then most likely someone
        // is tampering with the request on the frontend.
        //
        if (isset($emandateMethods['emandate'][$bank]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_BANK_FOR_EMANDATE,
                Payment\Entity::BANK,
                [
                    'bank' => $bank
                ]);
        }

        // Nested attributes, when flattened, aren't handled by laravel test requests
        if ($this->app->runningUnitTests() === true)
        {
            unset($input['_']);
        }

        $coproto = [
            'type'    => 'respawn',
            'request' => [
                'url'     => $this->route->getUrlWithPublicAuthInQueryParam('payment_create'),
                'method'  => 'POST',
                'content' => [
                    'input' => array_assoc_flatten($input, '%s[%s]'),
                    'bank_details' => $emandateMethods['emandate'][$input[Payment\Entity::BANK]],
                ]
            ],
            'method' => 'emandate',
            'version' => '1',
        ];

        return $coproto;
    }

    protected function preProcessPaymentInputsForWallet(array $input, Payment\Entity $payment)
    {
        $coproto = null;

        if ($payment->isWallet() === false)
        {
            return $coproto;
        }

        //
        // TODO: This needs to be fixed since we use dummy phone and email
        // in subscriptions subsequent charges too. We could be using
        // these values at other places also.
        // Also, need to add this in S2S wallet docs.
        // We currently return back JSON response.
        // Actually, this won't even work for S2S since we remove
        // `content` and `missing` attributes completely before returning
        //
        if (($payment->merchant->isPhoneOptional() === true) and
            (Wallet::isPhoneRequired($payment->getWallet()) === true) and
            ($payment->getContact() === Payment\Entity::DUMMY_PHONE))
        {
            $coproto = $coproto ?: $this->getCoprotoDefaultArrayForWallet($input);

            $coproto['missing'][] = 'contact';

            unset($coproto['request']['content']['contact']);
        }

        if (($payment->merchant->isEmailOptional() === true) and
            (Wallet::isEmailRequired($payment->getWallet()) === true) and
            ($payment->getEmail() === Payment\Entity::DUMMY_EMAIL))
        {
            $coproto = $coproto ?: $this->getCoprotoDefaultArrayForWallet($input);

            $coproto['missing'][] = 'email';

            unset($coproto['request']['content']['email']);
        }

        return $coproto;
    }

    protected function getCoprotoDefaultArrayForWallet(array $input)
    {
        // Nested attributes, when flattened, aren't handled by laravel test requests
        if ($this->app->runningUnitTests() === true)
        {
            unset($input['_']);
        }

        return [
            'type'    => 'respawn',
            'request' => [
                'url'     => $this->route->getUrlWithPublicAuthInQueryParam('payment_create'),
                'method'  => 'POST',
                'content' => array_assoc_flatten($input, '%s[%s]'),
            ],
            'method' => 'wallet',
            'version' => '1',
        ];
    }

    protected function preProcessPaymentInputsForUpi(array $input, Payment\Entity $payment)
    {
        $coproto = null;

        $missing = [];

        if ($payment->isUpi() === false)
        {
            return;
        }

        if (empty($input[Payment\Entity::VPA]) === false)
        {
            return;
        }

        /***
         * For collect payments, we need the vpa. However, in case of saved vpa, we dont get the vpa directly. We get
         * the token linked to the vpa entity in the request. Therefore adding a check here that either the vpa should
         * be present or token should be present.
         */
        if (($this->isFlowCollect($input))
            and (isset($input['token']) === false))
        {
            $missing[] = 'vpa';
        }
        else if (isset($input[Payment\Entity::UPI_PROVIDER]) === false)
        {
            return;
        }
        else if (isset($input[Payment\Entity::CONTACT]) === true)
        {
            return;
        }
        else
        {
            $missing[] = 'contact';
        }

        $host = $this->route->getHost();

        $coproto = [
            'type'    => 'respawn',
            'request' => [
                'url'     => $this->route->getUrlWithPublicAuthInQueryParam('payment_create'),
                'method'  => 'POST',
                'content' => $input,
            ],
            'image'     => $payment->merchant->getFullLogoUrlWithSize(Merchant\Logo::MEDIUM_SIZE),
            'theme'     => $payment->merchant->getBrandColorElseDefault(),
            'method'    => 'upi',
            'version'   => '1',
            'missing'   => $missing,
            'base'      => $host,
        ];

        return $coproto;
    }

    /**
     * This function is used while creating the Qr codes. It will
     * create dummy payment and fetch terminal corresponding to that.
     * No writes happens during this call, hence we can use replica connection
     * instead of master.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function processAndReturnTerminal(array & $input)
    {
        $receiver = $input[Payment\Entity::RECEIVER];

        unset($input[Payment\Entity::RECEIVER]);

        $this->tracePaymentNewRequest($input);

        $terminal = $this->repo->useSlave(
            function() use ($input, $receiver)
            {
                //creating dummy order if order_id_mandatory feature is enabled for a merchant
                //so that payment entity can be created. This is required during product switch.
                $feature = \RZP\Models\Feature\Constants::ORDER_ID_MANDATORY;

                if ($this->merchant->isFeatureEnabled($feature) === true)
                {
                    $order = $this->createDummyOrder($this->merchant);

                    $input += [Payment\Entity::ORDER_ID => $order->getId()];
                }

                //
                // We only create a dummy payment entity for purpose
                // of bharat qr terminal selection and returning it.
                // It's not going to be saved in the database.
                //
                $payment = $this->buildPaymentEntity($input);

                $payment->setMetadata($input);

                $payment->receiver()->associate($receiver);

                $this->dummyPrePaymentAuthorizeProcessing($payment, $input);

                $selectedTerminals = (new TerminalProcessor)->getTerminalsForPayment($payment);

                if ($payment->isQrV2UpiPayment() === true)
                {
                    return $selectedTerminals;
                }

                return $selectedTerminals[0] ?? null;
            });

        return $terminal;
    }

    public function createDummyOrder(Merchant\Entity $merchant)
    {
        $input = [
            'amount'   => 50000,
            'currency' => 'INR',
            'receipt'  => 'rcptid42',
        ];

        return (new Order\Core())->create($input, $merchant, false, true);
    }

    public function associateOrderWithPaymentForConvenienceFee($input, Payment\Entity $payment)
    {
        $order = $this->repo->order->findByPublicId($input['order_id']);

        //Associating order with payment here
        //in case convenience fee associated with Order
        if($order->getFeeConfigId() !== null) {

            $payment->order()->associate($order);
            //Setting payment amount to order amount
            //In case of convenience fee for fee calculation
            if(isset($input['fee']) === true and
                $input['fee'] == 0 and
                $payment->getConvenienceFee() === null)
            {
                $payment->setAmount($order->getAmount());
            }
        }
        return $order;
    }

    public function processAndReturnPaymentFees($payment)
    {
        $data = [];
        [$fee, $tax, $feeSplit] = (new Fee())->calculateMerchantFees($payment);

        if($this->merchant->isFeeBearerCustomerOrDynamic() === false)
        {
            $data['fees'] = $fee;
            $data['tax'] = $tax;
            $data['currency'] = $payment->getCurrency();
            $data['fee_bearer']= $payment->getFeeBearer();
            $data['fee_split']= $feeSplit;
            return $data;
        }

        $payment->setAmount($payment->getAmount()- $payment->getFee());

        if( $payment->hasOrder() === true and
            $payment->order->getFeeConfigId() !== null )
        {
            $order = $this->repo->order->findByPublicId($payment->getApiOrderId());
            $rzpFee = $fee - $tax;

            $customerFee = $this->calculateCustomerFee($payment, $order, $rzpFee);
            $customerFeeTax = $this->calculateCustomerFeeGst($customerFee, $rzpFee, $tax);
        }

        if(isset($customerFee) === true and $customerFee >= 0)
        {
            $payment->setFeeBearer(Merchant\FeeBearer::PLATFORM);
        }


        //Verifying if value sent in Convenience Fee
        //is valid or not
        if(isset($convenienceFee) === true)
        {
            if(isset($customerFee) === false or $customerFee > $convenienceFee)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The value sent in Convenience Fee Field is invalid ',
                    'Convenience Fee');
            }
        }

        $data = [
            'original_amount'  => $payment->getAmount(),
            'fees'            => $fee,
            'razorpay_fee'    => $fee - $tax,
            'tax'             => $tax,
            'amount'          => $payment->getAmount() + $fee,
            'currency'        => $payment->getCurrency(),
            'fee_bearer'      => $payment->getFeeBearer(),
            'fee_split'       => $feeSplit,
        ];

        //Adding extra fields for response in case of
        //additional customer fee associated with Payment
        if(isset($customerFee) === true)
        {
            $data['customer_fee'] = $customerFee;
            $data['customer_fee_gst'] = $customerFeeTax;
            $data['amount'] = $payment->getAmount() + $customerFee + $customerFeeTax;
        }

        return $data;
    }

    public function processAndReturnFees(array & $input)
    {
        $this->tracePaymentNewRequest($input);

        // Validate if customer is fee bearer then only move forward
        if ($this->merchant->isFeeBearerCustomerOrDynamic() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        if (isset($input['method']) === false)
        {
            $input['method'] = Payment\Method::CARD;
        }
        else if (empty($input['method']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please provide appropriate payment method',
                Payment\Entity::METHOD);
        }

        if ($input['method'] === Payment\Method::COD)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_COD_NOT_ENABLED_FOR_MERCHANT);
        }

        //In case of convenience fee associated with a payment
        //we may get this field, and this field cannot be
        // sent in input while building payment entity
        if(isset($input['convenience_fee']) === true)
        {
            $convenienceFee = $input['convenience_fee'];

            unset($input['convenience_fee']);
        }

        //
        // We only create a dummy payment entity for purpose
        // of pre-calculating fees and returning it.
        // It's not going to be saved in the database.
        //
        $payment = $this->buildPaymentEntity($input);

        if(isset($input['order_id']) === true)
        {
            $order = $this->associateOrderWithPaymentForConvenienceFee($input, $payment);
        }

        // Performing dummy set of processing for the same
        $res = $this->dummyPrePaymentAuthorizeProcessing($payment, $input);
        if(isset($res['mcc_request_id']) && !isset($input['mcc_request_id']))
        {
            $input['mcc_request_id'] = $res['mcc_request_id'];
        }

        list($fee, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payment);

        (new Currency\Core)->reverseMccConversionOnFeeIfApplicable($input, $fee, $tax);

        $input['fee'] = $fee;
        $input['tax'] = $tax;

        // Calculate and apply dcc
        $this->dummyApplyDcc($payment, $input);

        if( $payment->hasOrder() === true and
            $payment->order->getFeeConfigId() !== null )
        {
            $rzpFee = $fee - $tax;

            $customerFee = $this->calculateCustomerFee($payment, $order, $rzpFee);

            $customerFeeTax = $this->calculateCustomerFeeGst($customerFee, $rzpFee, $tax);
        }

        if(isset($customerFee) === true and
            $customerFee >= 0)
        {
            $payment->setFeeBearer(Merchant\FeeBearer::PLATFORM);
        }

        if ($payment->getFeeBearer() === Merchant\FeeBearer::PLATFORM)
        {
            $fee = 0;

            $tax = 0;
        }

        //Verifying if value sent in Convenience Fee
        //is valid or not
        if(isset($convenienceFee) === true)
        {
            if(isset($customerFee) === false or
                $customerFee > $convenienceFee)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The value sent in Convenience Fee Field is invalid ',
                    'Convenience Fee');
            }
        }

        $data = [
            'originalAmount'  => $input['dcc_amount'] ?? $input['amount'],
            'original_amount' => $input['dcc_amount'] ?? $input['amount'],
            'fees'            => $input['dcc_fee'] ?? $fee,
            'razorpay_fee'    => ($input['dcc_fee'] ?? $fee) - ($input['dcc_tax'] ?? $tax),
            'tax'             => $input['dcc_tax'] ?? $tax,
            'amount'          => ($input['dcc_amount'] ?? $input['amount']) + ($input['dcc_fee'] ?? $fee),
            'currency'        => (isset($input['dcc_applied']) && $input['dcc_applied']) ? $input['dcc_currency'] : $input['currency'],
        ];

        //Adding extra fields for response in case of
        //additional customer fee associated with Payment
        if(isset($customerFee) === true)
        {
            $data['customer_fee'] = $customerFee;

            $data['customer_fee_gst'] = $customerFeeTax;

            $data['amount'] = $input['amount'] + $customerFee + $customerFeeTax;

            $input['amount'] = $input['amount'] + $customerFee + $customerFeeTax;
        }

        // Unset dcc values from input
        unset($input['dcc_amount'], $input['dcc_fee'], $input['dcc_tax'], $input['dcc_applied']);

        // Set new input amount and fees
        $input['amount'] = $input['amount'] + $fee;

        $input['fee'] = $fee;

        return $data;
    }

    protected function setMethodForInput(& $input)
    {
        //
        // We use isset and not `empty` because if we receive
        // the key `method` in the input, but with empty string,
        // we want to let the validator throw the exception.
        // If the key itself is not present, we will set the method
        // to `card` or token's method.
        //
        if (isset($input[Payment\Entity::METHOD]) === true)
        {
            return;
        }

        // For google_pay, if method not received in the request,
        // payment creation happens with 'unselected' method.
        if (!isset($input[Payment\Entity::METHOD]) and (isset($input[Payment\Entity::PROVIDER]) and $input[Payment\Entity::PROVIDER] === E::GOOGLE_PAY))
        {
            $input[Payment\Entity::METHOD] = Payment\Method::UNSELECTED;
            return;
        }

        if ((isset($input[Payment\Entity::TOKEN]) === true) and
            (isset($input[Payment\Entity::CUSTOMER_ID]) === true))
        {
            $customerId = $input[Payment\Entity::CUSTOMER_ID];
            $tokenId = $input[Payment\Entity::TOKEN];

            Customer\Entity::verifyIdAndStripSign($customerId);

            //
            // TokenID can either be the token ID or the
            // `token` attribute of the token entity or the
            //`gateway_token` attribute of the token entity.
            //
            $token = (new Customer\Token\Core)->getByTokenIdAndCustomerId($tokenId, $customerId);

            //
            // It cannot be global token because customer_id is also being sent.
            // If customer_id is being sent, it has to be local customer.
            // If it's local customer, the token being sent should also be local
            // token. If it's local token, the token's merchant should match the
            // payment request's merchant.
            //
            $tokenMethod = $token->getMethod();

            $merchant = $this->merchant;

            if ($tokenMethod === Payment\Method::CARD and $token->isRecurring() === false) {

                $merchant = $merchant->getFullManagedPartnerWithTokenInteroperabilityFeatureIfApplicable($merchant);
            }

            if ($token->getMerchantId() !== $merchant->getId() )
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID,
                    'token');
            }

            $input[Payment\Entity::METHOD] = $tokenMethod;

            if ($tokenMethod === Payment\Method::EMANDATE or $tokenMethod === Payment\Method::NACH)
            {
                try
                {
                    $variant = $this->app->razorx->getTreatment(
                        $merchant->getId(),
                        Merchant\RazorxTreatment::EMANDATE_NET_REVENUE_IMPROVEMENT,
                        $this->mode);
                }
                catch (\Throwable $ex)
                {
                    $variant = 'off';
                }

                if($variant === 'on')
                {
                    $this->validateEmandateTokenStatus($token, $merchant);
                }
            }

            if ($tokenMethod === Payment\Method::EMANDATE)
            {
                $input[Payment\Entity::BANK] = $token->getBank();
            }
            else if ($tokenMethod === Payment\Method::WALLET)
            {
                $input[Payment\Entity::WALLET] = $token->getWallet();
            }
        }
        else
        {
            $input[Payment\Entity::METHOD] = Payment\Method::CARD;
        }
    }

    protected function validateEmandateTokenStatus(Token\Entity $token, Merchant\Entity $merchant)
    {
        $response = $this->fetchEmandateConfigs($token, $merchant);

        $tokenStatus = $response[TokenConstants::EMANDATE_TOKEN_STATUS] ?? null;

        $coolDownPeriod = $response[TokenConstants::COOLDOWN_PERIOD] ?? "";

        if($tokenStatus !== null and $tokenStatus === TokenConstants::BLOCKED_TEMPORARILY)
        {
            $msg = "token_" . $token->getId() . " has been put on hold temporarily for creating recurring payments.".
                "The next recurring payment can be created on the token after " . $coolDownPeriod;

            $this->trace->info(TraceCode::EMANDATE_TOKEN_BLOCK_ERROR, [
                "msg" => $msg
            ]);

            throw new Exception\BadRequestValidationFailureException($msg, 'token');
        }
    }

    protected function fetchEmandateConfigs(Token\Entity $token, Merchant\Entity $merchant)
    {
        try
        {
            $debitConfig = $this->fetchEmandateDcsConfigs($merchant->getId());

            $tempErrorEnableFlag = $debitConfig[EmandateConstants::TEMPORARY_ERRORS_ENABLE_FLAG] ?? false;

            $tokenNotes = $token->getNotes();

            if ($tokenNotes !== null and isset($tokenNotes[TokenConstants::EMANDATE_CONFIGS]) === true)
            {
                $this->trace->info(TraceCode::EMANDATE_FETCH_TOKEN_CONFIGS,
                    [
                        "emandate_token_configs" => $tokenNotes[TokenConstants::EMANDATE_CONFIGS],
                        "token_id"               => $token->getId(),
                        "merchant_id"            => $merchant->getId()
                    ]);

                $emandateTokenStatus = $tokenNotes[TokenConstants::EMANDATE_CONFIGS][TokenConstants::EMANDATE_TOKEN_STATUS] ?? null;

                $presentTime = Carbon::now('Asia/Kolkata')->getTimestamp();

                $coolDowntime = $tokenNotes[TokenConstants::EMANDATE_CONFIGS][TokenConstants::COOLDOWN_PERIOD] ?? $presentTime;

                $timeDifference = (int) $presentTime - $coolDowntime;

                $lastUpdatedMonth = $tokenNotes[TokenConstants::EMANDATE_CONFIGS][Token\Constants::LAST_UPDATED_MONTH] ?? '';

                $currentMonth = $this->getCurrentMonthIST();

                // resetting if blocked time is completed or current time time doesn't match blocked/counter data
                if(($currentMonth !== $lastUpdatedMonth) or
                    ($emandateTokenStatus === TokenConstants::BLOCKED_TEMPORARILY and $timeDifference >= 0))
                {
                    $this->trace->info(TraceCode::EMANDATE_TOKEN_CONFIG_RESET, [
                        "current_month"         => $currentMonth,
                        "last_updated_month"    => $lastUpdatedMonth,
                        "emandate_config"       => $tokenNotes[TokenConstants::EMANDATE_CONFIGS],
                        "token_id"              => $token->getId(),
                        "merchant_id"           => $merchant->getId()
                    ]);

                    $token->setNotes([]);

                    $this->repo->save($token);

                    return [];
                }

                if($tempErrorEnableFlag === true and ($emandateTokenStatus === TokenConstants::BLOCKED_TEMPORARILY)  and $timeDifference < 0)
                {
                    $date = new DateTime("@$coolDowntime");

                    $date->setTimeZone(new DateTimeZone('Asia/Kolkata'));

                    return
                        [
                            TokenConstants::COOLDOWN_PERIOD           => $date->format('Y-m-d H:i:s'),
                            TokenConstants::EMANDATE_TOKEN_STATUS     => TokenConstants::BLOCKED_TEMPORARILY
                        ];
                }
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::EMANDATE_TOKEN_VALIDATION_ERROR, [
                "merchant_id" => $merchant->getId(),
                "token_id"    => $token->getId()
            ]);
        }

        return [];
    }

    protected function fetchEmandateDcsConfigs(string $merchantId)
    {
        try
        {
            $dcsConfigService = new DcsConfigService();

            $key = EmandateConstants::EMANDATE_MERCHANT_CONFIGURATIONS;

            $fields = EmandateConstants::EMANDATE_CONFIG_FIELDS;

            return $dcsConfigService->fetchConfiguration($key, $merchantId, $fields, $this->mode);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::EMANDATE_DCS_CONFIG_FETCH_ERROR, [
                "merchant_id" => $merchantId
            ]);
        }

        return [
            TokenConstants::COOLDOWN_PERIOD => 0,
            TokenConstants::RETRY_ATTEMPTS => 0,
            EmandateConstants::TEMPORARY_ERRORS_ENABLE_FLAG => false
        ];
    }


    protected function validateTokenisedPayment(& $input)
    {
        if (isset($input[Payment\Entity::METHOD]) and
            ($input[Payment\Entity::METHOD] !== Payment\Method::CARD))
        {
            return;
        }

        $experimentVariable = UniqueIdEntity::generateUniqueId();

        $variant = $this->app->razorx->getTreatment($experimentVariable, Merchant\RazorxTreatment::DISABLE_RZP_TOKENISED_PAYMENT, $this->mode);

        $merchant = $this->merchant;

        $isMalaysianMerchant = Country::matches($merchant->getCountry(), Country::MY);

        if( strtolower($variant) === 'on')
        {
            if (($input[Payment\Entity::METHOD]) == Payment\Method::CARD and (isset($input[Payment\Entity::TOKEN]) === true)) {
                $tokenId = $input[Payment\Entity::TOKEN];

                if (isset($input[Payment\Entity::CUSTOMER_ID]) === true) {
                    $customerId = $input[Payment\Entity::CUSTOMER_ID];

                    Customer\Entity::verifyIdAndStripSign($customerId);

                    $token = (new Customer\Token\Core)->getByTokenIdAndCustomerId($tokenId, $customerId);

                } else {

                    $token = (new Customer\Token\Core)->getByTokenId($tokenId);
                    if (($token->getMerchantId() !== $merchant->getId()))
                    {
                        if (!$isMalaysianMerchant
                            and (($token->getMerchantId() !== self::RAZORPAY_ORG_ID)
                            or ($token->card->isInternational() === false)))
                        {
                            throw new Exception\BadRequestException(
                                ErrorCode::BAD_REQUEST_TOKEN_NOT_APPLICABLE,
                                'token');
                        }
                    }
                }

                if ($this->isCardAbsentforTokenisedPayment($token, $input))
                {
                    $input['card'] = [];
                }

                $this->trace->info(TraceCode::TRACK_TOKENISED_PAYMENT_VALIDATION, [
                    'token' => $token->getId(),
                    'isCompliant' => $token->card->isTokenisationCompliant($token->merchant),
                ]);

                if ($token->card->getVault() === Card\Vault::AXIS)
                {
                    $input[Payment\Method::CARD] = $input[Payment\Method::CARD] ?? [];
                    $input[Payment\Method::CARD][Card\Entity::VAULT] = Card\Vault::AXIS;
                }

                if ($token->card->isTokenisationCompliant($token->merchant) === false) {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_TOKEN_NOT_APPLICABLE,
                        'token');
                }
            }
        }
    }

    protected function isCardAbsentforTokenisedPayment($token, $input) : bool
    {
        if(($token->card->isAmex() || $token->card->isVisa() || $token->card->isRuPay() || $token->card->isMasterCard()) && ((array_key_exists('card', $input) === false) or (!isset($input['card']))))
        {
            return true;
        }
        return false;
    }

    protected function setMethodForSubscription(& $input)
    {
        if ((isset($input[Payment\Entity::SUBSCRIPTION_ID]) === true) and
            (isset($input[Payment\Entity::TOKEN]) === true) and
            (isset($input[Payment\Entity::METHOD]) === true) and
            ($input[Payment\Entity::METHOD] === Payment\Method::EMANDATE))
        {
            $tokenId = $input[Payment\Entity::TOKEN];

            $subscriptionId = $input[Payment\Entity::SUBSCRIPTION_ID];

            Subscription\Entity::verifyIdAndStripSign($subscriptionId);

            $token = (new Customer\Token\Core)->getByTokenIdAndSubscriptionId($tokenId, $subscriptionId);

            //
            // It cannot be global token because customer_id is also being sent.
            // If customer_id is being sent, it has to be local customer.
            // If it's local customer, the token being sent should also be local
            // token. If it's local token, the token's merchant should match the
            // payment request's merchant.
            //
            if ($token->getMerchantId() !== $this->merchant->getId())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID,
                    'token');
            }

            $tokenMethod = $token->getMethod();

            $input[Payment\Entity::METHOD] = $tokenMethod;

            if ($tokenMethod === Payment\Method::EMANDATE)
            {
                $input[Payment\Entity::BANK] = $token->getBank();
            }
        }
    }

    /**
     * This method sets the flag that this payment should be processed via
     * Core payment service
     * @param $payment
     * @param $gatewayInput
     */
    protected function setPaymentRoutedThroughCpsIfApplicable(Payment\Entity $payment, $gatewayInput)
    {
        $method = $payment->getMethod();

        if (empty($payment->getCpsRoute()) === false)
        {
            return;
        }

        $cpsEnabledMethods = [
            Payment\Method::CARD, Payment\Method::NETBANKING, Payment\Method::EMI,
            Payment\Method::EMANDATE, Payment\Method::CARDLESS_EMI, Payment\Method::UPI,
            Payment\Method::APP, Payment\Method::PAYLATER
        ];

        $cpsEnabledWallets = [
            Payment\Gateway::WALLET_FREECHARGE,
            Payment\Gateway::WALLET_PAYZAPP,
            Payment\Gateway::WALLET_PHONEPE,
            Payment\Gateway::WALLET_AMAZONPAY,
            Payment\Gateway::WALLET_BAJAJ,
            Payment\Gateway::WALLET_PAYPAL,
        ];

        if (((in_array($method, $cpsEnabledMethods, true) === false) or
                ($payment->isGooglePayCard() === true) or
                (empty($payment->getGooglePayMethods()) === false) or
                ($payment->isAppCred() === true)) and
                (in_array($payment->getGateway(), $cpsEnabledWallets, true) === false))
        {
            $payment->disableCpsRoute();
            return;
        }

        // Check if the method is UPI
        if ($method === Payment\Method::UPI)
        {
            // set appropriate cps_route for UPI Payments
            $this->setCpsRouteForUpi($payment);

            // We return here since we do not have any processing left for UPI Method
            return;
        }

        if (((Payment\Gateway::isNbPlusServiceGateway($payment->getGateway(), $payment) === true)) and
            ((Service::isNbplusSupportedMethods($method)) === true))
        {
            $this->handleNbPlusServiceGateways($payment, $gatewayInput);

            if ($payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE)
            {
                return;
            }
        }

        if (Payment\Gateway::isCardPaymentServiceGateway($payment->getGateway()))
        {
            if ($payment->isBharatQr() === true)
            {
                return;
            }

            $this->handleCardPaymentServiceGateways($payment, $gatewayInput);

            if ($payment->getCpsRoute() === Payment\Entity::CARD_PAYMENT_SERVICE)
            {
                return;
            }
        }

        $this->trace->info(TraceCode::CPS_ROUTE_CONFIG, [
            'payment_id'    => $payment->getId(),
            'cps_config'    => Admin\ConfigKey::get(Admin\ConfigKey::CPS_SERVICE_ENABLED, false),
        ]);

        // If the config flag is enabled check for razorx variant and enable cps_route
        if ((bool) Admin\ConfigKey::get(Admin\ConfigKey::CPS_SERVICE_ENABLED, false) === true)
        {
            $variant = $this->getRazorxVariant($payment, self::CPS_FEATURE_FLAG_PREFIX);

            $this->setPaymentService($payment, $variant);
        }
    }

    // Set Upi cps route for payment if applicable
    private function setCpsRouteForUpi(Payment\Entity $payment)
    {
        // Check if the method is UPI
        if ($payment->getMethod() !== Payment\Method::UPI)
        {
            return ;
        }

        // Check if the Upi Payment Service is enabled in config
        if ($this->isUpiPaymentServiceEnabled() === false)
        {
            return;
        }

        // Service does not support Bharat QR, UPI QR, Recurring and UPI transfer.
        if (($payment->isBharatQr() === true) or
            ($payment->isUpiQr() === true) or
            ($payment->isRecurring() === true) or
            ($payment->isUpiTransfer() === true))
        {
            return;
        }

        // Check if the payment gateway is supported by Upi payment service
        if (Payment\Gateway::isUpiPaymentServiceGateway($payment->getGateway()) === false)
        {
            return;
        }

        // Check if request in testing environment and is to be routed through Upi Payment Service
        if ($this->isRearchBVTRequestForUPI($this->app['request']->header(RequestHeader::X_RZP_TESTCASE_ID)) === true)
        {
            $this->setPaymentService($payment, 'upips');
            return;
        }

        $variant = $this->getRazorxVariantForUPS($payment);

        if ($variant !== 'upips')
        {
            return;
        }

        // set upi cps_route route for a payment.
        $this->setPaymentService($payment, 'upips');
    }

    /**
     * Get razorx variant for UPS payment initiation
     *
     * @param Payment\Entity $payment
     */
    protected function getRazorxVariantForUPS(Payment\Entity $payment)
    {
        if (Payment\Gateway::isUpiPaymentServiceFullyRamped($payment->getGateway()) === true) {
            return 'upips';
        }

        $feature = 'api'. '_' . $payment->getGateway() . '_v1';

        $requestOptions = [
            'connect_timeout' => 1,
            'timeout'         => 1,
        ];

        // hit razorx service to get the variant
        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(),
            $feature, $this->mode, 3, $requestOptions);

        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_RAZORX_VARIANT,
        [
            'payment_id'    => $payment->getId(),
            'variant'       => $variant,
            'gateway'       => $payment->getGateway(),
            'feature'       => $feature,
            'mode'          => $this->mode,
            'merchant_id'   => $payment->getMerchantId(),
        ]);

        return $variant;
    }

    /**
     * Handles Checks for Card Payment service gateways.
     */
    protected function handleCardPaymentServiceGateways(Payment\Entity $payment, $gatewayInput)
    {
        if ($this->isCardPaymentServiceConfigEnabled() === true)
        {
            if (Payment\Gateway::shouldAlwaysRouteThroughCardPaymentService($payment->getGateway()))
            {
                $this->setPaymentService($payment, self::CARD_PAYMENT_SERVICE_VARIANT_PREFIX);
                return;
            }

            $variant = $this->getRazorxVariant($payment, self::CARD_PAYMENTS_PREFIX);

            // To route all ivr payments through payments-card
            if (($variant !== 'cardps') and
                (isset($gatewayInput['auth_type']) === true) and
                ($gatewayInput['auth_type'] === 'ivr'))
            {
                $variant = 'cardps';
            }

            $this->setPaymentService($payment, $variant);
        }
    }

    protected function handleNbPlusServiceGateways(Payment\Entity $payment, $gatewayInput)
    {
        if (Payment\Gateway::gatewaysAlwaysRoutedThroughNbplusService($payment->getGateway(), $payment->getBank(), $payment->getMethod(), $payment))
        {
            $this->setPaymentService($payment, 'nbplusps');

            return;
        }

        if (Payment\Gateway::gatewayMigratedToNbPlusOnTerminalLevel($payment->getGateway()) === true)
        {
            $featureFlag = "nb_" . $payment->getGateway() . "_nbplus_merchant_whitelisting";

            $variant = $this->app->razorx->getTreatment($payment->getTerminalId(), $featureFlag, $this->mode);

            if($variant === "nbplusps")
            {
                $this->setPaymentService($payment, $variant);
            }

            $traceData = [
                'payment_id'             => $payment->getId(),
                'merchant_id'            => $payment->getMerchantId(),
                'gateway'                => $payment->getGateway(),
                'feature_flag'           => $featureFlag,
                'razorx_variant'         => $variant,
            ];

            $this->trace->info(TraceCode::CPS_RAZORX_VARIANT, $traceData);

            return;
        }

        $prefix = $payment->getMethod() . '_' . self::NB_PLUS_PAYMENTS_PREFIX;

        if (Payment\Gateway::gatewaysPartiallyMigratedToNbPlusWithBankCode($payment->getGateway()))
        {
            $prefix .= '_' . strtolower($payment->getBank());
        }

        $variant = $this->getRazorxVariant($payment, $prefix);

        $this->setPaymentService($payment, $variant);
    }

    /**
     * shouldUseNbPlusForPayPal: this function is responsible for
     * returning boolean result based on an experiment running for paypal
     * migration. The split service is called only when the gateway is paypal else it by default
     * returns false.
     */

    protected function shouldApplyDCConSubscriptionPayments($payment): bool
    {
        try
        {
            $properties = [
                'id'            => $payment->merchant->getId(),
                'experiment_id' => $this->app['config']->get('app.dcc_on_auto_subscription_payments_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $payment->merchant->getId(),
                    ]),
            ];

            $this->trace->info(TraceCode::FRESHDESK_CREATE_TICKET_INPUT_LOG, $properties);

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SUBSCRIPTION_AUTO_PAYMENT_DCC_SPLITZ_ERROR
            );
        }

        return false;
    }

    protected function isCardPaymentServiceConfigEnabled(): bool
    {
        return (bool) Admin\ConfigKey::get(Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED, false);
    }

    protected function isUpiPaymentServiceEnabled(): bool
    {
        return $this->app['config']->get('applications.upi_payment_service.enabled');
    }

    protected function isRearchBVTRequest(): bool
    {
        $rzpTestCaseID = $this->app['request']->header(RequestHeader::X_RZP_TESTCASE_ID);
        if(empty($rzpTestCaseID) === true)
        {
            return false;
        }

        return (app()->isEnvironmentQA() === true && str_ends_with(strtolower($rzpTestCaseID),'rearch'));
    }

    protected function getRazorxVariant(Payment\Entity $payment, $prefix)
    {
        $featureFlag = $prefix. '_' .$payment->getGateway();

        if (empty($payment->getAuthenticationGateway()) === false)
        {
            $featureFlag .= '_' .$payment->getAuthenticationGateway();
        }

        $variant = $this->app->razorx->getTreatment($payment->getId(), $featureFlag, $this->mode);

        $traceData = [
            'payment_id'             => $payment->getId(),
            'merchant_id'            => $payment->getMerchantId(),
            'gateway'                => $payment->getGateway(),
            'feature_flag'           => $featureFlag,
            'razorx_variant'         => $variant,
        ];

        if ($prefix !== self::NB_PLUS_PAYMENTS_PREFIX)
        {
            $traceData['authentication_gateway'] = $payment->getAuthenticationGateway();
            $traceData['auth_type']              = $payment->getAuthType() ?? AuthType::_3DS;
        }

        $this->trace->info(TraceCode::CPS_RAZORX_VARIANT, $traceData);

        return $variant;
    }

    protected function isUpiPaymentReArchBVTRequest(): bool
    {
        $rzpTestCaseID = $this->app['request']->header(RequestHeader::X_RZP_TESTCASE_ID);
        if(empty($rzpTestCaseID) === true)
        {
            return false;
        }

        return ((app()->isEnvironmentQA() === true) &&
                (str_ends_with($rzpTestCaseID,'_rearchUPSPayments')) === true);
    }

    protected function setPaymentService(Payment\Entity $payment, $variant)
    {
        //TODO Use Constants Here
        $variant = strtolower($variant);

        switch($variant)
        {
            case 'cps':

                $payment->enableCpsRoute();

                break;
            case 'cardps':

                $payment->enableCardPaymentService();

                break;
            case 'nbplusps':

                $payment->enableNbPlusService();

                break;
            case 'upips':

                $payment->enableUpiPaymentService();

                break;
            default:
                $payment->disableCpsRoute();
        }
    }

    protected function modifyAmountForDiscountedOfferIfApplicable(Payment\Entity $payment, array & $input)
    {
        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            return;
        }

        $this->setOfferForPaymentFromOrderOrInput($payment, $input);

        if (($this->offer !== null) and
            ($this->offer->getOfferType() === Offer\Constants::INSTANT_OFFER))
        {
            $orderAmount = $this->order->getAmount();

            $discountedAmount = $this->offer->getDiscountedAmountForPayment($orderAmount, $payment);

            $payment->setAmount($discountedAmount);

            //setting original order amount to input array to set back the original amount as payment
            //amount in case of offer validation fails.
            $input['order_amount'] = $orderAmount;
        }
    }

    protected function setOfferForPaymentFromOrderOrInput(Payment\Entity $payment, array $input)
    {
        $order = $payment->order;

        $offer = null;

        // When offer is forced, we do not expect offer_id in the payment input.
        // Instead we retrieve the offer to be applied (we can figure
        // this out ourselves from the payment) and validate it.
        if (($order->hasOffers() === true) and
            ($order->isOfferForced() === true))
        {
            $offer =  $this->selectForcedOfferForPayment($order);
        }
        // If offer is not forced, we expect it in the payment input. If it is
        // not present there, we assume the customer is opting to not use an offer.
        else if (isset($input[Payment\Entity::OFFER_ID]) === true)
        {
            $offer = $this->validateAndFetchOffer($payment, $input);
        }

        $this->offer = $offer;

        if ($this->offer !== null)
        {
            $payment->associateOffer($this->offer);

            $this->trace->info(TraceCode::OFFER_SELECTED_FOR_PAYMENT, [
                'offer_id'   => $offer->getPublicId(),
                'payment_id' => $payment->getPublicId(),
                'order_id'   => $order->getPublicId(),
            ]);
        }
    }

    /**
     * A forced offer is when merchant has decided that an offer is to be used
     * for a payment, and the customer does not have a choice to opt out of it.
     *
     * - In its simplest form, an offer is associated
     *   with the order, and we use it for the payment.
     * - Merchant can also associated multiple offers with a payment, wherein
     *   only one would be applicable for the payment itself (eg. one offer for each method).
     *   TODO: Implement auto selection of offer from order->offers, based on payment
     *
     * @param  Order\Entity $order
     * @return Offer\Entity
     */
    protected function selectForcedOfferForPayment(Order\Entity $order): Offer\Entity
    {
        $offers = $order->offers;

        if ($offers->count() === 1)
        {
            return $offers->first();
        }

        throw new Exception\LogicException('Auto selection of offer is not implemented yet.');
    }

    /*
     * This is a temporary measure to block payments for certain merchants who have "block_debit_2k" feature enabled
     * and if the amount is >2k and method is card and type is debit
     * In the future, this function will have validations for max amount that are method/type/currency etc specific
     * per merchant
     */
    protected function validateForMaxAmount(array $input, Payment\Entity $payment)
    {
        if (($this->merchant->isFeatureEnabled(Feature::BLOCK_DEBIT_2K) === false) or
            ($payment->getMethod() !== Payment\Method::CARD) or
            ($payment->getAmount() <= 200000)) //INR 2000
        {
            return;
        }

        if ($payment->card === null)
        {
            return;
        }

        if ($payment->card->getType() !== Card\Type::DEBIT)
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException(
            'Amount exceeds maximum amount allowed.',
            'amount',
            ['amount' => $payment->getAmount()]);
    }

    protected function validateAndFetchOffer(Payment\Entity $payment, array $input)
    {
        $offerId = $input[Payment\Entity::OFFER_ID];

        Offer\Entity::verifyIdAndStripSign($offerId);

        // TODO: this needs to be checked for shared merchant offers also
        // skipping for now because there aren't any
        $offer = $this->repo->offer->findByIdAndMerchant($offerId, $this->merchant);

        // if its just a checkout display offer, just return null so that further validations
        // and associations don't happen.
        if ($offer->getCheckoutDisplay() === true)
        {
            return null;
        }

        // If offer is present in the payment request, we need to validate it against the order.
        if ($payment->order->offers->contains($offerId) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER, null,
            [
                'offer_id' => Offer\Entity::getSignedId($offerId),
                'order_id' => $payment->order->getPublicId(),
            ]);
        }

        return $offer;
    }

    protected function checkSignature($input, $payment)
    {
        if (isset($input['signature']) === false)
        {
            return;
        }

        $payment->setSigned(true);

        if (isset($input['notes']['merchant_order_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'merchant_order_id field is required',
                'merchant_order_id');
        }

        $this->verifySignature($input, $payment);
    }

    protected function verifySignature($input, $payment)
    {
        $data = array(
            'amount'            => $payment->getAmount(),
            'currency'          => $payment->getCurrency(),
            'merchant_order_id' => $payment->getNotes()['merchant_order_id'],
        );

        $signature = $this->getSignature($data);

        // use hash_equals to prevent timing attacks
        if (hash_equals($signature, $input['signature']) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Signature does not match', 'signature');
        }

        return true;
    }

    protected function getSignature(array $data)
    {
        $publicKey = null;

        if (isset($data['razorpay_payment_id']) === true)
        {
            $payment = null;

            try
            {
                $payment = $this->repo->payment->findOrFail(Payment\Entity::stripDefaultSign($data['razorpay_payment_id']));
            }
            catch (\Throwable $exception){}

            $publicKey = $payment->getPublicKey();

            $this->trace->info(TraceCode::PUBLIC_KEY_SIGNATURE_GENERATION_TRACE, [
                'payment_id'   => $data['razorpay_payment_id'],
                'merchant_id'  => $payment->getMerchantId(),
            ]);
        }

        if ((empty($publicKey) === true) and
            (isset($data['razorpay_order_id']) === true))
        {
            $order = $this->repo->order->findOrFail(Order\Entity::stripDefaultSign($data['razorpay_order_id']));

            $publicKey = $order->getPublicKey();

            $this->trace->info(TraceCode::PUBLIC_KEY_SIGNATURE_GENERATION_TRACE, [
                'order_id'     => $data['razorpay_order_id'],
                'merchant_id'  => $order->getMerchantId(),
            ]);
        }

        ksort($data);

        $str = implode('|', $data);

        return (new CredcaseSigner)->sign($str, $publicKey);
    }

    protected function checkMerchantPermissions()
    {
        $merchant = $this->merchant;

        $mode = $this->mode;

        if ($mode === Mode::TEST)
        {
            return;
        }

        // On live request, either RBL CA should be activated for merchant for proxy auth calls or merchant should be activated
        if ($this->app['basicauth']->isProxyAuth() &&
            $this->app['basicauth']->isProductBanking() &&
            (new MerchantCore())->isRblCurrentAccountActivated($this->merchant))
        {
            // adding this check due to PG onboarding pause
            return;
        }
        else if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null,
                PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST);
        }
    }

    protected function verifyMerchantIsLiveForLiveRequest()
    {
        // On live request, ensure that merchant isn't blocked temporarily
        if (($this->mode === Mode::LIVE) and
            ($this->merchant->isLive() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED);
        }
    }

    protected function createForPayment($payment, $input, $deadLockRetryAttempts)
    {
        return $this->repo->transaction(function() use ($payment, $input)
        {
            return Tracer::inSpan(['name' => 'payment.transfer.create'], function() use ($payment, $input)
            {
                return (new TransferCore)->createForPayment(
                    $payment,
                    $input['transfers'],
                    $this->merchant,
                );
            });
        }, $deadLockRetryAttempts);
    }

    /**
     * Logs if a submerchant attempts to create a transfer for a payment created with partner auth
     *
     * @param Payment\Entity   $payment
     * @param PublicCollection $transfers
     * @param array            $input
     *
     * @return void
     */
    private function logRoutePartnershipV1Guard(Payment\Entity $payment, PublicCollection $transfers, array $input): void
    {

        if ($this->ba->isPartnerAuth() === true)
        {
            return;
        }

        $isExpEnabled = (new Merchant\Core)->isSplitzExperimentEnable(
            [
                'id'            => $this->ba->getMerchantId(),
                'experiment_id' => $this->app['config']->get('app.route_partnership_v1_guards_exp_id'),
            ],
            'enable'
        );

        // if the transfer request is not from partner auth
        // then we need to check if the payment was created by a partner.
        // if partner created the payment, then log this event
        if (($isExpEnabled === true))
        {
            // check if the payment has an associated entity origin of type application
            // & fetch the partner from the entity origin partner application
            $entityOriginCore = new EntityOrigin\Core();
            if ($entityOriginCore->isOriginApplication($payment) === true)
            {
                $application = $entityOriginCore->getOrigin($payment);
                $partnerId = $application->getMerchantId();
                if (empty($partner) === false)
                {
                    $this->trace->info(
                        TraceCode::SUBMERCHANT_CREATED_TRANSFER_FOR_PARTNER_INITIATED_PAYMENT,
                        [
                            'payment_id'        => $payment->getId(),
                            'transfer_ids'      => $transfers->getIds(),
                            'merchant_id'       => $this->ba->getMerchantId(),
                            'input'             => $input,
                            'partner_id'        => $partnerId
                        ]
                    );
                }
            }
        }
    }

    /**
     * Transfer a captured payment to customer/marketplace account
     *
     * @param  string $id    Payment ID
     * @param  array  $input Input Array
     *
     * @return PublicCollection
     * @throws Exception\BadRequestException
     */
    public function transfer(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_REQUEST,
            ['payment_id' => $id, 'input' => $input]);

        $payment = $this->retrieve($id);

        /** @var Payment\Validator $validator */
        $validator = $payment->getValidator();

        $validator->validateIsCaptured();

        $validator->validateInput('transfer', $input);

        $deadLockRetryAttempts = 1;

        $transfers = $this->mutex->acquireAndRelease(
            $payment->getId(),
            function() use ($payment, $input, $deadLockRetryAttempts)
            {
                $this->repo->reload($payment);

                $transfers = null;

                try {
                    $transfers = $this->createForPayment($payment, $input, $deadLockRetryAttempts);
                }
                catch (\Throwable $ex)
                {
                    // Checks if the exception is caused by db connection loss and reconnects to DB(one retry)
                    // made this change as a fix for production  issue SI-4668
                    $causedByLostConnection = $this->app['db.connector.mysql']->checkAndReloadDBIfCausedByLostConnection($ex);

                    if($causedByLostConnection === true)
                    {
                        $transfers = $this->createForPayment($payment, $input, $deadLockRetryAttempts);
                    }
                    else
                    {
                        $this->trace->traceException($ex, null, TraceCode::PAYMENT_TRANSFER_CREATE_EXCEPTION,[]);

                        throw $ex;
                    }
                }

                $asyncTransfer = true;

                if ($this->checkIfPaymentTransferSyncProcessingAllowed($input, $payment) === true)
                {
                    $asyncTransfer = false;
                }

                if ($asyncTransfer === false)
                {
                    try
                    {
                        $transfers = $this->processPaymentTransfersInSync($payment);
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::PAYMENT_TRANSFER_SYNC_PROCESSING_FAILURE,
                            [
                                'payment_id' => $this->payment->getId()
                            ]
                        );

                        // If sync processing has failed, set asyncTransfer flag based on the transfer_sync_via_cron
                        // experiment to decide whether the transfer should be dispatched to queue
                        if ($this->checkIfTransferSyncProcessingViaCronIsEnabled() === false)
                        {
                            $asyncTransfer = true;
                        }
                    }
                }

                if ($asyncTransfer === true)
                {
                    (new TransferCore())->dispatchForTransferProcessing(TransferConstant::PAYMENT, $payment);

                    $this->trace->info(
                        TraceCode::PAYMENT_DISPATCHED_FOR_TRANSFER_PROCESS,
                        [
                            'payment_id' => $payment->getId(),
                        ]
                    );
                }

                return $transfers;
            });

        $this->logRoutePartnershipV1Guard($payment, $transfers, $input);

        return $transfers;
    }

    protected function checkIfPaymentTransferSyncProcessingAllowed(array $input, Payment\Entity $payment): bool
    {
        $transfersCount = count($input);

        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::ENABLE_TRANSFER_SYNC_PROCESSING_VIA_API,
            $this->mode
        );

        $isExperimentEnabled = ($variant === 'on');

        $transaction = $payment->transaction;

        $txnAndBalanceUpdated = true;

        if ((empty($transaction) === true) or ($transaction->isBalanceUpdated() === false))
        {
            $txnAndBalanceUpdated = false;
        }

        $this->trace->info(TraceCode::PAYMENT_TRANSFER_SYNC_PROCESSING_CHECK,
            [
                'merchant'             => $this->merchant->getId(),
                'isExperimentEnabled'  => $isExperimentEnabled,
                'transfersCount'       => $transfersCount,
                'txnAndBalanceUpdated' => $txnAndBalanceUpdated,
            ]);

        if ($transfersCount <= 3 and ($isExperimentEnabled === true) and ($txnAndBalanceUpdated === true))
        {
            return true;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    protected function processPaymentTransfersInSync(Payment\Entity $payment)
    {
        $redis = $this->app['redis']->connection('secure');

        $semaphore = null;

        $semaphoreConfig = (new Admin\Service)->getConfigKey(['key' => ConfigKey::TRANSFER_SYNC_PROCESSING_VIA_API_SEMAPHORE_CONFIG]);

        if (empty($semaphoreConfig) === true)
        {
            $semaphoreConfig = self::PAYMENT_TRANSFERS_SYNC_PROCESSING_DEFAULT_CONFIG;
        }

        try
        {
            $semaphoreAcquireStartTime = microtime(true);

            $semaphore = new Semaphore($redis->client(), $this->merchant->getId(), (int) $semaphoreConfig['limit']);

            $isSemaphoreAcquired = $semaphore->acquire((float) $semaphoreConfig['retry_interval'], (int) $semaphoreConfig['retries']);

            if ($isSemaphoreAcquired === true)
            {
                $timeTakenToAcquireMs = (microtime(true) - $semaphoreAcquireStartTime) * 1000;

                (new TransferMetric())->pushSemaphoreAcquireSuccessMetrics($timeTakenToAcquireMs);

                if ($this->checkIfTransferSyncProcessingViaApiIsWithinLimit() === false)
                {
                    throw new Exception\RuntimeException('Transfer sync processing limit via API exceeded');
                }

                $this->trace->info(TraceCode::PAYMENT_TRANSFER_PROCESS_IN_SYNC,
                    [
                        'payment_id'     => $payment->getId(),
                        'sem_time_taken_ms' => $timeTakenToAcquireMs
                    ]
                );

                $transfer = new PaymentTransfer($payment);

                $transfersSyncProcessStartTime = microtime(true);

                [$transfersProcessed, $failedTransfersToRetry] = $transfer->process();

                $transfersSyncProcessTimeMs = (microtime(true) - $transfersSyncProcessStartTime) * 1000;

                (new TransferMetric())->pushTransfersProcessingTimeInSyncMetrics($transfersSyncProcessTimeMs);

                return $transfersProcessed->merge($failedTransfersToRetry);
            }
            else
            {
                (new TransferMetric())->pushSemaphoreAcquireFailureMetrics();

                throw new Exception\RuntimeException('Failed to acquire semaphore');
            }
        }
        finally
        {
            $semaphore?->release();
        }
    }



    /**
     * @throws Exception\BadRequestException
     */
    public function cancelEmandatePayment(Payment\Entity $payment)
    {
        $method = $payment->getMethod();

        if (in_array($method, [Payment\Method::EMANDATE, Payment\Method::NACH]) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED, null,
                [
                    'payment_id' => $payment->getPublicId(),
                    'order_id'   => $payment->getPublicOrderId(),
                    'method'     => $payment->getMethod(),
                ]);
        }

        $input = [];

        $this->setPayment($payment);

        $errorCode = $this->repo->transaction(function() use ($payment, $input)
        {
            $this->lockForUpdateAndReload($payment);

            $errorCode = $this->cancelPayment($payment, $input);

            return $errorCode;
        });

        throw new Exception\BadRequestException($errorCode, null,
            [
                'payment_id' => $payment->getPublicId(),
                'order_id'   => $payment->getPublicOrderId(),
                'method'     => $payment->getMethod(),
            ]);
    }

    /**
     * Cancels a previously created payment
     *
     * @param  string $id Id of payment to be captured
     * @return  $status Payment\Status
     * @throws Exception\BadRequestException
     */
    public function cancel($id, $input)
    {
        LocaleCore::setLocale($input, $this->merchant->getId());

        $status = null;

        $payment = $this->retrieve($id);

        if ($payment->isExternal() === true)
        {
            return $this->app['pg_router']->paymentCancel($id, $this->merchant->getId(), true);
        }

        $diff = time() - $payment->getCreatedAt();

        if ($diff > self::PAYMENT_CANCEL_TIME_DURATION)
        {
            $this->segment->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED, null,
             [
                 'payment_id'  => $payment->getPublicId(),
                 'order_id'    => $payment->getPublicOrderId(),
                 'method'      => $payment->getMethod(),
             ]);
        }

        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment, $input)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                // If payment is not in created state, then that means
                // it's already been processed. It's possible that payment
                // may have succeeded. In such cases, we need to send back
                // exact same response as we would have if the payment succeeded
                if ($payment->isCreated() === false)
                {
                    return $this->processPaymentCallbackSecondTime($payment);
                }

                if (empty($input) === false)
                {
                    $this->trace->info(TraceCode::PAYMENT_CANCELLED_METADATA, (array) $input);
                }

                $errorCode = $this->repo->transaction(function() use ($payment, $input)
                {
                    $this->lockForUpdateAndReload($payment);

                    $errorCode = $this->cancelPayment($payment, $input);

                    return $errorCode;
                });

                throw new Exception\BadRequestException($errorCode, null,
                    [
                        'payment_id'  => $payment->getPublicId(),
                        'order_id'    => $payment->getPublicOrderId(),
                        'method'      => $payment->getMethod(),
                    ]);
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }

    protected function cancelPayment($payment, $input)
    {
        $payment->getValidator()->cancelValidate($payment);

        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER;

        if ((isset($input['_']['reason']) === true) and
            (is_string($input['_']['reason']) === true))
        {
            $this->payment->setCancellationReason($input['_']['reason']);
        }

        $e = new Exception\BadRequestException($errorCode);

        if ($payment->merchant->isFeatureEnabled(Feature::CREATED_FLOW))
        {
            $this->setPaymentError($e, TraceCode::PAYMENT_CANCELLED);
        }
        else
        {
            $this->updatePaymentFailed($e, TraceCode::PAYMENT_CANCELLED);
        }

        return $errorCode;
    }

    /**
     * Returns the proper async response for the status checks
     * made by Checkout
     *
     * @param  string $id payment id
     * @return array
     * @throws Exception\BadRequestException
     */
    public function getAsyncResponse($id)
    {
        $response = $this->getCpsRoute($id);

        if (($response !== null) and
            ($response[Payment\Entity::CPS_ROUTE] === Payment\Entity::REARCH_UPI_PAYMENT_SERVICE))
        {
            $payment = $this->retrieveExternalPayment($id);

            return $this->getAsyncResponseUpiRearch($payment);
        }

        $response = $this->getUpiStatus($id);

        if ($response !== null)
        {
            return $response;
        }

        $payment = $this->retrieve($id);

        if ($payment->isRoutedThroughPaymentsUpiPaymentService() === true)
        {
            $value = [
                Payment\Entity::CPS_ROUTE => Payment\Entity::REARCH_UPI_PAYMENT_SERVICE
            ];

            $this->setCpsRoute($payment->getPublicId(), $value);

            return $this->getAsyncResponseUpiRearch($payment);
        }

        $gateway = $payment->getGateway();

        /**
         * As async processing approach is not applicable to paytm card,net-banking and wallet
         * Checking method if not upi returning an Exceptions
         */
        $method = $payment->getMethod();

        if (($gateway === Payment\Gateway::PAYTM or $gateway === Payment\Gateway::CCAVENUE) and
            ($method !== Payment\Method::UPI))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        // If the gateway is not async we just give a generic
        // error to not leak information
        if ((Payment\Gateway::supportsAsync($gateway) === false) and
            ($payment->getAuthenticationGateway() !== Payment\Gateway::GOOGLE_PAY))
        {
            // Throw exception of invalid id
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        // Handle special case where first gateway success happened using verify flow.
        // This is for those gateways where callback flow is not implemented,
        // and we are not aware of the gateway status until hitting their Inquiry API.
        // In such cases, we dont want to stop polling until we get to know failure/success from gateway. Default timeout applies.
        //
        // PS:- should be before checkForRecentFailedPayment
        //
        // - https://razorpay.slack.com/archives/CNP473LRF/p1648449676603789
        // - https://razorpay.slack.com/archives/CNXC0JHQF/p1648817541865879
        if (Payment\Gateway::isTransactionPendingGateway($payment->getMethod(),
                                                         $payment->getGateway(),
                                                         $payment->getInternalErrorCode()))
        {
            $response = [
                Payment\Entity::STATUS => Payment\Status::CREATED
            ];

            if ($payment->getMethod() === Payment\Method::UPI)
            {
                $this->setUpiStatus($payment->getPublicId(), $response);
            }

            return $response;
        }

        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        if ($payment->isCreated() === true)
        {
            // Earlier we were marking payment failed from this flow after 5 minutes of
            // payment creation, The timing out will be centralized from the cron only
            $now = Carbon::now();

            $shouldHaveTimedout = $payment->shouldTimeout($now->getTimestamp());

            // Payment should have timeout, but still in created state. Meaning something is not
            // right with our timeout cron, and that we need to look in to that asap

            if ($shouldHaveTimedout === true)
            {
                $delay = round(($now->getTimestamp() - $payment->getCreatedAt()) / 60);

                $this->trace->error(TraceCode::PAYMENT_SHOULD_HAVE_TIMED_OUT, [
                    'payment_id'    => $payment->getId(),
                    'merchant_id'   => $payment->getMerchantId(),
                    'created_at'    => $payment->getCreatedAt(),
                    'delay'         => $delay,
                    'method'        => $payment->getMethod(),
                    'gateway'       => $payment->getGateway(),
                ]);
            }

            // We will also take a 3 minute buffer, so that payment is actually picked
            // and marked failed from the cron, now if payment is not marked failed even after
            // 3 minutes buffer, we need to ask checkout/merchant to stop polling.
            $now->subMinutes(3);

            if ($payment->shouldTimeout($now->getTimestamp()) === true)
            {
                // We are not marking payment failed from, but only stopping the polling
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT);
            }

            $response = [
                Payment\Entity::STATUS => Payment\Status::CREATED
            ];

            if ($payment->getMethod() === Payment\Method::UPI)
            {
                $this->setUpiStatus($payment->getPublicId(), $response);
            }

            return $response;
        }

        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                $diff = time() - $payment->getCreatedAt();

                if (($payment->hasBeenAuthorized() === true) and
                    ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
                {
                    return $this->postPaymentAuthorizeProcessing($payment);
                }

                $this->app['segment']->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }

    /**
     * Returns the async response for the status check
     * for upi rearch payments
     *
     * @param  string $id payment id
     * @return array
     * @throws Exception\BadRequestException
    /  */
    public function getAsyncResponseUpiRearch(Payment\Entity $payment)
    {
        $gateway = $payment->getGateway();

        if ((Payment\Gateway::supportsAsync($gateway) === false) and
            ($payment->getAuthenticationGateway() !== Payment\Gateway::GOOGLE_PAY))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        if ($payment->isRoutedThroughPaymentsUpiPaymentService() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        if ($payment->isCreated() === true)
        {
            $now = Carbon::now();

            $shouldHaveTimedout = $payment->shouldTimeout($now->getTimestamp());

            // Payment should have timeout, but still in created state. Meaning something is not
            // right with our timeout cron, and that we need to look in to that asap
            if ($shouldHaveTimedout === true)
            {
                $delay = round(($now->getTimestamp() - $payment->getCreatedAt()) / 60);

                $this->trace->error(TraceCode::PAYMENT_SHOULD_HAVE_TIMED_OUT, [
                    'payment_id'    => $payment->getId(),
                    'merchant_id'   => $payment->getMerchantId(),
                    'created_at'    => $payment->getCreatedAt(),
                    'delay'         => $delay,
                    'method'        => $payment->getMethod(),
                    'gateway'       => $payment->getGateway(),
                ]);
            }

            // We will also take a 3 minute buffer, so that payment is actually picked
            // and marked failed from the cron, now if payment is not marked failed even after
            // 3 minutes buffer, we need to ask checkout/merchant to stop polling.
            $now->subMinutes(3);

            if ($payment->shouldTimeout($now->getTimestamp()) === true)
            {
                // We are not marking payment failed from, but only stopping the polling
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT);
            }

            $response = [
                Payment\Entity::STATUS => Payment\Status::CREATED
            ];

            return $response;
        }

        $diff = time() - $payment->getCreatedAt();

        if (($payment->hasBeenAuthorized() === true) and
            ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
        {
            $response = $this->processAuthorizeResponse($payment);

            return $response;
        }

        $this->app['segment']->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
    }

    public function callGatewayFunctionCaptureViaQueue($data, $payment)
    {
        $gatewayCaptureStartTime = microtime(true);

        $this->payment = $payment;

        $this->mutex->acquireAndRelease(
            $this->payment->getId(),
            function() use ($data, $gatewayCaptureStartTime)
            {
                $this->trace->info(TraceCode::CAPTURE_QUEUE_PAYMENT_ID_MUTEX_TIME_TAKEN,
                    [
                        'mutex_time_taken' => (microtime(true) - $gatewayCaptureStartTime) * 1000
                    ]
                );

                $this->repo->reload($this->payment);

                // return if payment is already gateway captured or (status is refunded and  gateway is not paysecure captured_at is null)
                // there can be cases where refund is created state so we need to capture the payment to process the refund
                // paysecure does not support the reverse api so we need to gateway capture the payment and in auto refund case sometimes refund get initiated before capture call
                if (($this->payment->isGatewayCaptured() === true) or
                    (($this->payment->getStatus() === Payment\Status::REFUNDED) and
                    ($this->payment->hasBeenCaptured() === false) and
                    ($this->payment->getGateway() !== Payment\Gateway::PAYSECURE)))
                {
                    return;
                }

                $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

                $this->payment->setGatewayCaptured(true);

                $this->repo->saveOrFail($this->payment);

                if ($this->payment->merchant->isFeatureEnabled(Features::PG_LEDGER_REVERSE_SHADOW) === true)
                {
                    (new ReverseShadowPaymentsCore())->createLedgerEntryForGatewayCaptureReverseShadow($this->payment);
                }

                $this->createLedgerEntriesForGatewayCapture($this->payment);
            }, 20);

//        $this->trace->info(TraceCode::BARRICADE_SQS_PUSH_START,
//            [
//                'data'      => $payment,
//            ]);

        $this->publishMessageToSqsBarricade($payment);
    }

    protected function publishMessageToSqsBarricade($payment)
    {
        try
        {
            //Add delay of 10 minutes
            $waitTime = 600;
            $methodResult = $this->app->razorx->getTreatment($payment->getMethod(), self::BARRICADE_PAYMENT_METHOD, $this->mode);
            if  ($methodResult !== 'on')
            {
                return;
            }

            if ($this->mode !== Mode::LIVE)
            {
                return;
            }

            // To Avoid duplicate Verification
            if ( $payment->isUpi() === true && $payment->getStatus() !== "authorized" ){
                return;
            }

            // To Verify Push Payment Flow
            $action = $payment->isBankTransfer() ? self::BARRICADE_BANK_PAYMENT_VERIFY :
                ($payment->isUpiTransfer() ? self::BARRICADE_UPI_PAYMENT_VERIFY :
                    ($payment->isBharatQr() ? self::BARRICADE_QR_PAYMENT_VERIFY : ''));

            if ($action !== '') {
                $data['action'] = [
                    'verify_action' => $action,
                    'action' => self::PUSH_PAYMENT_VERIFY,
                    'source' => 'api'
                ];
                $data['payment'] = $payment;

                $this->callPushForBarricade($data, $waitTime);
                return;
            }

            $authorizeVerifyCardGateways = $this->app->razorx->getTreatment($payment->terminal->getGateway(),self::BARRICADE_AUTHORIZE_VERIFY_CARD_GATEWAY, $this->mode);
            $gatewayResult = $this->app->razorx->getTreatment($payment->terminal->getGateway(), self::BARRICADE_PAYMENT_GATEWAY, $this->mode);
            // Skip push on capture for AuthorizeVerify Gateways
            // Change it to avoide duplicate payment from card gateway
            if ($payment->isCard() === true && $authorizeVerifyCardGateways === 'on' && $payment->getStatus() !== "authorized" ){
                return;
            }
            // To check card payment is gateway captured if gateway is not autorized
            if ( $payment->isCard() === true && $authorizeVerifyCardGateways === 'control' && $payment->isGatewayCaptured() === false )
            {
                return;
            }

            //Unexpected Payment
            if ( $gatewayResult !== 'on')
            {
                return;
            }

            // Skip push on capture for AuthorizeVerify Gateways
            if ($payment->isCard() === true && $authorizeVerifyCardGateways === 'on' && $payment->getStatus() !== "authorized" ){
                return;
            }

            if ( $payment->isUpi() === true || $payment->isNetbanking() === true ||  $payment-> isWallet() === true )
            {
                $data = $this->getAutorizeVerifyData($payment);
            }
            else
            {
                $data = $this->getCaptureVerifyData($payment);
            }

            //if card gateway is authorizeVerify then waitTime is 0
            if ($authorizeVerifyCardGateways === 'on'){
                $waitTime = 60;
            }
            $this->callPushForBarricade($data, $waitTime);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::BARRICADE_SQS_DATA_CREATE_FAILURE,
                []);
        }

    }

    protected function callPushForBarricade($data, $waitTime){
        try {
            $queueName = $this->app['config']->get('queue.barricade_verify.' . $this->mode);

            $this->app['queue']->connection('sqs')->later($waitTime, "Barricade Queue Push", json_encode($data), $queueName);

            $this->trace->info(TraceCode::BARRICADE_SQS_PUSH_SUCCESS,
                [
                    'queueName' => $queueName,
                    'data' => $data,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::BARRICADE_SQS_PUSH_FAILURE,
                []);
        }
    }

    protected function getAutorizeVerifyData($payment)
    {
        $data = [];

        $data['payment'] = [
            'amount'        => $payment->getAmount(),
            'id'            => $payment->getId(),
            'status'        => $payment->getStatus(),
            'description'   => $payment->getDescription(),
            'vpa'           => $payment->getVpa(),
            'method'        => $payment->getMethod(),
            'base_amount'   => $payment->getBaseAmount(),
            'currency'      => $payment->getCurrency(),
            'created_at'    => $payment->getCreatedAt(),
            'cps_route'     => $payment->getCpsRoute(),
        ];

        $terminal = $payment->terminal;

        $data['terminal'] = [
            'id'                    => $terminal->getId(),
            'gateway'               => $terminal->getGateway(),
            'vpa'                   => $terminal->getVpa(),
            'gateway_merchant_id'   => $terminal->getGatewayMerchantId(),
            'gateway_merchant_id2'  => $terminal->getGatewayMerchantId2(),
            'gateway_terminal_id'   => $terminal->getGatewayTerminalId(),
        ];

        $merchant = $payment->merchant;

        $data['merchant'] = [
            'id' => $merchant->getMerchantId(),
        ];

        $data['upi'] = [
            'gateway'                  => $payment->getGateway(),
            'gateway_amount'           => $payment->getAmount(),
            'npci_reference_id'        => $payment->getReference16(),
            'vpa'                      => $payment->getVpa(),
            'recurring'                => $payment->isRecurring(),

        ];

        $action =$payment->action;

        $data['action'] = [
            'action' => 'verify',
            'source' => 'api'
        ];

        return $data;
    }

    protected function getCaptureVerifyData($payment)
    {
        $data = [];
        $paymentMeta = $payment->paymentMeta;
        $gateway_captured = 0;
        if ($payment->getGatewayCaptured() === true) {
            $gateway_captured = 1;
        }
        $dcc_offered = false;
        $forex_rate = 1.0;
        $dcc_mark_up_percent=0.0;
        if($paymentMeta !== null)  {
            $dcc_offered= $paymentMeta->isDccOffered();
            $forex_rate=$paymentMeta->getForexRate();
            $dcc_mark_up_percent=$paymentMeta->getDccMarkUpPercent();
        }
        $data['payment'] = [
            'id'            => $payment->getId(),
            'amount'        => $payment->getAmount(),
            'currency'      => $payment->getCurrency(),
            'method'        => $payment->getMethod(),
            'status'        => $payment->getStatus(),
            'captured_at'   => $payment->getCapturedAt(),
            'created_at'    => $payment->getCreatedAt(),
            'merchant_id'   => $payment->getMerchantId(),
            'base_amount'   => $payment->getBaseAmount(),
            'gateway_amount' => $payment->getGatewayAmount(),
            'international' => $payment->isInternational(),
            'gateway_captured' => $gateway_captured,
            'gateway_currency' => $payment->getGatewayCurrency(),
            'dcc_markup_amount'=> $payment->getDccMarkUpAmount(),
            'dcc_offered' => $dcc_offered,
            'forex_rate' => $forex_rate,
            'dcc_mark_up_percent'=>$dcc_mark_up_percent,
            'fee_bearer' => $payment->getFeeBearer(),
            'fee' => $payment->getFee(),
            'is_direct_settlement' => $payment->isDirectSettlement(),
            'settled_by' => $payment->getSettledBy(),
            'convert_currency' => $payment->getConvertCurrency(),
        ];
        $terminal = $payment->terminal;

        $data['terminal'] = [
            'id'                  => $terminal->getId(),
            'gateway'             => $terminal->getGateway(),
            'acquirer'            => $terminal->getGatewayAcquirer(),
            'gateway_merchant_id' => $terminal->getGatewayMerchantId(),
            'gateway_terminal_id' => $terminal->getGatewayTerminalId(),
            'mode'                => $terminal->getMode(),
            'currency'            => $terminal->getCurrency(),
        ];

        $card = $payment->card;

        $data['card'] = [
            'type'      => $card->GetType(),
            'issuer'    => $card->getIssuer(),
            'network'   => $card->getNetwork(),
            'authentication_reference_number' => $card->getReference4(),

        ];

        $merchant = $payment->merchant;

        $data['merchant'] = [
            'is_vas_merchant' => $merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::VAS_MERCHANT),
            'send_dcc_compliance' => $merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::SEND_DCC_COMPLIANCE)
        ];

        $authorisation = $this->app['card.payments']->fetchEntity('authorization', $payment->getId());

        if ((empty($authorisation['success']) === false) and
            ($authorisation['success'] === true))
        {
            $data['gateway'] = [
                'gateway_reference_id1'  => $authorisation['gateway_reference_id1'],
                'gateway_reference_id2'  => $authorisation['gateway_reference_id2'],
                'verify_id'              => $authorisation['verify_id'],
                'gateway_transaction_id' => $authorisation['gateway_transaction_id'],
            ];
        }

        $data['action'] = [
            'action' => 'verify',
            'source' => 'api'
        ];

        return $data;
    }

    protected function tracePaymentInfo($traceCode, $level = Trace::INFO)
    {
        $data = $this->payment->toArrayTraceRelevant();

        $this->trace->addRecord($level, $traceCode, $data);
    }

    public function timeoutPayment()
    {
        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CRON_TIMEOUT_INITIATED, $this->payment);

        $payment = $this->payment;

        $traceCode = TraceCode::PAYMENT_TIMED_OUT;
        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT;

        if ($payment->getInternalErrorCode() !== null)
        {
            $errorCode = $payment->getInternalErrorCode();

            $traceCode = TraceCode::PAYMENT_STATUS_FAILED;
        }

        $exception = new Exception\BadRequestException($errorCode);

        $this->updatePaymentFailed($exception, $traceCode);
    }

    public function failNotificationNotSentCardAutoRecurringPayment(Payment\Entity $payment)
    {
        $this->payment = $payment;

        $traceCode = TraceCode::PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT;

        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT;

        $exception = new Exception\BadRequestException($errorCode);

        $this->updatePaymentFailed($exception, $traceCode);

        throw $exception;
    }

    public function failMandateCanceledCardAutoRecurringPayment(Payment\Entity $payment)
    {
        $this->payment = $payment;
        $traceCode = TraceCode::PAYMENT_CARD_MANDATE_CANCELLED_BY_USER;

        $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_CANCELLED_BY_USER;

        $data = [
            'payment_id' => $payment->getPublicId(),
            'order_id'   => $payment->getPublicOrderId(),
            Payment\Entity::METHOD => Payment\Method::CARD
        ];

        $exception = new Exception\BadRequestException($errorCode,null, $data);

        $this->updatePaymentFailed($exception, $traceCode);

        if ($payment->getCallbackUrl() === null)
        {
            throw $exception;
        }

        $returnData = [
            'razorpay_payment_id'      => $payment->getPublicId(),
            'razorpay_order_id'        => $payment->getPublicOrderId(),
            'error_code'               => $exception->getError()->getPublicErrorCode(),
            'error_desc'               => $exception->getError()->getDescription(),
            'error_source'             => $exception->getError()->getSource(),
            'error_step'               => $exception->getError()->getStep(),
            'error_reason'             => $exception->getError()->getReason(),
        ];

        $this->fillReturnRequestDataForMerchant($payment,$returnData);

        return $returnData;
    }

    public function failInvalidRecurringTokenCardAutoRecurringPayment(Payment\Entity $payment, $exception)
    {
        $this->payment = $payment;

        $traceCode = TraceCode::RECURRING_TOKEN_DELETED_OR_EXPIRED;

        $this->updatePaymentFailed($exception, $traceCode);
    }

    public function failPreDebitNotificationDeliveryToHubFailedCardAutoRecurringPayment(Payment\Entity $payment, $exception)
    {
        $this->payment = $payment;

        $traceCode = TraceCode::PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT;

        $this->updatePaymentFailed($exception, $traceCode);
    }

    public function failNotificationVerifyFailedCardAutoRecurringPayment(Payment\Entity $payment, $exception)
    {
        $this->payment = $payment;

        $traceCode = TraceCode::PAYMENT_CARD_MANDATE_NOTIFICATION_VERIFY_FAILED;

        $this->updatePaymentFailed($exception, $traceCode);
    }

    public function failMandateCreationFailedCardInitialRecurringPayment(Payment\Entity $payment, $exception)
    {
        $this->payment = $payment;

        $traceCode = TraceCode::PAYMENT_CARD_MANDATE_CREATION_FAILED;

        $this->updatePaymentFailed($exception, $traceCode);
    }

    public function failMandateHQPaymentAFANotApproved(Payment\Entity $payment)
    {
        $this->payment = $payment;

        $traceCode = TraceCode::PAYMENT_CARD_MANDATE_AFA_NOT_APPROVED;

        $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_APPROVED;

        $exception = new BadRequestException($errorCode,
            null,
            null,
            'The payment request failed as the predebit notification was not approved');

        $this->updatePaymentFailed($exception, $traceCode);
    }

    protected function updatePaymentFailed($exception, $traceCode)
    {
        $error = $exception->getError();

        $code = $error->getPublicErrorCode();

        $desc = $error->getEnglishDescription() !== null ? $error->getEnglishDescription() : $error->getDescription();

        $internalCode = $error->getInternalErrorCode();

        $updatedPayment = null;

        try
        {
            $updatedPayment = $this->repo->payment->findOrFail($this->payment->getId());
        }
        catch (\Throwable $exception){}

        if(isset($updatedPayment) === true)
        {
            $updatedStatus = $updatedPayment->getStatus();

            $this->payment->setStatus($updatedStatus);
        }

        $payment = $this->payment;

        $error->setDetailedError($internalCode, $payment->getMethod());

        $step = $error->getStep();

        $source = $error->getSource();

        $reason = $error->getReason();

        $internalErrorDetails = [
            'step'                  => $step,
            'reason'                => $reason,
            'source'                => $source,
            'internal_error_code'   => $internalCode,
        ];

        $status = $payment->getStatus();

        $segmentCustomProperties = [
            'error'                 => $error,
            'code'                  => $code,
            'description'           => $desc,
            'internal_error_code'   => $internalCode,
            'status'                => $status
        ];

        $this->segment->trackPayment($payment, $traceCode, $segmentCustomProperties);

        $shouldRunForEmandateFailedPayment = false;

        // we run this again for failed emandate payments to update the error codes
        if (($payment->isEmandate() === true) and ($payment->hasNotBeenAuthorized() === true))
        {
            $shouldRunForEmandateFailedPayment = true;
        }

        if (($status !== Status::CREATED) and
            ($status !== Status::AUTHENTICATED) and
            ($status !== Status::PENDING) and
            ($shouldRunForEmandateFailedPayment === false))
        {
            throw new Exception\LogicException(
                'Payment not in the appropriate status to be marked as failed.',
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'status'        => $status
                ]);
        }

        $payment->setStatus(Payment\Status::FAILED);

        $this->trace->info(
            TraceCode::PAYMENT_STATUS_FAILED,
            [
                'payment_id'    => $payment->getId(),
                'old_status'    => $status,
                'error'         => $error,
                'segment_data'  => $segmentCustomProperties,
            ]
        );

        $payment->setError($code, $desc, $internalCode);

        if(($payment->isEmandate() === true or $payment->isNach() === true) and $this->emandateDescError !== null)
        {
            $payment->setEmandateErrorDesc($this->emandateDescError);
        }

        if (($exception instanceof Exception\GatewayErrorException) and
            ($this->payment->merchant !== null) and
            ($this->payment->merchant->isFeatureEnabled(Features::EXPOSE_GATEWAY_ERRORS) === true))
        {
            $data = $exception->getGatewayErrorCodeAndDesc();

            if (empty($data[0]) === false)
            {
                $data = [
                    Payment\Entity::GATEWAY_ERROR_CODE          => $data[0],
                    Payment\Entity::GATEWAY_ERROR_DESCRIPTION   => $data[1],
                ];

                $payment->setReference17(json_encode($data));
            }

        }

        $this->updateVerifyBucketOnPaymentFailure($exception);

        $this->repo->saveOrFail($payment);

        $this->tracePaymentFailed($error, $traceCode);

        (new Payment\Metric)->pushFailedMetrics($payment);

        $this->eventPaymentFailed($exception);

        (new Notify($this->payment))->trigger(Payment\Event::CUSTOMER_FAILED);

        if ($this->merchant->isFeatureEnabled(Feature::PAYMENT_FAILURE_EMAIL) === true)
        {
            $notifier = new Notify($this->payment);

            $notifier->trigger(Payment\Event::FAILED);
        }

        if($traceCode !== TraceCode::PAYMENT_TIMED_OUT)
        {
            $offer = new Offer\Core();

            $offer->lockDecrementCurrentOfferUsage($payment);
        }

        if ($this->sendDopplerFeedback === true)
        {
            try
            {
                $this->app->doppler->sendFeedback($this->payment, Doppler::PAYMENT_AUTHORIZATION_FAILURE_EVENT, $code, $internalErrorDetails);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(
                    TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_FAILED,
                    [
                        'payment'             => $this->payment->toArray(),
                        'code'                => $code,
                        'internal_code'       => $internalCode,
                        'error'               => $e->getMessage()
                    ]
                );
            }
        }

        if ($this->shouldDisableUpiTerminal($payment, $error) === true)
        {
            $this->disableUpiTerminalIfRequired($payment);
        }
    }

    protected function shouldDisableUpiTerminal(Payment\Entity $payment, $error)
    {
        if ($payment->getMethod() !== Payment\Method::UPI)
        {
            return false;
        }

        if ($error === null)
        {
            return false;
        }

        $disableErrorCodes = [
            'U16',
        ];

        $gatewayErrorCode = $error->getGatewayErrorCode();

        $shouldDisable = in_array($gatewayErrorCode, $disableErrorCodes, true);

        // Return after gateway error code check for non-prod envs
        if (app()->isEnvironmentProduction() === false)
        {
            return $shouldDisable;
        }

        $variant = $this->app['razorx']->getTreatment(
            $this->app['request']->getTaskId(),
            Merchant\RazorxTreatment::DISABLE_UPI_TERMINAL,
            $this->app['rzp.mode']);

        return (($variant === 'on') and $shouldDisable);
    }

    protected function disableUpiTerminalIfRequired(Payment\Entity $payment)
    {
        if ($payment->getMethod() !== Payment\Method::UPI)
        {
            return ;
        }

        $dispatchData = [
            'mode'          =>  $this->mode,
            'terminal_id'   =>  $payment->getTerminalId(),
            'payment_id'    =>  $payment->getId(),
        ];

        $this->trace->info(
            TraceCode::TERMINAL_QUEUE_DATA,
            $dispatchData
        );

        try
        {
            TerminalDisable::dispatch($dispatchData);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TERMINAL_QUEUE_DISPATCH_FAILURE
            );
        }
    }

    /**
     * Checks for risk failures and creates log in risk table
     *
     * @param        $payment Payment\Entity
     * @param string $internalErrorCode
     */
    public function logRiskFailureForGateway(
        Payment\Entity $payment,
        string $internalErrorCode)
    {
        $riskData = Risk\FailureCodeMap::getRiskDataForError($internalErrorCode);

        // If it is not error raised due to fraud failure, ignore everything
        if (empty($riskData) === true)
        {
            return;
        }

        $source = $riskData[Risk\Entity::SOURCE];

        (new Risk\Core)->logPaymentForSource($payment, $source, $riskData);
    }

    protected function updateVerifyBucketOnPaymentFailure(Exception\BaseException $e)
    {
        $payment = $this->payment;

        $payment->setVerified(null);

        $payment->setVerifyBucket(0);

        $payment->setVerifyAt(time() + 120);

        //
        // In case the gateway error exception is thrown on authenticate
        // we set verify bucket to null
        //
        if ($e instanceof Exception\GatewayErrorException)
        {
            if (in_array($e->getAction(), Action::$nonVerifiableActions, true) === true)
            {
                $payment->setNonVerifiable();
            }
        }

        // If payment still doesnt exist we set verify_at as null
        // So that this payment doesnt get picked up by any cron
        // for verify
        if ($payment->exists === false)
        {
            $payment->setNonVerifiable();
        }
    }

    protected function setTwoFactorAuthAfterCallbackException(Exception\BaseException $exception)
    {
        $payment = $this->payment;

        //
        // For Netbanking and emandate payments two_factor_auth
        // was set to NOT_APPLICABLE on authorize itself
        //
        if (($payment->isNetbanking() === true) or
            ($payment->isEmandate() === true))
        {
            $twoFactorAuth = Payment\TwoFactorAuth::UNAVAILABLE;
        }
        else if (($exception instanceof Exception\GatewayErrorException) and
                 ($exception->hasTwoFaError()))
        {
            $twoFactorAuth = Payment\TwoFactorAuth::FAILED;
        }
        else
        {
            $twoFactorAuth = Payment\TwoFactorAuth::UNKNOWN;
        }

        $payment->setTwoFactorAuth($twoFactorAuth);
    }

    public function eventPaymentFailed($exception)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $this->payment
        ];

        $this->app['events']->dispatch('api.payment.failed', $eventPayload);

        $event = $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHORIZATION_FAILED, $this->payment, $exception);

        $this->app['shield.service']->enqueueShieldEvent($event);
    }

    protected function setPaymentError(Exception\BaseException $e, $traceCode)
    {
        $payment = $this->payment;

        $error = $e->getError();

        $internalCode = $error->getInternalErrorCode();

        $this->trace->info(
            $traceCode,
            [
                'payment_id'    => $payment->getId(),
                'status'        => $payment->getStatus(),
                'error'         => $error,
                'internalCode'  => $internalCode,
            ]
        );

        $payment->setInternalErrorCode($internalCode);

        $this->repo->saveOrFail($payment);
    }

    /**
     * @param $gatewayData
     * @param $action
     * @throws Exception\BadRequestException
     * Updates related to recurring transactions
     */
    protected function addCardMandateDataIfApplicable(& $gatewayData, $action) {

        $token = $this->payment->getGlobalOrLocalTokenEntity();

        if($this->shouldSendCardMandateDetails($action) === true) // Also check if the card has a registered mandate
        {
            $cardMandate = $token->cardMandate;

            if(($cardMandate->isActive() === true) || ($cardMandate->isMandateApproved() === true)) {
                // Set value available from the card_mandates table for the respective transaction
                $gatewayData['card_mandate']['max_debit_amount']     = $cardMandate->getMaxAmount();

                $gatewayData['card_mandate']['mandate_id']           = $cardMandate->getMandateId();

                $gatewayData['card_mandate']['recurring_frequency']  = $cardMandate->getFrequency();

                // Value sets at Mozart based on, if the payment has passed through some mandate manager like MandateHQ.
                $gatewayData['card_mandate']['is_mandate_validated'] = $cardMandate->isMandateValidated();

                // Value sets at Mozart based on number of recurring transactions - '99'
                $gatewayData['card_mandate']['recurring_count']      = $cardMandate->getRecurringCount();

                // Value sets at Mozart based on Fixed Amount or Max Amount for Recurring payment - 1/2
                $gatewayData['card_mandate']['pay_type']             = $cardMandate->getPayType();

                $gatewayData['card_mandate']['start_date']           = $cardMandate->getStartAt();

                $gatewayData['card_mandate']['end_date']             = $cardMandate->getEndAt();

                $gatewayData['card_mandate']['min_debit_amount']     = $cardMandate->getAmount();

            }
            else {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE);
            }

            $this->trace->info(
                TraceCode::TRACE_RECURRING_PAYMENT_CARD_MANDATE,
                [
                    'payment_id'   => $this->payment->getId(),
                    'card_mandate' => $gatewayData['card_mandate'],
                    'traceSlug'    => 'AddCardMandateDataIfApplicable'
                ]
            );
        }
        else if ($this->shouldPopulateSubscriptionInfoToGatewayData())
        {
            $gatewayData['card_mandate']['recurring_frequency'] = CardMandate\MandateHubs\MandateHQ\Constants::FREQUENCY_AS_PRESENTED;

        }
    }

    private function shouldPopulateSubscriptionInfoToGatewayData(){
        $merchant = $this->payment->merchant;
        if(Country::matches($merchant->getCountry() , Country::MY) && $this->payment->isRecurring())
        {
            return true;
        }
        return false;
    }

    /**
     * @param $action
     * @return bool
     */
    protected function shouldSendCardMandateDetails($action) : bool
    {
        $payment = $this->payment;

        if ($payment->isCardMandateRecurringAutoPayment() === true)
        {
            return true;
        }

        if ((($action === Payment\Action::PAY or $payment->getGateway() === Payment\Gateway::PAYSECURE) and
             $payment->isCardMandateRecurringInitialPayment() === true))

        {
            return true;
        }
        return false;
    }

    /**
     * Responsible for calling the gateway function
     *
     * @param  string $action      refund/capture etc.
     * @param  array  $gatewayData Relevant input for the corresponding
     *                             action
     *
     * @return array or null
     * @throws Exception\GatewayErrorException
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    protected function callGatewayFunction($action, array $gatewayData)
    {
        if ($this->shouldCallGatewayFunction() === false)
        {
            return;
        }

        $terminal = $this->repo->terminal->fetchForPayment($this->payment);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                null,
                ['payment_id' => $this->payment->getId(),
                    'method'  => $this->payment->getMethod()]);
        }

        $gateway = $this->payment->getGateway();

        if($gateway === Payment\Gateway::PAYLATER)
        {
            switch ($this->payment->getWallet())
            {
                case Payment\Gateway::GETSIMPL:
                    $gateway = Payment\Gateway::GETSIMPL;
                    break;
                case PayLater::ICICI:
                    $gateway = Payment\Gateway::PAYLATER_ICICI;
                    break;
            }
        }

        $gatewayData['terminal'] = $terminal;

        $gatewayData['merchant'] = $this->payment->merchant;

        //This is a temporary change till this is not migrated to claims service for handling gateway files.
        $this->processFirstDataCallback($action, $gatewayData);

        if ($this->isRoutedThroughCps($action, $gatewayData) === true)
        {
            // If CPS service is enabled then route this payment via CPS
            if ((bool) ConfigKey::get(ConfigKey::CPS_SERVICE_ENABLED, false) === true)
            {
                // Persist card details only when payment method is card or emi
                if ($this->payment->isMethodCardOrEmi() === true)
                {
                    $this->persistCardDetails($gateway, $action, $gatewayData);
                }

                //Temp changes for yesb as we need to route only authorize and verify to cps not callback.
                if (($action === Action::CALLBACK) and ($gateway === Payment\Gateway::NETBANKING_YESB))
                {
                    $gatewayData[Payment\Entity::CPS_ROUTE] = Payment\Entity::API;

                    $this->trace->info(TraceCode::GATEWAY_CPS_SWITCH_ROUTE_CALLBACK, [
                        'payment_id'             => $this->payment->getId(),
                        'gateway_cps_route'      => Payment\Entity::API,
                    ]);
                }
                else
                {
                    $gatewayData[Payment\Entity::CPS_ROUTE] = Payment\Entity::CORE_PAYMENT_SERVICE;
                }
            }
            // Else if this payment was earlier authorized by CPS then disable the cps_route flag
            else if ($action !== Action::AUTHORIZE)
            {
                $this->payment->disableCpsRoute();

                $this->repo->saveOrFail($this->payment);

                $this->trace->info(TraceCode::CPS_SWITCH_ROUTE, [
                    'payment_id'     => $this->payment->getId(),
                    'cps_route'      => Payment\Entity::API,
                ]);
            }
        }
        else if ($this->isRoutedThroughCardPayments($action, $gatewayData) === true)
        {
            if ($this->isCardPaymentServiceConfigEnabled() === true)
            {
                $gatewayData[Payment\Entity::CPS_ROUTE] = Payment\Entity::CARD_PAYMENT_SERVICE;
                // Persist card details only when payment method is card or emi
                if ($this->payment->isMethodCardOrEmi() === true)
                {
                    $this->persistCardDetails($gateway, $action, $gatewayData,$this->payment->card->toArray());
                }

                // Card Mandate flow for recurring transactions
                $this->addCardMandateDataIfApplicable($gatewayData, $action);
            }

            // card flow doesn't have any debit action
            if ($action === Action::DEBIT)
            {
                return;
            }
        }
        else if ($this->isRoutedThroughNbPlusService($action, $gatewayData) === true)
        {
            $gatewayData[Payment\Entity::CPS_ROUTE] = Payment\Entity::NB_PLUS_SERVICE;

            $gatewayData['callbackUrl'] = $this->getCallbackUrl();

            // netbanking flow doesn't have any debit action
            // TODO: handle when migrating wallets flow
            if ($action === Action::DEBIT)
            {
                return;
            }
        }
        else if ($this->isRoutedThroughUpiPaymentService($action, $gatewayData) === true)
        {
            $gatewayData[Payment\Entity::CPS_ROUTE] = $gatewayData[E::PAYMENT][Payment\Entity::CPS_ROUTE];
        }

        $gatewayData['merchant_detail'] = $this->repo->merchant_detail->fetchForMerchant($this->payment->merchant);

        //
        // This data was earlier picked up from env by gateways themselves.
        // With the migration to CPS, it will become necessary for API to pick
        // the values from env and pass them to CPS. As an intermediate step,
        // we are passing relevant config from API to gateway, and blocking
        // gateways from accessing env. It will then be easier to use CPS as
        // a drop-in replacement for Gateway.
        //
        $this->addGatewayConfig($gatewayData);

        // Wrapping all gateway call, We can take actions on Exception here.
        try
        {
            if ((is_array($gatewayData) === true) and (isset($gatewayData[Payment\Entity::CPS_ROUTE]) === true))
            {
                switch ($gatewayData[Payment\Entity::CPS_ROUTE])
                {
                    case Payment\Entity::CORE_PAYMENT_SERVICE:
                        return $this->app['cps']->action($gateway, $action, $gatewayData);

                    case Payment\Entity::CARD_PAYMENT_SERVICE:
                        return $this->callCpsAction($this->payment, $gateway, $action, $gatewayData);
                    case Payment\Entity::NB_PLUS_SERVICE:
                        return $this->callNbPlusServiceAction($this->payment, $gateway, $action, $gatewayData);
                    case Payment\Entity::UPI_PAYMENT_SERVICE:
                        return $this->callUpiPaymentServiceAction($this->payment, $gateway, $action, $gatewayData);
                    case Payment\Entity::REARCH_UPI_PAYMENT_SERVICE:
                        return $this->callUpiPaymentServiceAction($this->payment, $gateway, $action, $gatewayData);
                }
            }

            return $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);
        }
        catch (Exception\GatewayErrorException $ex)
        {
            $error = $ex->getError();

            $data = $ex->getData();

            $data['method'] = $this->payment->getMethod();

            $ex->setData($data);

            $this->changeTerminalCapabilityIfApplicable($terminal, $error);

            $this->addBackupMethodForRetry($this->payment, $this->merchant, $ex);

            throw $ex;
        } catch (Exception\BaseException $e)
        {
            $this->addBackupMethodForRetry($this->payment, $this->merchant, $e);
            throw $e;
        }
    }

    protected function processFirstDataCallback($action, $input)
    {
        if (($action === Action::CALLBACK) and
            (isset($input['payment']) === true) and
            ($input['payment'][Payment\Entity::GATEWAY] === Payment\Gateway::FIRST_DATA) and
            ($input['payment'][Payment\Entity::AUTHENTICATION_GATEWAY] === Payment\Gateway::MPI_BLADE) and
            ($input['payment'][Payment\Entity::CPS_ROUTE]) !== Payment\Entity::CARD_PAYMENT_SERVICE)
        {

            $str = $input['terminal'][Terminal\Entity::GATEWAY_MERCHANT_ID] . '|' . $input['payment']['id'] . '|' .
                $input['gateway'][\RZP\Gateway\Mpi\Blade\PARes::GATEWAY_PARES];

            $key = \RZP\Gateway\FirstData\Gateway::PARES_DATA_CACHE_KEY . $input['payment']['id'];

            try
            {
                $this->app['cache']->put($key, $str, 60 * 25 * 60); // 1 day 1 hour (in seconds)
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::GATEWAY_RAW_PARES_RESPONSE_REDIS_FAILURE,
                    ['key' => $key]);
            }

            $this->trace->info(TraceCode::GATEWAY_RAW_PARES_RESPONSE, [
                'pares' => $input['gateway'][\RZP\Gateway\Mpi\Blade\PARes::GATEWAY_PARES],
                'store_id' => $input['terminal'][Terminal\Entity::GATEWAY_MERCHANT_ID],
            ]);
        }
    }

    public function isGatewayDowntimeAction(string $action)
    {
        $gatewayDowntimeActions = [
            Action::AUTHENTICATE,
            Action::AUTHORIZE,
            Action::CALLBACK
        ];

        return in_array($action, $gatewayDowntimeActions, true);
    }

    public function isRoutedThroughCps($action, $input): bool
    {
        /**
         * This checks if the current request has to be routed to
         * core payment service or not. We are setting this flag(`cps_route`)
         * for new payments based on variant returned by RazorX.
         */
        if ((is_array($input) === true) and
            (isset($input[E::PAYMENT]) === true) and
            ($input[E::PAYMENT][Payment\Entity::CPS_ROUTE] === Payment\Entity::CORE_PAYMENT_SERVICE) and
            (in_array($action, Action::$cpsSupportedActions) === true))
        {
            return true;
        }

        return false;
    }

    public function isRoutedThroughCardPayments($action, $input): bool
    {
        /**
         * This checks if the current request has to be routed to
         * card payment service or not. We are setting this flag(`cps_route`)
         * for new payments based on variant returned by RazorX.
         */
        if ((is_array($input) === true) and
            (isset($input[E::PAYMENT]) === true) and
            ($input[E::PAYMENT][Payment\Entity::CPS_ROUTE] === Payment\Entity::CARD_PAYMENT_SERVICE) and
            (
                (in_array($action, Action::$cardPaymentsSupportedActions) === true) or
                ($action === Action::FORCE_AUTHORIZE_FAILED and (in_array($input[E::PAYMENT][Payment\Entity::GATEWAY], self::FORCE_AUTHORIZE_FAILED_ALLOW_GATEWAYS, true) === true))
            ))
        {
            return true;
        }

        return false;
    }

    public function isRoutedThroughNbPlusService($action, $input): bool
    {
        /**
         * This checks if the current request has to be routed to
         * nb plus service or not. We are setting this flag(`cps_route`)
         * for new payments based on variant returned by RazorX.
         */
        if ((is_array($input) === true) and
            (isset($input[E::PAYMENT]) === true) and
            ($input[E::PAYMENT][Payment\Entity::CPS_ROUTE] === Payment\Entity::NB_PLUS_SERVICE) and
            (in_array($action, NbPlusPaymentService\Action::SUPPORTED_ACTIONS) === true))
        {
            return true;
        }

        if ((is_array($input) === true) and
            (isset($input[E::PAYMENT]) === true) and
            ($input[E::PAYMENT][Payment\Entity::CPS_ROUTE] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS) and
            (in_array($action, NbPlusPaymentService\Action::PAYMENTS_SUPPORTED_ACTIONS) === true))
        {
            return true;
        }

        return false;
    }

    public function isRoutedThroughUpiPaymentService($action, $input): bool
    {
        // API refunds are processed through API gateway layer itself
        if (($action === Action::REFUND) or
            ($action === Action::VERIFY_REFUND))
        {
            return false;
        }
        /**
         * We check if the current request is to be routed through UPI payments service,
         * We set `cps_route` as 4 for gateways to be processed through service. The
         * flag is set based on config.
        */
        $cpsRoute = $input[E::PAYMENT][Payment\Entity::CPS_ROUTE]?? null;

        return (($cpsRoute === Payment\Entity::UPI_PAYMENT_SERVICE) ||
                ($cpsRoute === Payment\Entity::REARCH_UPI_PAYMENT_SERVICE));
    }

    protected function persistCardDetails($gatewayName, $action, &$input , $cardArray=[])
    {
        $action = snake_case($action);

        if ($action === Action::AUTHORIZE)
        {
            $this->persistCardDetailsTemporarily($input);
        }
        else if ($action === Action::CALLBACK or $action === Action::PAY)
        {
            $this->setCardNumberAndCvv($input,$cardArray);
        }
        else if ($action === Action::CAPTURE)
        {
            try
            {
                $this->setCardNumberAndCvv($input,$cardArray);
            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::CARD_DETAILS_DECRYPTION_ERROR,
                    [
                        'message'       => 'Failed to detokenize/decrypt data'
                    ]
                );
            }
        }
    }

    protected function addGatewayConfig(array & $gatewayData)
    {
        $commonGatewayConfig = $this->app['config']->get('gateway');

        if (isset($commonGatewayConfig[$this->payment->getGateway()]) === true)
        {
            $gatewayData['gateway_config'] = $commonGatewayConfig[$this->payment->getGateway()];
        }
    }

    protected function isAffordabilityReusePayment($input)
    {
        if (($input['method'] === Payment\Method::CARDLESS_EMI) === true or ($input['method'] === Payment\Method::PAYLATER) === true)
        {
            if ((isset($input['ott']) === true) and
                (isset($input['payment_id']) === true))
            {
                return true;

            }
            // in case of flexmoney ott would not be present in payment request

            if ((isset($input['payment_id']) === true) and isset($input['provider']) === true )
            {
                if (((empty($input['emi_duration']) === false) and
                    (Payment\Gateway::isCardlessEmiProviderAndRedirectFlowProvider($input['provider']) === true)) )
                {
                    return true;
                }
            }
        }
        return false;
    }

    protected function createPaymentEntity(array $input, Payment\Entity $payment = null): Payment\Entity
    {
        $this->tracePaymentNewRequest($input);

        if ($this->isAffordabilityReusePayment($input) === true)
        {
            $payment = null;

            try
            {
                $payment = $this->repo->payment->findOrFail(Payment\Entity::stripDefaultSign($input['payment_id']));
            }
            catch (\Throwable $exception){}
        }

        if ($payment == null)
        {
            $payment = $this->buildPaymentEntity($input);
        }

        $this->validateDisableS2SIfApplicable($payment, $input);

        if ($this->merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            $this->verifyProvidedFee($payment, $input);
        }

        $this->addOrderIdToInputForSubscriptionIfApplicable($input, $payment);

        $this->setCaptureForS2sCapturePayment($payment, $input);

        $this->validateAndSetOrderDetailsIfApplicable($payment, $input);

        $this->validateAndSetPaymentLinkIfApplicable($payment, $input);

        $this->validateAndSetReceiverIfApplicable($payment, $input);

        $this->validateBankTransferDetailsIfApplicable($payment);

        $this->validateAndSetInvoiceDetailsIfApplicable($payment);

        $this->validateUpiRecurringIfApplicable($payment, $input);

        $this->validateAndProcessCardRecurringIfApplicable($payment, $input);

        $this->setApplicationIfApplicable($payment, $input);

        $this->setGooglePayMethodsIfApplicable($payment, $input);

        $this->setForceTerminalIdIfApplicable($payment, $input);

        $this->setPayerAcccountTypeIfApplicable($payment, $input);

        $this->setAxisTokenHQGatewayIfApplicable($payment, $input);

        // Please keep this function at the end of transaction block, as
        // we are updating orders which lies in PG Router service now.
        // This has been done to temporarily handle the distributed transaction failures.
        $this->updateExternalOrder();

        $metadata = $payment->getMetadata();

        $this->trace->info(
            TraceCode::PAYMENT_METADATA,
            [
                'metadata'   => $metadata,
                'payment_id' => $payment->getId()
            ]);

        $this->payment = $payment;

        return $payment;
    }

    protected function updateExternalOrder()
    {
        if (($this->order !== null) and
            ($this->order->isExternal() === true))
        {
            $input = [
                Order\Entity::ATTEMPTS => $this->order->getAttempts(),
                Order\Entity::STATUS   => $this->order->getStatus()
            ];

            $order = $this->order;
            $this->app['pg_router']->updateInternalOrder($input,$order->getId(),$order->getMerchantId(), true);
        }
    }

    /**
     * This is required when the first charge is done via auth transaction.
     * We need to use the invoice which was created during subscription
     * creation.
     * For the subsequent charges, this is handled since we send order_id as
     * part of the payment create request itself. Since, the payment is created internally.
     * The first charge (payment) is created by the merchant and hence not feasible to ask
     * them to send an order_id along with the subscription_id.
     *
     * @param array          $input
     * @param Payment\Entity $payment
     *
     * @throws Exception\BadRequestException
     */
    protected function addOrderIdToInputForSubscriptionIfApplicable(array & $input, Payment\Entity $payment)
    {
        if (($this->subscription !== null) and
            (($this->subscription->isCreated() === true) or
             ((boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false)) === true))  and
            ($payment->getMethod() === Payment\Method::UPI))
        {
            $this->upiMandate = $this->app['repo']->upi_mandate->findByOrderId($this->order['id']);

            if($this->upiMandate === null)
            {
                $frequencyUpiAutoPay = UPIMandateFrequency::AS_PRESENTED;

                if ($this->app['razorx']->getTreatment($payment->merchant->getId(), Merchant\RazorxTreatment::UPI_AUTOPAY_CORRECT_FREQUENCY_FETCH, $this->app['rzp.mode']) === 'on')
                {
                    try
                    {
                        $subscriptionInput = [
                            Payment\Entity::SUBSCRIPTION_ID => Subscription\Entity::getSignedId($payment->getSubscriptionId())
                        ];

                        $subscriptionData = $this->app['module']->subscription->fetchSubscriptionInfoUpiAutoPay($subscriptionInput, $payment->merchant);

                        $frequencyUpiAutoPay = $subscriptionData['frequency'] ?? $frequencyUpiAutoPay;
                    }
                    catch (\Exception $ex)
                    {
                        $this->trace->traceException(
                            $ex,
                            Trace::CRITICAL,
                            TraceCode::UPI_AUTOPAY_SUBSCRIPTIONS_FETCH_FAILURE);
                    }
                }

                //upi token expires 1 week past the subscription's end_at
                $upitoken = [
                    'max_amount' => $this->subscription->getCurrentInvoiceAmount(),
                    'frequency' => $frequencyUpiAutoPay,
                    'start_at' => Carbon::now()->addMinute(1)->getTimestamp(),
                    'expire_at' => $this->subscription->getEndAt() + self::UPI_SUBSCRIPTION_MANDATE_EXPIRY_EXTENSION,
                ];

                $this->trace->info(
                    TraceCode::UPI_MANDATE_SUBSCRIPTION_CREATE,
                    [
                        'upi_token_info' => $upitoken,
                        'subscriptionId' => $this->subscription->getId(),
                    ]);

                $core = new Core();

                $orderId = $input['order_id'];

                $this->upiMandate = $core->create($upitoken, $this->order, null);
            }

        }

        if (($this->subscription === null) or ($this->subscription->isExternal() === true))
        {
            return;
        }

        //
        // In case the subscription is in active or halted state,
        // we don't want to add the order_id to the input.
        // 1. It would already be present if it's automated charge.
        // 2. Change card flow is being done. Hence, no invoice and stuff.
        //
        if ($this->subscription->isCreated() === true)
        {
            $this->addOrderIdToInputForCreatedSubscription($input);
        }
        else
        {
            $cardChange = boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false);

            if ($cardChange === true)
            {
                $subscription = $this->subscription;

                if ($subscription->isCardChangeStatus() === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_SUBSCRIPTION_CARD_CHANGE_NOT_ALLOWED,
                        null,
                        [
                            'subscription_id'       => $subscription->getId(),
                            'subscription_status'   => $subscription->getStatus(),
                        ]);
                }

                //
                // We do this because we are going to attempt to charge the invoice
                // directly along with card change.
                //
                if ($subscription->isPending() === true)
                {
                    $this->addOrderIdToInputForPendingSubscription($subscription, $input);
                }
            }
        }
    }

    protected function addOrderIdToInputForCreatedSubscription(array & $input)
    {
        //
        // Invoice would have been created if:
        // - First charge needs to be done as part of authentication with or without addons
        // - Only addons need to be added, and no first charge needs to be done as part of authentication.
        //
        $subscriptionInvoices = $this->repo->invoice->fetchIssuedInvoicesOfSubscription($this->subscription);

        $subscriptionInvoicesCount = $subscriptionInvoices->count();

        if ($subscriptionInvoicesCount === 0)
        {
            return;
        }
        else if ($subscriptionInvoicesCount === 1)
        {
            $subscriptionInvoice = $subscriptionInvoices->first();

            $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($subscriptionInvoice->getOrderId());
        }
        else
        {
            throw new Exception\LogicException(
                'We should not have more than 1 issued invoice at this stage!',
                ErrorCode::SERVER_ERROR_TOO_MANY_SUBSCRIPTION_INVOICES_FOUND,
                [
                    'count'             => $subscriptionInvoicesCount,
                    'subscription_id'   => $subscription->getId(),
                    'invoices'          => $subscriptionInvoices->toArrayPublic()
                ]);
        }
    }

    protected function addOrderIdToInputForPendingSubscription($subscription, array & $input)
    {
        $subscriptionInvoice = $this->repo->invoice->fetchLatestInvoiceOfPendingSubscription($subscription);

        $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($subscriptionInvoice->getOrderId());
    }

    protected function buildPaymentEntity(array $input): Payment\Entity
    {
        //
        //For simpl provider if $input['payment'] is not empty than we return the same payment
        //
        if ((empty($input['payment']) === false) and ($input['provider'] === Payment\Gateway::GETSIMPL))
        {
            return $input['payment'];
        }

        $payment = new Payment\Entity;

        $payment->generateId();

        $payment->merchant()->associate($this->merchant);

        $payment->build($input);

        $payment->setPublicKey($this->ba->getPublicKey());

        $this->payment = $payment;

        return $payment;
    }

    /**
     * When customer is fee-bearer, the amount received from checkout is
     * inclusive of fees. (Fees is not received from checkout when
     * merchant is the fee bearer.
     * For a robust verification, we re-calculate the fees from the base
     * amount and verify that it's the same as received from checkout.
     *
     * @param Payment\Entity $payment
     * @param                $input
     *
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\BadRequestException
     */
    protected function verifyProvidedFee(Payment\Entity $payment, array $input)
    {
        // This is not needed because FeeCalculator:calculateFee()
        // calculates the actual amount (amount - fee) in case of fee bearer merchant
        // $input['amount'] = $payment->getAmount() - $payment->getFee();

        // Re-calculates fees on the amount, using a dummy payment creation flow.
        // Also sets re-calculated fee and amount value (in paise) in $input.
        $feesArray = $this->processAndReturnFees($input);
        // The difference between the fees received from checkout and
        // and the fees re-calculated again. Ideally, this should be 0.
        $feeDifference = $input['fee'] - $payment->getFee();

        if(isset($feesArray['customer_fee']) === true)
        {
            $payment->setConvenienceFee($feesArray['customer_fee']);

            $payment->setConvenienceFeeGst($feesArray['customer_fee_gst']);
        }

        if (abs($feeDifference) !== 0)
        {
           throw new Exception\BadRequestValidationFailureException(
               'Payment failed because fees or tax was tampered',
               Payment\Entity::FEE,
                [
                    'checkout_fee'      => $input['fee'],
                    'calculated_fee'    => $payment->getFee(),
                ]);
        }

        $payment->setFeeBearer($this->payment->getFeeBearer());
    }

    protected function fetchOrderFromInput(array $input): Order\Entity
    {
        if ($this->order === null)
        {
            $order = $this->orderRepo
                          ->findByPublicIdAndMerchant(
                            $input[Payment\Entity::ORDER_ID],
                            $this->merchant);

            $this->order = $order;
        }

        return $this->order;
    }

    /**
     * S2S payment do not require separate amount validation via a capture call or order creation
     * We can allow merchants to send a simple payment request and queue a capture
     */
    protected function setCaptureForS2sCapturePayment(
        Payment\Entity $payment,
        array &$input
    )
    {
        if ($this->isCaptureRequired($input) === false)
        {
            return;
        }

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_SET,
            [
                'payment_id' => $payment->getPublicId(),
            ]);

        $payment->setCaptureTrue();
    }

    protected function isCaptureRequired(array $input): bool
    {
        // Can only do this when:
        // - Request is made via S2S, since that's the only time payment amount is sent by the merchant, not the customer
        // - Orders API is not being used
        // - The capture flag in the payment request is set to true
        //
        if (($this->app['api.route']->isS2SPaymentRoute() === true) and
            (empty($input[Payment\Entity::ORDER_ID]) === true) and
            (isset($input['capture']) === true) and
            (boolval($input['capture']) === true))
        {
            return true;
        }

        return false;
    }

    protected function isUpiTransferPayment($input)
    {
        if(array_key_exists('receiver',$input) === false)
        {
            return false;
        }
        if(array_key_exists('type',$input['receiver']) === false)
        {
            return false;
        }
        if($input['receiver']['type'] !== Receiver::VPA)
        {
            return false;
        }
        if($input['method'] !== Payment\Method::UPI)
        {
            return false;
        }

        return true;
    }

    protected function validateAndSetOrderDetailsIfApplicable(
        Payment\Entity $payment,
        array $input)
    {
        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            if($this->isUpiTransferPayment($input))
            {
                return;
            }

            $tpvRequired = (($payment->isTpvMethod() === true) and
                            ($this->merchant->isTPVRequired() === true));

            if (($tpvRequired === true) or
                ($payment->isEmandate() === true) or
                ($payment->isNach() === true) or
                (($payment->isUpiRecurring() === true) and
                    (empty($input[Payment\Entity::TOKEN]) === true)))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                    Payment\Entity::ORDER_ID,
                    [
                        'method' => $payment->getMethod()
                    ]);
            }


            return;
        }

        // for cardless emi and paylater, /create route is called twice in same payment flow hence we need not increment order attempt in 2nd call
        if ($this->isAffordabilityReusePayment($input) === true)
        {
            return;
        }

        if ($payment->isNach() === true)
        {
            $payment->setBank($this->order->getBankForNachMethod());
        }

        if (($payment->isUpiRecurring() === true) and
            (empty($input[Payment\Entity::TOKEN]) === true))
        {
            $this->validateOrderForUpiInitialRecurring($this->order);
        }

        // blocking upi autopay intent tpv flow for now. will remove this when it is supported
        if(($this->merchant->isTPVRequired() === true) and
           ($payment->isUpiIntentRecurring()))
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                [
                    'message'      => "autopay intent tpv payment creation not allowed",
                    'paymentId'    => $payment->getId(),
                    'merchantId'   => $payment->getMerchantId()
                ]
            );

            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_INTENT_TPV_NOT_SUPPORTED,
                Payment\Entity::ORDER_ID,
                [
                    'paymentId' => $payment->getId()
                ]);
        }

        if ($this->isOtmPayment($input) === true)
        {
            $this->validateOrderForUpiOtm($this->order);
        }

        $this->order->getValidator()->validatePaymentCreation($payment);

        if ($payment->isCoD() === false)
        {
            $this->order->setStatus(Order\Status::ATTEMPTED);
        }

        $this->order->incrementAttempts();

        $this->trace->info(
            TraceCode::ORDER_STATUS_ATTEMPTED,
            [
                'order_id'      => $this->order->getId(),
                'attempts'      => $this->order->getAttempts(),
            ]);

        if ($this->order->isExternal() === false)
        {
            $this->repo->saveOrFail($this->order);
        }

        $this->trace->info(
            TraceCode::TRACE_FOR_INCREASED_RESPONSE_TIMES,
            [
                'line'      => "Models/Payment/Processor/Processor.php:2421"
            ]
        );

        $payment->order()->associate($this->order);

        //
        // FIXME: Hack for reliance AMC, moving order receipt to payment
        // description
        //
        if ($payment->getMerchantId() === Merchant\Preferences::MID_RELIANCE_AMC)
        {
            $payment->setDescription($this->order->getReceipt());
        }

        $orderNotes = $this->order->getNotes()->toArray();

        $merchant = $payment->merchant;

        if ($merchant->isFeatureEnabled(Feature::SKIP_NOTES_MERGING) === false)
        {
            $paymentNotes = $payment->getNotes()->toArray();

            $notes = $orderNotes + $paymentNotes;

            // copying order notes in payment notes for all payments
            $payment->setNotes($notes);
        }

        $payment->setIntegrationMetadataUsingNotes($orderNotes);
    }

    protected function validateOrderForUpiOtm(Order\Entity $order)
    {
        if ($order->getPaymentCapture() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_AUTO_CAPTURE_NOT_ALLOWED,
                'order_id');
        }
    }

    protected function validateOrderForUpiInitialRecurring(Order\Entity $order)
    {
        $orderId = $order['id'];

        $upiMandate = $this->app['repo']->upi_mandate->findByOrderId($orderId);

        if ($upiMandate === null)
        {
            throw new Exception\BadRequestValidationFailureException (
                'Invalid order passed for upi recurring'
            );
        }

        $this->upiMandate = $upiMandate;
    }

    protected function validateAndSetReceiverIfApplicable(Payment\Entity $payment, array $input)
    {
        if (empty($input[Payment\Entity::RECEIVER]) === true)
        {
            return;
        }

        $receiverInput = $input[Payment\Entity::RECEIVER];

        $entity = $receiverInput['type'];

        switch ($entity)
        {
            case Receiver::BANK_ACCOUNT :
            case Receiver::QR_CODE :
            case Receiver::VPA :
                $receiver = $this->repo->$entity->findbyPublicIdAndMerchantAlsoWithTrash($receiverInput['id'], $this->merchant);
                break;
            case Receiver::OFFLINE_CHALLAN:
                $id = explode('_', $receiverInput['id'])[1];
                $receiver = $this->repo->$entity->findbyPublicId($id);
                break;
            case Receiver::POS :
                $payment->setReceiverType(Receiver::POS);
                break;
            default :
                $receiver = $this->repo->$entity->findbyPublicIdAndMerchant($receiverInput['id'], $this->merchant);
        }

        if($payment->getReceiverType() !== Receiver::POS)
            $payment->receiver()->associate($receiver);
    }

    protected function validateAndSetPaymentLinkIfApplicable(Payment\Entity $payment, array $input)
    {
        if (array_key_exists(Payment\Entity::PAYMENT_LINK_ID, $input) === false)
        {
            return;
        }

        $paymentLinkId = $input[Payment\Entity::PAYMENT_LINK_ID];

        $paymentLink   = $this->repo->payment_link->findByPublicIdAndMerchant($paymentLinkId, $this->merchant);

        (new PaymentLink\Core)->validateIsPaymentInitiatable($paymentLink, $payment);

        $payment->paymentLink()->associate($paymentLink);
    }

    protected function preProcessAndValidateForPaymentPagesIfApplicable(array $input)
    {
        (new PaymentLink\Core)->validatePaymentPagePaymentFromInput($input);
    }

    protected function validateAndSetInvoiceDetailsIfApplicable(Payment\Entity $payment)
    {
        if ($this->order === null)
        {
            return;
        }

        $invoice = $this->order->invoice()->withTrashed()->first();

        if ($invoice === null)
        {
            return;
        }

        $this->repo->invoice->lockForUpdateAndReload($invoice, true);

        /** @var Invoice\Validator $invoiceValidator */
        $invoiceValidator = $invoice->getValidator();

        $invoiceValidator->validateInvoicePayableForPayment($payment);

        $payment->invoice()->associate($invoice);

        if ($invoice->getEntityType() === E::SUBSCRIPTION_REGISTRATION)
        {
            $paymentNotes = $payment->getNotes()->toArray();

            if (empty($paymentNotes) === true)
            {
                $payment->setNotes($invoice->getNotes()->toArray());
            }
        }
    }

    protected function validateAndProcessCardRecurringIfApplicable(Payment\Entity $payment, array &$input)
    {
        if ($payment->isCardRecurring() === false)
        {
            return;
        }

        if (empty($input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::NOTIFICATION_ID]) === false)
        {
            if (($this->merchant->isFeatureEnabled(Feature::AUTH_SPLIT) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'recurring_token.notification is not required');
            }

            (new CardMandateNotification\Core)->validateAndAssociatePayment($payment,
                $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::NOTIFICATION_ID]);
        }

    }

    protected function validateDisableS2SIfApplicable(Payment\Entity $payment, array $input)
    {
        if((isset($payment) === true) &&
            ($input['method'] === Payment\Method::CARD) &&
            (strpos(Request::getUri(), "/payments/create/recurring") === false))
        {
            if (($this->app['api.route']->isS2SPaymentRoute() === true) && ($this->merchant->isFeatureEnabled(Feature::S2S_DISABLE_CARDS) === true)) {
                throw new Exception\BadRequestValidationFailureException(
                    'The requested URL was not found on the server');
            }
        }
    }

    protected function validateUpiRecurringIfApplicable(Payment\Entity $payment, array $input)
    {
        if ($payment->isUpiRecurring() === false)
        {
            return;
        }

        if (($this->upiMandate instanceof UpiMandate\Entity) === false)
        {
            $tokenId = $input[Payment\Entity::TOKEN] ?? null;

            Customer\Token\Entity::verifyIdAndStripSign($tokenId);

            $this->upiMandate = $this->repo->upi_mandate->findByTokenId($tokenId);

            assertTrue($this->upiMandate->getMerchantId() === $this->merchant->getId());
        }

        // As we are going to increment the used count
        $this->repo->upi_mandate->lockForUpdateAndReload($this->upiMandate);

        // This will handle upi autopay initial retry payment sequence count
        $current = (int) $this->upiMandate->getUsedCount();

        if(!(($current === 1) and
            ($this->upiMandate->getStatus() === UpiMandateStatus::CONFIRMED) and
            (isset($input[Payment\Entity::TOKEN]) === false)))
        {
            $this->upiMandate->incrementUsedCount();
        }

        $this->repo->saveOrFail($this->upiMandate);
    }

    protected function validateBankTransferDetailsIfApplicable(Payment\Entity $payment)
    {
        if ($payment->isBankTransfer() === false)
        {
            return;
        }

        //
        // Bank transfers are normally created by VA providers,
        // i.e. Kotak and Yesbank, which act as apps and use appAuth.
        //
        // They can also be inserted via Dashboard (also an app)
        // or in bulk via the bank transfer batch job (run via cli)
        //
        if ($this->app->runningInQueue() === true)
        {
            return;
        }

        $rblVaRoutes = ['bank_transfer_process_rbl', 'bank_transfer_process_rbl_test', 'bank_transfer_process_rbl_internal'];

        if (in_array(Route::currentRouteName(), $rblVaRoutes, true) === true)
        {
            return;
        }

        // TODO: Following is not testable in cases. Ref: BankTransferBatchTest
        if (($this->app['basicauth']->isAppAuth() === false) and
            (Route::currentRouteName() !== 'bank_transfer_process_test'))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid payment method given: ' . $payment->getMethod());
        }
    }

    protected function tracePaymentFailed($error, string $traceCode)
    {
        $traceData = array_merge(
                        $this->payment->toArrayTraceRelevant(),
                        ['error' => $error->getAttributes()]);

        $level = 'info';

        if ($error->isGatewayError())
        {
            $level = 'critical';
        }

        // Tracing
        $this->trace->$level(
            $traceCode,
            $traceData);

        $this->segment->trackPayment($this->payment, TraceCode::PAYMENT_FAILED, $traceData);
    }

    protected function retrieveToken(array $input)
    {
        $token = $this->repo->token->getByWalletTerminalAndCustomerId(
                            $input['payment']['wallet'],
                            $input['payment']['terminal_id'],
                            $input['customer']->getId());

        return $token;
    }

    protected function retrieve(string $id): Payment\Entity
    {
        $this->payment = $this->repo->payment->findByPublicIdAndMerchant(
                                                $id, $this->merchant);

        return $this->payment;
    }

    /**
     * Fetches external repo payment entity directly
     * without searching in api database
     *
     * @param  string $id payment id
     * @return Payment\Entity
     * @throws Exception\BadRequestException
     */
    protected function retrieveExternalPayment(string $id): Payment\Entity
    {
        $this->payment = $this->repo->payment->fetchExternalPaymentEntity($id, $this->merchant->getId());

        return $this->payment;
    }

    protected function getOrderForPayment(Payment\Entity $payment)
    {
        if ($payment->hasOrder())
        {
            $order = $this->repo->order->fetchForPayment($payment);

            return $order;
        }
    }

    /**
     * Sets both, the instance payment object and the passed
     * payment object, to the new payment object which is locked
     * for update.
     *
     * setRawAttributes is being used because of the way php
     * handles pass by reference for objects. If the passed object
     * is ASSIGNED to another object/value, the original object
     * from the calling function remains unaffected.
     * Any change ON the passed object will affect the original
     * object too.
     *
     * @param $payment
     */
    protected function lockForUpdateAndReload(Payment\Entity $payment)
    {
        if ($payment->isExternal() === true)
        {
            if ($payment->isUpi() === true)
            {
                $lockedPayment = $this->repo->payment->findByPublicId($payment->getPublicId());

                $this->payment->setRawAttributes($lockedPayment->getAttributes(), true);

                $payment->setRawAttributes($lockedPayment->getAttributes(), true);

                return ;
            }

            return;
        }

        $lockedPayment = $this->paymentRepo->lockForUpdate($payment->getKey());

        //
        // When $this->payment is being passed in the argument,
        // $this->payment will be the same object as $payment.
        // When $this->payment and $payment are two different objects,
        // we update both of them.
        //

        $this->payment->setRawAttributes($lockedPayment->getAttributes(), true);

        $payment->setRawAttributes($lockedPayment->getAttributes(), true);
    }

    public function setPayment(Payment\Entity $payment): Processor
    {
        $this->payment = $payment;

        return $this;
    }

    protected function setApplicationIfApplicable(Payment\Entity $payment, $input)
    {
        if ((isset($input['application']) === false) and
            ($payment->isVisaSafeClickStepUpPayment() === true))
        {
            $input['application'] = 'visasafeclick_stepup';
        }

        if (isset($input['application']) === true)
        {
            $payment->setApplication($input['application']);
        }

        if (isset($input['provider']) and
           $input['provider'] === E::GOOGLE_PAY and
           $input['method'] === Payment\Method::UNSELECTED)
        {
            $payment->setApplication($input['provider']);
        }
    }

    protected function setGooglePayMethodsIfApplicable(Payment\Entity $payment, $input)
    {
        if($payment->isGooglePay())
        {
            $payment->setGooglePayMethods(Payment\Method::GOOGLE_PAY_SUPPORTED_METHODS);
        }
    }

    protected function tracePaymentNewRequest(array $input)
    {
        $this->unsetSensitiveCardDetails($input);

        $inputTrace = $input;

        $this->unsetSensitiveBankDetails($inputTrace);

        unset($inputTrace['notes'], $inputTrace['contact'], $inputTrace['email'], $inputTrace['ott']);

        $this->trace->debug(TraceCode::PAYMENT_NEW_REQUEST, $inputTrace);
    }

    protected function unsetSensitiveBankDetails(array & $input)
    {
        if ((isset($input[Payment\Entity::BANK_ACCOUNT]) === true) and
            (is_array($input[Payment\Entity::BANK_ACCOUNT]) === true))
        {
            unset($input[Payment\Entity::BANK_ACCOUNT][BankAccount\Entity::ACCOUNT_NUMBER]);
        }
    }

    protected function unsetSensitiveCardDetails(array & $input)
    {
        if ((isset($input[Payment\Entity::CARD]) === true) and
            (is_array($input[Payment\Entity::CARD]) === true))
        {
            unset($input[Payment\Entity::CARD][Card\Entity::NAME]);
            unset($input[Payment\Entity::CARD][Card\Entity::NUMBER]);
            unset($input[Payment\Entity::CARD][Card\Entity::CVV]);
            unset($input[Payment\Entity::CARD][Card\Entity::EXPIRY_MONTH]);
            unset($input[Payment\Entity::CARD][Card\Entity::EXPIRY_YEAR]);
        }
    }

    protected function getMerchantBankAccount(Merchant\Entity $merchant): BankAccount\Entity
    {
        $ba = $merchant->bankAccount;

        if ($ba !== null)
        {
            return $ba;
        }

        assertTrue ($this->mode === Mode::TEST);

        $attributes = array(
            'merchant_id'           => $merchant->getId(),
            'ifsc_code'             => BankAccount\Entity::SPECIAL_IFSC_CODE,
            'beneficiary_name'      => $merchant->getAttribute('name'),
            'account_number'        => random_integer(11),
            'beneficiary_city'      => 'Mumbai',
            'beneficiary_state'     => 'MH',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '400069',
            'beneficiary_mobile'    => '9393993939',
        );

        $ba = (new BankAccount\Entity)->newInstance($attributes, true);

        $ba->merchant()->associate($merchant);

        $merchant->setRelation('bankAccount', $ba);

        return $ba;
    }

    /**
     * This function checks if AutoCapture timeout is set and if it is exceeded for Optimizer payments, and overrides
     * capture decision
     * Ref : https://docs.google.com/document/d/1FQEGHojgb74pyBtS0r7t_qWg05XsZ_636UyYYkUNKdE/edit#
     *
     * @param Payment\Entity $payment
     * @param array $captureResponse
     * @return array
     */
    protected function shouldAutoCaptureOptimizerExternalPgPayment(Payment\Entity $payment, array $captureResponse): array
    {
        // 1. If auto-capture settings are present, and authorized_at exceeds auto-capture timeout, do auto-refund
        // 2. In all other cases capture based on existing logics present in shouldAutoCapture method.
        $optimizerAutoCaptureResponse = [];

        $lateAuthConfig = $this->getLateAuthPaymentConfig($payment);

        if (isset($lateAuthConfig) === true)
        {
            $captureValue = $lateAuthConfig['capture'];

            $autoTimeoutDuration = $lateAuthConfig['capture_options']['automatic_expiry_period'];

            $difference = $this->getTimeDifferenceInAuthorizeAndCreated($payment);

            if (($captureValue === 'automatic') and
                (isset($autoTimeoutDuration) === true) and
                ($difference > $autoTimeoutDuration)) {

                $this->setPaymentRefundAtForConfig($payment, $autoTimeoutDuration);

                $optimizerAutoCaptureResponse['should_auto_capture'] = false;

                $optimizerAutoCaptureResponse['reason'] = Constants::OPTIMIZER_AUTO_CAPTURE_TIMEOUT_EXCEEDED;

            }
        }

         if (empty($optimizerAutoCaptureResponse) === false)
         {
             $this->trace->info(
                 TraceCode::OPTIMIZER_CAPTURE_SETTINGS_OVERRIDE,
                 [
                     'payment_id'                   => $payment->getId(),
                     'merchant_id'                  => $payment->getMerchantId(),
                     'optimizer_capture_response'   => $optimizerAutoCaptureResponse,
                     'pg_capture_response'          => $captureResponse,
                 ]);

             return $optimizerAutoCaptureResponse;

         }

        return $captureResponse;

    }

    /**
     * This function is used to identify if a payment can be auto captured or not
     * Please *note* that the order of the conditions is important and shouldn't be chnaged
     * without understanding the consequences
     *
     * @param Payment\Entity $payment
     *
     * @return bool
     */
    protected function shouldAutoCapture(Payment\Entity $payment): array
    {
        // For upi otm, Payments cannot auto captured, as merchants needs to hit the capture
        // api, to execute the mandate, we will block this scenario right now.
        if ($payment->isUpiOtm() === true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::UPI_OTM_PAYMENT;

            return $response;
        }

        // Bank transfers are auto-captured only if they are expected. This is checked later.
        if ($payment->isBankTransfer() === true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::BANK_TRANSFER_PAYMENT;

            return $response;
        }


        // We can't capture payments that are in authenticated state.
        if ($payment->getStatus() === Payment\Status::AUTHENTICATED)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::PAYMENT_STATUS_AUTHENTICATED;

            return $response;
        }

        if ($payment->isUpiTransfer() === true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::UPI_TRANSFER_PAYMENT;

            return $response;
        }

        if ($payment->isCoD() === true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::PAYMENT_METHOD_COD;

            return $response;
        }

        //
        // Post payment authorization payment link's payments are actually auto captured but there is more logic in
        // the flow and in handling capture failures etc which is all done in specific method(easy to move out to a
        // service) triggered from postPaymentAuthorizeProcessing() method.
        //

        // we are not auto capturing the payment page payment if the feature flag is enabled.
        if ($payment->hasPaymentLink() === true)
        {
            if ($payment->merchant->isFeatureEnabled(Feature::PAYMENT_PAGES_NO_CAPTURE) === true)
            {
                $response['should_auto_capture'] = false;

                $response['reason'] = Constants::PAYMENT_LINK_WITH_FEATURE;

                return $response;
            }
        }

        //
        // The payment should always be in authorized if it has reached this point.
        // Ideally, this should throw an exception. But, we do not want to fail
        // the payment because of an internal issue.
        //
        if ($payment->isAuthorized() === false)
        {
            $this->trace->error(
                TraceCode::PAYMENT_AUTO_CAPTURE_NOT_AUTHORIZED,
                [
                    'payment_id'    => $payment->getId(),
                    'status'        => $payment->getStatus()
                ]);

            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::PAYMENT_STATUS_NOT_AUTHORIZED;

            return $response;
        }

        if ($payment->isPos() === true and $payment->getMethod() === Method::CARD)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::POS_PAYMENT;

            return $response;
        }

        //
        // We do an auto capture direct settlement payment only if payment is not associated with an order.
        //
        // Later we are checking if the payment is associated with order and order status is paid then don't
        // capture this late auth payment since order is fullfilled by some other payment made for this order.
        if (($payment->isDirectSettlement() === true) and
            ($payment->hasOrder() === false))
        {
            $response['should_auto_capture'] = true;

            $response['reason'] = Constants::DIRECT_SETTLEMENT_PAYMENT;

            return $response;
        }

        if ($payment->getMethod() === Method::INTL_BANK_TRANSFER)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::INTL_BANK_TRANSFER_PAYMENT;

            return $response;
        }

        if ($payment->merchant->isFeatureEnabled(Feature::AUTH_SPLIT) === true)
        {
            $response['should_auto_capture'] = true;

            $response['reason'] = Constants::AUTH_SPLIT_FEATURE_ENABLED;

            return $response;
        }

        //
        // We do an auto capture if the payment is set to be captured
        // This happens for s2s payments where capture=1 is part of the request
        //
        if ($payment->getCapture() === true)
        {
            $response['should_auto_capture'] = true;

            $response['reason'] = Constants::PAYMENT_ATTRIBUTE_CAPTURE_TRUE;

            return $response;
        }
        //Adding this until terminal issue gets resolved , ideally direct terminal without order should auto capture
        if ($payment->isPos() === true and $payment->getMethod() === Method::UPI)
        {
            $response['should_auto_capture'] = true;

            $response['reason'] = Constants::POS_PAYMENT;

            return $response;
        }

        //
        // We do an auto capture only if payment is associated with an order.
        //
        if ($payment->hasOrder() === false)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::PAYMENT_HAS_NO_ORDER;

            return $response;
        }

        //
        // The flow would reach till here because subscription creates
        // an invoice, which in turn creates an order.
        //
        // Auto capturing a subscription payment is handled in a different
        // flow, because of some pre-processing and post-processing
        // that requires to be done.
        //
        if ($payment->hasSubscription() === true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::SUBSCRIPTION_PAYMENT;

            return $response;
        }

        //
        // In case of emandate debit payment, the payment would be in `created` status
        // and this flow will not get executed at all. Once the debit recon is done,
        // only then the payment gets authorized and this flow gets run.
        //
        // But in case of emandate registration payment, the payment would be in `authorized`
        // status and this flow will get executed. But, we should be capturing it only after
        // the token is successfully confirmed as recurring. This, we get to know only
        // after registration recon. Again, this is an issue only for async registration gateways.
        // In case of sync registration gateways, the token is marked as recurring/confirmed in
        // the normal flow itself.
        //
        // Hence, we don't need to handle for emandate debit and emandate sync register here.
        //
        if ($payment->isFileBasedEmandateRegistrationPayment() === true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::FILE_BASED_EMANDATE_PAYMENT;

            return $response;
        }

        // For PayU, in case of registration payment,
        // token confirmation will be sent via webhooks.
        // Cannot auto capture until we know final status of token.
        // For some banks, they will let us know the status in sync,
        // for others they will give the final status in T+2 days via webhooks.
        //
        // In case of debit payment, final confirmation is received from webhooks.
        if ($payment->isApiBasedEmandateAsyncPayment() === true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::API_BASED_EMANDATE_ASYNC_PAYMENT;

            return $response;
        }

        return $this->shouldAutoCaptureOrder($payment);
    }

    protected function shouldAutoCaptureAlreadyAuthenticatedSubscription(Payment\Entity $payment)
    {
        $subscription = $payment->subscription;

        //
        // Late authorizations are not going to happen here because
        // everything is S2S. On the off chance that it happens,
        // we log it and see what to do about it.
        //
        if ($payment->isLateAuthorized() === true)
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_LATE_AUTH_NO_AUTO_CAPTURE,
                [
                    'payment_id' => $payment->getId(),
                    'subscription_id' => $subscription->getId(),
                    'late_authorized' => $payment->isLateAuthorized(),
                ]);

            return false;
        }

        return true;
    }

    protected function shouldAutoCaptureNewSubscription(Payment\Entity $payment)
    {
        $subscription = $payment->subscription;

        //
        // This is commented out because all the attributes set
        // for the subscription and not saved will get overridden
        // with the values present in the DB.
        // TODO: Handle this because race conditions.
        //
        // $this->repo->reload($subscription);

        //
        // This function can be used here since this flow is processed
        // only for a new subscription.
        //
        $subscriptionInvoices = $this->repo->invoice->fetchIssuedInvoicesOfSubscription($subscription);

        //
        // We auto capture a subscription only if start_at is absent,
        // which means that the first transaction is being used as the
        // first charge also.
        // OR we auto capture if upfront_amount (addon) is present.
        //
        // We create an invoice if any of the above two conditions are satisfied.
        //
        if ($subscriptionInvoices->count() === 0)
        {
            return false;
        }

        if ($subscriptionInvoices->count() > 1)
        {
            throw new Exception\LogicException(
                'There should have been only one invoice created for a newly created subscription',
                ErrorCode::SERVER_ERROR_INCORRECT_NUMBER_OF_INVOICES_FOUND,
                [
                    'invoices_count'    => $subscriptionInvoices->count(),
                    'subscription_id'   => $subscription->getId(),
                    'payment_id'        => $payment->getId(),
                ]);
        }

        //
        // Ideally, the flow shouldn't reach till here since this function
        // is not called at all in case of a late auth payment.
        //
        if ($payment->isLateAuthorized() === true)
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_LATE_AUTH_NO_AUTO_CAPTURE,
                [
                    'payment_id'      => $payment->getId(),
                    'subscription_id' => $subscription->getId(),
                    'late_authorized' => $payment->isLateAuthorized(),
                ]);

            return false;
        }

        return true;
    }

    protected function shouldAutoCaptureOrder(Payment\Entity $payment)
    {
        $order = $payment->order;

        //
        // Assume a case where the first payment failed.
        // The second payment is getting authorized.
        // The first payment is now getting late authorized.
        // If the second payment gets captured, we need to ensure that we don't capture
        // the first payment. We do a reload here to ensure that we get the latest
        // status of the order before marking the payment as captured.
        // An order must not have more than one captured payment.
        //
        $this->repo->reload($order);

        // If order status is not paid yet and if the payment is direct settlement then capture
        if (($order->isPaid() === false) and
            ($payment->isDirectSettlement()))
        {
            $response['should_auto_capture'] = true;

            $response['reason'] = Constants::DIRECT_SETTLEMENT_ORDER_NOT_PAID;

            return $response;
        }

        if ((($order->isPaid() === true) and
                ($order->merchant->isFeatureEnabled(Feature::DISABLE_AMOUNT_CHECK) === false)))
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::ORDER_ALREADY_MARKED_PAID;

            $payment->setRefundAt(Carbon::now()->getTimestamp());

            $this->repo->saveOrFail($payment);

            return $response;
        }

        $amount = $payment->getAdjustedAmountWrtCustFeeBearer();

        $amount = $payment->getAmountWithoutConvenienceFeeIfApplicable($amount, $order);

        if (($amount > $order->getAmountDue($payment)) and
            ($this->merchant->isFeatureEnabled(Feature::EXCESS_ORDER_AMOUNT) === false))
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::PAYMENT_AMOUNT_GREATER_THAN_AMOUNT_DUE;

            return $response;
        }

        [$captureConfig, $captureSettings]  = $this->shouldAutoCapturePaymentConfig($payment);

        if ($captureConfig === true)
        {
            $response['should_auto_capture'] = true;

            $response['reason'] = Constants::CAPTURE_SETTINGS_AUTOMATIC;

            $response['data'] = $captureSettings;

            return $response;
        }
        elseif ($captureConfig === false)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::CAPTURE_SETTINGS_MANUAL;

            $response['data'] = $captureSettings;

            return $response;
        }

        if ($order->getPaymentCapture() === true)
        {
            $isLateAuthInvoicePayment = ($payment->isLateAuthorized() === true and $payment->hasInvoice() === true);

            if ($isLateAuthInvoicePayment === false)
            {
                $response['should_auto_capture'] = true;

                $response['reason'] = Constants::ORDER_PAYMENT_CAPTURE_TRUE;

                return $response;
            }
        }

        if ($order->getPaymentCapture() !== true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::ORDER_PAYMENT_CAPTURE_NOT_TRUE;

            return $response;
        }

        if ($this->isAutoRefundDelayExceeded($payment) === true)
        {
            $response['should_auto_capture'] = false;

            $response['reason'] = Constants::AUTO_REFUND_DELAY_EXCEEDED;

            return $response;
        }

        if ($payment->isLateAuthorized() === true)
        {
            $autoCaptureLateAuth =  $this->shouldAutoCaptureLateAuthorized($payment);

            if ($autoCaptureLateAuth === true)
            {
                $response['should_auto_capture'] = true;

                $response['reason'] = Constants::MERCHANT_AUTO_CAPTURE_LATE_AUTH_TRUE;

                return $response;
            }
            else
            {
                $response['should_auto_capture'] = false;

                $response['reason'] = Constants::MERCHANT_AUTO_CAPTURE_LATE_AUTH_FALSE;

                return $response;
            }
        }

        $response['should_auto_capture'] = true;

        $response['reason'] = Constants::PAYMENT_PASSED_ALL_CHECKS_FOR_CAPTURE;

        return $response;
    }

    protected function shouldAutoCapturePaymentConfig(Payment\Entity $payment)
    {
        $lateAuthConfig = $this->getLateAuthPaymentConfig($payment);

        if (isset($lateAuthConfig) === false)
        {
            return [null, null];
        }

        $autoTimeoutDuration = $lateAuthConfig['capture_options']['automatic_expiry_period'];

        if (isset($lateAuthConfig['capture_options']['manual_expiry_period']) === false)
        {
            $manualTimeoutDuration = $autoTimeoutDuration;
        }
        else
        {
            $manualTimeoutDuration = $lateAuthConfig['capture_options']['manual_expiry_period'];
        }

        if (($payment->isRecurring()) and
            ($payment->getRecurringType() === 'auto'))
        {
            [$autoTimeoutDuration,
                $manualTimeoutDuration] = $this->setCaptureTimeoutRecurringPayments($autoTimeoutDuration,
                                                                                    $manualTimeoutDuration,
                                                                                    $payment,
                                                                                    $lateAuthConfig);
        }

        $captureValue = $lateAuthConfig['capture'];

        $difference = $this->getTimeDifferenceInAuthorizeAndCreated($payment);

        $this->setPaymentRefundAtForConfig($payment, $manualTimeoutDuration);

        if ($captureValue === 'automatic')
        {
            if ($difference < $autoTimeoutDuration)
            {
                return [true, $lateAuthConfig];
            }
            elseif ($difference > $manualTimeoutDuration)
            {
                return [false, $lateAuthConfig];
            }
        }
        elseif ($captureValue === 'manual')
        {
            return [false, $lateAuthConfig];
        }

        return [null, $lateAuthConfig];
    }

    protected function setCaptureTimeoutRecurringPayments($autoTimeoutDuration, $manualTimeoutDuration, $payment, $lateAuthConfig)
    {
            if ($payment->getMethod() === Constants::UPI)
            {
                $variant = $this->app['razorx']->getTreatment($payment->merchant->getId(),
                    Merchant\RazorxTreatment::DEFAULT_CAPTURE_SETTING_CONFIG_UPI_AUTOPAY,
                    $this->app['rzp.mode']);

                if (strtolower($variant) === 'on')
                {
                    $defaultUpiAutoCaptureExpiry = Constants::AUTO_CAPTURE_DEFAULT_TIMEOUT_UPI_RECURRING_AUTO;

                    $canRetry = $this->checkUpiAutopayIncreaseDebitRetry($payment->getId(),$payment->merchant->getId());

                    if ($canRetry === true)
                    {
                        $defaultUpiAutoCaptureExpiry = Constants::AUTO_CAPTURE_TIMEOUT_FOR_UPI_RECURRING_AUTO_DEBIT_RETRIES;
                    }

                    if ($autoTimeoutDuration < $defaultUpiAutoCaptureExpiry) $autoTimeoutDuration = $defaultUpiAutoCaptureExpiry;
                    if ($manualTimeoutDuration < $defaultUpiAutoCaptureExpiry) $manualTimeoutDuration = $defaultUpiAutoCaptureExpiry;
                }

            } elseif ($payment->getMethod() === Constants::CARD)
            {
                $variant = $this->app['razorx']->getTreatment($payment->merchant->getId(),
                    Merchant\RazorxTreatment::DEFAULT_CAPTURE_SETTING_CONFIG_CARD_RECURRING,
                    $this->app['rzp.mode']);

                if (strtolower($variant) === 'on')
                {
                    $defaultCardAutoCaptureExpiry = Constants::AUTO_CAPTURE_DEFAULT_TIMEOUT_CARD_RECURRING_AUTO;

                    if ($autoTimeoutDuration < $defaultCardAutoCaptureExpiry) $autoTimeoutDuration = $defaultCardAutoCaptureExpiry;
                    if ($manualTimeoutDuration < $defaultCardAutoCaptureExpiry) $manualTimeoutDuration = $defaultCardAutoCaptureExpiry;
                }
            }

            $this->trace->info(
                TraceCode::DEFAULT_CAPTURE_SETTING_CONFIG_RECURRING,
                [
                    'capture_settings'          => $lateAuthConfig,
                    'payment'                   => $payment->toArrayTraceRelevant(),
                    'auto_timeout_duration'     => $autoTimeoutDuration,
                    'manual_timeout_duration'   => $manualTimeoutDuration,
                    'method'                    => $payment->getMethod(),
                ]);


        return [$autoTimeoutDuration, $manualTimeoutDuration];
    }

    private function setPaymentRefundAtForConfig($payment, $manualTimeoutDuration)
    {
        if (in_array($payment->getMethod() , [ Payment\Method::EMANDATE,
                    Payment\Method::NACH]) === true)
        {
            return;
        }

        $refundAt = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST)
                ->addMinutes($manualTimeoutDuration)->getTimestamp();

        $payment->setRefundAt($refundAt);

        $this->paymentRepo->saveOrFail($payment);

        $properties = [
            "auto_refund_epoch" => $refundAt,
            "reason" => sprintf(Constants::REFUND_AT_OVERRIDDEN_CAPTURE_SETTINGS, $manualTimeoutDuration),
        ];

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTO_REFUND_DATE_OVERRIDDEN, $payment, null, [], $properties);

    }

    public function getTimeDifferenceInAuthorizeAndCreated($payment)
    {
        $authorizedTime = Carbon::createFromTimestamp($payment->getAuthorizeTimestamp(), Timezone::IST);

        $createdTime = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST);

        return $authorizedTime->diffInMinutes($createdTime);
    }

    public function getLateAuthPaymentConfig(Payment\Entity $payment)
    {
        $order = $payment->order;

        $lateAuthConfigId = null;

        $paymentCaptureFlag = null;

        if (isset($order) === true)
        {
            $lateAuthConfigId = $order->getLateAuthConfigId();

            $paymentCaptureFlag = $order->getPaymentCapture();
        }

        $merchant = $this->merchant;

        $configEntity = null;

        if (isset($lateAuthConfigId) === false)
        {
            $configEntity = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), 'late_auth');

            if (isset($configEntity) === true)
            {
                $lateAuthConfig = json_decode($configEntity->config, true);

                $captureValue = $lateAuthConfig['capture'];

                if (($paymentCaptureFlag === true and $captureValue === 'manual') or
                    ($paymentCaptureFlag === false and $captureValue === 'automatic'))
                {
                    $configEntity = null;
                }
            }
        }
        else
        {
            $configEntity = $this->repo->config->findByPublicIdAndMerchantAndType($lateAuthConfigId, $merchant->getId(), 'late_auth');
        }

        $this->trace->info(
            TraceCode::CAPTURE_SETTINGS_FOR_PAYMENT,
            [
                'capture_settings'    => $configEntity,
                'payment_capture_flag' => $paymentCaptureFlag,
            ]);

        if (isset($configEntity) === false)
        {
            return null;
        }

        return json_decode($configEntity->config, true);

    }

    protected function isAutoRefundDelayExceeded(Payment\Entity $payment): bool
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $merchant = $payment->merchant;

        $autoRefundDelay = $merchant->getAutoRefundDelay();

        $createdAt = $payment->getCreatedAt();

        $minRefundAt = $createdAt + Merchant\Entity::MIN_AUTO_REFUND_DELAY;
        $merchantRefundAt = $createdAt + $autoRefundDelay;

        $refundAt = max($minRefundAt, $merchantRefundAt);

        if ($payment->isEmandate() === true)
        {
            $refundAt = $createdAt + Merchant\Entity::AUTO_REFUND_DELAY_FOR_EMANDATE;
        }
        else if ($payment->isNach() === true)
        {
            $refundAt = $createdAt + Merchant\Entity::AUTO_REFUND_DELAY_FOR_NACH;
        }

        $this->trace->info(
            TraceCode::AUTO_CAPTURE_REFUND_DELAY,
            [
                'payment_id'        => $payment->getId(),
                'status'            => $payment->getStatus(),
                'refund_delay'      => $autoRefundDelay,
                'should_refund_at'  => $refundAt,
                'current_time'      => $currentTime,
            ]);

        return ($currentTime > $refundAt);
    }

    protected function shouldAutoCaptureLateAuthorized(Payment\Entity $payment): bool
    {
        // Auto capturing a late authorized invoice has a little different logic.
        // Later, we would add logic for auto capturing a payment which is not
        // associated with an invoice also.
        if ($payment->hasInvoice() === true)
        {
            return $this->shouldAutoCaptureLateAuthorizedInvoice($payment);
        }

        $merchant = $payment->merchant;

        return $this->shouldAutoCaptureLateAuthorizedOrder($merchant);
    }

    /**
     * The merchant needs to have `auto_capture_late_auth` config set to true.
     *
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    protected function shouldAutoCaptureLateAuthorizedOrder(Merchant\Entity $merchant)
    {
        return $merchant->getAutoCaptureLateAuth();
    }

    /**
     * Invoice related checks
     *   - Check if invoice status is ISSUED
     *
     * @param Payment\Entity $payment
     *
     * @return bool
     */
    protected function shouldAutoCaptureLateAuthorizedInvoice(Payment\Entity $payment)
    {
        $invoice = $payment->invoice;

        $this->repo->invoice->lockForUpdateAndReload($invoice);

        try
        {
            $invoice->getValidator()->validateInvoicePayableForPayment($payment);
        }
        catch (Exception\BadRequestValidationFailureException $e)
        {
            return false;
        }

        return true;
    }

    protected function acquireMutexOnPayment(Payment\Entity $payment)
    {
        $resource = $payment->getId();

        if ($this->mutex->acquire($resource) === false)
        {
            $data = [
                'payment_id'  => $payment->getId(),
                'merchant_id' => $payment->getMerchantId(),
                'gateway'     => $payment->getGateway(),
                'terminal_id' => $payment->getTerminalId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS, null, $data);
        }
    }

    protected function releaseMutexOnPayment(Payment\Entity $payment)
    {
        $this->mutex->release($payment->getId());
    }

    protected function createOrUpdateToken(array $input, array $data): Customer\Token\Entity
    {
        $token = $this->retrieveToken($input);

        if ($token === null)
        {
            $token = (new Customer\Token\Core)
                        ->create($input['customer'], $data['token']);
        }
        else
        {
            $token->fill($data['token']);
            $token->saveOrFail();
        }

        return $token;
    }

    protected function getFormattedContact(string $contact): string
    {
        return substr($contact, -10);
    }

    protected function shouldHitGatewayForPayment(Payment\Entity $payment, array $gatewayInput = []): bool
    {
        if($payment->isPos() === true)
        {
            return false;
        }

        if ((isset($gatewayInput['skip_gateway_call']) === true) and
            ($gatewayInput['skip_gateway_call'] === true))
        {
            return false;
        }

        if ($payment->isFileBasedEmandateDebitPayment() === true)
        {
            if ($payment->shouldCreateGatewayEntityForDebit() === true)
            {
                return true;
            }
            //
            // If the payment is a second recurring payment of a file-based emandate bank
            // we do not hit the gateway, we send a debit request asynchronously
            //
            return false;
        }

        if ($payment->isNach() === true)
        {
            return false;
        }

        // UPI recurring payment when created are supposed to be left in created state
        // We will set a instantaneous reminder, which will process the payment state
        if ($this->shouldHitAuthorizeOnRecurringForUpi($payment, $gatewayInput) === false)
        {
            return false;
        }

       // not sending gateway request while creating mandate
        if ($payment->isCardMandateCreateApplicable() === true)
        {
            return false;
        }

        // Card recurring payment when created are supposed to be left in created state
        // We will set a instantaneous reminder, which will process the payment state
        if ($payment->isCardMandateNotificationCreateApplicable() === true)
        {
            return false;
        }

        if ($payment->isCoD() === true)
        {
            return false;
        }

        return true;
    }

    protected function changeTerminalCapabilityIfApplicable(Terminal\Entity $terminal, Error $error)
    {
        if (($error->getInternalErrorCode() === ErrorCode::GATEWAY_ERROR_PERMISSION_DENIED_FOR_ACTION) and
            ($terminal->getGateway() === Payment\Gateway::AXIS_MIGS) and
            ($terminal->getCapability() === Terminal\Capability::AUTHORIZE))
        {
            $terminal->setCapability(Terminal\Capability::ALL);

            $this->repo->saveOrFail($terminal);

            $this->app['slack']->queue(
                'terminal capability auto changed to ALL',
                [
                    'merchant_id'           => $terminal->getMerchantId(),
                    'merchant_name'         => $terminal->merchant->getName(),
                    'terminal_id'           => $terminal->getId(),
                    'payment_id'            => $this->payment->getId(),
                ],
                [
                    'channel'               => Config::get('slack.channels.tech_alerts'),
                    'username'              => 'alerts',
                    'icon'                  => ':x:',
                ]);

            $this->trace->error(
                TraceCode::TERMINAL_EDIT,
                [
                    'merchant_id'           => $terminal->getMerchantId(),
                    'terminal_id'           => $terminal->getId(),
                    'message'               => 'terminal capability auto changed to ALL'
                ]);
        }
    }

    protected function disableTerminalIfApplicable($terminal, $error)
    {
        /*
         * If error is because of invalid terminal and terminal
         * used is direct, we can disable the terminal
         */
        if (($error->isInvalidTerminalError() === true) and
            ($terminal->isShared() === false))
        {
            $this->disableTerminal($terminal);
        }
    }

    protected function disableTerminal(Terminal\Entity $terminal)
    {
        $this->app['slack']->queue(
            TraceCode::TERMINAL_AUTO_DISABLE,
            [
                    'merchant_id'           => $terminal->getMerchantId(),
                    'merchant_name'         => $terminal->merchant->getName(),
                    'terminal_id'           => $terminal->getId(),
                    'payment_id'            => $this->payment->getId(),
                    'channel'               => Config::get('slack.channels.tech_alerts'),
                    'username'              => 'alerts',
                    'icon'                  => ':x:'
            ]
        );

        $this->trace->error(
            TraceCode::TERMINAL_AUTO_DISABLE,
            [
                'merchant_id'           => $terminal->getMerchantId(),
                'terminal_id'           => $terminal->getId(),
            ]
        );

        $terminal->setEnabled(false);

        $this->repo->saveOrFail($terminal);
    }

    /**
     * Marks the payment as acknowledged.
     *
     * @param Payment\Entity $payment
     * @param array          $input
     */
    public function acknowledge(Payment\Entity $payment, array $input)
    {
        $notes = $input[Payment\Entity::NOTES] ?? [];

        $this->trace->info(
            TraceCode::PAYMENT_ACKNOWLEDGE_REQUEST,
            [
                'input'            => $input,
                Payment\Entity::ID => $payment->getId(),
            ]);

        $this->mutex->acquireAndRelease($payment->getId(),
            function() use ($payment, $notes)
            {
                $this->repo->reload($payment);

                $payment->getValidator()->acknowledgeValidate();

                $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

                $payment->setAcknowledgedAt($currentTime);

                $payment->appendNotes($notes);

                $this->repo->saveOrFail($payment);
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $this->trace->info(
            TraceCode::PAYMENT_ACKNOWLEDGED,
            [
                Payment\Entity::ID              => $payment->getId(),
                Payment\Entity::ACKNOWLEDGED_AT => $payment->getAcknowledgedAt()
            ]);
    }

    public function fixAttemptedOrder($payment, $order)
    {
        $this->payment = $payment;

        $offer = $this->selectForcedOfferForPayment($order);

        $this->payment->associateOffer($offer);

        $this->postPaymentAuthorizeOfferProcessing($this->payment);

        $this->updateOrderStatusPaidIfApplicable($order, $this->payment);

        if (($order->isExternal() === true) and
            ($order->getStatus() === Order\Status::PAID))
        {
            $input['status'] = Order\Status::PAID;

            \Event::dispatch(new TransactionalClosureEvent(function () use ($input, $order)
            {
                OrderUpdate::dispatchNow($this->mode, $input, $order);
            }));
        }
        else
        {
            $this->repo->saveOrFail($order);
        }

        $this->repo->saveOrFail($order);

        $this->eventOrderPaid();
    }

    public function redirectTo3ds($id)
    {
        $payment = $this->retrieve($id);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_3DS_REDIRECT_INITIATED, $payment);

        $diff = time() - $payment->getCreatedAt();

        if ($diff > self::PAYMENT_FALLBACK_TIME_DURATION)
        {
            $this->segment->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_REDIRECT);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_REDIRECT);
        }

        $authType = $payment->getAuthType();

        if (($authType === null) or
            (Payment\AuthType::isRedirectTo3dsAuth($authType) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REDIRECT_INVALID_AUTH,
                null,
                [
                    'auth_type' => $authType
                ]
            );
        }

        $key = $payment->getCacheInputKey();

        $inputDetails = $this->getInputDetails($payment, $key);

        if ($inputDetails === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
            );
        }

        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment, $inputDetails)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                if ($payment->hasBeenAuthorized() === true)
                {
                    return $this->processPaymentCallbackSecondTime($payment);
                }

                $this->setAnalyticsLog($payment);

                $payment->setAuthType(Payment\AuthType::_3DS);

                $payment->setAuthenticationGateway(null);

                $this->repo->saveOrFail($payment);

                // temporary code
                if (empty($inputDetails['gateway_input']) === true)
                {
                    return $this->authorize($payment, $inputDetails);
                }

                $gatewayInput = $inputDetails['gateway_input'];

                $gatewayInput['selected_terminals_ids'] = [$payment->getTerminalId()];

                unset($inputDetails['gatewayInput']);

                return $this->gatewayRelatedProcessing($payment, $inputDetails, $gatewayInput);
            },
            120,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }

    //
    // This function is used to reverse the payment's following attributes:
    // 1. amount_refunded: amount_refunded - $refund[amount]
    // 2. refund_status: {full to partial} {full to null} {partial to null}
    // 3. status: {refunded to captured} only in the case of amount_refunded being changed from full to partial/null
    //
    public function revertPaymentToRefundableState(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->mutex->acquireAndRelease($payment->getId(), function() use ($payment, $refund)
        {
            $this->trace->info(
                TraceCode::PAYMENT_STATUS_UPDATE_INITIATED,
                [
                    'refund_id'                    => $refund->getId(),
                    'payment_id'                   => $payment->getId(),
                    'payment_status'               => $payment->getStatus(),
                    'payment_refund_status'        => $payment->getRefundStatus(),
                    'payment_amount_refunded'      => $payment->getAmountRefunded(),
                    'payment_base_amount_refunded' => $payment->getBaseAmountRefunded(),
                ]);

            $amountRefunded = $payment->getAmountRefunded();

            $baseAmountRefunded = $payment->getBaseAmountRefunded();

            $amountRefunded = $amountRefunded - $refund->getAmount();
            $baseAmountRefunded = $baseAmountRefunded - $refund->getBaseAmount();

            $payment->setAmountRefunded($amountRefunded);
            $payment->setBaseAmountRefunded($baseAmountRefunded);

            $this->resetPaymentStatusAndRefundStatus($payment);

            $this->repo->saveOrFail($payment);

            $this->trace->info(
                TraceCode::PAYMENT_STATUS_UPDATE_COMPLETE,
                [
                    'refund_id'                    => $refund->getId(),
                    'payment_id'                   => $payment->getId(),
                    'payment_status'               => $payment->getStatus(),
                    'payment_refund_status'        => $payment->getRefundStatus(),
                    'payment_amount_refunded'      => $payment->getAmountRefunded(),
                    'payment_base_amount_refunded' => $payment->getBaseAmountRefunded(),
                ]);
        },
        120,
        ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
        20,
        1000,
        2000);
    }

    public function revertProcessedRefundToCreatedState(Payment\Refund\Entity &$refund)
    {
        $refund->setStatus(Payment\Refund\Status::CREATED);

        $this->updateReference1AndTriggerEventArnUpdated($refund, null, false);

        $refund->setProcessedAt(null);

        $refund->setGatewayRefunded(null);

        // Since we are filling this by default if refund is not being tried instantly
        $refund->setSpeedProcessed(Speed::NORMAL);
    }

    protected function resetPaymentStatusAndRefundStatus(Payment\Entity $payment)
    {
        // If total amount refund is 0, setting payment's refund status to null
        if ($payment->getAmountRefunded() === 0)
        {
            $payment->setRefundStatus(Payment\RefundStatus::NULL);
        }
        // If total amount refund is not 0, setting payment's refund status to partial if not already in partial
        else if ($payment->getAmountRefunded() !== $payment->getAmountAuthorized())
        {
            $payment->setRefundStatus(Payment\RefundStatus::PARTIAL);
        }

        $payment->setStatus(Payment\Status::CAPTURED);
    }

    protected function getUpiStatus(string $id)
    {
        $key = Payment\Entity::getCacheUpiStatusKey($id);

        try
        {
            return $this->cache->get($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::UPI_CACHE_READ_ERROR,
                ['key' => $key]);
        }
    }

    /**
     * Returns cps route of payment
     *
     * @param  string $id payment id
     * @return array value of cps Route Key
     */
    protected function getCpsRoute(string $id)
    {
        $key = Payment\Entity::getCacheCpsRouteKey($id);

        try
        {
            return $this->cache->get($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::UPI_CACHE_READ_ERROR,
                ['key' => $key]);
        }
    }

    /**
     * Key will be deleted from the cache when the upi
     * payment entity gets updated. Deletion is in the
     * observer class(Models/Payment/Observer.php).
     *
     * @param string $id
     * @param array  $value
     * @param float  $ttl (in seconds)
     */
    protected function setUpiStatus(string $id, array $value, float $ttl = 45)
    {
        $key = Payment\Entity::getCacheUpiStatusKey($id);

        try
        {
            $this->cache->put($key, $value, $ttl);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::UPI_CACHE_STORE_ERROR,
                ['key' => $key,
                 '$value' => $value]);
        }
    }

    /**
     * Sets value of cps route key in cache
     *
     * @param string $id payment id
     * @param array $value value to store in cache
     */
    protected function setCpsRoute(string $id, array $value, float $ttl = 45)
    {
        $key = Payment\Entity::getCacheCpsRouteKey($id);

        try
        {
            $this->cache->put($key, $value, $ttl);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::UPI_CACHE_STORE_ERROR,
                ['key' => $key,
                    '$value' => $value]);
        }
    }

    protected function logRequestTime($payment, $startTime)
    {
        try
        {
            $requestTime = get_diff_in_millisecond($startTime);

            (new Payment\Metric)->pushCreateRequestTimeMetrics($payment, $requestTime);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_ERROR_LOGGING_REQUEST_TIME_METRIC
            );
        }
    }

  protected function logCallbackRequestTime($payment, $startTime)
    {
        try
        {
            $requestTime = get_diff_in_millisecond($startTime);

            (new Payment\Metric)->pushCallbackRequestTimeMetrics($payment, $requestTime);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_ERROR_LOGGING_REQUEST_TIME_METRIC
            );
        }
    }

    protected function logPGRouterRequestTime($payment, $startTime)
    {
        try
        {
            $requestTime = get_diff_in_millisecond($startTime);

            (new Payment\Metric)->pushRequestTimeMetricsViaPGRouter($payment, $requestTime);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_ERROR_LOGGING_REQUEST_TIME_METRIC
            );
        }
    }

    protected function isAppTokenPresent(): bool
    {
        if ($this->request->hasSession() === false)
        {
            return false;
        }

        $key = $this->mode . '_app_token';

        $appToken = $this->request->session()->get($key);

        if ($appToken !== null)
        {
            return true;
        }

        return false;
    }

    protected function preProcesspaylaterResponseHandler($response, $payment, $input, $merchant, $terminal)
    {
        if($terminal['gateway'] === Payment\Gateway::SHARP)
        {
            return;
        }
        switch ($terminal['gateway_acquirer'])
        {
            case Payment\Gateway::GETSIMPL:
                //
                // Ajax flow for simpl will be supported in future
                //
                $coproto = $this->preProcessGetSimplCoproto($response, $input, $payment, $merchant);
                break;

            case PayLater::ICICI:
                return;
                break;

            case PayLater::FLEXMONEY:
            case Paylater::LAZYPAY:
                return;
                break;

            default:
                (new Customer\Raven)->sendOtp($input, $merchant);
                $coproto = $this->preProcessPaylaterCoproto($payment, $input, $merchant);
                break;
        }

        return $coproto;
    }

    protected function preProcessPaylaterCoproto($payment, $input, $merchant)
    {
        $coproto = [
            'type'      => 'respawn',
            'method'    => 'paylater',
            'request' => [
                'url'     => $this->route->getUrlWithPublicAuth('otp_verify', [
                    'method'   => 'paylater',
                    'provider' => $input['provider']
                ]),
                'method'  => 'POST',
                'content' => $input,
            ],
            'image'      => $payment->merchant->getFullLogoUrlWithSize(Merchant\Logo::MEDIUM_SIZE),
            'theme'      => $payment->merchant->getBrandColorElseDefault(),
            'merchant'   => $merchant->getDbaName(),
            'gateway'    => $this->getEncryptedGatewayText(Payment\Gateway::PAYLATER),
            'resend_url' => $this->route->getUrlWithPublicAuth('otp_post'),
            'key_id'     => $this->ba->getPublicKey(),
            'version'    => '1',
            'payment_create_url' => $this->route->getUrlWithPublicAuth('payment_create'),
        ];

        return $coproto;
    }

    protected function preProcessGetSimplCoproto($response, $input, $payment, $merchant)
    {
        $this->repo->saveOrFail($payment);

        if (empty($response['next']['redirect']['url']) === false )
        {
            $url = $response['next']['redirect']['url'];
            $coproto = [
                'type'       => 'first',
                'version'    => 1,
                'payment_id' => $payment->getPublicId(),
                'method'     => 'paylater',
                'gateway'    => $this->getEncryptedGatewayText(Payment\Gateway::GETSIMPL),
                'amount'     => $payment->getFormattedAmount(),
                'request'    => [
                    'url'     => $url,
                    'method'  => 'redirect',
                    'content' => $input,
                ],
            ];

            return $coproto;
        }

        else
        {
            unset($input['payment']);

            $input['payment_id'] = $payment->getPublicId();

            (new Customer\Raven)->sendOtp($input, $merchant);

            $coproto =  $coproto = [
                'type'      => 'respawn',
                'method'    => 'paylater',
                'request' => [
                    'url'     => $this->route->getUrlWithPublicAuth('otp_verify', [
                        'method'   => 'paylater',
                        'provider' => $input['provider'],
                        'payment_id' => $input['payment_id']
                    ]),
                    'method'  => 'POST',
                    'content' => $input,
                ],
                'payment_id' => $payment->getPublicId(),
                'image'      => $payment->merchant->getFullLogoUrlWithSize(Merchant\Logo::MEDIUM_SIZE),
                'theme'      => $payment->merchant->getBrandColorElseDefault(),
                'merchant'   => $merchant->getDbaName(),
                'gateway'    => $this->getEncryptedGatewayText(Payment\Gateway::PAYLATER),
                'resend_url' => $this->route->getUrlWithPublicAuth('otp_post'),
                'key_id'     => $this->ba->getPublicKey(),
                'version'    => '1',
                'payment_create_url' => $this->route->getUrlWithPublicAuth('payment_create'),
                'payment_authenticate_url' => $this->route->getUrl('payment_redirect_to_authenticate_get',['id' => $payment->id]),
            ];

            return $coproto;
        }
    }

    protected function shouldSaveVpaForUpiPayments():bool
    {
        return (($this->payment->isUpi() === true) and ($this->merchant->shouldSaveVpa() === true));
    }

    protected function setForceTerminalIdIfApplicable(Payment\Entity $payment, $input)
    {
        if (isset($input[Payment\Entity::FORCE_TERMINAL_ID]) === true)
        {
            $forceTerminalId = $input[Payment\Entity::FORCE_TERMINAL_ID];

            Terminal\Entity::verifyIdAndSilentlyStripSign($forceTerminalId);

            $this->trace->info(
                TraceCode::SETTING_FORCE_TERMINAL_ID_TO_PAYMENT,
                [
                    'payment'           => $payment->toArray(),
                    'force_terminal_id' => $forceTerminalId,
                ]
            );

            $payment->setForceTerminalId($forceTerminalId);
        }
    }

    protected function setAxisTokenHQGatewayIfApplicable(Payment\Entity $payment, $input)
    {
        $merchant = $payment->merchant;

        if ($merchant->isFeatureEnabled(Feature::ISSUER_TOKENIZATION_LIVE) === false) {
            return;
        }

        if (isset($input[Customer\Token\Entity::TOKEN]) === true && $payment->getMethod() == Method::CARD)
        {
            if ((isset($input[Payment\Method::CARD]) === true) and (isset($input[Payment\Method::CARD][Card\Entity::VAULT]) === true) and $input[Payment\Method::CARD][Card\Entity::VAULT] == Card\Vault::AXIS)
            {
                $payment->setGateway(Payment\Gateway::AXIS_TOKENHQ);
            }
        }
    }

    protected function getCardCacheTtl($input)
    {
        return self::REDIRECT_CACHE_TTL;
    }

    protected function pushPaymentToKafkaForVerify(Payment\Entity $payment)
    {
        $startTime = microtime(true);
        $gateway = $payment->getGateway();
        $method = $payment->getMethod();

        if (empty($payment->getGooglePayMethods()) === false)
        {
            $gateway = Payment\Entity::GOOGLE_PAY;
        }

        $isReminderVerifyPayment = true;
        $isReminderTimeoutPayment = true;

        if ((in_array($gateway, Payment\Gateway::$fileBasedEMandateDebitGateways) === true) and
            ($payment->getRecurringType() === Payment\RecurringType::AUTO))
        {
            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_STOPPED_FOR_FILE_BASED_DEBITS,
                [
                    'payment_id' => $payment->getId(),
                ]);

            $isReminderVerifyPayment = false;
            $isReminderTimeoutPayment = false;
        }

        if (in_array($method, Payment\Method::$timeoutDisabledMethods) === true)
        {
            $isReminderTimeoutPayment = false;
        }

        if (in_array($gateway, Payment\Gateway::$verifyDisabled) === true)
        {
            $isReminderVerifyPayment = false;
        }

        if (($this->app->runningUnitTests() === true) or ($this->mode !== Mode::LIVE) or (empty($gateway) === true))
        {
            $isReminderVerifyPayment = false;
            $isReminderTimeoutPayment = false;
        }

        $this->trace->info(
            TraceCode::PAYMENT_KAFKA_PUSH_INITIATED,
            [
                'payment_id'               => $payment->getId(),
                'isReminderTimeoutPayment' => $isReminderTimeoutPayment ,
                'isReminderVerifyPayment'  => $isReminderVerifyPayment,
            ]
        );

        $isPushedToKafka = (new Payment\Core())->pushPaymentToKafka($payment, $startTime, $isReminderTimeoutPayment, $isReminderVerifyPayment);

        (new Payment\Metric())->pushVerifyViaOldOrNewFlowMetrics(get_diff_in_millisecond($startTime), $isReminderVerifyPayment, $payment->getGateway());
        (new Payment\Metric())->pushTimeoutViaOldOrNewFlowMetrics(get_diff_in_millisecond($startTime), $isReminderTimeoutPayment, $payment->getMethod());

        return $isPushedToKafka;
    }

    public function calculateCustomerFee($payment, $order, $rzpFee) : ?int
    {
        $paymentConfig = $this->repo->config->findOrFail($order->getFeeConfigId());

        $feeConfig = $paymentConfig->getFormattedConfig();

        $feeConfigRules = $feeConfig['rules'];

        if(isset($feeConfigRules[$payment->method]) === false)
        {
            return null;
        }

        if($payment->isCard() === true)
        {
            /* Check if its possible for card to not have any type*/
            $cardType = $payment->card->getType();

            if(isset($feeConfigRules[$payment->getMethod()]['type'][$cardType]) === true)
            {
                $rule = $feeConfigRules[$payment->getMethod()]['type'][$cardType]['fee'];

                return $this->calculateCustomerFeeFromRule($rule, $rzpFee);
            }
            elseif(isset($feeConfigRules[$payment->getMethod()]['fee']) === true )
            {
                $rule = $feeConfigRules[$payment->getMethod()]['fee'];

                return $this->calculateCustomerFeeFromRule($rule, $rzpFee);
            }
        }
        else
        {
            $rule = $feeConfigRules[$payment->getMethod()]['fee'];

            return $this->calculateCustomerFeeFromRule($rule, $rzpFee);
        }
        return null;
    }

    public function calculateCustomerFeeGst($customerFee, $rzpFee, $tax) : ?int
    {
        if($customerFee === null)
        {
            return null;
        }

        if($rzpFee === 0)
        {
            return 0;
        }

        return ((int)round(($customerFee/($rzpFee) * $tax)));
    }

    protected function calculateCustomerFeeFromRule($rule, $rzpFee) : int
    {
        if($rule['payee'] === 'customer')
        {
            if(isset($rule['percentage_value']) === true)
            {
                return ((int)round($rule['percentage_value']/100 * ($rzpFee)));
            }
            else {
                return $rule['flat_value'] > $rzpFee ? $rzpFee : $rule['flat_value'];
            }
        }
        elseif($rule['payee'] === 'business')
        {
            if(isset($rule['percentage_value']) === true)
            {
                return ((int)round(((100 - $rule['percentage_value'])/100) * ($rzpFee)));
            }
            else {
                return ($rzpFee) - $rule['flat_value'] < 0 ?  0 : ($rzpFee) - $rule['flat_value'];
            }
        }
    }

    protected function shouldCallGatewayFunction(): bool
    {
        if ($this->payment->isCoD() === true or
            ($this->payment->isOffline() === true) or
            ($this->payment->getMethod() === Method::INTL_BANK_TRANSFER))
        {
            return false;
        }

        return true;
    }

    /**
     * Generic function to suggest backup options in case of payment failures
     * Enriches the exception object with metadata for next actions on meeting suitable criteria
     *
     * @param Payment\Entity $payment
     * @param Merchant\Entity $merchant
     * @param Exception\BaseException $e
     **/
    public function addBackupMethodForRetry(Payment\Entity $payment, Entity $merchant, Exception\BaseException &$e)
    {
        $library = (new Payment\Service)->getLibraryFromPayment($payment);
        if(in_array($e->getError()->getInternalErrorCode(), self::$errorCodesToAllowPaypal)) {
            if ($payment->isCard() === true && $payment->isInternational() === true
                && $merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::DISABLE_PAYPAL_AS_BACKUP) === false
                && $library === Payment\Analytics\Metadata::CHECKOUTJS) {

                //@TODO : Make the following block more generic to test if any method is enabled

                $wallets = $merchant->getMethods()->getEnabledWallets();

                foreach (self::$retryMethodsForIntl as $method) {
                    if (isset($wallets[$method]) === true && $wallets[$method] === true) {
                       Exception\Handler::constructErrorWithRetryMetadata($method, 'wallet', $e);
                    }
                }
            }
        }
    }

    public function createCardForNetworkTokenCardMandate($card, $token, $input, $payment)
    {
        try
        {
            if ($token->card->isRuPay() === true)
            {
                return $this->createCardForNetworkToken($card, $input, $payment);
            }

            if($token->cardMandate->getVaultTokenPan() === null)
            {
                $cryptogram = (new Card\CardVault)->fetchCryptogramForPayment($card->getVaultToken(), $card->merchant);

                $recurringTokenNumber = ["token" => ["number" => $cryptogram['token_number']]];

                (new CardMandate\Core())->storeVaultTokenPan($token->cardMandate, $recurringTokenNumber);

                return $this->createCardForNetworkToken($card, $input, $payment, null, $cryptogram['token_number']);
            }
            else
            {
                $recurringTokenNumber = (new Card\CardVault)->getCardNumber($token->cardMandate->getVaultTokenPan(),[],null,true);

                return $this->createCardForNetworkToken($card, $input, $payment, null, $recurringTokenNumber);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::MISC_TRACE_CODE, [
                'failedRetryStoringPanEntireLogic'     => $e,
            ]);
        }

        return $this->createCardForNetworkToken($card, $input, $payment);
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     * @throws \Throwable
     * @throws Exception\BadRequestException
     */
    private function validate1CCFlow(array $input)
    {
        if ($this->merchant->isFeatureEnabled(Features::ONE_CLICK_CHECKOUT) === false)
        {
            return;
        }

        if (isset($this->order) === true)
        {
            $orderMeta = null;

            foreach ($this->order->orderMetas as $oMeta)
            {
                if ($oMeta->getType() === Order\OrderMeta\Type::ONE_CLICK_CHECKOUT)
                {
                    $orderMeta = $oMeta;
                    break;
                }
            }
            if (empty($orderMeta) === true)
            {
                return;
            }

            // 1cc: Ensuring customer details check only occurs when oMeta is accompanied by shipping_fee etc.
            $keys = array_keys($orderMeta->getValue());

            if (sizeof($keys) === 1 and $keys[0] === 'line_items_total')
            {
                return;
            }

            $customerDetails = $orderMeta->getValue()[Order\OrderMeta\Order1cc\Fields::CUSTOMER_DETAILS] ?? null;

            if (empty($customerDetails) === true or empty($customerDetails[Order\OrderMeta\Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null);
            }

            $this->adjustCodFromGiftCardsIfApplicable($input, $orderMeta);

            $promotions = $orderMeta->getValue()[Order\OrderMeta\Order1cc\Fields::PROMOTIONS] ?? null;

            $couponData = null;
            if (empty($promotions) === false)
            {
                foreach ($promotions as $promotion)
                {
                    if (isset($promotion['type']) === false ||
                        $promotion['type'] !== 'gift_card')
                    {
                        $couponData = $promotion;
                    }
                }
            }
            if ($couponData !== null)
            {
                $couponConfig = $this->merchant->get1ccConfig(Type::COUPON_CONFIG);

                $disabledMethods =(new Merchant\MerchantPromotions\Service())->getDisabledMethods($couponConfig, $couponData['reference_id']);

                if(in_array($input['method'], $disabledMethods) === true)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_METHOD_DISABLED_FOR_COUPON);
                }
            }
        }
    }

    private function validateOTP(Payment\Entity $payment, array $gatewayInput)
    {
        if ($payment->getMethod() != Method::CARD)
        {
            return;
        }

        if (isset($gatewayInput['otp']) === false)
        {
            return;
        }

        if (strlen($gatewayInput['otp']) >= 4 && strlen($gatewayInput['otp']) <= 10)
        {
            return;
        }

        $e = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_INVALID_LENGTH, null,  [
            'next' => $this->getNextOtpAction(['resend_otp']),
        ]);

        $error = $e->getError();

        $error->setPaymentMethod(Payment\Method::CARD);

        throw $e;

    }

    public function isDarkRequest(): bool
    {
        $url = Request::url();

        return starts_with($url, 'https://api-dark.razorpay.com');
    }

    /**
     * @param $input
     * @return bool
     */
    private function isPaymentViaTokenisedCard($input): bool
    {
        return empty($input[Payment\Entity::CARD][Card\Entity::TOKENISED]) === false &&
            boolval($input[Payment\Entity::CARD][Card\Entity::TOKENISED]) === true;
    }


    /**
     * @param $input
     * @param $orderMeta
     *  @throws \Throwable
     */
    // this function checks the applied gift card still have enough balance that was applied , and adjust cod if gift card has extra balance
    protected function adjustCodFromGiftCardsIfApplicable($input, $orderMeta) {

        $promotionsDetails = $orderMeta->getValue()[Order\OrderMeta\Order1cc\Fields::PROMOTIONS] ?? [];

        $orderId = $input['order_id'];

        $method = $input[Payment\Entity::METHOD];

        if (empty($promotionsDetails) === false && count($promotionsDetails) > 0) {

            $appliedGiftCards = (new Merchant\OneClickCheckout\Utils\CommonUtils())->removeCouponsFromPromotions($promotionsDetails);

            if (count($appliedGiftCards) > 0) {
                (new Merchant\MerchantGiftCardPromotions\Service())->adjustCodFeeIfGiftCardBalanceAvailable($orderId, $orderMeta, $method);
            }

        }
    }

    private function fetchAndSetOrdertoCurrentContext(array $input)
    {
        if (isset($input['order_id']) === true)
        {
            $this->order = $this->repo->order->findByPublicIdAndMerchant($input['order_id'], $this->merchant);
        }
    }

    public function getCallbackMutexResource(Payment\Entity $payment): string
    {
        return 'callback_' . $payment->getId();
    }

    // If payment has order id then resource will contain order id else will use payment id
    public function getCallbackOrderMutexResource(Payment\Entity $payment): string
    {
        if ($payment->hasOrder() === true)
        {
            return 'callback_order_id_' . $payment->getApiOrderId();
        }

        return 'callback_order_id_' . $payment->getId();
    }

    private function isNetworkUnionPay(array $input)
    {
        if (empty($input['card']['number']) === true)
        {
            return true;
        }

        $iin = substr($input['card']['number'], 0, 6);

        $iinDetails = $this->repo->card->retrieveIinDetails($iin);

        if ($iinDetails->getNetwork() === Card\NetworkName::UNP)
        {
            return true;
        }

        return false;
    }

    /**
     * returns if route is valid upi rearch route
     *
     * @param string $route
     * @return boolean
     */
    public static function isUpiRearchRoute(string $route): bool
    {
        return (in_array($route, self::$upiRearchRoutes, true) === true);
    }

    private function checkIfTransferSyncProcessingViaApiIsWithinLimit()
    {
        $maxLimit = (int) (new Admin\Service)->getConfigKey(['key' => ConfigKey::TRANSFER_SYNC_PROCESSING_VIA_API_HOURLY_RATE_LIMIT_PER_MID]);

        if (empty($limit) === true)
        {
            $maxLimit = self::PAYMENT_TRANSFERS_SYNC_PROCESSING_HOURLY_RATE_LIMIT;
        }

        $rateLimiter = new FixedWindowLimiter($maxLimit, 60 * 60, 'route_sync_via_api_' . $this->merchant->getId());

        $isWithinLimit = $rateLimiter->checkLimit();

        $this->trace->info(TraceCode::PAYMENT_TRANSFER_SYNC_REQUEST_RATE_CHECK, [
            'is_within_limit' => $isWithinLimit,
            'merchant_id'     => $this->merchant->getId(),
            'current_num'     => $rateLimiter->getCurrentRequestNumber(),
            'max_limit'       => $maxLimit,
        ]);

        return $isWithinLimit;
    }

    private function checkIfTransferSyncProcessingViaCronIsEnabled()
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::ENABLE_TRANSFER_SYNC_PROCESSING_VIA_CRON,
            $this->mode
        );

        return $variant === 'on';
    }
}
