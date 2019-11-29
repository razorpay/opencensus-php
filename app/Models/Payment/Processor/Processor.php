<?php

namespace RZP\Models\Payment\Processor;

use App;
use Route;
use Config;
use Carbon\Carbon;

use RZP\Error\Error;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Risk;
use RZP\Models\Admin;
use RZP\Models\Order;
use RZP\Models\Offer;
use RZP\Models\Gateway;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Services\Doppler;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Models\EntityOrigin;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Flow;
use RZP\Constants\Environment;
use RZP\Models\Payment\Metric;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\AuthType;
use RZP\Constants\Entity as E;
use RZP\Base\RepositoryManager;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Methods;
use RZP\Models\Plan\Subscription;
use RZP\Constants\Mode as RZPMode;
use RZP\Gateway\Base\CardCacheTrait;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Transfer\Core as TransferCore;

use Razorpay\Trace\Logger as Trace;

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
     */
    const UPI_COLLECT_EXPIRY = 5;

    /**
     * Minimum payment amount for which mdr should be calculated
     */
    const MIN_MDR_PAYMENT_AMOUNT = 200000;

    /**
     * Timeout to store card details for fallback auth type
     */
    const CARD_CACHE_TTL = 10;

    /**
     * Timeout to store card details for redirect to authorize
     */
    const REDIRECT_CACHE_TTL = 20;

    /**
     * Timeout to store redirect authorize response cache
     */
    const REDIRECT_CACHE_RESPONSE_TTL = 2;

    const CACHE_KEY = 'fallback_%s_card_details';

    /**
     * Core payment service feature flag
     */
    const CPS_FEATURE_FLAG_PREFIX = 'cps_gateway_routing';
    const CARD_PAYMENTS_PREFIX    = 'card_payments_gateway_routing';

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
     * @var Subscription\Entity
     */
    protected $subscription;

    protected $receiver;
    protected $segment;

    protected $verifyRefundStatus;

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

    public function process(array $input, $gatewayInput = []): array
    {
        try
        {
            $startTime = microtime(true);

            $this->setMethodForInput($input);

            $this->appendMetadataForPayment($input);

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_INPUT_VALIDATIONS_INITIATED);

            $payment = $this->buildPaymentEntity($input);

            $this->preProcessForSubscriptionsIfApplicable($input, $payment);

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

            // This flow is being used for only hosted (Shopify).
            $this->checkSignature($input, $payment);

            $paymentData = $this->authorize($payment, $input, $gatewayInput);

            // Creates an origin entity for the payment based on the auth used to initiate the payment.
            (new EntityOrigin\Core)->createEntityOrigin($payment);

            $this->logRequestTime($payment, $startTime);

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATE_REQUEST_PROCESSED, $payment);

            return $paymentData;
        }
        catch (\Throwable $e)
        {
            $payment = $payment ?? null;

            $dimensions[Metric::LABEL_PAYMENT_IS_CREATED] = false;

            if ($payment instanceof Payment\Entity === true)
            {
                $dimensions[Metric::LABEL_PAYMENT_IS_CREATED] = $payment->wasRecentlyCreated;
            }

            (new Payment\Metric)->pushExceptionMetrics($e, Metric::PAYMENT_PROCESS_FAILED, $dimensions);

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATE_REQUEST_PROCESSED, $payment, $e);

            throw $e;
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

        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATION_RESPAWN, null, null, $properties);
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

            $payment->setRecurringType($subscriptionPaymentRecurringType);

            $this->addOrderIdToInputForExternalSubscription($input);
        }
    }

    protected function addOrderIdToInputForExternalSubscription(array & $input)
    {
        assert($this->subscription->isExternal() === true);

        // For subscription card change, we donot need to add order id
        // for the following subscription states. (For these states, we will be
        // using default auth amount as card change amount)
        if (($this->subscription->isActive() === true) or
            ($this->subscription->isHalted() === true) or
            ($this->subscription->isAuthenticated() === true))
        {
            $cardChange = boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false);

            if ($cardChange === true)
            {
                return;
            }
        }

        if (($this->subscription->hasCurrentInvoice() === true) and
            (isset($input[Payment\Entity::ORDER_ID]) === false))
        {
            $currentInvoiceId = $this->subscription->getCurrentInvoiceId();

            $invoice = $this->repo->invoice->findOrFailPublic($currentInvoiceId);

            $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($invoice->getOrderId());
        }
    }

    protected function addCustomerIdToInputForExternalSubscription(array & $input)
    {
        assert($this->subscription->isExternal() === true);
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

    protected function preProcessPaymentInputs(array $input, Payment\Entity $payment)
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
                break;

            case Payment\Method::PAYLATER:
                $coproto = $this->preProcessPaymentInputsForPayLater($input, $payment);
                break;
        }

        return $coproto;
    }

    protected function preProcessPaymentInputsForCardlessEmi($input, $payment)
    {
        $this->verifyCardlessEmiEnabled();

        if ((empty($input['ott']) === false) and
            (in_array($input['provider'], Payment\Gateway::$cardlessEmiRedirectFlowProvider) === false))
        {
            return;
        }

        $payment = $this->repo->transaction(function() use ($input, $payment)
        {
            $payment = $this->createPaymentEntity($input, $payment);

            $payment->setBaseAmount($payment->getAmount());

            $this->repo->saveOrFail($payment);

            return $payment;
        });

        $input['payment_id'] = $payment->getPublicId();

        if ((empty($input['emi_duration']) === false) and
            (in_array($input['provider'], Payment\Gateway::$cardlessEmiRedirectFlowProvider) === true))
        {
            return;
        }

        $gateway = Payment\Gateway::CARDLESS_EMI;

        $merchant = $payment->merchant;

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

            $coproto['payment_id'] = $payment->getPublicId();

            unset($coproto['request']['content']['contact']);

            return $coproto;
        }

        $terminal = $this->repo
                         ->terminal
                         ->getByMerchantProviderAndMethod($input[Payment\Entity::PROVIDER],
                                                          $merchant[Merchant\Entity::ID],
                                                          Payment\Method::CARDLESS_EMI);
        try
        {
            $checkAccountData = $this->app['gateway']->call($gateway, 'check_account', $input, $this->mode, $terminal);
        }
        catch (Exception\GatewayErrorException $exception)
        {
            $this->payment->setStatus(Payment\Status::FAILED);

            $error = $exception->getError();

            if ($error === null)
            {
                $this->payment->setError(null, null, null);
            }
            else
            {
                $errorCode = $error->getGatewayErrorCode();

                $errorDescription = $error->getDescription();

                $internalErrorCode = $error->getInternalErrorCode();

                $this->payment->setError($errorCode, $errorDescription, $internalErrorCode);
            }

            $this->payment->saveOrFail();

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

        if (in_array($input['provider'], Payment\Gateway::$cardlessEmiRedirectFlowProvider) === true)
        {
            $coproto['emi_plans'] = [
                $input['provider'] => $checkAccountData['emi_plans']
            ];
            $coproto['lender_branding_url'] = $checkAccountData['lender_branding_url'];
        }
        else
        {
            (new Customer\Raven)->sendOtp($input, $merchant);

            $coproto['resend_url'] = $this->route->getUrlWithPublicAuth('otp_post');
        }

        $coproto['payment_id'] = $payment->getPublicId();

        return $coproto;
    }

    protected function preProcessPaymentInputsForPayLater($input, $payment)
    {
        $this->verifyPayLaterEnabled();

        if (empty($input['ott']) === false)
        {
            return;
        }

        $merchant = $payment->merchant;

        $gateway = Payment\Gateway::PAYLATER;

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
                'method' => 'paylater',
                'version' => '1',
                'provider' => $input['provider'],
            ];

            $coproto['missing'][] = 'contact';

            unset($coproto['request']['content']['contact']);

            return $coproto;
        }

        $terminal = $this->repo
                         ->terminal
                         ->getByMerchantProviderAndMethod($input[Payment\Entity::PROVIDER],
                                                          $merchant[Merchant\Entity::ID],
                                                          Payment\Method::PAYLATER);

        $this->app['gateway']->call($gateway, 'check_account', $input, $this->mode, $terminal);

        $data = (new Customer\Raven)->sendOtp($input, $merchant);

        $coproto = [
            'type' => 'respawn',
            'method' => 'paylater',
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
            'gateway'    => $this->getEncryptedGatewayText($gateway),
            'resend_url' => $this->route->getUrlWithPublicAuth('otp_post'),
            'key_id'     => $this->ba->getPublicKey(),
            'version'    => '1',
            'payment_create_url' => $this->route->getUrlWithPublicAuth('payment_create'),
        ];

        return $coproto;
    }

    protected function preProcessPaymentInputsForEmandate(array $input, Payment\Entity $payment)
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
            ($currentRouteName === 'subscription_registration_charge_token'))
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

        //
        // We need this flow only if either:
        //   - bank_account is missing
        //   - auth_type is missing
        //
        if ((empty($input[Payment\Entity::BANK_ACCOUNT]) === false) and
            (empty($payment->getAuthType()) === false))
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
                'url'     => $this->route->getUrlWithPublicAuthInQueryParam($currentRouteName),
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

        if ((isset($input['_']['flow']) === false) or ($input['_']['flow'] === Payment\Flow::COLLECT))
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

        $terminal = $this->repo->beginTransactionAndRollback(
            function() use ($input, $receiver)
            {
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

                return $selectedTerminals[0] ?? null;
            });

        return $terminal;
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

        //
        // We only create a dummy payment entity for purpose
        // of pre-calculating fees and returning it.
        // It's not going to be saved in the database.
        //
        $payment = $this->buildPaymentEntity($input);

        // Performing dummy set of processing for the same
        $this->dummyPrePaymentAuthorizeProcessing($payment, $input);

        list($fee, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payment);


        if ($payment->getFeeBearer() === Merchant\FeeBearer::PLATFORM)
        {
            $fee = 0;

            $tax = 0;
        }


        $data = [
            'originalAmount'  => $input['amount'],
            'original_amount' => $input['amount'],
            'fees'            => $fee,
            'razorpay_fee'    => $fee - $tax,
            'tax'             => $tax,
            'amount'          => $input['amount'] + $fee,
        ];

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

        if ((isset($input[Payment\Entity::TOKEN]) === true) and
            (isset($input[Payment\Entity::CUSTOMER_ID]) === true))
        {
            $customerId = $input[Payment\Entity::CUSTOMER_ID];
            $tokenId = $input[Payment\Entity::TOKEN];

            Customer\Entity::verifyIdAndStripSign($customerId);

            //
            // TokenID can either be the token ID or the
            // `token` attribute of the token entity.
            //
            Customer\Token\Entity::verifyIdAndSilentlyStripSign($tokenId);

            $token = (new Customer\Token\Core)->getByTokenIdAndCustomerId($tokenId, $customerId);

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

    /**
     * This method sets the flag that this payment should be processed via
     * Core payment service
     */
    protected function setPaymentRoutedThroughCpsIfApplicable(Payment\Entity $payment, $gatewayInput)
    {
        // Check if payment is card
        if ($payment->isMethod(Payment\Method::CARD) === false)
        {
            $payment->disableCpsRoute();

            return;
        }

        if (Payment\Gateway::isCardPaymentServiceGateway($payment->getGateway()))
        {
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

            // Hardcoding this till wallet phonepe intent is moved to cps.
            if (($payment->getGateway() === Payment\Gateway::WALLET_PHONEPE) and ($gatewayInput['wallet']['flow'] === 'intent'))
            {
                $payment->disableCpsRoute();

                return;
            }

            $this->setPaymentService($payment, $variant);
        }
    }

    /**
     * Handles Checks for Card Payment service gateways.
     */
    protected function handleCardPaymentServiceGateways(Payment\Entity $payment, $gatewayInput)
    {
        if ((bool) Admin\ConfigKey::get(Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED, false) === true)
        {
            $variant = $this->getRazorxVariant($payment, self::CARD_PAYMENTS_PREFIX);

            $this->setPaymentService($payment, $variant);
        }
    }

    protected function getRazorxVariant(Payment\Entity $payment, $prefix)
    {
        $featureFlag = $prefix. '_' .$payment->getGateway();

        if (empty($payment->getAuthenticationGateway()) === false)
        {
            $featureFlag .= '_' .$payment->getAuthenticationGateway();
        }

        $variant = $this->app->razorx->getTreatment($payment->getMerchantId(), $featureFlag, $this->mode);

        $this->trace->info(TraceCode::CPS_RAZORX_VARIANT, [
            'payment_id'             => $payment->getId(),
            'merchant_id'            => $payment->getMerchantId(),
            'gateway'                => $payment->getGateway(),
            'authentication_gateway' => $payment->getAuthenticationGateway(),
            'auth_type'              => $payment->getAuthType() ?? AuthType::_3DS,
            'feature_flag'           => $featureFlag,
            'razorx_variant'         => $variant,
        ]);

        return $variant;
    }

    protected function setPaymentService(Payment\Entity $payment, $variant)
    {
        $variant = strtolower($variant);

        switch($variant)
        {
            case 'cps':

                $payment->enableCpsRoute();

                break;
            case 'cardps':

                $payment->enableCardPaymentService();

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

        $order = $this->fetchOrderFromInput($input);

        $this->setOfferForPaymentFromOrderOrInput($payment, $input);

        if (($this->offer !== null) and
            ($order->isDiscountApplicable() === true))
        {
            $orderAmount = $order->getAmount();

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
        ksort($data);

        $str = implode('|', $data);

        return $this->ba->sign($str);
    }

    protected function checkMerchantPermissions()
    {
        $merchant = $this->merchant;

        $mode = $this->mode;

        if ($mode === Mode::TEST)
        {
            return;
        }

        // On live request, ensure that merchant is activated
        if ($merchant->isActivated() === false)
        {
            throw new Exception\LogicException(
                'A non-activated merchant is making live request. Blasphemy!',
                null,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
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

        $merchantId = $payment->getMerchantId();

        $result = app('razorx')->getTreatment($merchantId, 'transfer_deadlock_retry', $this->mode);

        $deadLockRetryAttempts = 1;

        if (strtolower($result) === 'on')
        {
            $deadLockRetryAttempts = 2;
        }

        return $this->mutex->acquireAndRelease(
            $payment->getId(),
            function() use ($payment, $input, $deadLockRetryAttempts)
            {
                $this->repo->reload($payment);

                return $this->repo->transaction(function() use ($payment, $input)
                {
                    $transfers = (new TransferCore)->createForPayment(
                                    $payment,
                                    $input['transfers'],
                                    $this->merchant);

                    $this->trace->info(
                        TraceCode::PAYMENT_TRANSFER_SUCCESS,
                        ['transfer_ids' => $transfers->getIds()]);

                    return $transfers;
                }, $deadLockRetryAttempts);
            });
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
        $status = null;

        $payment = $this->retrieve($id);

        $diff = time() - $payment->getCreatedAt();

        if ($diff > self::PAYMENT_CANCEL_TIME_DURATION)
        {
            $this->segment->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED);
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

                throw new Exception\BadRequestException($errorCode);
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
        $response = $this->getUpiStatus($id);

        if ($response !== null)
        {
            return $response;
        }

        $payment = $this->retrieve($id);

        $order = $this->getOrderForPayment($payment);

        $gateway = $payment->getGateway();

        // If the gateway is not async we just give a generic
        // error to not leak information
        if (Payment\Gateway::supportsAsync($gateway) === false)
        {
            // Throw exception of invalid id
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        if ($payment->isCreated() === true)
        {
            // Throw payment failed exception if async payment timeout (5mins)
            // has been exceeded
            if ($payment->justCreated() === false)
            {
                $this->timeoutPayment();

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT);
            }

            $response = [
                Payment\Entity::STATUS => Payment\Status::CREATED
            ];

            $this->setUpiStatus($payment->getPublicId(), $response);

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

    public function callGatewayFunctionCaptureViaQueue($data, $payment)
    {
        $this->payment = $payment;

        $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

        $payment->setGatewayCaptured(true);

        $this->repo->saveOrFail($payment);
    }

    protected function tracePaymentInfo($traceCode, $level = Trace::INFO)
    {
        $data = $this->payment->toArrayTraceRelevant();

        $this->trace->addRecord($level, $traceCode, $data);
    }

    public function timeoutPayment()
    {
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

    protected function updatePaymentFailed($exception, $traceCode)
    {
        $error = $exception->getError();

        $code = $error->getPublicErrorCode();

        $desc = $error->getDescription();

        $internalCode = $error->getInternalErrorCode();

        $payment = $this->payment;

        $status = $payment->getStatus();

        $segmentCustomProperties = [
            'error'                 => $error,
            'code'                  => $code,
            'description'           => $desc,
            'internal_error_code'   => $internalCode,
            'status'                => $status
        ];

        $this->segment->trackPayment($payment, $traceCode, $segmentCustomProperties);

        if ($status !== Status::CREATED)
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

        $this->updateVerifyBucketOnPaymentFailure($exception);

        $this->repo->saveOrFail($payment);

        $this->tracePaymentFailed($error, $traceCode);

        (new Payment\Metric)->pushFailedMetrics($payment);

        $this->eventPaymentFailed();

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

        $isProduction = $this->app->environment(Environment::PRODUCTION);

        $variant  = $this->app->razorx->getTreatment($payment->getId(), 'api_hitting_doppler_service', $this->mode);

        if (($this->mode === RZPMode::LIVE) and
            (($isProduction === true)) and
            (strtolower($variant) === 'on'))
        {
            //TODO: Remove this later
            try
            {
                $this->app->doppler->sendFeedback($this->payment, Doppler::PAYMENT_AUTHORIZATION_FAILURE_EVENT, $code, $internalCode);
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

    protected function eventPaymentFailed()
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $this->payment
        ];

        $this->app['events']->fire('api.payment.failed', $eventPayload);
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
     * Responsible for calling the gateway function
     *
     * @param  string $action      refund/capture etc.
     * @param  array  $gatewayData Relevant input for the corresponding
     *                             action
     *
     * @return array or null
     * @throws Exception\GatewayErrorException
     * @throws Exception\LogicException
     */
    protected function callGatewayFunction($action, array $gatewayData)
    {
        $terminal = $this->repo->terminal->fetchForPayment($this->payment);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                null,
                ['payment_id' => $this->payment->getId()]);
        }

        $gateway = $this->payment->getGateway();

        $gatewayData['terminal'] = $terminal;

        $gatewayData['merchant'] = $this->payment->merchant;

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
            if ((bool) ConfigKey::get(ConfigKey::CARD_PAYMENT_SERVICE_ENABLED, false) === true)
            {
                $gatewayData[Payment\Entity::CPS_ROUTE] = Payment\Entity::CARD_PAYMENT_SERVICE;
                // Persist card details only when payment method is card or emi
                if ($this->payment->isMethodCardOrEmi() === true)
                {
                    $this->persistCardDetails($gateway, $action, $gatewayData);
                }
            }

            // card flow doesn't have any debit action
            if ($action === Action::DEBIT)
            {
                return;
            }
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
                        return $this->app['card.payments']->action($gateway, $action, $gatewayData);

                }
            }

            return $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);
        }
        catch (Exception\GatewayErrorException $ex)
        {
            $error = $ex->getError();

            $this->disableTerminalIfApplicable($terminal, $error);

            $this->changeTerminalCapabilityIfApplicable($terminal, $error);

            throw $ex;
        }
        finally
        {
            $variant  = $this->app->razorx->getTreatment(
                $this->merchant->getId(),
                'gateway_downtime_detection',
                $this->mode
            );

            // Gateway Downtime Detection only works on few actions.
            // Right now failure percentage is not considered on each
            // action individually, which we might do at later point of time.
            // For Example: Action AUTH and CALLBACK both need to succeed
            // for the payment to be successful. If one is working fine, then
            // Downtime configuration might not work properly.

            // Also, though we are putting the check here that
            // we only want these actions to succeed but inside
            // $this->app['gateway']->call(), we might call other actions.
            // Example: In case of international payments we call capture immediately.
            if ((strtolower($variant) === 'on') and
                ($this->isGatewayDowntimeAction($action) == true))
            {
                try
                {
                    (new Gateway\Downtime\Core)->createDowntimeIfApplicable($gatewayData);
                }
                catch (\Throwable $e)
                {
                    // This can be removed later.
                    // This is added for some time to test this feature.
                    $this->trace->traceException($e);
                }
            }
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
            (in_array($action, Action::$cardPaymentsSupportedActions) === true))
        {
            return true;
        }

        return false;
    }

    protected function persistCardDetails($gatewayName, $action, &$input)
    {
        $action = snake_case($action);

        if ($action === Action::AUTHORIZE)
        {
            $this->persistCardDetailsTemporarily($input);
        }
        else if ($action === Action::CALLBACK)
        {
            $this->setCardNumberAndCvv($input);
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

    protected function createPaymentEntity(array $input, Payment\Entity $payment = null): Payment\Entity
    {
        $this->tracePaymentNewRequest($input);

        if (($input['method'] === Payment\Method::CARDLESS_EMI) === true)
        {
            if ((isset($input['ott']) === true) and
                (isset($input['payment_id']) === true))
            {
                $payment = $this->repo->payment->find(Payment\Entity::stripDefaultSign($input['payment_id']));
            }
        }

        if ($payment == null)
        {
            $payment = $this->buildPaymentEntity($input);
        }

        if ($this->merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            $this->verifyProvidedFee($payment, $input);
        }

        $this->addOrderIdToInputForSubscriptionIfApplicable($input, $payment);

        $this->validateAndSetOrderDetailsIfApplicable($payment, $input);

        $this->validateAndSetPaymentLinkIfApplicable($payment, $input);

        $this->validateAndSetReceiverIfApplicable($payment, $input);

        $this->validateBankTransferDetailsIfApplicable($payment);

        $this->validateAndSetInvoiceDetailsIfApplicable($payment);

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
        $payment = new Payment\Entity;

        $payment->generateId();

        $payment->merchant()->associate($this->merchant);

        $payment->build($input);

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

    protected function validateAndSetOrderDetailsIfApplicable(
        Payment\Entity $payment,
        array $input)
    {
        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            $tpvRequired = (($payment->isTpvMethod() === true) and
                            ($this->merchant->isTPVRequired() === true));

            if (($tpvRequired === true) or
                ($payment->isEmandate() === true) or
                ($payment->isNach() === true))
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

        $this->order = $this->fetchOrderFromInput($input);

        $this->order->getValidator()->validatePaymentCreation($payment);

        $this->order->setStatus(Order\Status::ATTEMPTED);

        $this->order->incrementAttempts();

        $this->trace->info(
            TraceCode::ORDER_STATUS_ATTEMPTED,
            [
                'order_id'      => $this->order->getId(),
                'attempts'      => $this->order->getAttempts(),
            ]);

        $this->repo->saveOrFail($this->order);

        $payment->order()->associate($this->order);

        if ($payment->isNach() === true)
        {
            $payment->setBank($this->order->getBankForNachMethod());
        }

        //
        // FIXME: Hack for reliance AMC, moving order receipt to payment
        // description
        //
        if ($payment->getMerchantId() === Merchant\Preferences::MID_RELIANCE_AMC)
        {
            $payment->setDescription($this->order->getReceipt());
        }

        $orderNotes = $this->order->getNotes()->toArray();

        $payment->setIntegrationMetadataUsingNotes($orderNotes);
    }

    protected function validateAndSetReceiverIfApplicable(Payment\Entity $payment, array $input)
    {
        if (empty($input[Payment\Entity::RECEIVER]) === true)
        {
            return;
        }

        $receiverInput = $input[Payment\Entity::RECEIVER];

        $entity = $receiverInput['type'];

        $receiver = $this->repo->$entity->findbyPublicIdAndMerchant($receiverInput['id'], $this->merchant);

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

    protected function tracePaymentNewRequest(array $input)
    {
        $this->unsetSensitiveCardDetails($input);

        $this->trace->debug(TraceCode::PAYMENT_NEW_REQUEST, $input);
    }

    protected function unsetSensitiveCardDetails(array & $input)
    {
        if ((isset($input[Payment\Entity::CARD]) === true) and
            (is_array($input[Payment\Entity::CARD]) === true))
        {
            if (empty($input[Payment\Entity::CARD][Card\Entity::NUMBER]) === false)
            {
                $input[Payment\Entity::CARD][Card\Entity::IIN] = substr($input[Payment\Entity::CARD][Card\Entity::NUMBER], 0, 6);
            }

            unset($input[Payment\Entity::CARD][Card\Entity::CVV]);
            unset($input[Payment\Entity::CARD][Card\Entity::NUMBER]);
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
     * This function is used to identify if a payment can be auto captured or not
     * Please *note* that the order of the conditions is important and shouldn't be chnaged
     * without understanding the consequences
     *
     * @param Payment\Entity $payment
     *
     * @return bool
     */
    protected function shouldAutoCapture(Payment\Entity $payment): bool
    {
        // Bank transfers are auto-captured only if they are expected. This is checked later.
        if ($payment->isBankTransfer() === true)
        {
            return false;
        }

        //
        // Post payment authorization payment link's payments are actually auto captured but there is more logic in
        // the flow and in handling capture failures etc which is all done in specific method(easy to move out to a
        // service) triggered from postPaymentAuthorizeProcessing() method.
        //
        if ($payment->hasPaymentLink() === true)
        {
            return false;
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

            return false;
        }

        //
        // We do an auto capture direct settlement payment only if payment is not associated with an order.
        //
        // Later we are checking if the payment is associated with order and order status is paid then don't
        // capture this late auth payment since order is fullfilled by some other payment made for this order.
        if (($payment->isDirectSettlement() === true) and
            ($payment->hasOrder() === false))
        {
            return true;
        }

        //
        // We do an auto capture only if payment is associated with an order.
        //
        if ($payment->hasOrder() === false)
        {
            return false;
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
            return false;
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
            return false;
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
            return true;
        }

        if ((($order->isPaid() === true) and
            ($order->merchant->isFeatureEnabled(Feature::DISABLE_AMOUNT_CHECK) === false)) or
            ($order->getPaymentCapture() === false))
        {
            return false;
        }

        if ($this->isAutoRefundDelayExceeded($payment) === true)
        {
            return false;
        }

        if ($payment->isLateAuthorized() === true)
        {
            return $this->shouldAutoCaptureLateAuthorized($payment);
        }

        return true;
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

        //
        // There could be a case where the current time is greater
        // than the expire_by of the invoice. But, if we haven't
        // yet marked the invoice as expired, we still go ahead
        // and capture the payment.
        //

        //
        // Ideally this should check for `validateInvoicePayable` as a partially paid
        // PL would qualify for this.
        //
        // TODO: Change/fix this and test complete flow including the exception
        // cases with the feature BLOCK_PL_PAY_POST_EXPIRY set.
        //
        if ($invoice->isIssued() === false)
        {
            $this->trace->debug(
                TraceCode::INVOICE_PAYMENT_AUTO_CAPTURE_NOT_ALLOWED,
                [
                    'payment_id'        => $payment->getId(),
                    'status'            => $payment->getStatus(),
                    'invoice_id'        => $invoice->getId(),
                    'invoice_status'    => $invoice->getStatus(),
                ]);

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
        if ((isset($gatewayInput['skip_gateway_call']) === true) and
            ($gatewayInput['skip_gateway_call'] === true))
        {
            return false;
        }

        if ($payment->isFileBasedEmandateDebitPayment() === true)
        {
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

        $this->repo->saveOrFail($order);

        $this->eventOrderPaid();
    }

    public function redirectTo3ds($id)
    {
        $payment = $this->retrieve($id);

        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_AUTHENTICATION_3DS_REDIRECT_INITIATED, $payment);

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

        $refund->setReference1();

        $refund->setProcessedAt(null);

        $refund->setGatewayRefunded(null);
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
     * Key will be deleted from the cache when the upi
     * payment entity gets updated. Deletion is in the
     * observer class(Models/Payment/Observer.php).
     *
     * @param string $id
     * @param array  $value
     * @param float  $ttl
     */
    protected function setUpiStatus(string $id, array $value, float $ttl = 0.75)
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
}
