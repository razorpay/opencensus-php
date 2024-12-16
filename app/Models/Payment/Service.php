<?php

namespace RZP\Models\Payment;

use FuzzyWuzzy\Process;
use Illuminate\Support\Facades\DB;
use Mail;
use Crypt;
use Config;
use RZP\Constants\Metric as Metrics;
use RZP\Models\Admin;
use RZP\Models\BharatQr;
use RZP\Models\Emi\ProcessingFeePlan;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\QrPayment\Constants as QrConstants;
use RZP\Models\Reminders\ReminderProcessor;
use RZP\Http\Controllers\GatewayController;
use RZP\Jobs\MerchantBalanceUpdateAfterCLSOnboarding;
use RZP\Reconciliator\Base\SubReconciliator\PaymentReconciliate;
use RZP\Http\Request\Requests;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Jobs\CrossBorder\CrossBorderCommonUseCases;
use RZP\Services\NbPlus\CardlessEmi as CardlessEmiService;
use Throwable;
use Carbon\Carbon;
use RZP\Base\Luhn;
use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\Timezone;
use RZP\Constants\Mode;
use RZP\Base\RuntimeManager;

use RZP\Jobs;
use RZP\Models\QrCode\NonVirtualAccountQrCode;
use RZP\Diag\EventCode;
use RZP\Models\Merchant\Checkout;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Risk;
use RZP\Exception;
use RZP\Error;
use RZP\Mail\Merchant\AuthorizedPaymentsReminder as AuthorizedPaymentsReminderMail;
use RZP\Models\Base;
use RZP\Models\TrustedBadge;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card\IIN\Country;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Jobs\EsSync;
use RZP\Models\Pricing\Fee;
use RZP\Trace\Tracer;
use RZP\Models\Offer;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Payment\Processor\Notify;
use RZP\Models\Reminders;
use RZP\Models\Payment\Processor\FraudDetector;
use RZP\Models\Payment\Processor\UpiUnexpectedPaymentRefundHandler;
use RZP\Models\Transfer;
use RZP\Models\UpiMandate;
use RZP\Models\Transaction;
use RZP\Models\Admin\Org;
use RZP\Services\Doppler;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants;
use RZP\Models\Customer;
use RZP\Constants\MailTags;
use RZP\Models\Pos;
use RZP\Models\CardMandate;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Models\Settlement;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\ConnectionType;
use RZP\Models\Payment\Verify\Verify;
use RZP\Models\Locale\Core as Locale;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Partner\Service as PartnerService;
use RZP\Models\P2p\Preferences as P2pPreferences;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\CardMandate\CardMandateNotification;
use RZP\Models\Partner\Validator as PartnerValidator;
use RZP\Models\Payment\Verify\Result as VerifyResult;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payment\Processor\Constants as PaymentConstants;
use RZP\Exception\BadRequestException;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Payment\PaymentMeta;
use RZP\Models\Payment\Fraud;
use RZP\Constants\Shield as ShieldConstants;
use RZP\Services\Harvester\Constants as HarvesterConstants;
use RZP\Models\Batch\Processor\Nach\ErrorCodes\RegisterErrorCodes;
use RZP\Models\Invoice\Service as InvoiceService;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Payment\Constant as PaymentsConstants;
use RZP\Models\Invoice\Constants as InvoiceConstants;
use RZP\Models\Invoice\Type as InvoiceType;
use RZP\Models\GenericDocument\Service as DocumentService;
use RZP\Models\Payment\Processor\IntlBankTransfer;
use RZP\Services\UpiPayment\Constants as UpsConstants;
use RZP\Models\Workflow\Service\Builder as WorkflowBuilder;
use RZP\Models\Workflow\Service\Client as WorkflowServiceClient;
use RZP\Models\GenericDocument\Constants as GenericDocumentConstants;
use RZP\Models\Ledger\ReverseShadow as LedgerReverseShadow;
use Symfony\Component\HttpFoundation;
use RZP\Services\UfhService;


class Service extends Base\Service
{
    use FraudDetector;
    use UpiUnexpectedPaymentRefundHandler;

    protected $merchant;

    protected $core;

    protected $slack;

    protected $mutex;

    protected $redis;

    /**
     * Default rrn updation cache timeout duration in sec.
     * @var  integer
     */
    const RRN_TTL = 259200;

    const MUTEX_LOCK_TTL = 60; // in seconds

    const MUTEX_RETRY_COUNT = 10;

    const MUTEX_MIN_RETRY_DELAY = 1000; // in miliseconds

    const MUTEX_MAX_RETRY_DELAY = 2000;

    const GET_PAYMENTS_QUERY = "select p.id, a.rrn, p.created_at from hive.realtime_hudi_api.payments as p INNER JOIN hive.realtime_pgpayments_card_live.authorization AS a ON p.id = a.payment_id WHERE a.status in ('authorized', 'captured') AND p.method = 'card' and p.gateway = 'hdfc' and p.cps_route = 2 and p.created_at < %s and p.id > '%s' order by p.id asc limit %s";

    const PG_ROUTER_URL ="https://pg-router-int.razorpay.com";

    const TRANSACTION_ON_HOLD_WRITE_REMOVAL = 'transaction_on_hold_write_removal';

    public function __construct()
    {
        parent::__construct();

        $this->core = new Payment\Core;

        $this->slack = $this->app['slack'];

        $this->mutex = $this->app['api.mutex'];

        $this->redis = $this->app['redis']->connection('mutex_redis');
    }

    /**
     * Processes a payment.
     * @param array $input
     * @return array|mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function process(array $input)
    {
        return $this->getNewProcessor()->process($input);
    }

    /**
     * Processes a wallet payment
     *
     * @param array $input
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function processWallet(array $input)
    {
        // Just a hack to get around mobikwik normal flow
        $input['_']['source']   = 's2s';
        $input['method']        = 'wallet';

        if (Payment\Gateway::isPowerWallet($input['wallet']) === false)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED);
        }

        return $this->getNewProcessor()->process($input);
    }

    /**
     * Processes a upi payment
     *
     * @param array $input
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function processUpi(array $input)
    {
        $input['_']['source']   = 's2s';
        $input['method']        = 'upi';

        return $this->getNewProcessor()->process($input);
    }

    /**
     * Processes a nach initial payment
     *
     * @param array $input
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function processNachRegister(array $input)
    {
        (new Validator)->validateInput(__FUNCTION__, $input);

        $order = $this->repo->order->findByPublicIdAndMerchant($input[Entity::ORDER_ID], $this->merchant);

        $input[Entity::METHOD] = Method::NACH;

        $input[Entity::AUTH_TYPE] = AuthType::PHYSICAL;

        $input[Entity::CURRENCY] = Currency\Currency::INR;

        $input[Entity::AMOUNT] = 0;

        $input[Entity::RECURRING] = true;

        $input[Entity::NACH] = [
            Entity::SIGNED_FORM => array_pull($input, Entity::FILE),
        ];

        $paperMandate = (new SubscriptionRegistration\Core)->validateAndGetPaperMandateForNachOrder($order);

        $input[Entity::CONTACT] = $paperMandate->bankAccount->getBeneficiaryMobile();

        $input[Entity::EMAIL] = $paperMandate->bankAccount->getBeneficiaryEmail();

        $input[Entity::CUSTOMER_ID] = $paperMandate->customer->getPublicId();

        try
        {
            return $this->getNewProcessor()->process($input);
        }
        catch (BadRequestException $ex)
        {
            $errorData = $ex->getData();

            $errorData[Entity::METHOD]   = Method::NACH;

            $errorData[Entity::ORDER_ID] = $order->getPublicId();

            $ex->setData($errorData);

            throw $ex;
        }
    }

    public function processAndReturnFees(array & $input)
    {
        return $this->getNewProcessor()->processAndReturnFees($input);
    }

    public function processAndReturnCardlessConvenienceFees(array & $input)
    {
        return $this->getNewProcessor()->processAndReturnCardlessConvenienceFees($input);
    }

    /**
     * Resend OTP
     *
     * @param string  $id
     * @param array   $input
     *
     * @return array
     */
    public function otpResend($id, $input)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant(
            $id, $this->merchant);

        if ((empty($payment) === false) and
            ($payment->isExternal() === true))
        {
            return $this->app['pg_router']->otpResendPrivate($id, $input);
        }
        return $this->getNewProcessor()->otpResend($id, $input);
    }

    /*
     * Topup a wallet
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function topup($id, $input)
    {
        $data = $this->getNewProcessor()->topup($id, $input);

        return $data;
    }

    /**
     * Refunds a payment
     *
     * @param string $id
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function refund($id, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_REFUND_REQUEST,
            [
                'payment_id' => $id,
                'input'      => $input
            ]);

        // commented for now, will be enabled during further ramp-up
        // $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        // based on experiment, refund request will be routed to Scrooge

        // if ($this->getNewProcessor()->isRefundRequestV1_1($this->merchant->getId(),$payment))
        // {
        //     // Route refund creation to scrooge
        //     return (new Payment\Refund\Service())->scroogeRefundCreate($id, $input);
        // }

        // if ($this->merchant->isFeatureEnabled(Features::COLLECTX_ENABLED))
        // {
        //     throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REFUNDS_UNAVAILABLE_FOR_COLLECTX);
        // }

        $refund = $this->getNewProcessor()->refundPaymentViaMerchant($id, $input);

        $this->getNewProcessor()->pushRefundMessageForDCCEInvoiceCreation($refund, $id);

        return $refund->toArrayPublic();
    }

    /**
     * Refunds a payment
     *
     * @param string  $id
     * @param array   $input
     *
     * @return Payment\Entity
     */
    public function refundAuthorized($id, array $input)
    {
        //
        // Since this is in admin auth, we won't
        // have any merchant to check this with.
        //
        $payment = $this->repo->payment->findOrFailByPublicIdWithParams($id, []);

        // based on experiment, refund request will be routed to Scrooge
        $refund = $this->getNewProcessor($payment->merchant)->refundAuthorizedPayment($payment, $input);

        return $refund->toArrayPublic();
    }

    public function refundAuthorizedInBulk(array $input)
    {
        $paymentIds = $input['payment_ids'];

        $count = count($paymentIds);

        $success = $failure = 0;

        $failurePayments = $successRefunds = [];

        foreach ($paymentIds as $paymentId)
        {
            Entity::verifyIdAndSilentlyStripSign($paymentId);

            $payment = $this->repo->payment->findOrFailPublic($paymentId);

            $merchant = $payment->merchant;

            try
            {
                // based on experiment, refund request will be routed to Scrooge
                $refund = $this->getNewProcessor($merchant)->refundAuthorizedPayment($payment);

                $success++;

                $successRefunds[] = $refund->getId();
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failure++;

                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'count'            => $count,
            'success'          => $success,
            'failure'          => $failure,
            'failure_payments' => $failurePayments,
            'success_refunds'  => $successRefunds,
        ];

        $this->trace->info(
            TraceCode::REFUND_AUTHORIZE_BULK,
            $data
        );

        return $data;
    }

    public function verify($id, $isBarricade = false)
    {
        $payment = $this->repo->payment->findOrFailByPublicIdWithParams($id, []);

        if ($payment->isExternal() === true)
        {
            $response = $this->app['pg_router']->paymentVerify($id, true);

            return $this->buildVerifyResponse($response);
        }

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        return $this->getNewProcessor($merchant)->verify($payment, null, $isBarricade);
    }

    private function buildVerifyResponse($response)
    {
        $data = [
            "payment" => $response
        ];

        if (isset($response['merchant_id']) === true)
        {
            $merchant = $this->repo->merchant->findOrFail($response['merchant_id']);

            $data[EntityConstants::MERCHANT] = $merchant;

            $merchantDetail = $merchant->merchantDetail;

            $data[EntityConstants::MERCHANT][EntityConstants::MERCHANT_DETAIL] = isset($merchantDetail) === true ? $merchantDetail->toArrayPublic() : [];

            $data[EntityConstants::MERCHANT][Merchant\Entity::FEATURES] = $merchant->getEnabledFeatures();

            $data[EntityConstants::MERCHANT][EntityConstants::METHODS] = $this->repo->methods->getMethodsForMerchant($merchant);
        }

        if ((isset($response['card_id']) === true) and
            (isset($response['merchant_id']) === true))
        {
            $card = $this->repo->card->findByIdAndMerchantId($response['card_id'], $response['merchant_id']);

            $data['card'] = $card;
        }

        if ((isset($response['refund_id']) === true) and
            (isset($response['merchant_id']) === true))
        {
            $refund = $this->repo->refund->findByIdAndMerchantId($response['refund_id'], $response['merchant_id']);

            $data['refund'] = $refund->toArrayGateway();
        }

        if ((isset($response['token_id']) === true) and
            (isset($response['merchant_id']) === true))
        {
            $token = $this->repo->token->findByIdAndMerchantId($response['token_id'], $response['merchant_id']);

            $data['token'] = $token;
        }

        return $data;
    }

    public function cancel($id, $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_CANCELLED,
            [
                'payment_id' => $id,
                'input'      => $input
            ]);

        $data = $this->getNewProcessor()->cancel($id, $input);

        return $data;
    }

    public function redirectCallback($id)
    {
        return $this->getNewProcessor()->redirectCallback($id);
    }

    public function redirectTo3ds($id)
    {
        return $this->getNewProcessor()->redirectTo3ds($id);
    }

    public function otpGenerate($id, $input)
    {
        $traceData = [
            'payment_id' => $id,
            'input'    => $input,
        ];

        $payment = null;

        try
        {
            $this->trace->info(TraceCode::PAYMENT_OTP_GENERATE_REQUEST, $traceData);

            (new Payment\Validator)->validateInput('otp_generate', $input);

            $payment = $this->repo->payment->findByPublicId($id);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_OTP_GENERATE_INITIATED, $payment, null, [], $traceData);

            $response = $this->getNewProcessor()->processOtpGenerate($payment, $id);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_OTP_GENERATE_PROCESSED, $payment, null, [], $traceData);

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PAYMENT_OTP_GENERATE_FAILURE,
                $traceData
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_OTP_GENERATE_PROCESSED, $payment, $e, [], $traceData);

            throw $e;
        }

    }

    protected function isOtpResendAction($input)
    {
        if (empty($input[PaymentConstants::ACTION]) === true)
        {
            return false;
        }

        if ($input[PaymentConstants::ACTION] === PaymentConstants::ACTION_OTP_RESEND)
        {
            return true;
        }

        return false;
    }

    public function getAuthenticateUrl($id)
    {
        return [
            'url' => $this->app['api.route']->getUrl('payment_redirect_to_authenticate_get', ['id' => $id])
        ];
    }

    public function redirectToAuthorizeFromMandateHQ($id, $hash, $input)
    {
        $processor = $this->getNewProcessor();

        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $approved = $input[CardMandate\Constants::MANDATE_HQ_APPROVED] ?? CardMandate\Constants::MANDATE_HQ_TRUE;

        (new CardMandate\Core)->updateCardMandateAfterMandateAction($payment, $hash, $approved);

        if ($approved === CardMandate\Constants::MANDATE_HQ_FALSE)
        {
            return $processor->failMandateCanceledCardAutoRecurringPayment($payment);
        }

        $paymentAnalytics = $payment->analytics;

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        return $processor->processRedirectToAuthorize($payment, $payment->getId());
    }

    public function handleMandateHQCallback($input)
    {
        return (new CardMandate\Core)->processMandateHQCallBack($input);
    }

    public function handleSihubWebhook($input)
    {
        $isProduction = ($this->app->environment('production') === true);
        // set mode for unexpected payments
        $mode = $isProduction ? Mode::LIVE : Mode::TEST;
        $this->app['basicauth']->setModeAndDbConnection($mode);

        return (new CardMandate\Core)->processSihubWebhook($input);
    }

    public function redirectToAuthorize($id, $input=[])
    {
        $attrs = [
            'payment_id'       =>  $id,
            'task_id'          =>  $this->app['request']->getTaskId()
        ];

        $response = Tracer::inSpan(['name' => 'payment.redirect.authorize', 'attributes' => $attrs],
            function() use ($id, $input){
                return $this->coreRedirectToAuthorize($id, $input);
            });

        return $response;
    }

    public function coreRedirectToAuthorize($id, $input=[])
    {
        $traceData = ['track_id' => $id];

        $data = $this->checkMultipleRedirectionAndReturnResponse($id);

        if ($data != null)
        {
            return $data;
        }

        $this->trace->info(TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_REQUEST, $traceData);

        $payment = null;

        $merchant = null;

        try
        {
            list($merchant, $payment) = $this->setRequiredDetailsGetMerchantAndPaymentId($id);

            $this->markFirstRequestIfApplicable($merchant, $id);

            $response = $this->getResponseDataFromCache($payment);

            (new Payment\Analytics\Service())->updatePaymentAnalyticsData($payment);

            $payment->setMetadataKey('payment_analytics', $payment->analytics);

            if ($response !== null)
            {
                return $response;
            }

            // cant do this before as mode is set in above, and mode is required to ensure data goes to write place
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_REDIRECT_INITIATED, $payment, null, [], $traceData);

            $response = $this->getNewProcessor($merchant)->processRedirectToAuthorize($payment, $id, $input);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_REDIRECT_PROCESSED, $payment, null, [], $traceData);

            $this->cachePaysecureResponseDataIfApplicable($payment, $response);

            $this->cacheResponseIfApplicable($id, $merchant, $response);

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_FAILURE,
                $traceData
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_REDIRECT_PROCESSED, $payment, $e, [], $traceData);

            throw $e;
        }
    }

    /**
     * @throws BadRequestException
     */
    public function postCreatePosPayment($input)
    {
        (new Validator)->validatePosPaymentCreation($input);

        $merchant =$this->auth->getMerchant();

        $this->trace->info(TraceCode::POS_PAYMENT_CREATE_REQUEST,
            [
                'Payment Array' => $this->removeSensitiveData($input),
                'Merchant Id'   => $merchant->getId()
            ]
        );

        $dataResponse  = $this->getNewProcessor($merchant)->process($input);

        $payment = $this->repo->payment->findByPublicId($dataResponse['razorpay_payment_id']);

        if ($input['method'] === 'card')
        {
            try{
                //Register reminder to capture payment after 24 hours
                $this->setCapturePosPaymentReminder($payment);
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::WARNING,
                    TraceCode::BAD_REQUEST_REMINDER_CREATION_FAILURE
                );
            }
        }

        return $payment->toArrayPublic();

    }

    public function removeSensitiveData($input):array
    {
        if(isset($input['card']) === true)
        {
            unset($input['card']);
        }

        return $input;
    }

    /**
     * @param $payment
     * @return mixed
     */
    protected function setCapturePosPaymentReminder($payment)
    {
        $merchantId = Merchant\Account::SHARED_ACCOUNT;

        $reminderData = [
            'remind_at' => $payment['created_at'] + (24*60*60)
        ];

        $namespace  = Reminders\ReminderProcessor::CAPTURE_POS_PAYMENT;

        $paymentId  = $payment->GetId();

        $url = sprintf('reminders/send/%s/payment/%s/%s', $this->mode, $namespace, $paymentId);

        $request = [
            'namespace'     => $namespace,
            'entity_id'     => $paymentId,
            'entity_type'   => $payment->getEntityName(),
            'reminder_data' => $reminderData,
            'callback_url'  => $url,
        ];

        $response = $this->app['reminders']->createReminder($request, $merchantId);

        $reminderId = array_get($response, 'id');

        return $reminderId;
    }

    public function chargeToken($input)
    {
        $id = empty($input[Payment\Entity::TOKEN]) ? null : $input[Payment\Entity::TOKEN];

        if (empty($id) === true)
        {
            throw new Exception\BadRequestValidationFailureException('token is required');
        }

        $this->trace->info(TraceCode::PAYMENT_CHARGE_TOKEN_REQUEST,
            [
                'token_id'    => $id,
            ]);

        $token = $this->repo->token->findByPublicIdAndMerchant($id, $this->merchant);

        $customer = $token->customer;

        $additionalInput = [
            Payment\Entity::RECURRING   => '1',
            Payment\Entity::CUSTOMER_ID => $customer->getPublicId(),
            Payment\Entity::CONTACT     => $customer->getContact(),
            Payment\Entity::EMAIL       => $customer->getEmail(),
        ];

        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            $orderInput = [
                Order\Entity::AMOUNT          => $input[Payment\Entity::AMOUNT],
                Order\Entity::CURRENCY        => $input[Payment\Entity::CURRENCY],
                Order\Entity::PAYMENT_CAPTURE => true,
                Order\Entity::NOTES           => $input[Payment\Entity::NOTES] ?? [],
            ];

            $this->trace->info(
                TraceCode::PAYMENT_CHARGE_TOKEN_CREATE_ORDER,
                [
                    'token_id'     => $id,
                    'orderInput'   => $orderInput,
                ]
            );

            $orderCore = new Order\Core();

            $input[Payment\Entity::ORDER_ID] = $orderCore->create($orderInput, $this->merchant)->getPublicId();
        }

        $input = array_merge($additionalInput, $input);

        $this->trace->info(
            TraceCode::PAYMENT_CHARGE_TOKEN,
            [
                'token_id'     => $id,
                'paymentInput' => $input,
            ]
        );

        $paymentProcessor = $this->getNewProcessor($this->merchant);

        // todo: currently express service is passing key_id in url. remove this once it is fixed in express
        if (empty($input['key_id']) === false)
        {
            unset($input['key_id']);
        }

        return $paymentProcessor->process($input);
    }

    public function authorizePayment($input, $id)
    {
       $this->trace->info(TraceCode::PAYMENT_AUTHORIZATION_REQUEST,
        [
            'payment_id' => $id
        ]);

       Payment\Entity::verifyIdAndSilentlyStripSign($id);

       try
       {
            $payment = $this->repo->payment->findOrFail($id);

           (new Payment\Validator)->validateInput('authorize_payment', $input);

           $processor = $this->getNewProcessor($payment->merchant);

           if (($payment->isAuthenticated() === true) and
               ($payment->isCard() === true) and
               (empty($input[Payment\Entity::RECURRING_TOKEN]) === false))
           {
               try
               {
                   $this->handleInitialCardRecurringAuthorizeIfApplicable($payment, $input);
               }
               catch (BadRequestException $e)
               {
                   $processor->failMandateCreationFailedCardInitialRecurringPayment($payment, $e);

                   throw $e;
               }
           }

            return $processor->processPaymentAuthorize($payment, $input);
       }
       catch (\Throwable $e)
       {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PAYMENT_AUTHORIZATION_REQUEST_FAILURE
            );

            throw $e;
       }
    }

    protected function handleInitialCardRecurringAuthorizeIfApplicable(Payment\Entity $payment, $input)
    {
        $payment->setRecurring(true);

        $payment->setRecurringType(Payment\RecurringType::INITIAL);

        if ($payment->card->isRecurringSupportedOnTokenIINIfApplicable(true, $payment->hasSubscription()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED);
        }

        $tokenCreateInput = [
            Token\Entity::METHOD      => $payment->getMethod(),
            Token\Entity::CARD_ID     => $payment->getCardId(),
        ];

        if (empty($input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT]) === false)
        {
            $tokenCreateInput[Token\Entity::MAX_AMOUNT] = $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT];
        }

        if (empty($input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::EXPIRE_BY]) === false)
        {
            $tokenCreateInput[Token\Entity::EXPIRED_AT] = $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::EXPIRE_BY];
        }

        $customer = $payment->customer;

        if (empty($customer) === true)
        {
            $customer = (new Customer\Core)->createLocalCustomer([
                Customer\Entity::CONTACT       => $payment->getContact(),
                Customer\Entity::EMAIL         => $payment->getEmail(),
            ], $payment->merchant, false);
        }

        $newToken = (new Token\Core)->Create($customer, $tokenCreateInput, null, false);

        $payment->localToken()->associate($newToken);

        if (empty($input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::NOTES]) === false)
        {
            $payment->appendNotes($input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::NOTES]);
        }

        $payment->saveOrFail();

        $cardMandateCreateInput = $input[Payment\Entity::RECURRING_TOKEN];

        $cardMandateCreateInput[CardMandate\Entity::SKIP_SUMMARY_PAGE] = true;

        $cardMandate = (new CardMandate\Core)->create($payment, $cardMandateCreateInput);

        $newToken->cardMandate()->associate($cardMandate->getId());

        $newToken->saveOrFail();
    }

    protected function checkMultipleRedirectionAndReturnResponse(string $trackId)
    {
        $trackIdKey = Payment\Entity::getTrackIdRequestKey($trackId);

        $data = $this->app['cache']->get($trackIdKey);

        if (empty($data) === true)
        {
            return null;
        }

        $responseKey = Payment\Entity::getTrackIdResponseKey($trackId);


        $this->trace->info(TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_REQUEST_ONHOLD,
        [
            'track_id' => $trackId
        ]);

        // we wait for 5 seconds, every second, we check cache whether the first request got processed or not. if it's processed we return the response.
        $delay = 1;
        do
        {
            // usleep works on microsec. delay is in millisec so multiply by 1000
            usleep(1000000);

            $data = $this->app['cache']->get($responseKey);

            if (empty($data) === false)
            {
                $this->setRequiredDetailsGetMerchantAndPaymentId($trackId);
                return $data;
            }

            $data = $this->app['cache']->get($trackIdKey);

            if (empty($data) === true)
            {
                return null;
            }

            $delay = $delay + 1;

        }
        while ($delay <= 5);

        return null;
    }

    protected function getResponseDataFromCache($payment)
    {
        if (!($payment->getGateway() === Gateway::PAYSECURE or $payment->getMethod() === Gateway::PAYLATER or $payment->getMethod() === Gateway::CARDLESS_EMI) )
        {
            return;
        }

        $key = $payment->getPaymentResponseCacheKey();

        $payload = $this->app['cache']->get($key);

        if (empty($payload) === true)
        {
            return;
        }

        $data = Crypt::decrypt($payload);

        return  $data;
    }

    protected function cacheResponseIfApplicable($trackId, $merchant, $data)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::REDIRECTION_ONHOLD) === false)
        {
            return;
        }

        $responseKey = Payment\Entity::getTrackIdResponseKey($trackId);

        $this->app['cache']->put($responseKey, $data, Processor\Processor::REDIRECT_CACHE_RESPONSE_TTL);
    }

    protected function cachePaysecureResponseDataIfApplicable($payment, $data)
    {
        if ($payment->getGateway() !== Gateway::PAYSECURE)
        {
            return;
        }

        $response = $this->app->razorx->getTreatment($payment->getMerchantId(), 'redirect_cache_response', Mode::LIVE);

        if (strtolower($response) !== 'on')
        {
            return;
        }
        $this->cachePaymentResponse($payment, $data);
    }

    public function cachePaylaterCardlessEmiResponseIfApplicable($payment, $data)
    {
        if ($data == null)
        {
            return;
        }

        if (!($payment->getMethod() === Gateway::PAYLATER or $payment->getMethod() === Gateway::CARDLESS_EMI))
        {
            return;
        }

        $route = $this->app['api.route'];

        if ($route->getCurrentRouteName() === "payment_create_private_json")
        {
            if ((empty($data['method']) === false and ($data['method'] === 'paylater') or ($data['method'] === 'cardless_emi'))
                and (empty($data['type']) === false and $data['type'] === 'respawn'))
            {
                $this->cachePaymentResponse($payment, $data);
            }
        }
        else
        {
            unset($data['payment_authenticate_url']);
        }

    }

    protected function cachePaymentResponse($payment, $data)
    {
        $key = $payment->getPaymentResponseCacheKey();

        $payload = Crypt::encrypt($data);

        $this->app['cache']->put($key, $payload, Processor\Processor::REDIRECT_CACHE_RESPONSE_TTL);
    }

    // This is hack for s2s json support for paylater abd cardless emi payments
    protected function isCardlessEmiPaylaterPaymentCachedForRedirect($payment, $mode)
    {
        if ($payment->getWallet() === Gateway::GETSIMPL and $mode === Mode::LIVE)
        {
            return true;
        }
        else if($payment->getWallet() === CardlessEmi::EARLYSALARY or
                ($payment->getMethod() === Method::CARDLESS_EMI and (in_array($payment->getWallet(), CardlessEmi::$fullNameForSupportedBanks, true))))
        {
            return true;
        }
        return false;

    }
    //
    // Since, redirectToAuthorize is a direct auth we don't have any
    // merchant/auth/mode. We set merchant in basic auth and return
    // merchant and paymentId
    //
    protected function setRequiredDetailsGetMerchantAndPaymentId($id)
    {
        $mode = $this->repo->determineLiveOrTestModeForEntity($id, 'payment');

        if ($mode === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $payment = $this->core->retrievePaymentById($id);

        $merchant = $payment->merchant;

        $this->app['basicauth']->setMerchant($merchant);

        if ($this->isCardlessEmiPaylaterPaymentCachedForRedirect($payment, $mode) === true)
        {
            if (($payment->isCreated() === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
                );
            }
            return [$merchant, $payment];
        }
        $key = Payment\Entity::getRedirectToAuthorizeTrackIdKey($id);

        $encryptedText = $this->app['cache']->get($key);

        if (($encryptedText === null) and
            ($payment->isCreated() == true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        $payload = Crypt::decrypt($encryptedText);

        if ((empty($payload) === true) and
            ($payment->isCreated() == true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        if (empty($payload) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_REQUEST_PAYLOAD,
                $payload
            );

            $this->app['basicauth']->setAuthDetailsUsingPublicKey($payload['public_key']);

            if (empty($payload['account_id']) === false)
            {
                $this->app['basicauth']->authCreds->creds['account_id'] = $payload['account_id'];
            }

            if (empty($payload['oauth_client_id']) === false)
            {
                $this->app['basicauth']->setOAuthClientId($payload['oauth_client_id']);
            }
        }
        return [$merchant, $payment];
    }

    public function markFirstRequestIfApplicable($merchant, $trackId)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::REDIRECTION_ONHOLD) === false)
        {
            return;
        }

        $trackIdKey = Payment\Entity::getTrackIdRequestKey($trackId);

        // this code is for marking that we have recieved the first request.
        // TTL is for 10 seconds, if we receive subsequent request before this key
        // expires we hold that thread and wait for the first request response.
        // hold that thread to wait for 5 sec and check the first request response.
         $this->trace->info(
            TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_REQUEST_FIRST_REQUEST,
            [
                "track_id" => $trackId,
            ]
         );

        // Outdated: put method multiplies $ttl with 60 hence, 0.17 * 60 = 9.6 sec
        // Update: put expects in seconds
        $this->app['cache']->put($trackIdKey, $trackIdKey, 9.6);
    }
    public function forceAuthorizeFailed($id, $input)
    {
        $payment = $this->core->retrieveById($id);

        if ($payment->getCpsRoute() === Payment\Entity::REARCH_CARD_PAYMENT_SERVICE) {
            $payment->enableCardPaymentService();
        }

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $data = $this->getNewProcessor($merchant)
                     ->forceAuthorizeFailedPayment($payment, $input);

        return $data;
    }

    /**
     * forceAuthorizePayment is payment method-generic function for force authorizing payment
     * @param $id payment_id
     * @param $input
     * @return array|void
     * @throws BadRequestException
     */
    public function forceAuthorizePayment($id, $input)
    {
        (new Payment\Validator)->validateInput('force_authorize_payment', $input);

        $method = $input['payment']['method'];

        switch($method)
        {
            case Payment\Method::UPI:
                return $this->authorizeFailedUpiPayment($input);

                //ToDo: Cards team to handle their case here

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'incorrect method provided');
        }

    }

    public function authorizeLockTimeOutPayments($paymentIds)
    {
        $paymentIds = explode(',', $paymentIds);

        $failurePayments = [];

        $successes = $failures = 0;

        $total = count($paymentIds);

        foreach ($paymentIds as $paymentId)
        {
            $payment = $this->repo->payment->findOrFail($paymentId);

            $merchant = $payment->merchant;

            try
            {
                $this->getNewProcessor($merchant)->forceAuthorizeFailedPayment($payment);
                $successes++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);
                $failures++;
                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'success_count'     => $successes,
            'failure_count'     => $failures,
            'failure_payments'  => $failurePayments,
            'total'             => $total,
        ];

        $this->trace->info(
            TraceCode::FORCE_AUTHORIZE_TIMEOUT_PAYMENTS_RESPONSE,
            $data);

        return $data;
    }

    public function authorizeFailed($id)
    {
        $payment = $this->core->retrieveById($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);
        $data = $this->getNewProcessor($merchant)->authorizeFailedPayment($payment);

        return $data;
    }

    public function fixAttemptedOrders($input)
    {
        $paymentIds = $input['payment_ids'];
        $success = 0;
        $failed  = 0;
        $failedPaymentIds = [];

        foreach ($paymentIds as $paymentId)
        {
            try
            {
                $payment = $this->repo->payment->findByPublicId($paymentId);

                $order = $payment->order;

                $merchant = $payment->merchant;

                if(($payment->isCaptured() === true) and ($order->isPaid() === false))
                {
                    $success = $this->repo->transaction(
                    function() use ($payment, $order, $merchant, $success)
                    {
                        $this->getNewProcessor($merchant)->fixAttemptedOrder($payment, $order);

                        $success++;

                        return $success;
                    });
                }
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException($ex);
                $failedPaymentIds[] = $paymentId;
                $failed++;
                continue;
            }
        }

        return [
            'success'          => $success,
            'failed'           => $failed,
            'failedPaymentIds' => $failedPaymentIds,
        ];

    }

    public function fixAuthorizeAt($input)
    {
        $paymentIds = $input['payment_ids'];

        $failurePayments = [];

        $successes = $failures = 0;

        $total = count($paymentIds);

        foreach ($paymentIds as $paymentId)
        {
            $payment = $this->core->retrieveById($paymentId);

            if (($payment->isFailed() === false) or
                ($payment->hasBeenCaptured() === true))
            {
                $failures++;
                $failurePayments[] = $paymentId;
                continue;
            }

            $this->trace->info(TraceCode::PAYMENT_AUTHORIZED_NULL, [
                'payment_id' => $paymentId,
                'old_authorized_at' => $payment->getAuthorizeTimestamp()
            ]);

            $payment->setAuthorizedAtNull();

            $this->repo->saveOrFail($payment);

            $successes++;
        }

        $data = [
            'success_count'     => $successes,
            'failure_count'     => $failures,
            'failure_payments'  => $failurePayments,
            'total'             => $total,
        ];

        return $data;
    }

    public function retrieveRefundByIdAndPaymentId($paymentId, $rfndId)
    {
        $this->trace->info(TraceCode::API_REFUNDS_FETCH_REQUEST, [
            'route_name'  => $this->app['api.route']->getCurrentRouteName(),
            'extra_trace' => $this->app['basicauth']->getAuthType(),
        ]);

        return $this->app['scrooge']->refundsFetchByIdAndPayment($paymentId, $rfndId);
    }

    public function getCardForPayment($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->hasCard() === false)
        {
            throw new Exception\BadRequestException(Error\ErrorCode::BAD_REQUEST_NOT_CARD_PAYMENT);
        }

        $card = $this->repo->card->fetchForPayment($payment);

        $card->setDummyCardName();

        return $card->toArrayPublic();
    }

    public function getCardMetadataForPayment($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->hasCard() === false)
        {
            throw new Exception\BadRequestException(Error\ErrorCode::BAD_REQUEST_NOT_CARD_PAYMENT);
        }

        $card = $this->repo->card->fetchForPayment($payment);

        return $card->getMetadata();
    }

    public function retrieveRefundsForPayment($id, array $input = [])
    {
        $this->trace->info(TraceCode::API_REFUNDS_FETCH_REQUEST, [
            'route_name'  => $this->app['api.route']->getCurrentRouteName(),
            'extra_trace' => $this->app['basicauth']->getAuthType(),
            'input'       => $input,
        ]);

        return $this->app['scrooge']->refundsFetchByPayment($id, $input);
    }

    public function callApiForBackFilling(array $input)
    {
        $timeNow1 = Carbon::now()->getTimestamp();

        $month = $input['month'];
        $limit = $input['limit'];
        $endTimeStampOfMonth = $input['end_time_stamp'];

        $key = 'backfill_rrn_api_' . $month;

        $paymentId = $this->app['cache']->get($key);

        if($paymentId == null || empty($paymentId)){
            $paymentId = $input['payment_id'];
        }

        $this->trace->info(TraceCode::RRN_BACKFILLING_INPUT,[
            'key ' => $key,
            'payment_id ' => $paymentId,
            'month ' => $month,
            'end_time_stamp' => $endTimeStampOfMonth,
            'limit ' => $limit
        ]);

        $query = sprintf(self::GET_PAYMENTS_QUERY, $endTimeStampOfMonth, $paymentId, $limit);
        $data = $this->app['datalake.presto']->getDataFromDataLake($query);

        $this->trace->info(TraceCode::RRN_PAYMENT_DATA,[
            'data ' => $data
        ]);

        $lastPayment = "";
        $updatedCount = 0;

        try {
            foreach ($data as $rows) {
                $currentPayment = $rows['id'];
                $rrn = $rows['rrn'];

                $currentPaymentEntity = $this->repo->payment->findOrFail($currentPayment);
                $currentPaymentEntity->setReference16($rrn);
                $this->repo->saveOrFail($currentPaymentEntity);

                $this->trace->info(TraceCode::CURRENT_PAYMENT_UPDATION_STATUS, [
                    'Payment id' => $currentPayment,
                    'Updated Payment entity' => $currentPaymentEntity
                ]);

                $updatedCount += 1;

                $lastPayment = $currentPayment;
            }

            $this->app['cache']->put($key, $lastPayment, self::RRN_TTL);

            $timeNow2 = Carbon::now()->getTimestamp();

            $this->trace->info(TraceCode::TIME_TAKEN_TO_FETCH_DATA_FROM_DB, [
                'start time ' => $timeNow1,
                'end time ' => $timeNow2,
                'time taken ' => $timeNow2 - $timeNow1,
                'updated count' => $updatedCount,
                'total rows ' => sizeof($data)
            ]);

        } catch(Throwable $t)
        {
            $this->trace->traceException(
                $t,
                null,
                TraceCode::LAST_PAYMENT_UPDATED_BEFORE_EXCEPTION,
                [
                    'last payment updated before exception' => $lastPayment,
                ]
            );
        }

    }

    public function fetchTransactionByPaymentId($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->hasBeenCaptured() === false)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED
            );
        }

        $transaction = $this->repo->transaction->findByEntityId($payment->getId(), $this->merchant, true);

        return $transaction->toArrayPublic();
    }

    /**
     * Captures a payment
     *
     * @param string $id
     * @param array  $input
     *
     * @return Payment\Entity
     */
    public function capture($id, $input)
    {
        /** @var Entity $payment */
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->isExternal() === true)
        {
            if ($payment->hasOrder() === true)
            {
                $order = $payment->order;

                # have to send conv_fee & gst to PG-router to validate order with payment amount
                if($order->getFeeConfigId() !== null and
                    $payment->getConvenienceFee() !== null)
                {
                    $input['convenience_fee'] = $payment->getConvenienceFee();
                    $input['convenience_fee_gst'] = $payment->getConvenienceFeeGst();
                }
            }

            $resource = $this->getNewProcessor($payment->merchant)->getCallbackMutexResource($payment);

            $payment = $this->mutex->acquireAndRelease($resource,
                function() use ($id, $input) {

                    $paymentMap = $this->app['pg_router']->paymentCapture($id, $input, true);

                    $payment = (new Payment\Entity)->forceFill($paymentMap);

                    if ((isset($paymentMap['card_id']) === true) and
                        (isset($paymentMap['merchant_id']) === true)) {
                        $card = $this->repo->card->findByIdAndMerchantId($paymentMap['card_id'], $paymentMap['merchant_id']);

                        $payment->card()->associate($card);
                    }

                    return $payment;
                },
                20,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }
        else
        {
            $payment = $this->getNewProcessor()->capture($payment, $input);
        }

        return $payment->toArrayPublic();
    }

    /**
     * Captures payments in bulk
     *
     * @param  array  $input
     *
     * @return array
     */
    public function captureInBulk(array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_BULK_REQUEST,
            $input
        );

        (new Payment\Validator)->validateInput('bulk_capture', $input);

        $payments = $input['payment_ids'];

        $success = $failure = 0;

        $failurePayments = [];

        foreach ($payments as $paymentId)
        {
            try
            {
                $payment = $this->repo->payment->findByPublicId($paymentId);

                $merchant = $payment->merchant;

                $amount = $payment->getAmount();

                // For bulk capture, we hit capture with the total payment amount(Payment amount+fee).We are subtracting
                // fee here as we add fee while capturing the payments. This will ensure that the correct amount is sent
                // for capture.
                if ($payment->isFeeBearerCustomer() === true)
                {
                    $amount = $amount - $payment->getFee();
                }

                $captureInput = [
                    Payment\Entity::AMOUNT   => $amount,
                    Payment\Entity::CURRENCY => $payment->getCurrency()
                ];

                if ($payment->isExternal() === true)
                {
                    if ($payment->hasOrder() === true)
                    {
                        $order = $payment->order;

                        if (isset($order) === true)
                        {
                            $input[Payment\Entity::ORDER] = $order;
                        }
                    }

                    $paymentMap = $this->app['pg_router']->paymentCapture($paymentId, $captureInput, true);

                    $payment = (new Payment\Entity)->forceFill($paymentMap);

                    if ((isset($paymentMap['card_id']) === true) and
                        (isset($paymentMap['merchant_id']) === true))
                    {
                        $card = $this->repo->card->findByIdAndMerchantId($paymentMap['card_id'], $paymentMap['merchant_id']);

                        $payment->card()->associate($card);
                    }
                }
                else
                {
                    $payment = $this->getNewProcessor($merchant)->capture($payment, $captureInput);
                }

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::INFO,
                    TraceCode::PAYMENT_CAPTURE_BULK_FAILURE,
                    [
                        'payment_id' => $paymentId
                    ]);

                $failure++;

                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'count'            => count($payments),
            'success'          => $success,
            'failure'          => $failure,
            'failure_payments' => $failurePayments,
        ];

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_BULK_RESPONSE,
            $data
        );

        return $data;
    }

    public function createTransferFromBatch(string $paymentId, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_REQUEST_VIA_BATCH,
            [
                'payment_id'    => $paymentId,
                'input'         => $input,
            ]
        );

        $this->parseAttributesForPaymentTransferBatch($input);

        //
        // Modifying the input as per the existing payment transfer API.
        //
        $input = [
            'transfers' => array($input),
        ];

        try
        {
            $transfers = $this->getNewProcessor()->transfer($paymentId, $input);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PAYMENT_TRANSFER_VIA_BATCH_FAILED,
                [
                    'payment_id' => $paymentId,
                    'input'      => $input,
                ]
            );

            (new Transfer\Metric)->pushCreateFailedMetrics($ex);

            throw $ex;
        }

        (new Transfer\Metric)->pushCreateSuccessMetrics(current($input['transfers']));

        //
        // Exactly 1 transfer is created in this flow.
        //
        $transfer = $transfers->pop();

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_VIA_BATCH_SUCCESSFUL,
            [
                'transfer_id' => $transfer->getPublicId(),
            ]
        );

        return $transfer->toArrayPublic();
    }

    /**
     * Transfers a payment
     * /payment/:id/transfer
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function transfer(string $id, array $input) : array
    {
        if ($this->auth->isAppAuth() && $this->auth->isRouteApp())
        {
            $payment = $this->repo->payment->findByPublicId($id);

            $this->merchant = $payment->merchant;

            $this->auth->setMerchant($this->merchant);
        }

        try
        {
            $transfers = $this->getNewProcessor()->transfer($id, $input);

            return $transfers->toArrayPublic();
        }
        catch (\Exception $e)
        {
            (new Transfer\Metric)->pushCreateFailedMetrics($e);

            throw $e;
        }
    }

    public function fixTransferAmountTransferred(string $id, array $input) : array
    {
        try
        {
            $payment = $this->repo->payment->findByPublicId($id);

            $transferPayments = $this->repo->transfer_payment->getTransferPayment($payment->getId());

            if  ($transferPayments->count() > 0)
            {
                $transferPayment = $transferPayments[0];
            }

            else
            {
                $this->trace->info(
                    TraceCode::TRANSFER_PAYMENT_FIX_FAILURE,
                    [
                        'payment_id' => $id,
                        'Reason'     => 'TRANSFER_PAYMENT_NOT_FOUND'
                    ]
                );

                return [
                    'Success' => false,
                    'Reason'  => 'TRANSFER_PAYMENT_NOT_FOUND'
                ];
            }

            return $this->repo->transaction(function () use ($payment, $transferPayment, $transferPayments)
            {
                $response = $this->updatePaymentAmountTransferredFix($payment, $transferPayment);

                if  ($transferPayments->count() > 1)
                {
                    $transferPayment2 = $transferPayments[1];

                    $this->repo->transfer_payment->deleteTransferPayment($transferPayment2->getId());

                }

                return $response;

            });


        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::TRANSFER_PAYMENT_FIX_FAILURE,
                [
                    'payment_id' => $id,
                ]
            );

            throw $e;
        }
    }

    public function updatePaymentAmountTransferredFix(Payment\Entity $payment, Transfer\Payment\Entity $transferPayment)
    {
        $amountTransferredDetailsFromPayment = $this->repo->transfer->fetchAmountTransferred($payment->getId());

        if (empty($amountTransferredDetailsFromPayment) === true or empty($amountTransferredDetailsFromPayment['amount_transferred']) === true)
        {
            $amountTransferredFromPayment = 0;
        }
        else
        {
            $amountTransferredFromPayment = $amountTransferredDetailsFromPayment['amount_transferred'];
        }

        $amountTransferredDetailsFromOrder = $this->repo->transfer->fetchAmountTransferred($payment->getApiOrderId());

        if (empty($amountTransferredDetailsFromOrder) === true or empty($amountTransferredDetailsFromOrder['amount_transferred']) === true)
        {
            $amountTransferredFromOrder = 0;
        }
        else
        {
            $amountTransferredFromOrder = $amountTransferredDetailsFromOrder['amount_transferred'];
        }

        $amountTransferred = $amountTransferredFromPayment + $amountTransferredFromOrder;

        $this->trace->info(
            TraceCode::TRANSFER_AMOUNT_TRANSFERRED,
            [
                'payment_id'              => $payment->getId(),
                'calculated_amount'       => $amountTransferred,
            ]
        );

        $this->repo->transfer_payment->lockForUpdateAndReload($transferPayment);

        $transferPayment->transferAmountFix($amountTransferred);

        $this->repo->saveOrFail($transferPayment);

        $this->repo->payment->lockForUpdateAndReload($payment);

        $payment->transferAmountFix(0);

        $this->repo->saveOrFail($payment);

        $this->trace->info(
            TraceCode::TRANSFER_PAYMENT_FIX_SUCCESS,
            [
                'payment_id' => $payment->getId(),
            ]
        );

        return [
            'Success' => true,
        ];
    }

    /**
     * Get Transfers for a payment_id
     *
     * @param  string $id   Payment ID
     * @return array
     */
    public function getTransfers(string $id) : array
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $transfers = Tracer::inSpan(['name' => 'transfer.fetch_by_payment'], function() use ($id)
        {
            return (new Transfer\Core())->getForPayment($id);
        });

        $payment = $this->repo
                        ->payment
                        ->findByIdAndMerchant($id, $this->merchant);

        if ($payment->hasOrder() === true)
        {
            $orderId = $payment->getApiOrderId();

            $transfersFromOrder = Tracer::inSpan(['name' => 'transfer.fetch_by_order'], function() use ($orderId)
            {
                return (new Transfer\Core())->getForOrder($orderId);
            });

            foreach ($transfersFromOrder as $transferFromOrder)
            {
                $transfers->push($transferFromOrder);
            }
        }

        return ((new Transfer\Service())->setPartnerDetailsForTransfers($transfers))->toArrayPublic();
    }

    /**
     * Create a payout from a payment
     *
     * @param string    $id
     * @param array     $input
     *
     * @return array
     */
    public function payout(string $id, array $input) : array
    {
        $payout = $this->getNewProcessor()->payout($id, $input);

        return $payout->toArrayPublic();
    }

    /**
     * If a payment has been captured on gateway but not on the api side,
     * we create a transaction for the payment.
     *
     * @param $paymentId
     * @return array
     */
    public function verifyCapture($paymentId)
    {
        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchantId = $payment->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $data = $this->getNewProcessor($merchant)->verifyCapture($payment);

        $this->trace->info(
            TraceCode::VERIFY_CAPTURE_RESPONSE,
            [
                'payment_id'    => $paymentId,
                'data'          => $data
            ]
        );

        return $data;
    }

    public function postPendingGatewayCapture($input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_BULK_REQUEST,
            $input
        );

        $limit = $input['limit'] ?? 100;

        if (isset($input['payment_ids']) === true)
        {
            (new Payment\Validator)->validateInput('bulk_capture', $input);

            $paymentIds = $input['payment_ids'];

            Entity::verifyIdAndStripSignMultiple($paymentIds);

            $payments = [];

            foreach ($paymentIds as $paymentId)
            {
                try
                {
                    $payments[] = $this->repo->payment->findOrFail($paymentId);
                }
                catch (\Throwable $e) {}
            }
        }
        else
        {
            $from = Carbon::today(Timezone::IST)->subDays(8)->getTimestamp();
            $to = Carbon::today(Timezone::IST)->subDays(3)->getTimestamp();

            $payments = $this->repo->payment->fetchPendingCapturePaymentsBetweenTimestamps($from, $to, $limit);
        }

        $total = sizeof($payments);
        $success = 0;

        foreach ($payments as $payment)
        {
            $result = $this->getNewProcessor($payment->merchant)->manualGatewayCapture($payment);

            $success += intval($result);
        }

        return [
            'total'   => $total,
            'success' => $success
        ];
    }

    public function manualGatewayCapture($paymentId)
    {
        Entity::verifyIdAndSilentlyStripSign($paymentId);

        $payment = $this->repo->payment->findOrFail($paymentId);

        $result = $this->getNewProcessor($payment->merchant)->manualGatewayCapture($payment);

        return [
            'payment_id' => $payment->getId(),
            'result'     => $result
        ];
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies
     * 'auth' is successful.
     *
     * @param  array  $input Contains fields provided
     *                       by bank
     *
     * @return array
     */
    public function callback($id, $hash, array $input)
    {
        return $this->getNewProcessor()->callback($id, $hash, $input);
    }

    public function s2sCallback($id, $input)
    {
        $payment = $this->repo->payment->findByPublicId($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        // TODO: Hack to prevent S2S callback processing for TPV Merchants.
        // All TPV Merchant transactions will be made through BILLDESK.
        // Issue is currently on BILLDESK end. Remove once the fix has been
        // made from the BILLDESK side.
        if (($merchant->isTPVRequired() === true) and
            ($payment->isGateway(Payment\Gateway::BILLDESK) === true))
        {
            return ['success' => true];
        }

        return $this->getNewProcessor($merchant)->s2sCallback($payment, $input);
    }

    public function mandateUpdateCallback($id, $input)
    {
        $payment = $this->repo->payment->findByPublicId($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        return $this->getNewProcessor($merchant)->mandateUpdateCallback($payment, $input);
    }

    public function mandatePauseCallback($id, $input, $gateway)
    {
        $upiMandate = $this->repo->upi_mandate->findOrFail($id);

        (new UpiMandate\Core())->validateUpiMandateForPause($upiMandate);

        $this->merchant = $this->repo->merchant->findOrFail($upiMandate['merchant_id']);

        return $this->getNewProcessor($this->merchant)->mandatePause($input, $upiMandate, $gateway);
    }

    public function mandateResumeCallback($id, $input, $gateway)
    {
        $upiMandate = $this->repo->upi_mandate->findOrFail($id);

        (new UpiMandate\Core())->validateUpiMandateForResume($upiMandate);

        $this->merchant = $this->repo->merchant->findOrFail($upiMandate['merchant_id']);

        return $this->getNewProcessor($this->merchant)->mandateResume($input, $upiMandate);
    }

    public function mandateCancelCallback($id, $input, $gateway)
    {
        $upiMandate = $this->repo->upi_mandate->findOrFail($id);

        (new UpiMandate\Core())->validateUpiMandateForCancelCallback($upiMandate);

        $this->merchant = $this->repo->merchant->findOrFail($upiMandate['merchant_id']);

        return $this->getNewProcessor($this->merchant)->mandateCancelViaCallback($input, $upiMandate, $gateway);
    }

    public function unexpectedCallback(array $input, string $referenceId, string $gateway, $isCallback = false)
    {
        $isProduction = ($this->app->environment('production') === true);

        // set mode for unexpected payments
        $mode = $isProduction ? Mode::LIVE : Mode::TEST;

        $this->app['basicauth']->setModeAndDbConnection($mode);

        // use demo accounts for unexpected payments
        $merchantId = $isProduction ? Merchant\Account::DEMO_PAGE_ACCOUNT : Merchant\Account::DEMO_ACCOUNT;

        // If the input is already parsed to payment and terminal, we do not need parse again
        if ((is_array($input['payment'] ?? null) === true) and
            (is_array($input['terminal'] ?? null) === true))
        {
            $data = $input;
        }
        else
        {
            $gatewayClass = $this->app['gateway']->gateway($gateway);

            $data = $gatewayClass->getParsedDataFromUnexpectedCallback($input);
        }

        $traceInput = $input;

        unset($traceInput['account_number'], $traceInput['payer_va'], $traceInput['phone_number']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, [
            'input'         => $traceInput,
            'data'          => $data,
            'gateway'       => $gateway,
            'reference_id'  => $referenceId,
            'unexpected'    => 1,
        ]);

        $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $data['terminal']);

        if ($terminal->isExpected() === true)
        {
            $merchantId = $terminal->getMerchantId();
        }

        if ($terminal->isDirectSettlement() === true)
        {
            $merchantId = $terminal->getMerchantId();
        }
        // marking unexpected payments as offline if terminal has type offline
        if ($terminal->isOffline() === true)
        {
            $data['payment']['reference13'] = 'in_person';
        }

        if (empty($input['meta']['art_reason']) === false and $input['meta']['art_reason'] === "amount_mismatch"){
            $merchantId = $isProduction ? Merchant\Account::DEMO_PAGE_ACCOUNT : Merchant\Account::DEMO_ACCOUNT;
        }

        if ((empty($input['meta']['version']) === false) and
            ($input['meta']['version'] === 'api_v2') and
            empty($input['upi']['gateway_merchant_id']) === true)
        {
            $input['upi']['gateway_merchant_id'] = $terminal->getGatewayMerchantId();
        }

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        return $this->getNewProcessor($merchant)
                    ->authorizePush($input, $referenceId, $data, $terminal, $isCallback);
    }

    public function getSplitzResponse(string $merchantId, string $experimentName)
    {
        try
        {
            $experimentId = $this->app['config']->get($experimentName);

            $response = $this->app['splitzService']->evaluateRequest([
                'id'            => $merchantId,
                'experiment_id' => $experimentId,
            ]);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'merchant_id'   => $merchantId,
                'experiment_id' => $experimentId,
                'result'        => $response
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id'   => $merchantId,
                'experiment_id' => $this->app['config']->get($experimentName) ?? null
            ]);
        }

        return $response['response']['variant']['name'] ?? '';
    }

    public function isSplitzExperimentEnable(string $merchantId, string $experimentName, string $checkVariant): bool
    {
        $variant = $this->getSplitzResponse($merchantId, $experimentName);

        return $variant === $checkVariant;
    }

    private function isExpEnableEsSearchOnCreatedAtAndThenOnScore(string $merchantId, array $input): bool
    {
        $expEnable = $this->isSplitzExperimentEnable($merchantId, Constant::ELASTIC_SEARCH_ON_CREATED_AT_THEN_ON_SCORE_KEY, Constant::VARIANT_ENABLE);

        return ($expEnable === true) && (isset($input[Entity::NOTES]) === true);
    }

    private function fetchCustomTxnEnabledAndSubmerchants(): array
    {
        $response['custom_txn_enabled'] = false;

        $response['submerchants'] = [];

        if ($this->merchant !== null &&
            $this->merchant->isFeatureEnabled(Feature\Constants::CUSTOM_TXN_TAB_VIEW) === true) {
            $response['custom_txn_enabled'] = true;

            $response['submerchants'] =$this->repo->merchant_access_map->fetchSubMerchantIDsLinkedOnlyToAPartner($this->merchant->getId());

            $response['submerchants'][] = $this->merchant->getId();
        }

        return $response;
    }

    public function fetchMultipleInternal(array $input): array
    {
        $this->trace->info(TraceCode::PAYMENTS_BULK_FETCH, [
            'filters'     => $input,
        ]);

        $this->modifyInputForVATransaction($input);

        $payments = $this->repo
            ->payment
            ->fetchPaymentWithForceIndex($input);

        return $payments->toArrayAdmin();
    }

    public function fetchMultiple(array $input)
    {
        $merchantId = $this->merchant->getId();

        $this->addInputTrace($input);

        $this->modifyInputForVATransaction($input);

        $isExpEnable = $this->isExpEnableEsSearchOnCreatedAtAndThenOnScore($merchantId, $input);

        $customTxnEnabled = $this->fetchCustomTxnEnabledAndSubmerchants();

        if($customTxnEnabled['custom_txn_enabled'] === true)
        {
            $payments = $this->repo
                ->payment
                ->setExperimentForESearchOnCreatedAtFirst($isExpEnable)
                ->setCustomTxnViewAndSubMerchants($customTxnEnabled)
                ->fetchPaymentForSubmerchantsWithForceIndex($input, $merchantId);

            // Get payment supporting documents for opgsp import flow on dashboard.
            if($this->auth->isProxyAuth() === true and
                ($this->merchant->isOpgspImportEnabled() || $this->merchant->isJpmcImportFlowEnabled()))
            {
                return $this->fetchPaymentDocumentsThroughInvoice($payments, $merchantId);
            }

            return $payments->toArrayPublic();
        }

        $payments = $this->repo
                        ->payment
                        ->setExperimentForESearchOnCreatedAtFirst($isExpEnable)
                        ->fetchPaymentWithForceIndex($input, $this->merchant);

        // Get payment supporting documents for opgsp import flow on dashboard.
        if($this->auth->isProxyAuth() === true and
            ($this->merchant->isOpgspImportEnabled() || $this->merchant->isJpmcImportFlowEnabled()))
        {
           return $this->fetchPaymentDocumentsThroughInvoice($payments, $merchantId);
        }

        return $payments->toArrayPublic();
    }

    public function fetchPaymentNotesKeys(array $input)
    {
        $merchantId = $this->merchant->getId();

        if ($this->merchant->isFeatureEnabled(Features::CUSTOM_TXN_TAB_VIEW) === false) {
            throw new  Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $this->modifyInputForNotesKeys($input, $merchantId);

        return $this->repo
            ->payment
            ->fetchNotesKeys($input);
    }

    protected function fetchPaymentDocumentsThroughInvoice($payments, $merchantId)
    {
        $additionalDocumentType = '';
        try
        {
            $paymentIds = $this->getPaymentIds($payments);

            $paymentDocuments = (new InvoiceService())->findByPaymentIds($paymentIds, $merchantId);

            $paymentDocumentTypeMap = [];
            foreach($paymentDocuments as $document)
            {
                if(isset($paymentDocumentTypeMap[$document[InvoiceEntity::ENTITY_ID]]))
                {
//                    $documentTypeMap = [];
//                    $documentTypeMap[$document[InvoiceEntity::TYPE]] =  $document[InvoiceEntity::REF_NUM];
//                    $paymentDocumentTypeMap[$document[InvoiceEntity::ENTITY_ID]] =  $documentTypeMap;

                    $existingObject = $paymentDocumentTypeMap[$document[InvoiceEntity::ENTITY_ID]];
                    $existingObject[$document[InvoiceEntity::TYPE]]=  $document[InvoiceEntity::REF_NUM];
                    $paymentDocumentTypeMap[$document[InvoiceEntity::ENTITY_ID]] =$existingObject;
                }
                else
                {
                    $paymentDocumentTypeMap[$document[InvoiceEntity::ENTITY_ID]][$document[InvoiceEntity::TYPE]] =
                        $document[InvoiceEntity::REF_NUM];
                }
            }

            $paymentsResponse = $payments->toArrayPublic();

            switch (true)
            {
                case ($this->merchant->isOpgspImportEnabled() === true):
                    $invoiceType = InvoiceType::OPGSP_INVOICE;
                    $additionalDocumentType = InvoiceType::OPGSP_AWB;
                    break;
                case ($this->merchant->isJpmcImportFlowEnabled() === true):
                    $invoiceType = InvoiceType::JPMC_INVOICE;
                    break;
                default:
                    $invoiceType = '';
                    break;
            }

            foreach ($paymentsResponse['items'] as &$paymentResponse)
            {
                if(!isset($paymentDocumentTypeMap[substr($paymentResponse['id'],4)]))
            {
                continue;
            }
                $documentData = $paymentDocumentTypeMap[substr($paymentResponse['id'],4)];

                $paymentResponse[InvoiceType::OPGSP_INVOICE . '_doc'] =
                        $documentData[$invoiceType];


                if ($additionalDocumentType !== '' && isset($documentData[$additionalDocumentType])) {
                    $paymentResponse[InvoiceType::OPGSP_AWB  . '_doc'] = $documentData[$additionalDocumentType];
                }
            }


            return $paymentsResponse;
        } catch (\Exception $e)
        {
            $this->trace->error(TraceCode::FETCH_PAYMENT_DOCUMENT_FOR_PAYMENTS_FAILED, [
                'merchantId' => $merchantId,
            ]);

            throw $e;
        }
    }

    protected function getPaymentIds($payments)
    {
        $ids = [];

        foreach ($payments as $payment)
        {
            array_push($ids, $payment->getId());
        }

        return $ids;
    }

    private function modifyInputForVATransaction(&$input)
    {
        if (isset($input[Entity::VA_TRANSACTION_ID]) === true)
        {
            unset($input[Entity::VIRTUAL_ACCOUNT]);
        }
    }

    private function modifyInputForNotesKeys(&$input, $merchantId)
    {
        $input['submerchants'] = $this->repo->merchant_access_map->fetchSubMerchantIDsLinkedOnlyToAPartner($merchantId);

        $input['submerchants'][] = $merchantId;

        $input['to'] = $input['to'] ?? Carbon::now()->timestamp;

        $input['from'] = Carbon::createFromTimestamp($input['to'], Timezone::IST)->subhours(24)->getTimestamp();

        $input['count'] = 2000;
    }

    public function fetchStatusCount(array $input)
    {
        $merchantId = $this->merchant->getId();

        (new Payment\Validator)->validateInput('fetch_status_count', $input);

        $paymentsStatusCounts = $this->repo->payment->fetchPaymentsStatusCountBetweenTimestamps($input, $merchantId, false);

        $statusItem = [];

        //fill the status count to 0
        $statusList = Payment\Status::getStatusList();

        foreach ($statusList as $status)
        {
            $statusItem[$status] = 0;
        }

        foreach ($paymentsStatusCounts->toArrayWithItems()['items'] as $paymentsStatusCount)
        {
            $statusItem[$paymentsStatusCount->getStatus()] = $paymentsStatusCount->getAttribute("count");
        }

        $statusItem['count'] = array_sum($statusItem);

        $response['status'] = "true";

        $response['response'] = $statusItem;

        return $response;
    }

    public function fetchStatusCountInternal(array $input)
    {
        $merchantId = $input['merchant_id'];

        // Removing merchant_id from input so that the validations on $params will pass
        array_delete($merchantId, $input);

        $this->trace->info(TraceCode::PAYMENTS_MERCHANT_PAYMENTS_STATUS_COUNT_INTERNAL, [
            'filters'     => $input,
            'merchant_id' => $merchantId,
        ]);

        $paymentsStatusCounts = $this->repo->payment->fetchPaymentsStatusCountBetweenTimestamps($input, $merchantId, true);

        $statusItem = [];

        // Fill the status count to 0
        $statusList = Payment\Status::getStatusList();

        foreach ($statusList as $status)
        {
            $statusItem[$status] = 0;
        }

        foreach ($paymentsStatusCounts->toArrayWithItems()['items'] as $paymentsStatusCount)
        {
            $statusItem[$paymentsStatusCount->getStatus()] = $paymentsStatusCount->getAttribute('count');
        }

        $statusItem['count'] = array_sum($statusItem);

        $response['status'] = 'true';

        $response['response'] = $statusItem;

        return $response;
    }

    public function addAdditionalPaymentErrorDetails(&$entity, $internal_error_code)
    {
        $method = $entity['method'] ?? null;

        $error_details = [];

        if ($internal_error_code != null and $method != null)
        {
            [$error_details,] = $this->app['error_mapper']->getErrorMapping($internal_error_code, $method);
        }

        $entity[PaymentsConstants::ERROR_MONEY_IMPLICATION] = $error_details[PaymentsConstants::MONEY_IMPLICATION] ?? null;

        $entity[PaymentsConstants::ERROR_NEXT_STEP] =  $error_details[PaymentsConstants::NEXT_STEP] ?? null;

        $entity[PaymentsConstants::ERROR_MERCHANT_DESC] = $error_details[PaymentsConstants::MERCHANT_DESC] ?? null;

        $entity[PaymentsConstants::ERROR_PERCEPTION] =  $error_details[PaymentsConstants::PERCEPTION] ?? null;

        $entity[PaymentsConstants::ERROR_RZP_ACTIONABLE] =  $error_details[PaymentsConstants::RZP_ACTIONABLE] ?? null;
    }

    public function fetch(string $id, array $input = []): array
    {
        $id = Entity::stripSignWithoutValidation($id);

        $showSettlementHoldStatus = false;

        (new Payment\Validator())->validateExpandsForPaymentFetch($input, $showSettlementHoldStatus);

        $ledgerDualWrite = false;

        $txn = null;

        $merchant = $this->app['basicauth']->getMerchant();

        $isReverseShadowMerchant = empty($merchant) === false ? $merchant->isFeatureEnabled(Features::PG_LEDGER_REVERSE_SHADOW) : false;

        $requestId = empty($merchant) === false ? $merchant->getId() : $this->app['request']->getTaskId();

        $readExp = $this->getTransactionReadSplitzResponse($requestId) === 'enable';

        if($isReverseShadowMerchant === true and $readExp === true)
        {
            $ledgerDualWrite = true;

            if((empty($input) === false) and (isset($input[Base\Repository::EXPAND]) === true)
                and (in_array(Payment\Entity::TRANSACTION, $input[Base\Repository::EXPAND]) === true
                    or in_array('transaction.settlement', $input[Base\Repository::EXPAND]) === true))
            {
                array_delete(Payment\Entity::TRANSACTION, $input['expand']);

                $txn = $this->repo->transaction->findByEntityIdWithoutMerchantTidb($id);

                if (in_array('transaction.settlement', $input[Base\Repository::EXPAND]) === true
                    and empty($txn) === false)
                {
                    array_delete('transaction.settlement', $input['expand']);

                    if (empty($txn->getSettlementId()) === false)
                    {
                        $settlement = $this->repo->settlement->find($txn->getSettlementId());

                        if (empty($settlement) === false)
                        {
                            $txn['settlement'] = $settlement->toArrayPublic();
                        }
                        else
                        {
                            $txn['settlement'] = null;
                        }
                    }
                    else
                    {
                        $txn['settlement'] = null;
                    }
                }
            }
        }

        $payment = $this->repo->payment->findOrFailByPublicIdWithParams($id, $input);

        $paymentMerchantId = $payment->getMerchantId();

        if ($this->merchant->getId() !== $paymentMerchantId)
        {
            // if payment merchant is not same as context merchant, other valid possibility is that fetch is called by
            // the partner merchant of that submerchant
            $this->checkAuthMerchantAccessToEntity($paymentMerchantId);
        }

        $entity = $payment->toArrayPublicWithExpand();

        if ($this->app['basicauth']->isOptimiserDashboardRequest() === true &&
            $payment->getSettledBy() !== 'Razorpay')
        {
            $entity = $this->setSettlementDetailsForOptimizer($entity, $payment);
        }

        // Adding support to add additional params to payment entity for frontend
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            $this->addDashboardFlags($entity, $payment, $input);
        }

        if (isset($entity['card']) and
           ($payment->isRoutedThroughCardPayments() === true || ($payment->getCpsRoute() === Payment\Entity::REARCH_CARD_PAYMENT_SERVICE))
        )
          {
            $authenticationData = (new Payment\Service)->getAuthenticationEntity3ds2($payment->getPublicId());
            if ((isset($authenticationData['success']) === true) and ($authenticationData['success'] === true))
            {
                $this->addAuthenticationObject($entity, $authenticationData);
            }
          }
        if (isset($entity['card'])) {
            if (isset($authenticationData['cavv']) && $payment->card->network === Card\Network::$fullName[Card\Network::AMEX]){
                $authenticationData = (new Payment\Service)->getAuthenticationEntity3ds2($payment->getPublicId());
                $entity['acquirer_data']['authentication_reference_number'] = $authenticationData['cavv'];
            }
        }

        if (isset($entity['card']) && ($payment->card->isInternational() === false))
        {
            $entity['card']['name'] = "";
        }

        if(isset($entity['token']))
        {
           $this->updateTokenDetails($entity, $payment);
        }

        //For 3ds2 sdk we have to initiate callback during fetch payment call
        if(isset($entity['authentication']['authentication_channel']) &&
            $entity['authentication']['authentication_channel'] == "app" && $payment['status'] === 'created')
        {
            if($this->app['basicauth']->isPrivateAuth())
            {
                $secret = $this->app->config->get('app.key');
                $hash = hash_hmac('sha1', $payment->getPublicId(), $secret);
                if(isset($payment['cps_route']) && $payment['cps_route'] === 5)
                {
                    $this->app['pg_router']->paymentCallback($payment->getPublicId(), $hash, [], true);
                }
                else {
                    $this->callback($payment->getPublicId(), $hash, ["gateway" => $payment['gateway']]);
                }
            }
        }

        // Add additional error details for failed payments to show on merchant dashboard
        // Whitelisting admin dashboard as well to show the details for admin login as merchant
        if (in_array($this->app['basicauth']->getInternalApp(), ['merchant_dashboard', 'admin_dashboard']) === true)
        {
            $this->addAdditionalPaymentErrorDetails($entity, $payment->getInternalErrorCode());
        }

        if ($showSettlementHoldStatus === true)
        {
            $paymentTxn =  $ledgerDualWrite ? $this->repo->transaction->findByEntityIdWithoutMerchantTidb($id) : $payment->transaction;

            $entity[PaymentsConstants::SETTLEMENT_ONHOLD] = empty($paymentTxn) ? true : $paymentTxn->isOnHold();
        }

        if ($txn != null)
        {
            $entity['transaction'] = $txn->toArrayPublicWithExpand();
        }
        else if ($isReverseShadowMerchant === true and $readExp === true)
        {
            $entity['transaction'] = null;
        }

        return $entity;
    }

    public function getTransactionReadSplitzResponse($merchantId)
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.transaction_read_experiment'),
        ];
        $response = $this->app['splitzService']->evaluateRequest($properties);

        return $response['response']['variant']['name'] ?? '';
    }

    public function getPaymentTimeline(string $id, array $input = []): array
    {
        $id = Entity::stripSignWithoutValidation($id);

        $payment = $this->repo
                        ->payment
                        ->findOrFailByPublicIdWithParams($id, $input, ConnectionType::REPLICA);

        $paymentMerchantId = $payment->getMerchantId();

        if ($this->merchant->getId() !== $paymentMerchantId)
        {
            // if payment merchant is not same as context merchant, other valid possibility is that fetch is called by
            // the partner merchant of that submerchant
            $this->checkAuthMerchantAccessToEntity($paymentMerchantId);
        }

        $paymentTimeline = [
            Entity::CREATED_AT    => $payment->getCreatedAt(),
            Entity::AUTHORIZED_AT => $payment->getAuthorizeTimestamp(),
            Entity::CAPTURED_AT   => $payment->getCaptureTimestamp(),
        ];

        return $paymentTimeline;
    }

    public function fetchById(string $id, array $input = []): array
    {
        $id = Entity::stripSignWithoutValidation($id);

        $txn = null;

        $requestId = $this->app['request']->getTaskId();

        $readExp = $this->getTransactionReadSplitzResponse($requestId) === 'enable';

        if($readExp === true)
        {
            if((empty($input) === false) and (isset($input[Base\Repository::EXPAND]) === true)
                and (in_array(Payment\Entity::TRANSACTION, $input[Base\Repository::EXPAND]) === true
                    or in_array('transaction.settlement', $input[Base\Repository::EXPAND]) === true))
            {
                array_delete(Payment\Entity::TRANSACTION, $input['expand']);

                $txn = $this->repo->transaction->findByEntityIdWithoutMerchantTidb($id);

                if (in_array('transaction.settlement', $input[Base\Repository::EXPAND]) === true
                        and empty($txn) === false)
                {
                    array_delete('transaction.settlement', $input['expand']);

                    if (empty($txn->getSettlementId()) === false)
                    {
                        $settlement = $this->repo->settlement->find($txn->getSettlementId());

                        if (empty($settlement) === false)
                        {
                            $txn['settlement'] = $settlement->toArrayPublic();
                        }
                        else
                        {
                            $txn['settlement'] = null;
                        }
                    }
                    else
                    {
                        $txn['settlement'] = null;
                    }
                }
            }
        }

        $payment = $this->repo
                        ->payment
                        ->findOrFailByPublicIdWithParams($id, $input);

        $paymentMerchantId = $payment->getMerchantId();

        $entity = $payment->toArrayPublicWithExpand();

        $entity = array_merge($entity,[
            Entity::AUTHORIZED_AT => $payment->getAuthorizeTimestamp(),
            Entity::CAPTURED_AT => $payment->getCapturedAt(),
            Entity::MERCHANT_ID => $paymentMerchantId,
            Entity::TERMINAL_ID => $payment->getTerminalId(),
            Entity::UPDATED_AT => $payment->getUpdatedAt(),
            Entity::INTERNAL_ERROR_CODE => $payment->getInternalErrorCode(),
        ]);

        if ($entity['order_id'] != null)
        {
            $order = $this->repo
                ->order
                ->findByPublicId($entity['order_id']);

            $orderEntity = $order->toArrayPublic();

            $orderEntity = array_merge($orderEntity,[
                Order\Entity::ACCOUNT_NUMBER => $order->getAccountNumber(),
                Order\Entity::BANK => $order->getBank(),
            ]);

            $entity['order'] = $orderEntity;
        }

        if ($entity[Payment\Entity::EMI] != null && $entity[Payment\Entity::EMI]['issuer'] != null && $entity[Payment\Entity::EMI]['type'] != null)
        {
            $processingFeePlan = (new ProcessingFeePlan())->getProcessingFeePlan($entity[Payment\Entity::EMI]['issuer'], $entity[Payment\Entity::EMI]['type'],$entity[Payment\Entity::EMI]['duration'],$payment->getAmount());
            if (!empty($processingFeePlan))
            {
                $percentageFee = 0;
                if ($processingFeePlan[ProcessingFeePlan::TYPE] == ProcessingFeePlan::COMBINATION) {

                    $paymentAmount = $payment->getAmount();
                    $percentageFee = $processingFeePlan[ProcessingFeePlan::PERCENTAGE] * $paymentAmount / 100;

                }
                $entity[Payment\Entity::EMI][Payment\Entity::PROCESSING_FEE] = max($percentageFee, $processingFeePlan[ProcessingFeePlan::AMOUNT]);
            }

        }

        if ($txn != null)
        {
            $entity['transaction'] = $txn->toArrayPublicWithExpand();
        }
        else if ($readExp === true)
        {
            $entity['transaction'] = null;
        }

        return $entity;
    }

    public function setSettlementDetailsForOptimizer($paymentArray, $payment)
    {
        try {
            if ($payment->getTransactionId() !== null)
            {
                $fetchInput = [
                    'transaction_id' => $payment->getTransactionId(),
                    'merchant_id'    => $payment->getMerchantId(),
                ];

                $settlementResponse = app('settlements_merchant_dashboard')->getSettlementForTransaction($fetchInput);

                $settlement = $settlementResponse['settlement'];

                unset($paymentArray['transaction']['settlement_id']);
                unset($paymentArray['transaction']['settlement']);

                if ($settlement != null) {
                    $setl = new Settlement\Entity($settlement);

                    $setl->setPublicAttributeForOptimiser($settlement);

                    $paymentArray['transaction']['settlement'] = $setl->toArrayPublic();

                    $paymentArray['transaction']['settlement_id'] = $setl->getId();
                }
            }

        } catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::GET_SETTLEMENT_DETAILS_FOR_PAYMENT_FAILED,
                [
                    'transaction_id' => $payment->getTransactionId(),
                ]);
        } finally {
            return $paymentArray;
        }
    }

    protected function checkAuthMerchantAccessToEntity(string $entityMerchantId)
    {
        if($this->merchant->isPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null, null);
        }

        $partners = (new Merchant\Core())->fetchAffiliatedPartners($entityMerchantId);

        //check if the auth merchant is one of the affiliated partners of the sub-merchant.
        $applicablePartners = $partners->filter(function(Merchant\Entity $partner)
        {
            return ((($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true)) and ($partner->getId() === $this->merchant->getId()));
        });

        if ( $applicablePartners->isEmpty() === true )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null, null);
        }
    }

    protected function addDashboardFlags(array &$entity, $payment, array $input = [])
    {
        if (isset($input['dashboard_flag']) === true)
        {
            foreach ($input['dashboard_flag'] as $key)
            {
                $func = 'addDashboardFlag' . studly_case($key);

                if (method_exists($this, $func))
                {
                    $this->$func($entity, $payment);
                }
            }
        }
    }

    protected function addInputTrace(array $input)
    {
        $this->trace->info(TraceCode::PAYMENTS_BULK_FETCH, [
            'filters'     => $input,
            'merchant_id' => $this->merchant->getId(),
        ]);
    }

    protected function addDashboardFlagInstantRefundSupport(array &$entity, $payment)
    {
        $entity[RefundConstants::INSTANT_REFUND_SUPPORT] = $this->getNewProcessor($this->merchant)
                                                                ->isInstantRefundSupportedOnPayment($payment);
    }

    protected function addDashboardFlagRefundCreateData(array &$entity, $payment)
    {
        $this->getNewProcessor($this->merchant)->getRefundCreationDataForDashboard($payment, $entity);
    }

    protected function addDashboardFlagupiPayerName(array &$entity, $payment)
    {
        if (empty($this->merchant) === true)
        {
            return;
        }

        $experimentResult = $this->isDisplayPayerNameExperimentEnabled();

        if ($experimentResult === false)
        {
            return;
        }

        $featureResult = $this->merchant->org->isFeatureEnabled(Feature\Constants::DISPLAY_UPI_PAYER_NAME);

        if ($featureResult === false)
        {
            return;
        }

        $upi = $this->repo->upi->fetchByPaymentId($payment->id);

        if (isset($upi['name']) === true)
        {
            $entity['upi']['payer_name'] = $upi['name'];
        }
    }

    private function isDisplayPayerNameExperimentEnabled()
    {
        $properties = [
            'id'            => $this->merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.display_upi_payer_name_experiment_id'),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? 'control';

        if ($variant === 'display_upi_payer_name')
        {
            return true;
        }

        return false;
    }

    //qr_device_detail: Frontend needs to send this flag in the input inside dashboard_flag array
    protected function addDashboardFlagQrDeviceDetail(array &$entity, $payment)
    {
        if (($payment->getReceiverType() === "qr_code") && ($payment->getReference13() === QrConstants::PAYMENT_TYPE_IN_PERSON))
        {
            $qrCode = (new NonVirtualAccountQrCode\Repository())->fetchQrCodeForPaymentId($payment->getPublicId(), $this->merchant->getId());

            if ((empty($qrCode[0]) === false) && ($qrCode[0] instanceof NonVirtualAccountQrCode\Entity))
            {
                $entity['payee_vpa'] = $qrCode[0]->getQrVpa();
                $entity['device_detail'] = $qrCode[0]->getDeviceId();
            }
        }
    }

    public function redirectToDCCInfo($id)
    {
        $data = [];

        $dccInfo = [];

        list($merchant, $payment) = $this->setRequiredDetailsGetMerchantAndPaymentId($id);

        if ( $payment->isCreated() === false )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
            );
        }

        $iin = $payment->card->iinRelation ?? null;

        if($payment->getMethod() === Method::APP) {
            $payment['provider'] = $payment->getWallet();
        }

        $this->updateDccDataIfApplicable($payment, $iin, $merchant, $dccInfo);

        $this->updateCurrencyWrapperForAppsIfApplicable($payment,$merchant, $dccInfo);

        $route = $this->app['api.route'];

        $data['type'] = 'dcc';

        $this->updateCountriesForDcc($payment,$merchant,$data);

        $data['payment_id'] = $payment->getPublicId();
        $data['amount'] = number_format(($payment->getAmount() / 100), 2);
        $data['currency'] = $payment->getCurrency();
        $data['formatted_amount'] = $payment->getFormattedAmount();
        $data['gateway'] = '';

        $data['request'] = [
            'url'      => $route->getUrl('payment_update_and_redirect', ['id' => $payment->getId()]),
            'method'   => 'post',
            'content'  => []
        ];
        $data['version'] = 1;

        $data['theme_color'] = $merchant->getBrandColorElseDefault();
        $data['nobranding'] = $merchant->isFeatureEnabled(Feature\Constants::PAYMENT_NOBRANDING);
        $data['merchant_id'] = $merchant->getId();
        $data['merchant'] = $merchant->getBillingLabel();

        $data['dcc_info'] = $dccInfo;
        $data['payment_method'] = $payment->getMethod();


        //Check if Address is required for DCC transaction
        $library = $this->getLibraryFromPayment($payment);
        $data['avs_required'] = $this->isAddressRequired($library, $iin, $merchant);

        $data['address_name_required'] = $this->isAddressWithNameRequired($library,$payment,$merchant);

        $data['show_mor_tnc'] = $merchant->isShowMorTncEnabled();

        return $data;
    }

    public function redirectToAddressCollect($id)
    {
        $data = [];

        list($merchant, $payment) = $this->setRequiredDetailsGetMerchantAndPaymentId($id);

        if ( $payment->isCreated() === false )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
            );
        }

        $route = $this->app['api.route'];

        $data['type'] = 'address_collect';

        $data['payment_id'] = $payment->getPublicId();
        $data['amount'] = number_format(($payment->getAmount() / 100), 2);
        $data['currency'] = $payment->getCurrency();
        $data['formatted_amount'] = $payment->getFormattedAmount();
        $data['gateway'] = '';

        $data['request'] = [
            'url'      => $route->getUrl('payment_update_and_redirect', ['id' => $payment->getId()]),
            'method'   => 'post',
            'content'  => []
        ];
        $data['version'] = 1;

        $data['theme_color'] = $merchant->getBrandColorElseDefault();
        $data['nobranding'] = $merchant->isFeatureEnabled(Feature\Constants::PAYMENT_NOBRANDING);
        $data['merchant_id'] = $merchant->getId();
        $data['merchant'] = $merchant->getBillingLabel();
        $data['show_mor_tnc'] = $merchant->isShowMorTncEnabled();
        $data["countries"] = $this->getCountryCodes($payment);

        if ($merchant->isOpgspImportEnabled()){
            $data['address_name_required'] = true;
            $data['domestic_address'] = true;
        }

        return $data;
    }

    public function updateAndRedirectToAuthorize($id, $input)
    {
        return $this->redirectToAuthorize($id, $input);
    }

    // $input contains start_time_stamp in int64 and month in int
    public function callCpsForBackFilling($input)
    {
        $path = 'payments/rearch/backfill';

        $this->app['card.payments']->sendRequest('POST', $path, $input);
    }

    public function getPaymentFlows(array $input)
    {
        $data = [];
        try {
            $merchant = $this->merchant;

            Locale::setLocale($input, $merchant->getId());

            if (isset($input['token']) === true)
            {
                $tokenId = $input['token'];

                $token = $this->repo->token->findByPublicId($tokenId);

                if (($token !== null) and
                    ($token->hasCard() === true))
                {
                    $input['iin'] = $token->card->getIin();
                }
            }

            (new Payment\Validator)->validateInput('get_flows', $input);

            if (isset($input['iin']) === true)
            {
                $iinEntity = $this->repo->iin->find($input['iin']);
            }
            else
            {
                $iinEntity = null;
            }

            $data = $merchant->getPaymentFlows($iinEntity);

            $library = '';
            if(isset($input['_']) === true and isset($input['_']['source']) === true)
            {
                $library = $input['_']['source'];
            }
            elseif (isset($input['source']) === true)
            {
                $library = $input['source'];
            }

            $this->updateDccDataIfApplicable($input, $iinEntity, $merchant,$data);

            $data['avs_required'] = $this->isAddressRequired($library, $iinEntity, $merchant);

            $data['address_name_required'] = $this->isAddressWithNameRequired($library,$input, $merchant);

            $this->updateCurrencyWrapperIfApplicable($input, $merchant,$data);

            $this->updateCurrencyWrapperForAppsIfApplicable($input, $merchant, $data);

            $this->updateCurrencyWrapperForIntlBankTransfer($input, $merchant, $data);

            if (isset($input['order_id']) === true)
            {
                $order = $this->repo->order->findByPublicIdAndMerchant($input['order_id'], $this->merchant);

                if ($order->hasOffers() === true)
                {
                    $payment = $this->getDummyPayment($order, $iinEntity);

                    $applicableOffers = (new Offer\Core)->getApplicableOffersForPayment($order, $payment);

                    $data['offers'] = $applicableOffers;
                }
            }
            $this->trace->count(Metric::GET_PAYMENT_FLOWS_COUNT, [
                'success' => "true",
                'error'   => ""
            ]);
        } catch (\Exception $e) {
            $this->trace->count(Metric::GET_PAYMENT_FLOWS_COUNT, [
                'success' => "false",
                'error'   => $e->getCode()
            ]);
        }
        return $data;
    }

    public function updateDccDataIfApplicable($input, $iinEntity, $merchant, & $data)
    {
        // get dcc options for customer if dcc is enabled for merchant
        if (($merchant->isDCCEnabledInternationalMerchant() === false) or
            ($iinEntity === null))
        {
            return;
        }

        $this->merchant = $merchant;

        if ((isset($input['currency']) === true) and
            (isset($input['amount']) === true))
        {
            $amount = $input['amount'];
            $currency = $input['currency'];

            /* conditions for showing dcc:
                1. iin is dcc enabled and international(i.e country != IN)
                2. merchant's currency != card currency
            */
            if (($this->isDccEnabledIIN($iinEntity, $merchant) === true)
                and ($currency !== $iinEntity->getIinCurrency()))
            {
                $dccInfo = $this->getDCCInfo($merchant->getId(), $amount, $currency, $merchant->getDccMarkupPercentage(), Payment\Method::CARD);

                if(isset($dccInfo) === false)
                {
                    return;
                }

                $dccInfo['card_currency'] = $iinEntity->getIinCurrency() ?? Currency\Currency::USD;

                $dccInfo['show_markup'] = $this->showMarkupExperimentEnabled($merchant->getId(), $iinEntity->getNetwork()) === "v4";

                $dccInfo['dcc_version'] = $this->showMarkupExperimentEnabled($merchant->getId(), $iinEntity->getNetwork());

                $data = array_merge($data, $dccInfo);
            }
        }
    }

    public function updateCurrencyWrapperIfApplicable($input, $merchant, & $data)
    {
        $methods = $merchant->getMethods();

        if ((isset($input['wallet']) === false) or
            ($input['wallet'] !== Payment\Processor\Wallet::PAYPAL) or
            ($methods->isPaypalEnabled() === false))
        {
            return;
        }

        $terminal = $this->repo->terminal->getByMerchantIdAndGateway($merchant->getId(), Gateway::WALLET_PAYPAL);

        if ($terminal === null)
        {
            return;
        }

        $enabledCurrencyList = $terminal->getCurrency();

        // REMOVE DISABLED PAYPAL CURRENCIES
        $enabledCurrencyList = array_diff($enabledCurrencyList, Gateway\Constants::PAYPAL_DISABLED_CURRENCIES);

        if($input['flow'] === Method::PAYLATER)
        {
            $enabledCurrencyList = array_intersect(Gateway\Constants::PAYPAL_PAYLATER_SUPPORTED_CURRENCIES, $enabledCurrencyList);
        }

        if (empty($enabledCurrencyList) === true)
        {
            return;
        }

        if ((isset($input['currency']) === true) and
            (isset($input['amount']) === true))
        {
            $amount   = $input['amount'];
            $currency = $input['currency'];

                // markup of 5 is hardcoded at org-level
                $currencyInfo = $this->getDCCInfo($merchant->getId(), $amount, $currency, Merchant\Entity::DEFAULT_DCC_MARKUP_PERCENTAGE_FOR_PAYPAL, Payment\Method::WALLET);

                if(isset($currencyInfo) === false)
                {
                    return;
                }

                $currencyInfo['wallet_currency'] = Currency\Currency::USD;

                $currencyInfo['all_currencies'] = array_intersect_key(
                                                              $currencyInfo['all_currencies'],
                                                              array_flip($enabledCurrencyList));

                $data = array_merge($data, $currencyInfo);
        }
    }

    public function updateCurrencyWrapperForAppsIfApplicable($input, $merchant, & $data)
    {
        if ((isset($input['provider']) !== true) or
            (Gateway::isDCCRequiredApp($input['provider']) !== true) or
            ($merchant->isDCCEnabledInternationalMerchant() === false))
        {
            return;
        }

        $enabledCurrencyList = Gateway::getSupportedCurrenciesByApp($input['provider']);

        if (empty($enabledCurrencyList) === true)
        {
            return;
        }

        if ((isset($input['currency']) === true) and
            (isset($input['amount']) === true))
        {
            $amount   = $input['amount'];
            $currency = $input['currency'];

            $this->merchant = $merchant;

            // For Method APP Default DCC Markup is set as 6
            $currencyInfo = $this->getDCCInfo($merchant->getId(), $amount, $currency, $merchant->getDccMarkupPercentageForApps(), Payment\Method::APP);

            if(isset($currencyInfo) === false)
            {
                return;
            }

            // First Currency in Currency Map is set as default currency for an app.
            $currencyInfo['app_currency'] = $enabledCurrencyList[0];

            $currencyInfo['all_currencies'] = array_intersect_key(
                                                          $currencyInfo['all_currencies'],
                                                          array_flip($enabledCurrencyList));

            $data = array_merge($data, $currencyInfo);
        }
    }


    public function updateCurrencyWrapperForIntlBankTransfer($input, $merchant, & $data)
    {
        $mode = Gateway::CURRENCY_TO_MODE_MAPPING_FOR_INTL_BANK_TRANSFER[strtoupper($input['provider'])];

        if ((isset($mode) === false) or
            (IntlBankTransfer::isValidIntlBankTransferMode($mode) === false)) {
            return;
        }

        $enabledCurrencyList = Gateway::getSupportedCurrenciesForIntlBankTransferByMode($mode);

        if (empty($enabledCurrencyList) === true) {
            return;
        }

        if ((isset($input['currency']) === true) and
            (isset($input['amount']) === true)) {
            $amount = $input['amount'];
            $currency = $input['currency'];

            // For Method Intl Bank Transfer Default DCC Markup is set as 3
            $currencyInfo = $this->getDCCInfo($merchant->getId(), $amount, $currency, $merchant->getDccMarkupPercentageForIntlBankTransfer(), Payment\Method::INTL_BANK_TRANSFER);

            // First Currency in Currency Map is set as default currency for an app.
            $currencyInfo['provider_currency'] = in_array($input['currency'], $enabledCurrencyList, true) ? $input['currency'] : $enabledCurrencyList[0];

            $currencyInfo['all_currencies'] = array_intersect_key(
                $currencyInfo['all_currencies'],
                array_flip($enabledCurrencyList));

            $data = array_merge($data, $currencyInfo);
        }
    }

    private function updateCountriesForDcc($payment, $merchant, & $data) {

        if($merchant->isDCCEnabledInternationalMerchant() === false) {
            return;
        }
        $data["countries"] = $this->getCountryCodes($payment);
    }

    /**
     * This method returns the list of countries which are applicable for the payment method
     * by default it will return us/gb/ca for backward compatibility
     * @param $payment
     * @return array
     */
    private function getCountryCodes($payment): array {

        if(isset($payment) === false) {
            return [];
        }

        //for alternate payment methods
        if(Gateway::isDCCRequiredApp($payment->getWallet()) === true) {
            $countryCodes = (new Checkout)->getCountryCodesForAlternatePaymentMethods($payment->getWallet());
        }
        else
        {
            // default we are returning us,gb and ca
            /* For s2s we had harcoded only few countries before, making a change to fetch all countries
                and display in avs screen
                slack link: https://razorpay.slack.com/archives/C7WEGELHJ/p1690436848877569?thread_ts=1690375625.625109&cid=C7WEGELHJ */
            $countryCodes = Constants\Country::getAllCountries();
        }
        $countries = [];
        foreach ($countryCodes as $code) {
            $countries = array_merge($countries, [Constants\Country::getCountryDetailsFromCountryCode($code)]);
        }
        return $countries;
    }

    public function isDccEnabledIIN($iinEntity, $merchant): bool
    {
        if (($iinEntity !== null) and
            (IIN\IIN::isInternational($iinEntity->getCountry(), $merchant->getCountry()))  === true and
            (Card\Network::isDCCSupportedNetwork($iinEntity->getNetworkCode())) === true and
            $iinEntity->isDCCBlacklisted() === false)
        {
            return true;
        }

        return false;
    }

    public function getDCCInfo($merchantID, $baseAmount, $baseCurrency, $markupPercent, $method)
    {
        $dccInfo = [];

        $isZeroExponentCurrencySupported = $this->isZeroExponentCurrencySupported($merchantID);
        if($this->canFetchRatesThroughRearch() === true)
        {
            (new Currency\DCC\Service)->getConvertedCurrenciesFromRearch($merchantID, $baseCurrency, $baseAmount, $markupPercent, $method, $isZeroExponentCurrencySupported, $dccInfo);
        }
        else {
            try {
                $currencyRequestId = UniqueIdEntity::generateUniqueId();

                $dccInfo['all_currencies'] = (new Currency\DCC\Service)->getConvertedCurrencies($merchantID, $baseCurrency, $baseAmount, $currencyRequestId, $markupPercent, $method, $isZeroExponentCurrencySupported);

                $dccInfo['currency_request_id'] = $currencyRequestId;
                $this->trace->count(Metric::GET_DCC_INFO_COUNT, [
                    'success' => "true",
                    'error' => ""
                ]);
            } catch (\Exception $e) {
                $this->trace->count(Metric::GET_DCC_INFO_COUNT, [
                    'success' => "false",
                    'error' => $e->getCode()
                ]);
            }
        }

        return $dccInfo;
    }

    protected function isZeroExponentCurrencySupported($merchantID)
    {
        $mode = $this->mode ?? Mode::LIVE;
        try {
            // check the experiment
            $variant = $this->app['razorx']->getTreatment($merchantID,
                RazorxTreatment::ZERO_EXPONENT_CURRENCY_SUPPORT, $mode);
            if (strtolower($variant) === 'on')
            {
                return true;
            }
        } catch (\Exception $e) {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ZERO_EXPONENT_CURRENCY_SUPPORT_RAZORX_ERROR
            );
        }
        return false;
    }

    public function getPaymentFlowsPrivate(array $input)
    {
        (new Payment\Validator)->validateInput('post_flows', $input);

        $iin = null;

        if (isset($input['card_number']) === true)
        {
            $iin = substr($input['card_number'], 0, 6);

            unset($input['card_number']);
        }
        else if (isset($input['iin']) === true)
        {
            $iin = $input['iin'];
        }

        $input['iin'] = $iin;

        return $this->getPaymentFlows($input);
    }

    /**
     * We only return the payment status in case of an async
     * payment + status being either of created or authorized
     *
     * Note: This will only work within 15 minutes of the payment creation
     *
     * @param $id
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fetchStatus($id)
    {
        $data = $this->getNewProcessor()->getAsyncResponse($id);

        return $data;
    }

    public function addPaymentMetadata($id, $input)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $this->trace->info(TraceCode::PAYMENT_METADATA, $input);

        if (isset($input['otp_read']) === true)
        {
            $otpRead = $input['otp_read'];

            if ($payment->isMethodCardOrEmi() === false)
            {
                return [];
            }

            $card = $payment->card;

            $cardIin = $card->iin;
            $iin = $this->repo->iin->find($cardIin);

            if ($iin === null)
            {
                return [];
            }

            if ($otpRead === '1')
            {
                //adding bin service update for dual write
                if ((new IIN\Service())->shouldDualWrite() === true) {
                    (new IIN\Service())->updateBinServiceFlows($iin, "otp", "enable");
                }
                $iin->setOtpRead(true);
                $this->repo->saveOrFail($iin);
            }
            else if (($otpRead === '0') and
                     ($iin->getOtpRead() === true))
            {
                $this->trace->error(
                    TraceCode::PAYMENT_OTP_READ_FAILURE,
                    ['iin' => $cardIin, 'otp_read' => $otpRead]);
            }
        }

        return [];
    }

    public function update($id, $input)
    {
        $paymentId = Entity::verifyIdAndStripSign($id);

        $payment = $this->mutex->acquireAndRelease($paymentId,
            function() use ($paymentId, $input)
            {
                $payment = $this->repo->payment->findByIdAndMerchant($paymentId, $this->merchant);

                $payment->edit($input);

                $this->repo->saveOrFail($payment);

                return $payment;
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $payment->toArrayPublic();
    }

    /**
     * This method is triggered by a CRON job.
     *
     * Refunds all authorized (read extra) payments for paid orders.
     *
     * There is case when there will be authorized payment against paid order which
     * is mostly LATE_AUTHORIZED payments. Though those will get auto refunded
     * within 5 days (or set auto refund delay) but this CRON helps in refunding
     * those payments immediately.
     *
     * For optimization purposes we only pick payments in last 10 days. This picked
     * '10 days' is sufficient filter logically.
     */
    public function refundAuthorizedPaymentsOfPaidOrders()
    {
        $payments = $this->repo->payment->getAuthorizedPaymentsOfPaidOrderForRefund();

        $time = time();

        $failedPaymentIds = []; // Refund failed for these payments.

        foreach ($payments as $payment)
        {
            $this->repo->reload($payment);
            //Add a check to double check in case of TIDB lag
            if ($payment->getStatus() !== Status::AUTHORIZED) {
                $this->trace->info(TraceCode::PAYMENT_NOT_IN_AUTHORIZED_STATE, [
                    "payment_id" => $payment->getId(),
                    "status" => $payment->getStatus()
                ]);
                continue;
            }

            $paymentId = $payment->getId();
            $orderId   = $payment->getApiOrderId();

            $merchant  = $payment->merchant;

            $tracePayload = [
                'payment_id' => $paymentId,
                'order_id'   => $orderId,
            ];

            try
            {
                // based on experiment, refund request will be routed to Scrooge
                $this->getNewProcessor($merchant)->refundAuthorizedPayment($payment);

                $this->trace->info(TraceCode::ORDER_REFUNDED, $tracePayload);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_AUTO_REFUND_FAILURE,
                    $tracePayload);

                $failedPaymentIds[] = $paymentId;
            }
        }

        $time = time() - $time;

        $summary = [
            'count'      => $payments->count(),
            'total_time' => $time . ' secs',
            'failed_ids' => $failedPaymentIds,
        ];

        $this->trace->info(TraceCode::ORDERS_MULTIPLE_AUTHORIZED_REFUNDS, $summary);

        return $summary;
    }

    protected function isRefundRequiredForPayment($payment)
    {
        /**
         * Take a lock, in case it is already updated by another thread
         * and determine should we refund the payments now.
         */
        $response = $this->mutex->acquireAndRelease($payment->getId(), function () use ($payment)
        {
            /**
             * @var $payment Payment\Entity
             */

            $payment->reload();

            if ($payment->isAuthorized() === false)
            {
                return false;
            }

            return true;
        });

        return $response;
    }

    public function refundOldAuthorizedPayments($input = [])
    {
        $this->increaseAllowedSystemLimits();

        $this->trace->info(
            TraceCode::PAYMENT_AUTO_REFUND_CRON_REQUEST,
            [
                'input'         => $input,
            ]);

        // Using current time for fetching payments as refund_at is set properly.
        $ts = Carbon::now()->getTimestamp();

        if (isset($input['offset']) === true)
        {
            $ts -= $input['offset'];
        }

        $limit = 1000;

        if (isset($input['limit']) === true)
        {
            $limit = $input['limit'];
        }

        if (isset($input['refund_source']) and $input['refund_source'] == 'ups')
        {
            // Fetch all the authorized UPS payments whose refund_at is on or before current time.
            $payments = $this->repo->payment->getAuthorizedUpiServicePaymentsToBeRefundedUsingRefundAt($ts, $limit);
        }
        else
        {
            // Fetch all the authorized API payments whose refund_at is on or before current time.
            $payments = $this->repo->payment->getAuthorizedPaymentsToBeRefundedUsingRefundAt($ts, $limit);
        }


        // Re fetch by payment id to reload entity if fetched from warm storage
        $reloadedPayments = New Base\PublicCollection();

        foreach ($payments as $payment)
        {
            try
            {
                $reloadedPayment = $this->repo->payment->findOrFail($payment->getId());

                $reloadedPayments->push($reloadedPayment);
            }
            catch (Throwable $exception)
            {
                $this->trace->traceException($exception, Trace::INFO, TraceCode::PAYMENT_FETCH_EXCEPTION);
            }
        }

        $payments = $reloadedPayments;

        // Counts for determining the unsetting refund_at work and rejections
        $initialCount = $payments->count();
        $updatedRefundAt = 0;

        /**
         * Rejecting all the disputed payments
         * as the query only depends on refund_at column
         */

        $payments = $payments->reject(function ($payment) use ($input, &$updatedRefundAt)
        {
            /**
             * @var $payment Payment\Entity
             */
            if ($payment->isDisputed() === true)
            {
                return true;
            }

            if (isset($input['block_order_mismatch']) === true){

                $paymentHasOrder = $payment->hasOrder() && !empty($payment->getAttribute(Payment\Entity::ORDER_ID));

                $orderMismatch =  $input['block_order_mismatch'];

                $this->trace->info(
                    TraceCode::PAYMENT_AUTO_REFUND_CRON_DEBUG,
                    [
                        'payment_id'             => $payment->getId(),
                        'cron_request'           => $orderMismatch,
                        'payment_has_Order'      => $paymentHasOrder,
                    ]);

                if ($orderMismatch === true and $paymentHasOrder === true)
                {
                    $order = $payment->order;

                    $this->trace->info(
                        TraceCode::PAYMENT_AUTO_REFUND_CRON_DEBUG_STATUS_MISMATCH,
                        [
                            'payment_id'                => $payment->getId(),
                            'order_status'              => $order->getStatus(),
                            'order_attempt'             => $order->getAttempts(),
                            'payment_authorized'        => $payment->isAuthorized(),
                        ]);

                    if (($order->getStatus() === Order\Status::PAID) and
                        ($order->getAttempts() === 1) and ($payment->isAuthorized() === true))
                    {
                        $this->trace->info(
                            TraceCode::ORDER_STATUS_MISMATCHED,
                            [
                                'payment_id'         => $payment->getId(),
                                'order_id'           => $order->getId(),
                            ]);

                        return true;
                    }
                }

            }

            $isRefundRequired = $this->isRefundRequiredForPayment($payment);

            if ($payment->merchant->isFeatureEnabled(Features::DISABLE_AUTO_REFUNDS))
            {
                return true;
            }

            /**
             * Check that if the refund is not required , then unset the refund_at
             * for the payment.
             * Ideally, This should not happen.But there are old payments which have refund_at
             * set and have a failed or refunded state. This will eventually clean all
             * payments where refund_at shouldn't be set.
             */
            if ($isRefundRequired === false)
            {
                $previousRefundAt = $payment->getRefundAt();

                $this->core->updateRefundAt($payment->getPublicId(), null);

                $this->trace->info(
                    TraceCode::PAYMENTS_UPDATE_REFUND_AT,
                    [
                        'payment_id'         => $payment->getId(),
                        'payment_status'     => $payment->getStatus(),
                        'previous_refund_at' => $previousRefundAt,
                        'current_refund_at'  => null,
                    ]);

                $updatedRefundAt++;

                return true;
            }

            return false;
        });



        $authorized = $payments->count();
        $refunded = 0;

        $timedOut = 0;
        $failed = 0;
        $error = 0;

        $time = time();

        $payments = $payments->shuffle();

        $this->trace->info(
            TraceCode::PAYMENT_AUTO_REFUND_CRON,
            [
                'count' => $authorized,
                'start_time' => $time
            ]);

        foreach ($payments as $payment)
        {
            try
            {
                $this->repo->reload($payment);

                $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTO_REFUND_ELIGIBLE, $payment);

                assertTrue ($payment->isAuthorized() === true);

                $merchant = $payment->merchant;

                // based on experiment, refund request will be routed to Scrooge
                $refund = $this->getNewProcessor($merchant)
                               ->refundAuthorizedPayment($payment);

                $this->trace->info(
                    TraceCode::PAYMENT_AUTO_REFUND,
                    [
                        'payment_id'        => $payment->getId(),
                        'refund_id'         => $refund->getId(),
                        'auto_refund_delay' => $merchant->getAutoRefundDelay()
                    ]);

                $refunded++;
            }
            catch (Exception\GatewayTimeoutException $e)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_REQUEST_TIMEOUT,
                    ['payment_id' => $payment->getId()]);

                // Just continue
                $timedOut++;
            }
            catch (Exception\GatewayErrorException $e)
            {
                $failed++;

                $this->trace->traceException($e, Trace::INFO, TraceCode::REFUND_EXCEPTION);

                // Now Just continue
            }
            catch (\Throwable $e)
            {
                // @note: If payment refund fails due to any reason
                // other than expected ones, we should log it as an error
                // exception.
                //
                // If for eg, exception is BadRequestException, then it won't
                // get logged by global handler because it's not a critical
                // exception but in this context it really shouldn't have
                // occurred.
                $traceData = [];

                // Not all exception are of type base exception, so getData wont be available for them,
                // adding that check.
                if ($e instanceof Exception\BaseException)
                {
                    $traceData = $e->getData() ?? [];
                }

                // Resetting in case the trace data is not array.
                if (is_array($traceData) === false)
                {
                    $traceData = [];
                }

                // adding extra data, in case the exception does not have enough info
                $traceData = array_merge([], $traceData,
                    [
                        'meta' => [
                            'payment_id' => $payment->getId(),
                            'method'     => $payment->getMethod()
                        ]
                    ]);

                $this->trace->traceException($e, Trace::INFO, TraceCode::REFUND_EXCEPTION, $traceData);

                // Just continue
                $error++;
            }
        }

        $time = time() - $time;

        $results = array(
            'inital count'    => $initialCount,
            'updatedRefundAt' => $updatedRefundAt,
            'authorized'      => $authorized,
            'refunded'        => $refunded,
            'error'           => $error,
            'failed'          => $failed,
            'timed out'       => $timedOut,
            'total time'      => $time . ' secs');

        $this->trace->info(
            TraceCode::PAYMENT_AUTO_REFUND_CRON_SUMMARY,
            $results
        );

        return $results;
    }

    public function timeoutOldPayments(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $count = 0;

        $limit = $input['limit'] ?? 1000;

        $allMethods = Payment\Method::getAllPaymentMethods();

        $preAuthorizeGooglePayMethods = Payment\Method::getPreAuthorizeGooglePayMethods();

        $allMethods = array_merge($allMethods, $preAuthorizeGooglePayMethods);

        if (isset($input['methods']) === true)
        {
            $allMethods = $input['methods'] ;
        }

        $emandateRecurringType = null;

        if (isset($input['recurring_type']) === true)
        {
            $emandateRecurringType = $input['recurring_type'];
        }

        $includeMerchantList = $input['include_merchants'] ?? [];

        $excludeMerchantList = $input['exclude_merchants'] ?? [];

        $filterPaymentPushedToKafka = $input['filter_payment_pushed_to_kafka'] ?? false;

        foreach ($allMethods as $method)
        {
            $count = $count + $this->timeoutOldPaymentsForMethod($limit, $method, $emandateRecurringType, $includeMerchantList, $excludeMerchantList, $filterPaymentPushedToKafka);
        }

        return ['count' => $count];
    }

    public function timeoutAuthenticatedPayments(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $limit = $input['limit'] ?? 1000;

        // Only card methods have authenticated status.
        $method = Payment\Method::CARD;

        $count = $this->timeoutAuthenticatedPaymentsForMethod($limit, $method);

        return ['count' => $count];
    }

    public function timeoutAuthenticatedPaymentsForMethod($limit, $method)
    {
        $count = 0;

        $error = 0;

        $startTime = microtime(true);

        $now = time();

        $toTimestamp = $now - Payment\Entity::PAYMENT_TIMEOUT_DEFAULT_OLD;

        $fromTimestamp = $this->repo->payment->fetchOldPaymentsMinAuthenticatedForMethodForTimeout($method);

        if (isset($fromTimestamp) === false)
        {
            return 0;
        }

        $payments = $this->repo->payment->fetchOldAuthenticatedPaymentsForMethodForTimeout($fromTimestamp, $toTimestamp, $limit, $method);

        $total = count($payments);

        foreach ($payments as $payment)
        {
            $payment->reload();
            if (($payment->isAuthenticated() === true) and
                ($payment->shouldTimeoutAuthenticatedPayment($now) === true))
            {
                $this->repo->transaction(function () use ($payment, & $count, & $error)
                {
                    $this->repo->payment->lockForUpdateAndReload($payment);

                    try
                    {
                        $this->getNewProcessor($payment->merchant)
                            ->setPayment($payment)
                            ->timeoutPayment();

                        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHORIZATION_DROPPED, $payment);

                        $count++;
                    }
                    catch (\Throwable $e)
                    {
                        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CRON_TIMEOUT_FAILED, $payment, $e);

                        $this->trace->traceException($e);

                        $error++;
                    }
                });
            }
        }

        $this->trace->info(
            TraceCode::PAYMENT_AUTH_TIMED_OUT,
            [
                'method'     => $method,
                'total'      => $total,
                'count'      => $count,
                'error'      => $error,
                'timestamp'  => time(),
                'time_taken' => microtime(true) - $startTime
            ]);

        return $count;
    }

    public function timeoutOldPaymentsForMethod($limit, $method, $emandateRecurringType, array $includeMerchantList, array $excludeMerchantList, $filterPaymentPushedToKafka)
    {
        $count = 0;

        $error = 0;

        $startTime = microtime(true);

        // All Payments in created state will be marked as failed after 9 minutes
        $now = time();

        $toTimestamp = $now - Payment\Entity::PAYMENT_TIMEOUT_DEFAULT_OLD;

        $fromTimestamp = $this->repo->payment->fetchOldPaymentsMinCreatedForMethodForTimeout($method,
                                                                                             $emandateRecurringType,
                                                                                             $includeMerchantList,
                                                                                             $excludeMerchantList,
                                                                                             $filterPaymentPushedToKafka
                                                                                            );

        if (isset($fromTimestamp) === false)
        {
            return;
        }

        $payments = $this->repo->payment->fetchOldCreatedPaymentsForMethodForTimeout($fromTimestamp,
                                                                                     $toTimestamp,
                                                                                     $limit,
                                                                                     $method,
                                                                                     $emandateRecurringType,
                                                                                     $includeMerchantList,
                                                                                     $excludeMerchantList,
                                                                                     $filterPaymentPushedToKafka
                                                                                    );

        $total = count($payments);

        foreach ($payments as $payment)
        {
            $payment->reload();

            if (($payment->isCreated() === true) and
                ($payment->shouldTimeout($now) === true))
            {
                $this->repo->transaction(function () use ($payment, & $count, & $error, $method)
                {
                    $this->repo->payment->lockForUpdateAndReload($payment);

                    try
                    {
                        $this->getNewProcessor($payment->merchant)
                             ->setPayment($payment)
                             ->timeoutPayment();

                        if ($method === Payment\Method::NACH and
                            $payment->isRecurring() and
                            $payment->getRecurringType() === Payment\RecurringType::INITIAL)
                        {
                            $this->moveTimedoutRecurringNachPaymentTokensToRejectedState($payment);
                        }

                        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHORIZATION_DROPPED, $payment);

                        $count++;

                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException($e);

                        $error++;
                    }
                });
            }
        }

        $this->trace->info(
            TraceCode::PAYMENT_TIMED_OUT,
            [
                'method'     => $method,
                'total'      => $total,
                'count'      => $count,
                'error'      => $error,
                'timestamp'  => time(),
                'time_taken' => microtime(true) - $startTime
            ]);

        return $count;
    }


    /**
     * Handles callback from reminder service to mark the payment as failed incase its in pending status for > 45d
     * The return boolean variable is to decide whether to continue further callback from reminder service.
     */
    public function handleReminderCallbackToTimeoutCoDPayment($paymentId): bool
    {
        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        $now = time();

        $expectedTimeoutAt = $payment->getCreatedAt() + Payment\Entity::PAYMENT_TIMEOUT_COD_PENDING;

        if ($payment->isPending() === false)
        {
            return false;
        }

        if ($now < $expectedTimeoutAt)
        {
            return true;
        }

        $this->getNewProcessor($payment->merchant)
              ->setPayment($payment)
              ->timeoutPayment();

        return false;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1200);

        RuntimeManager::setMaxExecTime(1200);
    }

    public function autoCaptureOldAuthorizedPayments()
    {
        $timeLowerLimit = Carbon::now()->subHour()->getTimestamp();

        $timeUpperLimit = Carbon::now()->subMinutes(5)->getTimestamp();

        $payments = $this->repo->useSlave(function () use ($timeLowerLimit, $timeUpperLimit)
        {
            return $this->repo->payment->getAuthorizedAutoCapturePaymentsBetweenTimestamps(
                $timeLowerLimit, $timeUpperLimit
             );
        });


        $success          = 0;
        $totalCount       = count($payments);
        $failedPaymentIds = [];

        foreach ($payments as $payment)
        {
            $payment->reload();

            $this->merchant = $payment->merchant;

            try
            {
                if ($payment->isCaptured() === false)
                {
                    $this->getNewProcessor()->autoCapturePaymentIfApplicable($payment);

                    $success++;
                }
            }
            catch (Exception\RecoverableException $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::PAYMENT_AUTO_CAPTURE_FAILED,
                    [
                        'step'          => 'auto_capture_authorized',
                        'payment_id'    => $payment->getId()
                    ]
                );

                $failedPaymentIds[] = $payment->getId();

                continue;
            }
        }

        $dataToTrace = [
            'count'              => $totalCount,
            'success_count'      => $success,
            'failed_payment_ids' => $failedPaymentIds
        ];

        $this->trace->info(TraceCode::PAYMENT_AUTO_CAPTURE_CRON, $dataToTrace);

        return $dataToTrace;
    }

    public function deliverAutoCaptureEmail()
    {
        $timeLowerLimit = Carbon::yesterday(Timezone::IST)->getTimestamp();
        $timeUpperLimit = Carbon::today(Timezone::IST)->getTimestamp();

        $payments = $this->repo->payment->getAutoCapturedPaymentsBetweenTimestamps(
                                                        $timeLowerLimit, $timeUpperLimit);

        $count = $payments->count();
        $emailCount = 0;
        $i = 0;

        while ($i < $count)
        {
            $autoCaptured = new Base\Collection;
            $merchantId = $payments[$i]->getMerchantId();
            $str = 'Payments with below Ids have been auto-captured:\n';

            while (($i < $count) and
                   ($payments[$i]->getMerchantId() === $merchantId))
            {
                $autoCaptured->push($payments[$i]->getPublicId());
                $str .= $payments[$i]->getPublicId() . '\n';
                $i++;
            }

            $merchant = $this->repo->merchant->findOrFail($merchantId);
            $this->app['mailgun']->sendAutoCaptureEmail($merchant->email, $str);
            $emailCount++;
        }

        return ['payments_count' => $count, 'emails_count' => $emailCount];
    }

    public function verifyAllPayments(array $input)
    {
        (new Payment\Validator)->validateInput('verify_all', $input);

        $gateway = $input['gateway'] ?? null;

        $delay = $input['delay'] ?? 0;

        $count = $input['count'] ?? 200;

        $bucket = $input['bucket'] ?? null;

        $end = Carbon::now(Timezone::IST)->subSeconds($delay)->getTimestamp();

        $start = $this->getStartTimestamp($delay);

        $filterPaymentPushedToKafka = $input['filter_payment_pushed_to_kafka'] ?? false;

        return (new Verify)->verifyAllPayments([$start, $end], $gateway, $count, $bucket, $filterPaymentPushedToKafka);
    }

    public function verifyAllPaymentsNewRoute(array $input)
    {
        (new Payment\Validator)->validateInput('verify_all', $input);

        $gateway = $input['gateway'] ?? null;

        $delay = $input['delay'] ?? 0;

        $count = $input['count'] ?? 200;

        $bucket = $input['bucket'] ?? null;

        $useSlave = $input['use_slave'] ?? false;

        $end = Carbon::now(Timezone::IST)->subSeconds($delay)->getTimestamp();

        $start = $this->getStartTimestamp($delay);

        $startTime = Carbon::createFromTimestamp($start, Timezone::IST)->toTimeString();
        $endTime = Carbon::createFromTimestamp($end, Timezone::IST)->toTimeString();

        $filterPaymentPushedToKafka = $input['filter_payment_pushed_to_kafka'] ?? false;

        return (new Verify)->verifyAllPaymentsNewRoute([$start, $end], $gateway, $count, $bucket, $useSlave, $filterPaymentPushedToKafka);
    }

    public function verifyPaymentsInBulk(array $input)
    {
        (new Payment\Validator)->validateInput('bulk_verify', $input);

        $paymentIds = Payment\Entity::verifyIdAndStripSignMultiple($input['payment_ids']);

        return (new Verify)->verifyPaymentsWithIds($paymentIds);
    }

    public function verifyPaymentsInBulkNewRoute(array $input)
    {
        (new Payment\Validator)->validateInput('bulk_verify', $input);

        $paymentIds = Payment\Entity::verifyIdAndStripSignMultiple($input['payment_ids']);

        return (new Verify)->verifyPaymentsWithIdsNewRoute($paymentIds);
    }

    public function verifyMultiplePayments(string $filter, array $input)
    {
        (new Payment\Validator)->validateInput('verify', $input);

        $bucket = $input['bucket'] ?? [];

        $gateway = $input['gateway'] ?? null;

        return (new Verify)->verifyPaymentsWithFilter($filter, $bucket, $gateway);
    }

    public function verifyPayment($payment)
    {
        return (new Verify)->verifyPayment($payment);
    }

    public function verifyRecurringPayment($payment, $input)
    {
        return (new Verify)->verifyPayment($payment, null, $input['upi']);
    }

    public function verifyCapturedPayments(array $input)
    {
        (new Payment\Validator)->validateInput('verify_all', $input);

        $gateway = $input['gateway'] ?? null;

        $delay = $input['delay'] ?? 0;

        $count = $input['count'] ?? 200;

        $bucket = $input['bucket'] ?? null;

        $end = Carbon::now(Timezone::IST)->subSeconds($delay)->getTimestamp();

        $start = $this->getStartTimestamp($delay);

        $startTime = Carbon::createFromTimestamp($start, Timezone::IST)->toTimeString();
        $endTime = Carbon::createFromTimestamp($end, Timezone::IST)->toTimeString();

        $filterPaymentPushedToKafka = $input['filter_payment_pushed_to_kafka'] ?? false;

        return (new Verify)->verifyCapturedPayments([$start, $end], $gateway, $count, $bucket, $filterPaymentPushedToKafka);
    }

    /**
     * Certain gateways require gateway data such as bank reference number for payment verification
     * to function accurately
     *
     * @param $payment
     * @param null $gatewayData
     * @return null|string
     */
    public function verifyPaymentWithGatewayData($payment, $gatewayData = null)
    {
        return (new Verify)->verifyPayment($payment, null, $gatewayData);
    }

    public function sendReminderMerchantMailForAuthorizedPayments()
    {
        $result = [
            'initial'   => $this->sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay(2, false),
            'final'     => $this->sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay(4, true)
        ];

        $this->trace->info(TraceCode::PAYMENT_AUTHORIZE_REMINDER, $result);

        return $result;
    }

    public function sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay($day, $final = false)
    {
        $result = [
            // This holds the counts
            'counts' => []
        ];

        // This is the start of the day 00:00, $day ago
        $start = Carbon::today(Timezone::IST)->subDays($day);
        $end   = Carbon::today(Timezone::IST)->subDays($day)->addDays(1);

        $to = $end->getTimestamp();
        $from = $start->getTimestamp();

        $result['from'] = (string) $start;
        $result['to']   = (string) $end;

        $authorizedPayments = $this->repo->payment->getAuthorizedPaymentsBetweenTimestamps($from, $to);

        $grouped = $authorizedPayments->groupBy(Payment\Entity::MERCHANT_ID);

        // Put the counts in for debug purposes
        $result['counts'] = [
            'payments'  => count($authorizedPayments),
            'merchants' => count($grouped),
            'failures'  => 0,
        ];

        foreach ($grouped as $merchantId => $payments)
        {
            // Send mail only if we have some payments
            if (count($payments) > 0)
            {
                try
                {
                    $this->sendAuthorizedPaymentsReminderMail(
                        $merchantId, $payments, $final);

                    $result['counts'][$merchantId] = count($payments);
                }
                catch (\Exception $ex)
                {
                    $this->trace->warning(TraceCode::PAYMENT_AUTHORIZE_REMINDER_FAILURE,
                        [
                            'merchant_id' => $merchantId,
                            'payments'    => count($payments),
                            'message'     => $ex->getMessage(),

                        ]);

                    $result['counts']['failures'] += 1;
                }
            }
        }

        return $result;
    }

    /**
     * Fetch and update on_hold flag for all payment
     * and source transfer with on_hold_until less than today's timestamp
     *
     * @return array
     */
    public function updateOnHold(array $input): array
    {
        RuntimeManager::setMaxExecTime(600);

        $timestamp = Carbon::today(Timezone::IST)->getTimestamp();

        $limit = $input['limit'] ?? 500;

        $paymentIdsToUpdate = $this->repo->payment->getPaymentsOnHoldBeforeTimestamp($timestamp, $limit)->pluck(Entity::ID)->toArray();

        $cronSummary = $this->updateOnHoldForPayments($paymentIdsToUpdate, $timestamp);

        return [
            'success'   => true,
            'summary'   => $cronSummary
        ];
    }

    private function updateOnHoldForPayments(?array $paymentIds, int $timestamp, bool $checkTransfer = true): array
    {
        $this->trace->debug(
            TraceCode::PAYMENT_UPDATE_HOLD_CRON,
            [
                'step'          => 'fetch_payments',
                'ids_fetched'   => $paymentIds,
                'timestamp'     => Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d-m-Y H:i:s')
            ]
        );

        $cronSummary = [
            'total_count' => empty($paymentIds) ? 0 : sizeof($paymentIds),
            'failed_ids'  => []
        ];

        if (empty($paymentIds))
        {
            return $cronSummary;
        }

        $mapForSettlementService = [];

        foreach ($paymentIds as $paymentId)
        {
            try
            {
                // Re fetch by payment id to reload entity fetched from warm storage
                $payment = $this->repo->payment->findOrFail($paymentId);

                $txn = $this->repo->transaction(
                    function() use ($payment, $checkTransfer)
                    {
                        return $this->setHoldFalse($payment, $checkTransfer);
                    });

                $mid = $txn->getMerchantId();

                $bucketCore = New Settlement\Bucket\Core;

                if(isset($mapForSettlementService[$mid]) === false)
                {
                    $mapForSettlementService[$mid] = false;
                }

                if($bucketCore->shouldProcessViaNewService($mid) === true)
                {
                    $mapForSettlementService[$mid] = true;
                }

                if ($mapForSettlementService[$mid] === true)
                {
                    $bucketCore->settlementServiceToggleTransactionHold([$txn->getId()], null);
                }
                else
                {
                    (new Transaction\Core)->dispatchForSettlementBucketing($txn);
                }
            }
            catch (\Throwable $e)
            {
                $cronSummary['failed_ids'][] = $paymentId;

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_UPDATE_HOLD_CRON,
                    [
                        'step'  => 'update_failed',
                        'id'    => $payment->getId()
                    ]
                );
            }
        }

        $this->trace->debug(TraceCode::PAYMENT_UPDATE_HOLD_CRON, ['step' => 'summary', 'summary' => $cronSummary]);

        return $cronSummary;
    }

    /**
     * Fetch the on-hold payments of all sub-merchants of partners having feature "subm_manual_settlement" enabled
     * @param int $timestamp
     * @return mixed
     */
    public function fetchSubmManualSettlementPaymentsBeforeTimestamp(int $timestamp): ?array
    {
        $partnerIds = $this->repo->feature->findMerchantIdsHavingFeatures([Feature\Constants::SUBM_MANUAL_SETTLEMENT]);

        if (empty($partnerIds))
        {
            return null;
        }

        $subMerchantIds = $this->repo->merchant_access_map->getSubmIdsFromEntityOwnerIds($partnerIds);

        $paymentIds = $this->repo->transaction->fetchOnHoldPaymentIdsForMerchantsBeforeTimestamp($subMerchantIds, $timestamp);

        $this->trace->debug(
            TraceCode::MANAUL_SETTLEMENT_PAYMENT_UPDATE_ON_HOLD_CRON,
            [
                'partner_ids'       => $partnerIds,
                'submerchant_ids'   => $subMerchantIds,
                'payment_ids'       => $paymentIds
            ]
        );

        return $paymentIds;
    }

    public function updateOnHoldBulkUpdate(array $input)
    {
       (new Payment\Validator)->validateInput('payment_onhold_bulk_update', $input);

       $onHold = $input['on_hold'];

        $paymentsToUpdate = [];

        foreach ($input['payment_ids'] as $paymentId)
        {
            try
            {
                $paymentsToUpdate[] = $this->repo->payment->findOrFailPublic($paymentId);
            }
            catch (\Throwable $e) {}
        }

        $this->trace->info(
            TraceCode::PAYMENT_ON_HOLD_TOGGLE,
            [
                'payment_ids'   => $paymentsToUpdate->getIds(),
                'on_hold'       => $onHold,
            ]
        );

        $result = [
            'count' => $paymentsToUpdate->count(),
            'failed_ids' => [],
            'successful' => 0,
        ];

        $paymentCore = new Payment\Core;

        foreach ($paymentsToUpdate as $payment)
        {
            try
            {
                $merchant = $payment->merchant;

                $paymentCore->updatePaymentOnHold($payment, $onHold);

                $result['successful'] += 1;
            }
            catch (\Exception $e)
            {
                $result['failed_ids'][] = $payment->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_ON_HOLD_TOGGLE_FAILED,
                    [
                        'step'  => 'update_failed',
                        'id'    => $payment->getId()
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * Marks the payment as acknowledged, if not already acknowledged.
     * Also, updates payments.notes field with acknowledged data if any.
     *
     * @param string $paymentId
     * @param array  $input
     *
     * @throws Exception\BadRequestException
     */
    public function acknowledge(string $paymentId, array $input)
    {
        (new Payment\Validator)->validateInput('acknowledge', $input);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        $this->getNewProcessor()->acknowledge($payment, $input);
    }

    public function updateReceiverData()
    {
        return $this->core->updateReceiverData();
    }

    /**
     * @param $input
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function validateVpa($input)
    {
        $merchant = $this->merchant;

        /**
         * - Doing this for calls from FAVpaValidation Worker since merchant is not set in async processing
         * - Tried with basicauth but has related issues of repo null
         */
        if (($merchant === null) and (empty($input['merchant_id']) === false))
        {
            $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);
            unset($input['merchant_id']);
        }

        // Check if we can process the validate VPA request for numeric/non-numeric VPA to UPS directly
        // For the route named `payment_validate_account`, this check is already happening at
        // app/Models/Payment/Validation/Vpa.php::processValidation().
        // We have the same check here for the route named `payment_validate_vpa`
        if ($this->shouldRouteValidateVpaRequestToUps($merchant))
        {
            try
            {
                return $this->app['upi.payments']->action(Payment\Action::VALIDATE_VPA_PROXY, $input, "");
            }
            catch (Exception\GatewayErrorException $exception)
            {
                $this->trace->error(
                    TraceCode::VALIDATE_VPA_UPS_GATEWAY_REQUEST_FAILED,
                    [
                        'error_message' => $exception->getMessage(),
                    ]);

                if ($exception->getCode() === ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA)
                {
                    return [
                        'vpa'           => $input['vpa'],
                        'success'       => false,
                        'customer_name' => null,
                    ];
                }

                throw $exception;
            }
            catch(\Throwable $e)
            {
                $this->trace->error(
                    TraceCode::VALIDATE_VPA_UPS_REQUEST_FAILED,
                    [
                        'error_message' => $e->getMessage(),
                    ]);

                $this->trace->count(Metric::VALIDATE_VPA_UPS_REQUEST_FAILED_COUNT);

                // In case of errors, continue with the old flow
            }
        }

        $data = $this->getNewProcessor($merchant)->validateVpa($input);

        return $data;
    }

    /**
     * Check whether to route validate account request to UPS
     * @return bool
     */
    protected function shouldRouteValidateVpaRequestToUps($merchant): bool
    {
        $mode = $this->mode ?? Mode::LIVE;

        $merchantId = optional($merchant)->getId() ?? 'default';

        $variant = $this->app->razorx->getTreatment($merchantId, RazorxTreatment::VALIDATE_VPA_REARCH_UPS, $mode);

        return str_starts_with(strtolower($variant), 'on') === true;
    }

    public function mandateUpdate($id, $token, $input)
    {
        $data = $this->getNewProcessor()->mandateUpdate($id, $token, $input);
    }

    public function mandateCancel($id, $upiMandate, $token)
    {
        (new UpiMandate\Core())->validateUpiMandateForCancel($upiMandate);

        return $this->getNewProcessor()->mandateCancel($id, $upiMandate, $token);
    }

    public function validateEntity(array $input)
    {
        Locale::setLocale($input, $this->merchant->getId());

        (new Payment\Validator())->validateInput('validate_entity', $input);

        $validator = Payment\Validation\Factory::build($input['entity']);

        $data = $validator->processValidation($input);

        return $data;
    }

    protected function setHoldFalse(Payment\Entity $payment, bool $checkTransfer)
    {
        $this->repo->payment->lockForUpdateAndReload($payment);

        $payment->setOnHold(false);

        $payment->setOnHoldUntil(null);

        $this->repo->saveOrFail($payment);

        $isExpEnabled = $this->checkIfTransactionOnholdWriteRemovalEnabled($payment->merchant);

        if($isExpEnabled===false)
        {

            $txn = $this->repo->transaction->lockForUpdate($payment->getTransactionId());

            $txn->setOnHold(false);

            $this->repo->saveOrFail($txn);
        }
        else
        {
            try
            {
                $txn = $this->createVirtualPaymentTxnFromLedger($payment);
            }
            catch (\Throwable $e)
            {
                // Fallback to TiDB incase of failure
                $this->trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::PAYMENT_TXN_CREATION_FROM_LEDGER_FAILED,
                    [
                        'payment_id'     => $payment->getId(),
                        'transaction_id' => $payment->getTransactionId(),
                    ]
                );

                $txn = $this->repo->transaction->findByEntityIdWithoutMerchantTidb($payment->getId());
            }

            $txn->setOnHold(false);
        }

        //
        // If the payment has a transfer, update the
        // on_hold flag for the transfer as well
        //
        if ($payment->hasTransfer() === true)
        {
            $transfer = $this->repo->transfer->lockForUpdate($payment->getTransferId());

            $transfer->setOnHold(false);

            $transfer->setOnHoldUntil(null);

            $transfer->setSettlementStatus(Transfer\SettlementStatus::PENDING);

            $this->repo->saveOrFail($transfer);
        }
        // Temp: Payments can't have hold enabled right now without a linked transfer
        // Fail if no associated transfer and transfer check is enabled. @todo - Remove this when payment hold is added\
        else if ($checkTransfer === true)
        {
            throw new Exception\LogicException(
                'Hold update attempted for payment with no transfer',
                null,
                [
                    'transaction_id'    => $txn->getId(),
                    'payment_id'        => $payment->getId(),
                ]);
        }

        return $txn;
    }

    public function createVirtualPaymentTxnFromLedger($payment){
        $txn = [];
        if ($payment->hasTransfer() === true)
        {
            $transfer = $this->repo->transfer->findOrFail($payment->getTransferId());
            $transferCore = new Transfer\Core();
            $reverseShadowTransfersCore = new LedgerReverseShadow\Transfers\Core();

            // fetch the credit journal
            [$creditJournal,] = $transferCore->fetchJournalsFromLedgerForTransfer($transfer, $transfer->getToId());

            // set txn to virtual payment txn
            $txn = $reverseShadowTransfersCore->createVirtualTransferPaymentTransactionFromLedgerJournal($creditJournal, $payment);

        }
        else
        {
            $reverseShadowPaymentsCore = new LedgerReverseShadow\Payments\Core();
            $journalResponse = $reverseShadowPaymentsCore->fetchLedgerJournalForPaymentMerchantCapture($payment);
            // set txn to virtual payment txn
            $txn = $reverseShadowPaymentsCore->transformJournalResponseToTransactionEntityForPayments($journalResponse);
        }

        return $txn;
    }

    /**
     * Sends the authorized payments reminder email
     *
     * @param  string  $merchantId
     * @param  array   $payments
     * @param  boolean $final Whether this is the final payment reminder
     */
    protected function sendAuthorizedPaymentsReminderMail($merchantId, $payments, $final)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $autoRefundsDisabledForMerchant = $merchant->isFeatureEnabled(Feature\Constants::DISABLE_AUTO_REFUNDS);

        $merchant = $merchant->toArray();

        $data = compact('merchant', 'payments', 'final', 'autoRefundsDisabledForMerchant');

        $authorizedPaymentsReminderMail = new AuthorizedPaymentsReminderMail($data);

        Mail::send($authorizedPaymentsReminderMail);
    }

    protected function getNewProcessor(Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $processor = new Processor\Processor($merchant);

        return $processor;
    }

    public function getDummyPayment(Order\Entity $orderEntity, Card\IIN\Entity $iinEntity)
    {
        $payment = new Payment\Entity;

        $card = new Card\Entity;

        $payment->merchant()->associate($this->merchant);

        $paymentInput = $payment->getDummyPaymentArray(Payment\Method::CARD, null, $iinEntity->getNetworkCode());

        $payment->fill($paymentInput);

        $cardInput = $card->getDummyCardArray(null, $iinEntity);

        $card->fill($cardInput);

        $card->setAttribute(Card\Entity::IIN, $iinEntity->getIin());

        $payment->card()->associate($card);

        return $payment;
    }

    //$ttl is in minutes
    public function generateAndSaveOneTimeTokenWithContact($input, $ttl=15)
    {
        $cacheTtl = $ttl * 60; // multiplying by 60 since put() expects in seconds

        $length = 14;

        $bytes = random_bytes($length / 2);

        $token = bin2hex($bytes);

        $key = Payment\Entity::getCardlessEmiOnetimeTokenCacheKey($token);

        $data = [
            Entity::CONTACT   => $input[Entity::CONTACT],
            Entity::PROVIDER  => $input[Entity::PROVIDER],
        ];

        if (isset($input['payment_id']) === true)
        {
            $data['payment_id'] = $input['payment_id'];
        }

        $this->app['cache']->put($key, $data, $cacheTtl);

        return $token;
    }

    public function migrateCardVaultToken(string $cardId, string $paymentId = null, bool $bulkUpdate = false, string $gateway = null)
    {
        $updated = null;

        (new Card\Service)->migtateCardVaultToken($cardId, $bulkUpdate, $gateway);

        if ($paymentId !== null)
        {
            $payment = null;

            try
            {
                $payment = $this->repo->payment->findOrFail($paymentId);
            }
            catch (\Throwable $exception){}

            $card = null;

            try
            {
                $card = $this->repo->card->findOrFail($cardId);
            }
            catch (\Throwable $exception) {}

            $updated = (new Token\Core)->updatePaymentToken($payment, $card);

            if ($updated === true)
            {
                $this->repo->saveOrFail($payment);
            }
        }

        return $updated;
    }

    public function createCorrespondingCLSAdjustment(string $paymentId)
    {
        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);

            $transaction = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

            $amount = $transaction->getCredit() - $transaction->getDebit();

            $transactorEvent = "";

            if ($amount >= 0)
            {
                $transactorEvent = "positive_adjustment";
            }
            else
            {
                $transactorEvent = "negative_adjustment";
            }

            $transactorId = 'adj_' . $payment->getId();

            $journalPayload = [
                LedgerConstants::MERCHANT_ID           => $transaction->getMerchantId(),
                LedgerConstants::CURRENCY              => LedgerConstants::INR_CURRENCY,
                LedgerConstants::TRANSACTION_DATE      => $transaction->getUpdatedAt(),
                LedgerConstants::TRANSACTOR_ID         => $transactorId,
                LedgerConstants::TRANSACTOR_EVENT      => $transactorEvent,
                LedgerConstants::MONEY_PARAMS          => [
                        "merchant_balance_amount"       => strval($amount),
                        "base_amount"                   => strval($amount),
                        "adjustment_amount"             => strval($amount),
                        "merchant_balance_limit"        => "0",
                ],
            ];

            $ledgerOutboxCore = (new LedgerOutboxCore());

            $ledgerOutboxCore->createJournalInLedger($journalPayload, false, false, true);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::CLS_ONBOARDING_FAILURE_ADJUSTMENT_CREATION,
                [
                    'payment_id'            => $paymentId,
                ]
            );
        }
    }

    public function updateMerchantBalance(string $paymentId, $asyncTxnEnabled = false)
    {
        $payment = null;

        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);

            $merchant = $this->repo->merchant->findOrFailPublic($payment->getMerchantId());

            $featureCore = new Feature\Core();

            $clsOnboardingStatus = $featureCore->isLedgerOnboardingFeaturesInAPI($merchant->getId());

            if ($clsOnboardingStatus === true)
            {
                $input = [
                    'payment_id'  => $payment->getId(),
                    'mode'        => $this->app['rzp.mode'],
                    'async_txn_fill_enabled' => $asyncTxnEnabled,
                ];

                $delaySecs = 5 * 60; // 5 minutes

                MerchantBalanceUpdateAfterCLSOnboarding::dispatch($input, $this->mode, $asyncTxnEnabled)->delay($delaySecs);;
                return;
            }

            if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW))
            {
                app('worker.ctx')->setLedgerDualWriteFlow(true);
            }
        }
        catch (\Throwable $exception){}

        $transaction = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

        if (($payment->hasBeenCaptured() === false) or
            (empty($transaction) === true))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
                [
                    'payment_id' => $paymentId,
                    'status'    => $payment->getStatus()
                ]
            );
        }

        if ($transaction->isBalanceUpdated() === true)
        {
            $this->trace->info(TraceCode::TRANSACTION_BALANCE_ALREADY_UPDATED,
                [
                    'merchant_id'           => $payment->getMerchantId(),
                    'payment_id'            => $paymentId,
                ]
            );

            return;
        }

        $this->getNewProcessor($payment->merchant)->updateMerchantBalance($payment, $transaction, $asyncTxnEnabled);
    }


    public function fetchpaymentwithSubscription(string $subscriptionId): array
    {
        $payment = $this->repo->payment->fetchBySubscriptionId($subscriptionId);

        $payload = [];

        if($payment != null) {


            $payload = $payment->toArrayAdmin();

            $payload['merchant'] = [
                Merchant\Entity::BILLING_LABEL => $payment->merchant->getBillingLabel(),
                Merchant\Entity::WEBSITE => $payment->merchant->getWebsite(),
                Merchant\Entity::EMAIL => $payment->merchant->getTransactionReportEmail(),
            ];

            $payload['customer'] = [
                'email' => $payment->customer->getEmail(),
                'phone' => $payment->customer->getContact(),
            ];

            if ($payment->hasCard() === true) {
                $card = $payment->card;
                $expiryMonth = str_pad($card->getExpiryMonth(), 2, '0', STR_PAD_LEFT);

                $cardDetails = $card->toArrayPublic();

                $cardFormatted = [
                    'number' => '**** **** **** ' . $card->getLast4(),
                    'expiry' => $expiryMonth . '/' . $card->getExpiryYear(),
                    'network' => $card->getNetworkCode(),
                    'color' => $card->getNetworkColorCode()
                ];

                $payload['card'] = array_merge($cardDetails, $cardFormatted);
            }

            $this->addInvoiceOfferDetailsForSubscription($payment, $payload);
        }

        return $payload;
    }

    public function fetchpaymentwithSubscriptionEmailAndContactNotNull(string $subscriptionId): array
    {
        $payment = $this->repo->payment->fetchBySubscriptionIdEmailAndContactNotNull($subscriptionId);

        $payload = [];

        if($payment != null) {


            $payload = $payment->toArrayAdmin();

            $payload['customer'] = [
                'email' => $payment->email,
                'phone' => $payment->contact,
            ];
        }

        return $payload;
    }

    private function addInvoiceOfferDetailsForSubscription(Payment\Entity $payment, &$payload)
    {
        if ($payment->hasInvoice() === true)
        {
            $payload['invoice'] = [
                Invoice\Entity::BILLING_START => $payment->invoice->getBillingStart(),
                Invoice\Entity::BILLING_END => $payment->invoice->getBillingEnd()
            ];
        }

        // offer payload
        $paidOffer = $payment->getOffer();

        if($paidOffer !== null)
        {
            $discountAmount = $paidOffer->getDiscountAmountForPayment($payment->order->getAmount(), $payment);

            $paidOfferSubscription = $this->repo->offer->fetchSubscriptionOfferById($paidOffer->getId(), $payment->getMerchantId());

            // TODO Change to gettter after offer team approval
            $paidOfferSubscriptionDetails = [
                'id'              => $paidOffer->getId(),
                'name'            => $paidOfferSubscription->getName(),
                'payment_method'  => $paidOfferSubscription->getPaymentMethod(),
                'applicable_on'   => $paidOfferSubscription['applicable_on'],
                'redemption_type' => $paidOfferSubscription['redemption_type'],
                'no_of_cycles'    => $paidOfferSubscription['no_of_cycles'],
            ];

            $payload['offer'] = [
                'order_amount'      => $payment->order->getAmount(),
                'discounted_amount' => $discountAmount,
                'offer_details'     => $paidOfferSubscriptionDetails,
            ];
        }
    }


    public function fetchForSubscription(string $paymentId, string $subscriptionId): array
    {
        $payment = null;
        try {
            $payment = $this->repo->payment->fetchByIdandSubscriptionId($paymentId, $subscriptionId);
        } catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::PAYMENT_FETCH_EXCEPTION,
                ['payment_id' => $paymentId, 'subscription_id' => $subscriptionId],
            );
            $payment = $this->repo->payment->findByIdAndMerchant($paymentId, $this->merchant);
        }

        if($payment->getSubscriptionId() != Entity::stripDefaultSign($subscriptionId)) {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND);
        }

        $payload = $payment->toArrayAdmin();

        $payload['merchant'] = [
            Merchant\Entity::BILLING_LABEL => $payment->merchant->getBillingLabel(),
            Merchant\Entity::WEBSITE       => $payment->merchant->getWebsite(),
            Merchant\Entity::EMAIL         => $payment->merchant->getTransactionReportEmail(),
        ];

        $customerEmail = null;
        $customerContact = null;

        if ($payment->customer !== null)
        {
            $customerEmail = $payment->customer->getEmail();
            $customerContact = $payment->customer->getContact();
        }

        $payload['customer'] = [
            'email' => $customerEmail,
            'phone' => $customerContact,
        ];

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;
            $expiryMonth = str_pad($card->getExpiryMonth(), 2, '0', STR_PAD_LEFT);

            $cardDetails = $card->toArrayPublic();

            $cardFormatted = [
                'number'  => '**** **** **** ' . $card->getLast4(),
                'expiry'  => $expiryMonth . '/' . $card->getExpiryYear(),
                'network' => $card->getNetworkCode(),
                'color'   => $card->getNetworkColorCode()
            ];

            $payload['card'] = array_merge($cardDetails, $cardFormatted);
        }

        $this->addInvoiceOfferDetailsForSubscription($payment, $payload);

        return $payload;
    }

    // verify to fetch the payments between certain duration
    protected function getStartTimestamp(int $delay)
    {
        $delay = 3 * $delay;

        // keeping the min fetch window to 5 mins
        if ($delay < 300)
        {
            $delay = 300;
        }

        return Carbon::now(Timezone::IST)->subSeconds($delay)->getTimestamp();
    }

    public function paymentCardVaultMigrate($input)
    {
        (new Payment\Validator)->validateInput('payment_card_migrate', $input);

        $limit = $input['limit'] ?? 1000;

        $migrateMissingFingerprintCards = $input['migrate_missing_fingerprint_cards'] ?? false;

        $payments = $cards = $cardsWithoutFingerprint = [];

        $startTime = $this->app['cache']->get(Processor\Processor::FINGERPRINT_MIGRATION_CACHE_KEY, 1546300800);

        $timeWindow = $input['time_window'] ?? 86400;

        if($migrateMissingFingerprintCards and $startTime < time())
        {
            $cardsWithoutFingerprint = $this->repo->card->findCardsWithoutFingerprint($limit, $startTime, $timeWindow);

            // Update start time in redis if no records are found for migration in the window
            if (count($cardsWithoutFingerprint) === 0)
            {
                $startTime = $startTime + $timeWindow;

                $this->app['cache']->forever(Processor\Processor::FINGERPRINT_MIGRATION_CACHE_KEY, $startTime);
            }
        }
        else
        {
            $payments = $this->repo->useSlave(function() use ($limit)
            {
                return $this->repo->payment->findPaymentsWithCardVault(Card\Vault::RZP_ENCRYPTION, $limit);
            });

            $cardIds = $payments->pluck(Entity::CARD_ID)->toArray();

            $cards = $this->repo->useSlave(function() use ($limit, $cardIds)
            {
                return $this->repo->card->findCardsWithVaultAndNoPayments(Card\Vault::RZP_ENCRYPTION, $limit, $cardIds);
            });

        }

        $this->trace->info(
            TraceCode::VAULT_TOKEN_MIGRATION_CRON_REQUEST,
            [
                'payments_count' => count($payments),
                'cards_count'    => count($cards) + count($cardsWithoutFingerprint),
                'start_time'     => $startTime,
                'end_time'       => $startTime + $timeWindow,
            ]);

        $result = [
            'payments_count' => count($payments),
            'cards_count'    => count($cards) + count($cardsWithoutFingerprint),
            'payment_failed' => [],
            'card_failed'    => [],
        ];

        foreach ($payments as $payment)
        {
            try
            {
                $this->migrateCardDataIfApplicable($payment, $payment->card);
            }
            catch (\Throwable $e)
            {
                $result['payment_failed'][] = $payment->getId();
            }
        }

        foreach ($cards as $card)
        {
            try
            {
                $this->migrateCardDataIfApplicable(null, $card);
            }
            catch (\Throwable $e)
            {
                $result['card_failed'][] = $card->getId();
            }
        }

        foreach ($cardsWithoutFingerprint as $card)
        {
            try
            {
                $this->migrateCardDataIfApplicable(null, $card, true);
            }
            catch (\Throwable $e)
            {
                $result['card_failed'][] = $card->getId();
            }
        }

        return $result;
    }

    public function migrateCardDataIfApplicable($payment, $card, $bulkUpdate=false)
    {
        $payload = [];

        try
        {
            $payload = [
                'card_id'     => $card->getId(),
                'token'       => $card->getVaultToken(),
                'mode'        => $this->mode,
                'bulk_update' => $bulkUpdate,
            ];

            if ($payment !== null)
            {
                $payload['payment_id'] = $payment->getId();
            }

            $this->trace->info(
                TraceCode::VAULT_TOKEN_MIGRATION_CRON_REQUEST_INIT,
                [
                    'payload' => $payload,
                ]);

            Jobs\CardVaultMigrationJob::dispatch($payload, $this->mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::VAULT_TOKEN_MIGRATION_CRON_DISPATCH_FAILED,
                ['payment_id' => $payment->getId()]
            );

            throw $e;
        }
    }

    public function getPaymentMerchantActions($id)
    {
        $data = [
            "capture" => false,
            "refund" => false
        ];

        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->isAuthorized() === false)
        {
            return $data;
        }

        $processor = $this->getNewProcessor();

        $lateAuthConfig = $processor->getLateAuthPaymentConfig($payment);

        if (isset($lateAuthConfig) === false)
        {
            $data['capture'] = true;

            return $data;
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

        $difference = $processor->getTimeDifferenceInAuthorizeAndCreated($payment);

        if ($difference < $manualTimeoutDuration)
        {
            $data['capture'] = true;
        }
        else
        {
            $data['refund'] = true;
        }

        return $data;
    }

    public function updateRefundAtForPayments($input)
    {
        (new Payment\Validator)->validateInput('bulk_update_refund_at', $input);

        $payments = $input['payments'];

        $count = count($payments);

        $this->trace->info(
          TraceCode::PAYMENTS_UPDATE_REFUND_AT,
          [
              'type'  => 'request',
              'count' => $count,
              'input' => $input,
          ]);

        $success = 0;
        $successful = [];
        $failed  = [];

        foreach ($payments as $item)
        {
            try
            {
                $this->core->updateRefundAt($item['id'], $item['refund_at']);

                $successful[] = $item;

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                  $ex,
                  // Not need to raise it as critical, because this is manual operation
                  Trace::WARNING,
                  TraceCode::PAYMENTS_UPDATE_REFUND_AT,
                  [
                      'type'    => 'failure',
                      'item'    => $item,
                  ]);

                $failed[] = $item;
            }
        }

        $response = [
            'count'         => $count,
            'success'       => $success,
            'failure'       => $count - $success,
            'failed'        => $failed,
            'successful'    => $successful,
        ];

        $this->trace->info(
          TraceCode::PAYMENTS_UPDATE_REFUND_AT,
          [
              'type'        => 'response',
              'response'    => $response,
          ]);

        return $response;

    }

    public function processOtpSubmitPrivate($id, $hash, $input)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant(
            $id, $this->merchant);

        if ((empty($payment) === false) and
            ($payment->isExternal() === true))
        {
            return $this->app['pg_router']->otpSubmitPrivate($id, $input);
        }

        $data = $this->callback($id, $hash, $input);

        if ($this->merchant->isFeatureEnabled(Feature\Constants::OTP_SUBMIT_RESPONSE) === true) {
            Entity::verifyIdAndSilentlyStripSign($id);

            $payment = $this->repo->payment->findOrFailPublic($id);

            return array_merge($data, $payment->toArrayPublic());
        }

        return $data;
    }

    public function postPaymentMetaReference($input)
    {
        (new PaymentMeta\Validator)->validateInput('reference_id',$input);

        $fetchLast = ((isset($input[PaymentMeta\Validator::FETCH_LAST]) === true) and
                      ((bool)$input[PaymentMeta\Validator::FETCH_LAST] === true));

        unset($input[PaymentMeta\Validator::FETCH_LAST]);

        $result = $this->repo->payment_meta->fetchByParams($input);

        if (($fetchLast === true) and ($result->count() > 0))
        {
            // return last entity as an object in array to keep consistency in the response structure
            return [$result->last()];
        }

        return $result;
    }

    public function fetchPaymentEntity(string $id)
    {
        Entity::stripSignWithoutValidation($id);

        $payment = $this->repo->payment->findOrFailPublic($id);

        $paymentMerchantId = $payment->getMerchantId();

        if ($this->merchant->getId() !== $paymentMerchantId) {
            // if payment merchant is not same as context merchant, other valid possibility is that fetch is called by
            // the partner merchant of that submerchant
            $this->checkAuthMerchantAccessToEntity($paymentMerchantId);
        }

        return $payment;
    }

    public function getAuthenticationEntity($id)
    {
        $paymentId = Payment\Entity::verifyIdAndStripSign($id);

        $response = $this->app['card.payments']->fetchEntity('authentication', $paymentId);

        if ((isset($response['success']) === true) and ($response['success'] === false))
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND);
        }

        return $response;
    }

    public function getAuthenticationEntity3ds2($id)
    {
        $paymentId = Payment\Entity::verifyIdAndStripSign($id);

        $response = $this->app['card.payments']->fetchEntity('authentication', $paymentId);

        return $response;
    }

    public function getCardProtcolVersion($authenticationData)
    {
        if ($authenticationData !== null && isset($authenticationData['protocol_version'])) {
            if($authenticationData['protocol_version'] == '2.1.0' || $authenticationData['protocol_version'] == '2.2.0'){
                return "3DS2";
            }
            else {
                return "3DS1";
            }
        }
        return null;
    }

    public function getAuthenticationEntityForAcquirerData($id)
    {
        return $this->app['card.payments']->fetchEntity('authentication', $id);
    }

    public function getAuthorizationEntity($id)
    {
        $paymentId = Payment\Entity::verifyIdAndStripSign($id);

        $response = $this->app['card.payments']->fetchEntity('authorization', $paymentId);

        if ((isset($response["gateway"]) && $response["gateway"] === "fulcrum") &&
            (isset($response['status']) && ($response['status'] === Payment\Status::AUTHORIZED ||
                    $response['status'] === Payment\Status::CAPTURED))) {
            $response["reason_code"] = "00";
        }

        if ((isset($response['success']) === true) and ($response['success'] === false))
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND);
        }

        return $response;
    }

    public function getPaymentMetaByPaymentIdAction($paymentId, $actionType)
    {
        return $this->repo->payment_meta->findByPaymentIdAction($paymentId, $actionType);
    }

    protected function parseAttributesForPaymentTransferBatch(array & $input)
    {
        (new Transfer\Core())->parseNotesForBatch($input);

        if(empty($input[Transfer\Entity::ON_HOLD]) === false)
        {
            $input[Transfer\Entity::ON_HOLD] = $input[Transfer\Entity::ON_HOLD] === '1';
        }
        else
        {
            unset($input[Transfer\Entity::ON_HOLD]);
        }

        if(empty($input[Transfer\Entity::ON_HOLD_UNTIL]) === false)
        {
            $input[Transfer\Entity::ON_HOLD_UNTIL] = (int)$input[Transfer\Entity::ON_HOLD_UNTIL];
        }
        else
        {
            unset($input[Transfer\Entity::ON_HOLD_UNTIL]);
        }
    }

    public function sendNotification(array $input)
    {
        if ((isset($input['event']) === false) or
            (isset($input['event_type']) === false))
        {
            throw new Exception\BadRequestValidationFailureException("Event or Event Type is missing");
        }

        $event = $input['event'];

        $eventType = $input['event_type'];

        $payment = new Payment\Entity();

        if (isset($input['payment']['card']) === true)
        {
            $card = (new Card\Entity)->forceFill($input['payment']['card']);

            unset($input['payment']['card']);
        }

        unset($input['payment']['public_id']);

        $payment->forceFill($input['payment']);

        if ($payment->isRoutedThroughPaymentsUpiPaymentService() === true)
        {
            $rearchPayment = $input['payment'];

            (new Payment\Entity)->modifyInput($rearchPayment);

            $payment = (new Payment\Entity)->forceFill($rearchPayment);
        }

        $payment->setExternal(true);

        $merchant =  $this->repo->merchant->findByPublicId($payment->getMerchantId());

        $payment->merchant()->associate($merchant);

        // set the merchant in basic auth
        // this is required because base template data is read directly from basic auth
        // for sending template data on stork
        $this->app['basicauth']->setMerchant($merchant);

        if (($payment->isCard() === true) &&
            (isset($card) === true))
        {
            $payment->card()->associate($card);
        }

        if ($eventType === "webhook")
        {
            $processor = new Payment\Processor\Processor($merchant);

            $processor->setPayment($payment);

            switch ($event)
            {
                case "payment_created_event":
                    $processor->eventPaymentCreated();
                    break;
                case "payment_authorized_event":
                    $processor->eventPaymentAuthorized();
                    break;
                case "payment_captured_event":
                    $processor->eventPaymentCaptured();

                    if ($this->shouldTriggerInvoiceWebhooksForRearchPayments($payment->getMerchantId())) {
                        $processor->eventInvoicePaid();
                    }

                    break;
                case "payment_failed_event":
                    $processor->eventPaymentFailed(null);
                    break;
                case "order_paid":
                    $processor->eventOrderPaid();
                    break;
                default:
                    $this->trace->info(TraceCode::INVALID_WEBHOOK_EVENT_NAME_FROM_PG_ROUTER,
                        ["event_name" => $event]);
                    return;
            }

        }
        else if ($eventType === "mail")
        {
            if($payment->isFpx() && $payment->isFailed() &&
                ($payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION ||
                    $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING ||
                    $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)){
                $this->trace->info(TraceCode::FPX_EMAIL_SUPPRESS, [
                    'payment_id' => $payment['id'],
                ]);
                return;
            }

            if($payment->isWallet() && $payment->isFailed() &&
                ($payment->isGateway(Payment\Gateway::EGHL) === true ||
                    $payment->isGateway(Payment\Gateway::TNGD) === true) &&
                ($payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING ||
                    $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_WALLET_PAYMENT_PENDING ||
                    $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)){
                $this->trace->info(TraceCode::WALLET_EMAIL_SUPPRESS, [
                    'payment_id' => $payment['id'],
                ]);
                return;
            }

            if($payment->isDuitNowPay() && $payment->isFailed() &&
                ($payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION ||
                    $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING ||
                    $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)){
                $this->trace->info(TraceCode::DUITNOW_PAY_EMAIL_SUPPRESS, [
                    'payment_id' => $payment['id'],
                ]);
                return;
            }

            if($payment->isGiftcard() && $payment->isFailed() &&
                ($payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION ||
                    $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING ||
                    $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)){
                $this->trace->info(TraceCode::GIFTCARD_EMAIL_SUPPRESS, [
                    'payment_id' => $payment['id'],
                ]);
                return;
            }

            (new Notify($payment))->trigger($event);
        }
    }

    public function getWebhookPayload(array $input)
    {
        $event = $input['event'];

        $id = $input["id"];

        $payment = $this->repo->payment->findOrFail($id);

        $processor = new Payment\Processor\Processor($payment->merchant);

        $processor->setPayment($payment);

        return match ($event) {
            "payment_created_event" => $processor->getWebhookPayload("payment.created"),
            "payment_authorized_event" => $processor->getWebhookPayload("payment.authorized"),
            "payment_captured_event" => $processor->getWebhookPayload("payment.captured"),
            "payment_failed_event" => $processor->getWebhookPayload("payment.failed"),
            "order_paid" => $processor->getWebhookPayload("order.paid"),
            default => null,
        };
    }

    public function sendNotificationCron(array $input)
    {
        if ((isset($input['event']) === false) or
            (isset($input['event_type']) === false))
        {
            throw new Exception\BadRequestValidationFailureException("Event or Event Type is missing");
        }

        $event = $input['event'];

        $eventType = $input['event_type'];

        $paymentsArrString = $input['payments_arr'];

        $paymentsArr = explode(',', $paymentsArrString);

        for ($i = 0; $i < count($paymentsArr); $i++)
        {
            $currentPaymentId = $paymentsArr[$i];

            $payment = $this->repo->payment->findByPublicId($currentPaymentId);

            $merchant = $this->repo->merchant->findByPublicId($payment->getMerchantId());

            // set the merchant in basic auth
            // this is required because base template data is read directly from basic auth
            // for sending template data on stork
            $this->app['basicauth']->setMerchant($merchant);

            if ($payment->isCard() === true)
            {
                $cardDetails = $this->repo->card->fetchForPayment($payment);

                $payment->card()->associate($cardDetails);
            }

            $payment->merchant()->associate($merchant);

            if ($eventType === "webhook")
            {
                $processor = new Payment\Processor\Processor($merchant);

                $processor->setPayment($payment);

                switch ($event)
                {
                    case "payment_created_event":
                        $processor->eventPaymentCreated();
                        break;
                    case "payment_authorized_event":
                        $processor->eventPaymentAuthorized();
                        break;
                    case "payment_captured_event":
                        $processor->eventPaymentCaptured();
                        break;
                    case "payment_failed_event":
                        $processor->eventPaymentFailed(null);
                        break;
                    case "order_paid":
                        $processor->eventOrderPaid();
                        break;
                    default:
                        $this->trace->info(TraceCode::INVALID_WEBHOOK_EVENT_NAME_FROM_PG_ROUTER,
                            ["event_name" => $event]);
                        return;
                }

            }
            else if ($eventType === "mail")
            {
                if($payment->isFpx() && $payment->isFailed() &&
                    ($payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION ||
                        $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING ||
                        $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)){
                    $this->trace->info(TraceCode::FPX_EMAIL_SUPPRESS, [
                        'payment_id' => $payment['id'],
                    ]);
                    return;
                }

                if($payment->isWallet() && $payment->isFailed() &&
                    ($payment->isGateway(Payment\Gateway::EGHL) === true ||
                        $payment->isGateway(Payment\Gateway::TNGD) === true) &&
                    ($payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING ||
                        $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_WALLET_PAYMENT_PENDING ||
                        $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)){
                    $this->trace->info(TraceCode::WALLET_EMAIL_SUPPRESS, [
                        'payment_id' => $payment['id'],
                    ]);
                    return;
                }

                if($payment->isDuitNowPay() && $payment->isFailed() &&
                    ($payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION ||
                        $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING ||
                        $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)){
                    $this->trace->info(TraceCode::DUITNOW_PAY_EMAIL_SUPPRESS, [
                        'payment_id' => $payment['id'],
                    ]);
                    return;
                }

                if($payment->isGiftcard() && $payment->isFailed() &&
                    ($payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION ||
                        $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING ||
                        $payment->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT)){
                    $this->trace->info(TraceCode::GIFTCARD_EMAIL_SUPPRESS, [
                        'payment_id' => $payment['id'],
                    ]);
                    return;
                }

                (new Notify($payment))->trigger($event);
            }
        }
    }

    public function internalPricingFetchForPayment($id, $input)
    {
        if (isset($id) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Payment Id is a required field");
        }

        $payment = $this->repo->payment->findByPublicId($id);
        $this->merchant = $this->repo->merchant->findOrFail($payment['merchant_id']);

        $processor = $this->getNewProcessor($this->merchant);
        $data = $processor->processAndReturnPaymentFees( $payment);

        try
        {
            if ($this->merchant->getCountry() === 'IN')
            {
                $data['zero_pricing_rule_id'] = (new Fee)->getZeroPricingPlanRule($payment)->getId();
            }
        }
        catch (\Throwable $exception){}

        $esInput['payment_ids'] = array($id);
        $this->paymentsCardEsSyncCron($esInput);

        return $data;
    }

    public function internalPricingFetch( $entityType, $entityId, $input): array
    {
        if (isset($entityId) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Entity Id is a required field");
        }

        if (isset($entityType) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Entity type is a required field");
        }

        switch($entityType)
        {
            case "payment":
                $payment = $this->repo->payment->findByPublicId($entityId);
                $this->merchant = $this->repo->merchant->findOrFail($payment->getMerchantId());

                $processor = $this->getNewProcessor($this->merchant);
                $data = $processor->processAndReturnPaymentFees($payment);

                try
                {
                    if ($this->merchant->getCountry() === 'IN')
                    {
                        $data['zero_pricing_rule_id'] = (new Fee)->getZeroPricingPlanRule($payment)->getId();
                    }
                }
                catch (\Throwable $exception){}

                break;

            case "refund":
                $refund = $this->repo->refund->findByPublicId($entityId);

                [$fee, $tax, $feeSplit] = (new Fee())->calculateMerchantFees($refund);

                $data = [
                    'original_amount'  => $refund->getAmount(),
                    'fees'            => $fee,
                    'razorpay_fee'    => $fee - $tax,
                    'tax'             => $tax,
                    'amount'          => $refund->getAmount() + $fee,
                    'currency'        => $refund->getCurrency(),
                    'fee_bearer'      => $refund->getFeeBearer(),
                    'fee_split'       => $feeSplit,
                ];
                break;

            default:
                throw new Exception\BadRequestValidationFailureException("Entity type is invalid");
        }

        return $data;
    }

    public function internalRiskNotificationForRearch($id, $input)
    {
        if (isset($id) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Payment Id is a required field");
        }

        $payment = $this->repo->payment->findByPublicId($id);


        if (empty($payment) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Payment Id is a required field");
        }

        $merchant = $payment->merchant;

        $riskData = $input['risk'];

        (new Fraud\Notify())->notifyOpsIfNeeded($id, $merchant, $riskData[ShieldConstants::TRIGGERED_RULES]);

        if ($riskData[Risk\Entity::FRAUD_TYPE] === Risk\Type::CONFIRMED)
        {
            $data = [
                'payment_id' => $payment->getPublicId(),
                'method'     => $payment->getMethod(),
                'risk_data'  => $riskData,
            ];

            $errorCode = $this->getErrorCodeFromTriggeredRules($riskData[ShieldConstants::TRIGGERED_RULES]);

            (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, $errorCode);
        }
    }

    public function addVerifyDisabledGateway(array $input)
    {
        $gateways = $input['gateways'];

        $ttl = $input['ttl'] ?? 30;

        $expireTime = Carbon::now()->getTimestamp() + ($ttl * 60);

        foreach ($gateways as $gateway)
        {
            $this->redis->hSet(
                Verify::GATEWAY_BLOCK_CACHE_KEY,
                $gateway,
                $expireTime
            );
        }
        return true;
    }

    public function verifyPaymentNewRoute($id)
    {
        $data = [];

        $statusToVerify = [Payment\Status::FAILED, Payment\Status::CREATED, Payment\Status::AUTHORIZED, Payment\Status::CAPTURED];

        $payment = null;

        try
        {
            $payment =  $this->repo->payment->findOrFail($id);
        }
        catch (\Throwable $exception){}

        if (isset($payment) === false)
        {
            $data['retry_verify'] = false;

            $this->trace->info(
                TraceCode::PAYMENT_NOT_FOUND_FOR_VERIFY,
                [
                    'payment_id' => $id
                ]);

            return $data;
        }

        // Skipping Payment Verification for non card authorized payments.
        // to prevent issue in updating gateway entities for failed verify authorized payments.
        if (($payment->getMethod() !== Method::CARD) and
            (($payment->getStatus() === Payment\Status::AUTHORIZED) or
                ($payment->getStatus() === Payment\Status::CAPTURED)))
        {
            $data['retry_verify'] = false;
            $data['payment'] = $payment;

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_DISABLED_FOR_SUCCESS_NON_CARD_PAYMENTS,
                [
                    'payment_id' => $id,
                    'method' => $payment->getMethod(),
                    'gateway' => $payment->getGateway()
                ]);

            return $data;
        }

        if((in_array($payment->getGateway(),Payment\Gateway::$fileBasedEMandateDebitGateways)=== true) and
            ($payment->getRecurringType() === Payment\RecurringType::AUTO))
        {
            $this->traceRetryVerifyFalse($id, TraceCode::PAYMENT_VERIFY_STOPPED_FOR_FILE_BASED_DEBITS, $data);

            $data['retry_verify'] = false;

            return $data;
        }

        if (($payment->isBharatQr() === true) or ($payment->isUpiTransfer() === true))
        {
            $this->traceRetryVerifyFalse($id, TraceCode::PAYMENT_VERIFY_STOPPED_FOR_BHARAT_QR_AND_VPA, $data);

            $data['retry_verify'] = false;

            return $data;
        }

        $extraProperties = [
            'is_pushed_to_kafka'  => $payment->getIsPushedToKafka(),
        ];

        $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_SCHEDULER_VERIFY_INITIATED, $payment, null, $extraProperties);

        if(array_search($payment->getStatus(), $statusToVerify) === false)
        {
            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_STATUS_NOT_FOR_VERIFY, $payment, null, $extraProperties);

            $this->traceRetryVerifyFalse($id, TraceCode::STATUS_NOT_FOR_VERIFY, $data);
        }
        else if (empty($payment->getGateway()) === true)
        {
            $this->traceRetryVerifyFalse($id, TraceCode::PAYMENT_VERIFY_GATEWAY_NULL, $data);
        }
        else if ((new Verify())->isFinalErrorCode($payment) === true)
        {
            $this->traceRetryVerifyFalse($id, TraceCode::FINAL_ERROR_CODE, $data);
        }
        else
        {
            $filter = ($payment->isCreated() === true) ? Payment\Verify\Filter::PAYMENTS_CREATED : Payment\Verify\Filter::PAYMENTS_FAILED;

            try
            {
                $result = (new Verify())->verifyPaymentNewRoute($payment, $filter);

//                $this->trace->info(
//                    TraceCode::VERIFY_NEW_ROUTE_RESULT,
//                    [
//                        'payment_id' => $id,
//                        'result'     => $result,
//                    ]);

                if (($payment->getStatus() === Payment\Status::CAPTURED) or
                    ($payment->getStatus() === Payment\Status::AUTHORIZED))
                {
                    $data['retry_verify'] = false;
                }
                else
                {
                    $data['retry_verify'] = true;
                }
            }
            catch(\Exception $ex)
            {
                $this->trace->traceException($ex);

                $data['retry_verify'] = true;
            }
        }

        $data['payment'] = $payment;

        return $data;
    }

    public function timeoutPaymentsNew($paymentId)
    {
        $now = time();

        $data = [];
        $data['retry_timeout'] = false;

        $payment = null;

        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);
        }
        catch (\Throwable $exception){}

        if (isset($payment) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_TIMEOUT_SCHEDULER_INVALID_ID,
                [
                    'payment_id' => $paymentId
                ]);

            return $data;
        }

        $data['payment'] = $payment->toArrayPublic();

        $extraProperties = [
            'is_pushed_to_kafka'  => $payment->getIsPushedToKafka(),
        ];

        $shouldTimeout = $payment->shouldTimeout($now);

        $this->trace->info(
            TraceCode::PAYMENT_TIMEOUT_SCHEDULER_INITIATED,
            [
                'payment_id' => $payment->getId()
            ]);

        $this->app['diag']->trackTimeoutPaymentEvent(EventCode::PAYMENT_TIMEOUT_SCHEDULER_INITIATED, $payment, null, $extraProperties);

        if($payment->isCreated() !== true)
        {
            $this->app['diag']->trackTimeoutPaymentEvent(EventCode::PAYMENT_TIMEOUT_SCHEDULER_STATUS_FAILURE, $payment, null, $extraProperties);

            $this->trace->info(
                TraceCode::PAYMENT_TIMEOUT_SCHEDULER_STATUS_NOT_CREATED,
                [
                    'payment_id'        => $payment->getId(),
                    'payment_status'    => $payment->getStatus()
                ]);

            return $data;
        }
        else if($shouldTimeout !== true)
        {
            $this->app['diag']->trackTimeoutPaymentEvent(EventCode::PAYMENT_TIMEOUT_SCHEDULER_TIME_FAILURE, $payment, null, $extraProperties);

            $this->trace->info(
                TraceCode::PAYMENT_TIMEOUT_SCHEDULER_SHOULD_NOT_TIMEOUT,
                [
                    'payment_id' => $payment->getId()
                ]);

            return $data;
        }
        else
        {
            $this->trace->info(
                TraceCode::PAYMENT_TIMEOUT_SCHEDULER_PROCESSING_STARTED,
                [
                    'payment_id'       => $paymentId,
                ]);

            $paymentProcessor = new Payment\Processor\Processor($payment->merchant);

            $resource = $this->getNewProcessor($payment->merchant)->getCallbackMutexResource($payment);

            $data = $this->mutex->acquireAndRelease(
                $resource,
                function() use ($payment, $data, $extraProperties, $paymentProcessor)
                {
                    $orderMutex = $paymentProcessor->getCallbackOrderMutexResource($payment);

                    return $this->mutex->acquireAndRelease($orderMutex,
                        function() use ($payment, $data, $extraProperties, $paymentProcessor)
                        {
                            return $this->timeoutPaymentProcess($payment, $data, $extraProperties, $paymentProcessor);
                        },  self:: MUTEX_LOCK_TTL,ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,self:: MUTEX_RETRY_COUNT, self:: MUTEX_MIN_RETRY_DELAY, self:: MUTEX_MAX_RETRY_DELAY);
                },
            );
        }

        return $data;
    }

    private function timeoutPaymentProcess($payment, $data, $extraProperties, $paymentProcessor)
    {
        $this->repo->transaction(function () use ($payment, & $data, $extraProperties, $paymentProcessor)
        {
            $this->repo->payment->lockForUpdateAndReload($payment);

            try
            {
                if ($payment->getMethod() === Payment\Method::CARD and
                    $payment->isRecurring() and
                    $payment->getRecurringType() === Payment\RecurringType::AUTO and
                    isset($payment->token->cardMandate) and
                    $payment->token->cardMandate->getMandateHub() === IIN\MandateHub::MANDATE_HQ)
                {
                    $paymentProcessor->setPayment($payment)
                        ->failMandateHQPaymentAFANotApproved($payment);
                }
                else {
                    $paymentProcessor->setPayment($payment)
                        ->timeoutPayment();
                }

                if ($payment->getMethod() === Payment\Method::NACH and
                    $payment->isRecurring() and
                    $payment->getRecurringType() === Payment\RecurringType::INITIAL)
                {
                    $this->moveTimedoutRecurringNachPaymentTokensToRejectedState($payment);
                }

                $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHORIZATION_DROPPED, $payment);

                $payment->reload();

                $data['payment'] = $payment->toArrayPublic();

                $this->trace->info(
                    TraceCode::PAYMENT_TIMEOUT_SCHEDULER_SUCCESS,
                    [
                        'payment_id'       => $payment->getId(),
                        'payment_status'   => $payment->getStatus()
                    ]);

                $this->app['diag']->trackTimeoutPaymentEvent(EventCode::PAYMENT_TIMEOUT_SCHEDULER_SUCCESS, $payment, null, $extraProperties);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e);

                $this->app['diag']->trackTimeoutPaymentEvent(EventCode::PAYMENT_TIMEOUT_SCHEDULER_ERROR, $payment, $e, $extraProperties);

                $data['retry_timeout'] = true;
            }
        });

        return $data;
    }

    public function updateReference6($id)
    {
        $response = [];
        if (isset($id) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_NOT_FOUND_TO_UPDATE_REFERENCE6,
                [
                    'payment_id' => $id
                ]);

            $response['reference6_updated'] = false;

            return $response;
        }

        $isReference6Updated = $this->mutex->acquireAndRelease($id,
            function() use ($id)
            {
                $payment = null;

                try
                {
                    $payment = $this->repo->payment->findOrFail($id);
                }
                catch (\Throwable $exception){}

                if (isset($payment) === true)
                {
                    $payment->setIsPushedToKafka(null);

                    $this->repo->saveOrFail($payment);

                    $this->trace->info(TraceCode::PAYMENT_REFERENCE6_MARKED_NULL,
                        [
                            'payment_id' => $id
                        ]);

                    return true;
                }

                $this->trace->info(
                    TraceCode::PAYMENT_NOT_FOUND_TO_UPDATE_REFERENCE6,
                    [
                        'payment_id' => $id
                    ]);

                return false;
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $response['reference6_updated'] = $isReference6Updated;

        return $response;
    }

    /**
     * Used in two places [GetFlows , PaymentCreate]
     * @param $iinEntity
     * @param Merchant\Entity $merchant
     * @return bool
     */
    public function isAddressRequired($library, $iinEntity, Merchant\Entity $merchant):bool
    {
        if ($this->isLibrarySupportedForAddressCollection($library) and
            ($merchant !== null) and ($merchant->isInternational() === true)
            and ($merchant->isAddressRequiredEnabled() === true)
        ) {

            if ($iinEntity !== null)
            {
                if(IIN\IIN::isInternational($iinEntity->getCountry(), $merchant->getCountry()) === true)
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $id
     * @param $message
     * @param &$data
     */
    protected function traceRetryVerifyFalse($id, $message, &$data)
    {
        $data['retry_verify'] = false;

        $this->trace->info(
            $message,
            [
                'payment_id' => $id
            ]);
    }

    public function getPaymentIdFromARNorRRN(array $allArn,array $arnVsRrn)
    {
        // the resultant array
        $arnVsPaymentDetail = [];

        // the arn from first query
        $arnFromQuery = [];

        /*
         *  For ARN
         *  way to fetch Data from Data Lake payments_fact
        */
        $query1 = "select payments_reference1, payments_id, payments_merchant_id from payments_auth_fact where payments_reference1 in ('" . implode("','", $allArn) . "')";

        $pinotService = $this->app['eventManager'];

        try {

            $content = [
                'query' => $query1
            ];

            $data1 = $pinotService->getDataFromPinot($content);

            $this->trace->info(TraceCode::DISPUTE_CHARGEBACK_PINOT_RESPONSE, [
                'data1'      => $data1,
            ]);

        } catch (\Throwable $e) {
            // Not logging anything here as the datalake.presto client takes care of that
        }

        if (isset($data1) === false || array_key_exists(0, $data1) === false)
        {
            $this->trace->info(TraceCode::PAYMENT_DATA_NOT_FOUND_ON_PINOT, [
                'arn-data' => $allArn,
                'data'     => $data1  ?? [],
            ]);
        }

        foreach ($data1 as $item)
        {
            $item = $pinotService->parsePinotDefaultType($item, HarvesterConstants::PINOT_TABLE_PAYMNETS_AUTH_FACT);

            //  data entered for payment ids array
            if (empty($item['payments_reference1']) === false and
                $item ['payments_id'] !== null)
            {
                $arnVsPaymentDetail[$item['payments_reference1']] = [
                    'payment_id'  => $item ['payments_id'],
                    'merchant_id' => $item ['payments_merchant_id']
                ];
            }
        }

        // required arn for the second query
        $requiredArn = array_diff($allArn, array_keys($arnVsPaymentDetail));

        // required rrn for the second query
        $requiredRrn = [];

        foreach ($arnVsRrn as $arn => $rrn)
        {
            if (in_array($arn, $requiredArn) === true)
            {
                if (strlen($rrn) !== 0) {
                    array_push($requiredRrn, $rrn);
                }
            }
        }

        // we got all payments ids
        if (sizeof($requiredRrn) === 0)
        {
            return $arnVsPaymentDetail;
        }

        /*
         *  For RRN
         *  way to fetch Data from pinot payments_fact
        */
        $query2 = "select authorization_rrn, authorization_payment_id, payments_merchant_id from payments_auth_fact where authorization_rrn in ('" . implode("','", $requiredRrn) . "')";

        try {
            $content = [
                'query' => $query2
            ];

            $data2 = $pinotService->getDataFromPinot($content);

            $this->trace->info(TraceCode::DISPUTE_CHARGEBACK_PINOT_RESPONSE, [
                'data2'     => $data2,
            ]);
        } catch (\Throwable $e) {
            // Not logging anything here as the datalake.presto client takes care of that
        }

        if (isset($data2) === false || array_key_exists(0, $data2) === false)
        {
            $this->trace->info(TraceCode::PAYMENT_DATA_NOT_FOUND_ON_PINOT, [
                'rrn-data' => $requiredRrn,
                'data'     => $data2 ?? [],
            ]);
        }

        $rrnVsPaymentData =  [];

        foreach ($data2 as $dataItem)
        {
            $dataItem = $pinotService->parsePinotDefaultType($dataItem, HarvesterConstants::PINOT_TABLE_PAYMNETS_AUTH_FACT);

            // match the rrn with the input arn
            //  data entered for payment ids array
            if (empty($dataItem['authorization_rrn']) === false and
                empty($dataItem['authorization_payment_id']) === false)
            {
                // insert the data in arn vs payments from second query
                $rrnVsPaymentData[$dataItem['authorization_rrn']] = [
                    'payment_id'  => $dataItem['authorization_payment_id'],
                    'merchant_id' => $dataItem['payments_merchant_id']
                ];
            }
        }
        // add arn values for the second query
        foreach ($arnVsRrn as $arn => $rrn)
        {
            if (array_key_exists($rrn,$rrnVsPaymentData)){

                $arnVsPaymentDetail[$arn] = $rrnVsPaymentData[$rrn];
            }
        }

        foreach ($allArn as $arn)
        {
            // at last put null for the arns we didn't find a value for
            if (array_key_exists($arn, $arnVsPaymentDetail) === false)
            {
                $arnVsPaymentDetail[$arn] = [
                    'payment_id'  => null,
                    'merchant_id' => null
                ];
            }
        }

        return $arnVsPaymentDetail;
    }

    /**
     * isRazorxTreatmentForRefundsV1_1: Used within jobs.
     *
     * @param $merchantId
     * @return bool
     */
    public function isRazorxTreatmentForRefundsV1_1(string $merchantId = ""): bool
    {
        // handling for internal routes,
        // where merchantId or merchant obj is empty, then just return true.
        //
        $mid = (empty($this->merchant) === false) ? $this->merchant->getId() : $merchantId;
        if (empty($mid) === true)
        {
            return true;
        }

        // excluding this flow for initial ramp up, will be changed later
        return false;

        // $variant = $this->app->razorx->getTreatment(
        //         $mid,
        //         Merchant\RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1,
        //         $this->mode
        // );

        // return (strtolower($variant) === RefundConstants::RAZORX_VARIANT_ON);
    }

    /**
     * Used in two places [GetFlows , PaymentCreate]
     * @param $iinEntity
     * @param Merchant\Entity $merchant
     * @return bool
     */
    public function isAddressWithNameRequired($library, $input, Merchant\Entity $merchant): bool
    {
        if ($this->isLibrarySupportedForAddressCollection($library) and
            ($merchant !== null) and ($merchant->isInternational() === true) and
            ($merchant->isAddressWithNameRequiredEnabled() === true))
        {
            if ((isset($input['provider']) === true) and
                (in_array($input['provider'], Payment\Gateway::ADDRESS_REQUIRED_APPS) === true))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Create payment for post recon edge cases like
     * amount mismatch,unexpected payment,rrn mismatch etc..
     * @param array $input
     * @return array
     */
    public function createUpiUnexpectedPayment(array $input)
    {
        $unexpectedPaymentId = null;

        (new Payment\Validator)->validateInput('create_upi_unexpected_payment', $input);

        $npciReferenceId = $input['upi']['npci_reference_id'];

        $gateway = $input['terminal']['gateway'];

        try
        {
            $this->trace->info(
                TraceCode::UPI_UNEXPECTED_PAYMENT_INITIATED,
                [

                    'npci_reference_id'         => $npciReferenceId,
                    'unexpected_payment_ref_id' => $input['upi']['merchant_reference'],
                    'gateway'                   => $gateway
                ]);

            /* This check provides early action for unexpected payments created in callback flow. If the upi_entity is
            already present, then we can simply return without entering the payment creation flow by unexpectedCallback.
            TODO : Revisit this logic against isDuplicateUnexpectedPaymentV2
            */

            $upiEntity = null;

            $merchant_reference = $input['upi']['merchant_reference'];

            // This checks if merchant_reference can be used for fetching unexpected payments
            if (($this->shouldUseMerchantReferenceForUnexpectedPayment($gateway) === true) and
                (empty($merchant_reference) === false))
            {
                $upiEntity = $this->repo->upi->fetchAllByMerchantReferenceAndNpciReferenceIdAndGateway($merchant_reference, $npciReferenceId, $gateway);
            }
            else
            {
                $upiEntity = $this->repo->upi->findAllByNpciReferenceIdAndGateway($npciReferenceId, $gateway);
            }

            if ((empty($upiEntity) === false) and ($upiEntity->count() > 1))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        'payment_id'        => null,
                        'npci_reference_id' => $npciReferenceId,
                        'gateway'           => $gateway,
                    ],
                    'Multiple payments with same RRN'
                );
            }

            if ((empty($upiEntity) === false) and ($upiEntity->count() === 1))
            {
                if ($upiEntity->first()->getAmount() === (int) ($input['payment']['amount']))
                {
                    $unexpectedPaymentId = $upiEntity->first()->getPaymentId();

                    $payment = null;

                    try
                    {
                        $payment = $this->repo->payment->findOrFail($unexpectedPaymentId);
                    }
                    catch (\Throwable $exception){}

                    $this->handleUnExpectedPaymentRefundInRecon($payment);

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR,
                        null,
                        [
                            'payment_id'        => $unexpectedPaymentId,
                            'npci_reference_id' => $npciReferenceId,
                            'gateway'           => $gateway,
                        ],
                        'Duplicate Unexpected payment with same amount'
                    );
                }
            }

            if ($this->shouldCreateQrPaymentFromUnexpectedUpiPayment($gateway) === true)
            {
                $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $input['terminal']);

                if (($terminal !== null) and ($terminal->isQrV2Terminal() === true))
                {
                    $paymentId = (new BharatQr\Service())->getPaymentIdForQrPayment($input);

                    if(empty($paymentId) === false)
                    {
                        return [
                            'payment_id' => $paymentId,
                            'success'    => true,
                        ];
                    }

                    try
                    {
                        $standardInput = $this->prepareInputForQrFromRecon($input);

                        $response = (new BharatQr\Service())->processPaymentInternal($standardInput, $gateway, $terminal);
                    }
                    catch (\Throwable $ex)
                    {
                        $this->trace->traceException(
                            $ex,
                            Trace::ERROR,
                            TraceCode::UPI_UNEXPECTED_PAYMENT_FAILED,
                            [
                                'npci_reference_id' => $npciReferenceId,
                                'gateway'           => $gateway,
                            ]
                        );
                        throw $ex;

                    }

                    if(empty($response) === false)
                    {
                        return [
                            'payment_id' => Entity::silentlyStripSign($response['payment']['id']),
                            'success'    => true,
                        ];
                    }
                }
            }

            if ($this->shouldProcessUpsUnexpectedPayment($gateway) === true)
            {
                $this->processUpsUnexpectedPayment($input);
            }

            $response = $this->unexpectedCallback($input, $input['upi']['merchant_reference'], $gateway);

            if (empty($response['payment_id']) === false)
            {
                $unexpectedPaymentId = $response['payment_id'];

                $payment = null;

                try
                {
                    $payment = $this->repo->payment->findOrFail($unexpectedPaymentId);
                }
                catch (\Throwable $exception){}

                $this->handleUnExpectedPaymentRefundInRecon($payment);

                $this->trace->info(
                    TraceCode::UPI_UNEXPECTED_PAYMENT_CREATED,
                    [
                        'payment_id'        => $unexpectedPaymentId,
                        'npci_reference_id' => $npciReferenceId,
                        'gateway'           => $gateway,

                    ]);
            }
            else
            {
                $this->trace->info(
                    TraceCode::UPI_UNEXPECTED_PAYMENT_FAILED,
                    [
                        'npci_reference_id' => $npciReferenceId,
                        'gateway'           => $gateway,

                    ]);
            }

            return [
                'payment_id' => $unexpectedPaymentId,
                'success' => (empty($unexpectedPaymentId) === false),
            ];
        }
        catch (BadRequestException $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::UPI_UNEXPECTED_PAYMENT_FAILED,
                [
                    'npci_reference_id'         => $npciReferenceId,
                    'gateway'                   => $gateway,
                    'payment_id'                => $unexpectedPaymentId,
                ]
            );

            $ex->getError()->setMetadata(['payment_id' => $unexpectedPaymentId]);

            throw $ex;
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::UPI_UNEXPECTED_PAYMENT_FAILED,
                [
                    'npci_reference_id'         => $npciReferenceId,
                    'gateway'                   => $gateway,
                ]
            );

            throw $ex;
        }
    }

    /**
     * Creates payment for post recon edge case amount mismatch.
     * @param array $input
     * @return array
     */
    public function createUpiPaymentAmountMismatch(array $input)
    {
        $unexpectedPaymentId = null;

        (new Payment\Validator)->validateInput('create_upi_unexpected_payment', $input);

        $npciReferenceId = $input['upi']['npci_reference_id'];

        $gateway = $input['terminal']['gateway'];

        $amount = $input['payment']['amount'];

        $merchantReference = $input['upi']['merchant_reference'];

        try
        {
            $this->trace->info(
                TraceCode::UPI_AMOUNT_MISMATCH_PAYMENT_CREATION_INITIATED,
                [
                    'merchant_reference'        => $merchantReference,
                    'npci_reference_id'         => $npciReferenceId,
                    'unexpected_payment_ref_id' => $input['upi']['merchant_reference'],
                    'amount'                    => $input['payment']['amount'],
                    'gateway'                   => $gateway
                ]);

            $upiEntity = $this->repo->upi->findAllByNpciReferenceIdAmountAndGatewayAndMerchantReference($npciReferenceId, $gateway,$amount, $merchantReference);

            if ((empty($upiEntity) === false) and ($upiEntity->count() > 0))
            {

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        'amount'            => $amount,
                        'npci_reference_id' => $npciReferenceId,
                        'merchant_reference'=> $merchantReference,
                        'gateway'           => $gateway,
                    ],
                    'Duplicate Payments Found for amount mismatch'
                );
            }

            $response = $this->unexpectedCallback($input, $input['upi']['merchant_reference'], $gateway);

            if (empty($response['payment_id']) === false)
            {
                $unexpectedPaymentId = $response['payment_id'];

                $payment = null;

                try
                {
                    $payment = $this->repo->payment->findOrFail($unexpectedPaymentId);
                }
                catch (\Throwable $exception){}

                $this->trace->info(
                    TraceCode::UPI_AMOUNT_MISMATCH_PAYMENT_CREATED,
                    [
                        'payment_id'        => $unexpectedPaymentId,
                        'amount'            => $amount,
                        'merchant_reference'=> $merchantReference,
                        'npci_reference_id' => $npciReferenceId,
                        'gateway'           => $gateway,

                    ]);
            }
            else
            {
                $this->trace->info(
                    TraceCode::UPI_AMOUNT_MISMATCH_PAYMENT_CREATION_FAILED,
                    [
                        'merchant_reference'=> $merchantReference,
                        'amount'            => $amount,
                        'npci_reference_id' => $npciReferenceId,
                        'gateway'           => $gateway,

                    ]);
            }

            return [
                'payment_id' => $unexpectedPaymentId,
                'success' => (empty($unexpectedPaymentId) === false),
            ];
        }
        catch (BadRequestException $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::UPI_AMOUNT_MISMATCH_PAYMENT_CREATION_FAILED,
                [
                    'merchant_reference'        => $merchantReference,
                    'amount'                    => $amount,
                    'npci_reference_id'         => $npciReferenceId,
                    'gateway'                   => $gateway,
                    'payment_id'                => $unexpectedPaymentId,
                ]
            );

            $ex->getError()->setMetadata(['payment_id' => $unexpectedPaymentId]);

            throw $ex;
        }
    }

    /**
     * Authorizes failed payment based on ART input
     * [force_authorize_failed,verify_authorize_failed]
     * @param array $input
     * @return array
     * @throws BadRequestException
     */
    public function authorizeFailedUpiPayment(array $input)
    {
        $fields = [
            EntityConstants::UPI,
            EntityConstants::PAYMENT,
            Entity::META,
        ];

        $input = array_only($input, $fields);

        (new Payment\Validator)->validateInput('authorize_failed_upi_payment', $input);

        $paymentId = $input['payment']['id'];

        $gateway = $input['upi']['gateway'];

        $payment = $this->repo->payment->findOrFail($paymentId);

        if (($payment !== null) and
            ($payment->getAmount() !== (int) $input['payment']['amount']))
        {
            throw new Exception\BadRequestValidationFailureException(
                Error\PublicErrorDescription::BAD_REQUEST_AMOUNT_MISMATCH,
                Payment\Entity::AMOUNT,
                [
                    'payment_entity_amount' => $payment->getAmount(),
                    'input_amount'          => $input['payment']['amount'],
                    'payment_id'            => $payment->getId(),
                ]);
        }

        if ($payment->isFailed() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Non failed payment given for authorization');
        }

        if (($input['meta']['force_auth_payment'] === true) and
            ($this->isForceAuthAllowed($gateway) ===true) and
            ($this->isUpiAutopayForceAuthAllowed($payment) === true))
        {
            if($payment->isUpiRecurring() === true)
            {
                // skipping verify call and doing force authorization for recon
                // need to add some details as we are skipping verify call and some flow
                $input['upi']['internal_status'] = UpiMetadata\InternalStatus::AUTHORIZED;
                $input['upi_mandate'] = [];
            }

            $resource = $this->getNewProcessor($payment->merchant)->getCallbackMutexResource($payment);

            return $this->mutex->acquireAndRelease($resource,
                function() use ($payment, $input) {
                    return $this->forceAuthorizeUpiPayment($payment, $input);
                },
                20,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }
        else
        {
            return $this->verifyAuthorizeFailedPayment($payment, $input);
        }

    }

    private function isUpiAutopayForceAuthAllowed($payment)
    {
        if($payment['recurring'] === false)
        {
            return true;
        }
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.enable_force_auth_on_upi_autopay')
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(TraceCode::UPI_AUTOPAY_SPLITZ_FAILURE,
                [
                    'error' => $ex->getMessage(),
                    'experiment' => "enable_force_auth_on_upi_autopay"
                ]
            );
        }

        return false;
    }

    public function getPaymentIdFromInput($input)
    {
        $paymentId = $input['payment']['id'];

        if ($input['cardless_emi']['gateway'] == Payment\Processor\CardlessEmi::LIQUILOANS) {

            $urn =  $input['cardless_emi'][CardlessEmiService::PROVIDER_REFERENCE_NUMBER];

            $request = [
                'gateway' => Payment\Processor\CardlessEmi::LIQUILOANS,
                CardlessEmiService::PROVIDER_REFERENCE_NUMBER => $urn
            ];

            $paymentId = $this->app['nbplus.payments']->fetchPaymentIdFromProviderReferenceNumber($request);

            if ($paymentId == null) {
                throw new Exception\BadRequestValidationFailureException(
                    Error\PublicErrorDescription::BAD_REQUEST_PAYMENT_NOT_FOUND
                );
            }
        }

        return $paymentId;
    }


    /**
     * Authorizes failed payment based on ART input
     * [force_authorize_failed,verify_authorize_failed]
     * @param array $input
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws \Exception
     */
    public function authorizeFailedNbplusPayment(array $input)
    {
        $fields = [
            EntityConstants::WALLET,
            EntityConstants::NETBANKING,
            EntityConstants::CARDLESS_EMI,
            EntityConstants::PAYMENT,
            EntityConstants::PAYLATER,
            Entity::META,
        ];

        $input = array_only($input, $fields);

        switch ($input['payment']['method'])
        {
            case Payment\Method::NETBANKING;
                (new Payment\Validator)->validateInput('authorize_failed_netbanking_payment', $input);
                break;
            case Payment\Method::WALLET:
                (new Payment\Validator)->validateInput('authorize_failed_wallet_payment', $input);
                break;
            case Payment\Method::CARDLESS_EMI:
                (new Payment\Validator)->validateInput('authorize_failed_cardless_emi_payment', $input);
                break;
            case Payment\Method::PAYLATER:
                (new Payment\Validator)->validateInput('authorize_failed_paylater_payment', $input);
                break;
            default:
                throw new Exception\BadRequestValidationFailureException(
                    Error\PublicErrorDescription::BAD_REQUEST_INVALID_PAYMENT_METHOD
                );
        }

        $paymentId = $this->getPaymentIdFromInput($input);

        $payment = $this->repo->payment->findOrFail($paymentId);

        $gateway = $payment->getGateway();

        if (($payment !== null) and
            ($payment->getAmount() !== (int) $input['payment']['amount']))
        {
            throw new Exception\BadRequestValidationFailureException(
                Error\PublicErrorDescription::BAD_REQUEST_AMOUNT_MISMATCH,
                Payment\Entity::AMOUNT,
                [
                    'payment_entity_amount' => $payment->getAmount(),
                    'input_amount'          => $input['payment']['amount'],
                    'payment_id'            => $payment->getId(),
                ]);
        }

        if (($input['meta']['force_auth_payment'] === true) and
            ($this->isForceAuthAllowed($gateway) === true))
        {
            return $this->forceAuthorizeNbplusPayment($payment, $input);
        }
        else
        {
            return $this->verifyAuthorizeFailedPayment($payment, $input);
        }

    }

   public function reconCreateCardTransaction (array $input)
   {
        $this->trace->info(
                    TraceCode::ART_PAYMENT_CREATE_TRANSACTION_REQUEST,
                    [
                        'input'            => $input,
                    ]);

        (new Payment\Validator)->validateInput('create_transaction_authorized_card_payment', $input);

        $paymentId = $input['payment_id'];

        $payment = $this->repo->payment->findOrFail($paymentId);

        if ($payment->isMethodCardOrEmi() === false)
        {
             throw new Exception\BadRequestValidationFailureException(
                'Method is not Card/Emi');
        }

        $paymentRecon = new PaymentReconciliate();

        $paymentRecon->setGateway($payment->getGateway());

        $paymentRecon->setPayment($payment);

        $isSuccess = $this->repo->transactionOnLiveAndTestAndAsv(function () use ($paymentRecon) {
             return $paymentRecon->handleVerifyAuthorized(); // this function  is creating transaction entity
                    });

        $this->trace->info(
                    TraceCode::ART_PAYMENT_CREATE_TRANSACTION_RESPONSE,
                    [
                        'success'                    => $isSuccess,
                        'gateway'                    => $payment->getGateway(),

                    ]);
        return  [
            'success'              => $isSuccess,
            'payment_id'           => $payment->getId(),
            'art_request_id'       => $input['art_request_id'] ?? '',
        ];

   }




    /**
     * Authorizes failed payment based on ART input
     * [force_authorize_failed,verify_authorize_failed]
     * @param array $input
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws \Exception
     */
    public function authorizeFailedCardPayment(array $input)
    {

        $fields = [
            EntityConstants::CARD,
            EntityConstants::PAYMENT,
            Entity::META,
        ];

        $input = array_only($input, $fields);

        $this->trace->info(
                    TraceCode::ART_PAYMENT_FORCE_AUTHORIZE_REQUEST,
                    [
                        'input'            => $input,
                    ]);

        switch ($input['payment']['method'])
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                (new Payment\Validator)->validateInput('authorize_failed_card_payment', $input);
                break;
            default:
                throw new Exception\BadRequestValidationFailureException(
                    Error\PublicErrorDescription::BAD_REQUEST_INVALID_PAYMENT_METHOD
                );
        }

        $paymentId = $input['payment']['id'];

        $payment = $this->repo->payment->findOrFail($paymentId);

        $paymentRecon = new PaymentReconciliate();

        $paymentRecon->setPayment($payment);

        $paymentRecon->setTransaction();

        $gateway = $payment->getGateway();

        if (($payment !== null) and
            ($payment->getAmount() !== (int) $input['payment']['amount']))
        {
            throw new Exception\BadRequestValidationFailureException(
                Error\PublicErrorDescription::BAD_REQUEST_AMOUNT_MISMATCH,
                Payment\Entity::AMOUNT,
                [
                    'payment_entity_amount' => $payment->getAmount(),
                    'input_amount'          => $input['payment']['amount'],
                    'payment_id'            => $payment->getId(),
                ]);
        }

         if ($payment->isFailed() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Non failed payment given for authorization');
        }

        if (($input['meta']['force_auth_payment'] === true) and
            ($this->isForceAuthAllowed($gateway) === true))
        {
            return $this->forceAuthorizeCardPayment($payment, $input, $paymentRecon);
        }
        else
        {


          $verifySuccess = $this->repo->transactionOnLiveAndTestAndAsv(function () use ($paymentRecon) {
             return $paymentRecon->handleVerifyPayment();
                    });

           $payment->reload();
           return  [
            'success'               => $verifySuccess  and  (($payment->getStatus() === Payment\Status::AUTHORIZED) or ($payment->getStatus() === Payment\Status::CAPTURED)),
            'payment_id'            => $payment->getId(),
            'amount'                => $payment->getAmount(),
            'status'                => $payment->getStatus(),
            'rrn'                   => $payment->getReference16(),
            'gateway_fee'           => $paymentRecon->getPaymentTransaction() ? $paymentRecon->getPaymentTransaction()->getGatewayFee() : null,
            'gateway_service_tax'   => $paymentRecon->getPaymentTransaction() ? $paymentRecon->getPaymentTransaction()->getGatewayServiceTax() : null,
            'art_request_id'        => $input['meta']['art_request_id'],
           ];
        }

    }

    /**
     * Force authorize failed payment
     * @param Entity $payment
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function forceAuthorizeNbplusPayment(Payment\Entity $payment, array $input = [])
    {
        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $input['acquirer'] = $this->getAcquirerData($input);

        switch ($input['payment']['method'])
        {
            case Payment\Method::NETBANKING;
                $input['gateway_payment_id'] = $input['netbanking']['bank_transaction_id'];
                break;
            case Payment\Method::WALLET:
                $input['gateway_payment_id'] = $input['wallet']['wallet_transaction_id'];
                break;
            case Payment\Method::CARDLESS_EMI:
                $input[CardlessEmiService::ADDITIONAL_DATA] = $input['cardless_emi'][CardlessEmiService::ADDITIONAL_DATA];
                break;
        }

        $this->repo->transaction(function () use ($payment, $input, $merchant)
        {
            $processor = $this->getNewProcessor($merchant);

            $response = $processor->forceAuthorizeFailedPayment($payment, $input);

            if ((empty($response['status']) === false) and
                ($response['status'] !== Payment\Status::FAILED))
            {
                $authResponse = $this->verifyPaymentTransaction($payment->getId());

                //check if it's re-arch payment and send entity updates to respective payment service
                if (($authResponse === true) and ($payment->isExternal() === true))
                {
                    (new Transaction\Core)->dispatchUpdatedTransactionToCPS($payment->transaction, $payment);
                }
            }
            else
            {
                $this->trace->info(
                    TraceCode::ART_PAYMENT_FORCE_AUTHORIZE_FAILED,
                    [
                        'payment_id' => $payment->getId(),
                        'amount'     => $payment->getAmount(),
                        'gateway'    => $payment->getGateway(),
                    ]);
            }
        });

        return [
            'success'        => (($payment->getStatus() === Payment\Status::AUTHORIZED) or ($payment->getStatus() === Payment\Status::CAPTURED) or ($payment->getStatus() === Payment\Status::REFUNDED)),
            'payment_id'     => $payment->getId(),
            'amount'         => $payment->getAmount(),
            'status'         => $payment->getStatus(),
            'rrn'            => $payment->getReference16(),
            'art_request_id' => $input['meta']['art_request_id'],
        ];
    }



    /**
     * Force authorize failed payment
     * @param Entity $payment
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function forceAuthorizeCardPayment(Payment\Entity $payment, array $input = [],$paymentRecon)
    {
        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $input['acquirer'] = $this->getAcquirerData($input);

        $this->repo->transaction(function () use ($paymentRecon, $payment, $input, $merchant)
        {

            $processor = $this->getNewProcessor($merchant);

            // to send force_authorize call to cps
            if ($payment->getCpsRoute() === Payment\Entity::REARCH_CARD_PAYMENT_SERVICE)
            {
                $payment->enableCardPaymentService();
            }

            $response = $processor->forceAuthorizeFailedPayment($payment, $input);

            if ((empty($response['status']) === false) and
                ($response['status'] !== Payment\Status::FAILED))
            {
                $success = $paymentRecon->handleVerifyAuthorized();

                try
                {
                    $payment = $payment->reload();

                    $this->setAutoRefundReminderForForceAuthPaymentIfApplicable($payment);

                    $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

                    if (($success === true) && ($payment->isExternal() === true))
                    {
                        if ($payment->hasBeenCaptured() === true and
                            $payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === false) {
                            (new Transaction\Core)->dispatchUpdatedTransactionToCPS($txn, $payment);
                        }
                        else if ($payment->hasBeenCaptured() === false) {
                            (new Transaction\Core)->dispatchUpdatedTransactionToCPS($txn, $payment);
                        }
                    }
                }
                catch(\Throwable $e)
                {
                    $this->trace->traceException($e, null, TraceCode::ART_PAYMENT_CPS_TRANSACTION_DISPATCH_FAILED);
                }

            }
            else
            {
                $this->trace->info(
                    TraceCode::ART_PAYMENT_FORCE_AUTHORIZE_FAILED,
                    [
                        'payment_id' => $payment->getId(),
                        'amount'     => $payment->getAmount(),
                        'gateway'    => $payment->getGateway(),
                    ]);
            }
        });


        return [
            'success'               => (($payment->getStatus() === Payment\Status::AUTHORIZED) or ($payment->getStatus() === Payment\Status::CAPTURED)),
            'payment_id'            => $payment->getId(),
            'amount'                => $payment->getAmount(),
            'status'                => $payment->getStatus(),
            'rrn'                   => $payment->getReference16(),
            'gateway_fee'           => $paymentRecon->getPaymentTransaction()->getGatewayFee(),
            'gateway_service_tax'   => $paymentRecon->getPaymentTransaction()->getGatewayServiceTax(),
            'art_request_id'        => $input['meta']['art_request_id'],
        ];
    }


    protected function setAutoRefundReminderForForceAuthPaymentIfApplicable($payment)
    {

       // Disable Auto Refunds Merchants' payments must not have the auto refund timestamp set
        $payment->reload();

        if( $payment->isExternal() === false || $payment->isMethodCardOrEmi() === false || $payment->isAuthorized() === false or  $payment->merchant->isFeatureEnabled(Feature\Constants::DISABLE_AUTO_REFUNDS) === true)
         {
            return ;
         }


         if ((empty($payment->getRefundAt()) === false) and $payment->getRefundAt() <= Carbon::now()->getTimestamp())
            {
            // 10 mins after authorization
                $payment->setRefundAt(Carbon::now()->getTimestamp() + 600);
            }

        $reminderData = [
                'refund_at' => $payment->getRefundAt(),
            ];
        $namespace  = $payment->getMethod().'_'.$payment->getGateway().'_refund';
        $paymentId  = $payment->getId();
        $merchantId = Merchant\Account::SHARED_ACCOUNT;
        $callbackUrl = self::PG_ROUTER_URL.'/v1/scheduler/'.$payment->getId().'/callback/refund';

        $request = [
            'namespace'     => $namespace,
            'entity_id'     => $paymentId,
            'entity_type'   => 'payment',
            'reminder_data' => $reminderData,
            'callback_url'  => $callbackUrl,
            "configurable_keyword"=> "refund_payments",
        ];

        $response = [];

        try
        {

             $response = $this->app['reminders']->createReminder($request, $merchantId);

        }
        catch (\Throwable $e)
        {
            // We will have fallback for reminder failures, thus no need to throw exception
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REMINDERS_RESPONSE,
                [
                    'request'           => $request,
                    'merchant_id'       => $merchantId,
                ]);
        }

        return array_get($response, Entity::ID);
    }

    /**
     * Force authorize failed payment
     * @param Entity $payment
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function forceAuthorizeUpiPayment(Payment\Entity $payment, array $input = [])
    {
        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $input['acquirer'] = $this->getAcquirerData($input);

        $this->repo->transaction(function () use ($payment, $input, $merchant)
        {
            $processor = $this->getNewProcessor($merchant);

            $response = $processor->forceAuthorizeFailedPayment($payment, $input);

            if ((empty($response['status']) === false) and
                ($response['status'] !== Payment\Status::FAILED))
            {
                $this->verifyPaymentTransaction($payment->getId());
            }
            else
            {
                $this->trace->info(
                    TraceCode::ART_PAYMENT_FORCE_AUTHORIZE_FAILED,
                    [
                        'payment_id' => $payment->getId(),
                        'amount'     => $payment->getAmount(),
                        'gateway'    => $payment->getGateway(),
                    ]);
            }
        });

        return [
            'success'        => (($payment->getStatus() === Payment\Status::AUTHORIZED) or ($payment->getStatus() === Payment\Status::CAPTURED)),
            'payment_id'     => $payment->getId(),
            'amount'         => $payment->getAmount(),
            'status'         => $payment->getStatus(),
            'rrn'            => $payment->getReference16(),
            'art_request_id' => $input['meta']['art_request_id'],
        ];
    }

    /**
     * Authorize failed payment by verifying it at gateway
     * @param Entity $payment
     * @param array $input
     * @return array
     * @throws \Exception
     */
    protected function verifyAuthorizeFailedPayment(Payment\Entity $payment, array $input = [])
    {
        try
        {
            if ($payment->isExternal() === true)
            {
                $verifyResponse = $this->handleRearchPaymentVerification($payment);
            }
            else if($payment->isUpiRecurring() === true)
            {
                $verifyResponse = $this->verifyRecurringPayment($payment, $input);
            }
            else
            {
                // Try to make it authorized
                $verifyResponse = $this->verifyPayment($payment);
            }

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_RESPONSE,
                [
                    'payment_id'    => $payment->getId(),
                    'amount'        => $payment->getAmount(),
                    'gateway'       => $payment->getGateway(),
                    'verify_status' => $verifyResponse,
                ]);

            if ($verifyResponse === VerifyResult::REARCH_CAPTURED)
            {
                $this->trace->info(
                    TraceCode::RECON_REARCH_PAYMENT_CAPTURED,
                    [
                        'message'       => 'CPS has already captured the payment and txn will get created',
                        'payment_id'    => $payment->getId(),
                        'amount'        => $payment->getAmount(),
                        'gateway'       => $payment->getGateway(),
                        'captured_at'   => $payment->getCapturedAt(),
                    ]);
            }
            else if ($verifyResponse === VerifyResult::AUTHORIZED)
            {
                $this->verifyPaymentTransaction($payment->getId());
            }

            $updatedPayment = $payment;
            // reload the payment for cps_route=7, as the payment is authorized and updated in UPS DB
            if (($payment->isExternal() === true) and
                ($payment->getCpsRoute() === Payment\Entity::REARCH_UPI_PAYMENT_SERVICE))
            {
                $updatedPayment = $this->repo->payment->findOrFail($payment->getId());
            }

            return [
                'success'        => (($updatedPayment->getStatus() === Payment\Status::AUTHORIZED) or ($updatedPayment->getStatus() === Payment\Status::CAPTURED)  or ($updatedPayment->getStatus() === Payment\Status::REFUNDED)),
                'payment_id'     => $updatedPayment->getId(),
                'amount'         => $updatedPayment->getAmount(),
                'status'         => $updatedPayment->getStatus(),
                'rrn'            => $updatedPayment->getReference16(),
                'art_request_id' => $input['meta']['art_request_id'],
            ];
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                [
                    'message'    => sprintf('Verification/Authorization threw an exception. -> %s', $ex->getMessage()),
                    'payment_id' => $payment->getId(),
                    'amount'     => $payment->getAmount(),
                    'gateway'    => $payment->getGateway(),
                ]);

            throw $ex;
        }
    }

    /**
     * handle rearch payment verification
     */
    protected function handleRearchPaymentVerification(Payment\Entity $payment)
    {
        $status = $payment->getStatus();

        $response = $this->app['pg_router']->paymentVerify($payment->getId());

        $payment = $this->repo->payment->findOrFail($payment->getId());

        if ($payment->hasBeenCaptured() === true)
        {
            return VerifyResult::REARCH_CAPTURED;
        }

        if (($status === Payment\Status::FAILED) and
            ($payment->hasBeenAuthorized() === true))
        {
            return VerifyResult::AUTHORIZED;
        }

        if ($status !== $payment->getStatus())
        {
            return VerifyResult::UNKNOWN;
        }

        return VerifyResult::SUCCESS;
    }

    /**
     * @param string $paymentId
     * @throws \Throwable
     */
    protected function verifyPaymentTransaction(string $paymentId)
    {
        $payment = $this->repo->payment->findOrFail($paymentId);

        if ($payment->hasTransaction() === true)
        {
            return true;
        }

        try
        {
            $this->repo->transaction(function () use ($payment)
            {
                if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true){


                    $reverseShadowPaymentsCore = new LedgerReverseShadow\Payments\Core();

                    $reverseShadowPaymentsCore->createLedgerEntryForGatewayCaptureReverseShadow($payment);

                }
                else {

                    list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentAuthorized($payment);

                    $this->repo->saveOrFail($txn);

                }
                $this->repo->saveOrFail($payment);

                return true;
            });

            return true;
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::ART_PAYMENT_CREATE_TRANSACTION_FAILED,
                [
                    'message' => $ex->getMessage(),
                    'payment_id' => $payment->getId(),
                    'gateway' => $payment->getGateway(),
                ]);

            throw $ex;
        }
    }

    /**
     * Adding acquirer data to be set in payment entity during force authorize
     * @param array $input
     * @return array[]
     */
    protected function getAcquirerData(array $input)
    {
        if($input['payment']['method'] == Method::UPI) {
            return [
                Payment\Entity::VPA => $input['upi']['vpa'],
                Payment\Entity::REFERENCE16 => $input['upi']['npci_reference_id'],
            ];
        }
        else if($input['payment']['method'] == Method::NETBANKING) {
            return [
                Payment\Entity::REFERENCE1 => $input['netbanking']['bank_transaction_id'],
            ];
        }
         else if($input['payment']['method'] == Method::CARD) {
            return [
                Payment\Entity::REFERENCE16 => $input['card']['rrn'],
                Payment\Entity::REFERENCE2  => $input['card']['auth_code']
            ];
        }
         else if($input['payment']['method'] == Method::CARDLESS_EMI) {
             return [
                 Payment\Entity::REFERENCE1 => $input['cardless_emi']['gateway_reference_number'],
             ];
         }

        return [];
    }

    protected function isForceAuthAllowed(string $gateway)
    {
        if ((in_array($gateway, Payment\Gateway::FORCE_AUTHORIZE_GATEWAYS, true) === true))
        {
            return true;
        }
        return false;
    }

    /*
    * @param $library
    * @return bool
    */
   public function isLibrarySupportedForAddressCollection($library): bool
   {
       if ((isset($library) === true) and
           (in_array($library, Analytics\Metadata::ADDRESS_UNSUPPORTED_LIBRARIES) === false)
       ) {
           return true;
       }
       return false;
    }

    public function getLibraryFromPayment($payment)
    {
        $library = $payment->getMetadata(Analytics\Entity::LIBRARY);

        if(is_null($library))
        {

            $paymentAnalytics = $payment->analytics;

            if (isset($paymentAnalytics))
            {
                $library = $paymentAnalytics->library;
            }
        }

        return $library;

    }

    public function paymentsDualWriteSync($input): array
    {
        $response = [];

        try
        {
            $this->trace->info(TraceCode::PAYMENTS_DUAL_WRITE_SYNC_INPUT, $input);

            (new Payment\Validator)->validateInput(__FUNCTION__, $input);

            $paymentIds = [];
            $extraLog   = [];

            $updateCacheTimestamp = 'NO';

            $newUpdatedAtCacheTimestamp        = Carbon::now()->getTimestamp();
            $newUpdatedAtCacheTimestampReverse = Carbon::now()->getTimestamp();

            $cacheKey        = 'API_PAYMENTS_DUAL_WRITE_SYNC_LAST_UPDATED_AT';
            $cacheKeyReverse = 'API_PAYMENTS_DUAL_WRITE_SYNC_REVERSE_LAST_UPDATED_AT';

            if (empty($input['payment_ids']) === false)
            {
                $paymentIds = $input['payment_ids'];
            }
            else if (empty($input['time_range']) === false)
            {
                $customTimeRange = $input['time_range'];

                $timeLowerLimit = $customTimeRange['from'];
                $timeUpperLimit = $customTimeRange['to'];

                $paymentIds = $this->repo->payment->getDualWriteMismatchPayments($timeLowerLimit, $timeUpperLimit);
            }
            else if (empty($input['bucket_interval']) === false)
            {
                $bucketInput = $input['bucket_interval'];

                $currentTime = Carbon::now()->getTimestamp();

                $timeLowerLimit = $currentTime - 60 * $bucketInput['duration'] - 60 * $bucketInput['offset'];
                $timeUpperLimit = $currentTime - 60 * $bucketInput['offset'];

                $extraLog['from'] = $timeLowerLimit;
                $extraLog['to']   = $timeUpperLimit;

                $paymentIds = $this->repo->payment->getDualWriteMismatchPayments($timeLowerLimit, $timeUpperLimit);
            }
            else if (empty($input['cache_based']) === false)
            {
                $cacheBasedUpdateRange = $input['cache_based'];

                $timeLowerLimit  = $cacheBasedUpdateRange['from'];
                $timeUpperLimit  = $cacheBasedUpdateRange['to'];
                $timeRangeBucket = $cacheBasedUpdateRange['bucket_interval'];
                $reverseFill     = $cacheBasedUpdateRange['reverse'];

                if ($reverseFill === true)
                {
                    $cacheResponse = $this->app['cache']->get($cacheKeyReverse);

                    if ((empty($cacheResponse) === false) and
                        ((isset($cacheBasedUpdateRange['reset_cache_timestamp']) === false) or
                            ($cacheBasedUpdateRange['reset_cache_timestamp'] !== true)))
                    {
                        $timeUpperLimit = min(intval($cacheResponse), $timeUpperLimit);
                    }

                    $timeLowerLimit = max($timeLowerLimit, $timeUpperLimit - 60 * $timeRangeBucket);
                }
                else
                {
                    $cacheResponse = $this->app['cache']->get($cacheKey);

                    if ((empty($cacheResponse) === false) and
                        ((isset($cacheBasedUpdateRange['reset_cache_timestamp']) === false) or
                            ($cacheBasedUpdateRange['reset_cache_timestamp'] !== true)))
                    {
                        $timeLowerLimit = max(intval($cacheResponse), $timeLowerLimit);
                    }

                    $timeUpperLimit = min($timeUpperLimit, $timeLowerLimit + 60 * $timeRangeBucket);
                }

                $extraLog['from'] = $timeLowerLimit;
                $extraLog['to']   = $timeUpperLimit;

                $paymentIds = $this->repo->payment->getDualWriteMismatchPayments($timeLowerLimit, $timeUpperLimit);

                $updateCacheTimestamp = ($reverseFill === true) ? 'reverse' : 'forward';

                $newUpdatedAtCacheTimestamp = $timeUpperLimit;

                $newUpdatedAtCacheTimestampReverse = $timeLowerLimit;
            }

            $this->trace->info(TraceCode::PAYMENTS_DUAL_WRITE_SYNC_IDS, [
                'payment_ids' => $paymentIds,
                'extra_data'  => $extraLog,
            ]);

            foreach ($paymentIds as $paymentId)
            {
                // will have null values when queried (joins) of harvester replica as it has rearch payments too
                if (empty($paymentId) === true)
                {
                    continue;
                }

                $this->mutex->acquireAndRelease($paymentId, function() use ($paymentId)
                {
                    $payment = $this->repo->payment->findOrFail($paymentId);

                    $this->repo->saveOrFail($payment);
                },
                20,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

                $response['synced_payment_ids'][] = $paymentId;
            }

            if ($updateCacheTimestamp === 'forward')
            {
                $this->app['cache']->put($cacheKey, $newUpdatedAtCacheTimestamp, 86400);
            }
            else if ($updateCacheTimestamp === 'reverse')
            {
                $this->app['cache']->put($cacheKeyReverse, $newUpdatedAtCacheTimestampReverse, 86400);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENTS_DUAL_WRITE_SYNC_FAILURE,
            );
        }

        return $response;
    }

    public function paymentsCardEsSyncCron($input)
    {
        $backfill = false;

        if (empty($input['payment_ids']) === false)
        {
            $paymentIds = $input['payment_ids'];
        }
        else
        {
            if (empty($input['backfill']) == false)
            {
                $backfill = true;
            }

            $response = $this->app['card.payments']->fetchEntityForEsSync($backfill);

            $paymentIds = $response['data'];
        }


        $successCount = 0;
        $failedCount = 0;

        foreach ($paymentIds as $paymentId)
        {
            // If $mode is provided use that else default to set rzp.mode
            $mode = $this->app['rzp.mode'];

            $tracePayload = [
                'entity_id' => $paymentId,
                'mode'      => $mode,
            ];

            try
            {
                EsSync::dispatch($mode, Base\EsRepository::CREATE, EntityConstants::PAYMENT, $paymentId, true);
                $successCount ++;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::ES_SYNC_PUSH_FAILED,
                    $tracePayload);

                $failedCount ++;
            }
        }

        return [
            'success' => $successCount,
            'failed'  => $failedCount
        ];
    }

    public function updateB2BInvoiceDetails($id,$input)
    {
        try{
            (new Validator)->validateInput("updateB2BInvoiceDetails", $input);
        }
        catch (\Throwable $e)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILED,null,[
                'error_description' => $e->getMessage(),
                'error_code'        => $e->getCode(),
            ]);
        }

        $response = [];

        if (isset($id) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_NOT_FOUND_TO_UPDATE_B2B_INVOICE,
                [
                    'payment_id' => $id
                ]);

            $response['b2b_invoice_updated'] = false;

            return $response;
        }

        $document_id = $input["document_id"];

        //Added check for internal route for receiving request from pxb service

        if ($this->app['api.route']->routeThroughPaymentCrossBorder() === true)
        {
            if(!isset($input["merchant_id"])){
                $this->trace->info(
                    TraceCode::MERCHANT_ID_NOT_FOUND_TO_UPDATE_B2B_INVOICE,
                    [
                        'payment_id' => $id
                    ]);
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID);
            }
            $merchantId = $input["merchant_id"];
            $merchant = $this->repo->merchant->find($merchantId);
        }
        else
        {
            $this->trace->info(
                TraceCode::PAYMENT_UPDATE_REQUEST_WITH_B2B_DASHBOARD,
                [
                    'payment_id' => $id
                ]);
            $merchant = $this->merchant;
        }

        $isB2BInvoiceUpdated = $this->mutex->acquireAndRelease($id,
            function() use ($id,$document_id,$merchant)
            {
                $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $merchant);

                if (isset($payment) === true)
                {
                    if(!$payment->isB2BExportCurrencyCloudPayment())
                        {
                            throw new Exception\BadRequestException(
                                Error\ErrorCode::BAD_REQUEST_INVALID_PAYMENT_ID);
                        }
                    $payment->setReference2($document_id);

                    $this->repo->saveOrFail($payment);

                    $this->trace->info(TraceCode::PAYMENT_UPDATED_WITH_B2B_INVOICE,
                        [
                            'payment_id' => $id,
                            'b2b_invoice_document_id' => $document_id
                        ]);

                    // workflow creation for invoice verification
                    $this->createWorkflowForInvoiceVerification($payment);

                    return true;
                }

                $this->trace->info(
                    TraceCode::PAYMENT_NOT_FOUND_TO_UPDATE_B2B_INVOICE,
                    [
                        'payment_id' => $id
                    ]);

                return false;
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $response['b2b_invoice_updated'] = $isB2BInvoiceUpdated;

        return $response;
    }

    private function createWorkflowForInvoiceVerification(Payment\Entity $payment) {
        if ($this->mode == Mode::LIVE)
        {
            $workflowPriority = $payment->merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B) ? "P1" : "P0";
            $body = (new WorkflowBuilder\CBInvoiceWorkflow())->buildInvoiceWorkflowPayload($payment, $workflowPriority, $payment->getMethod());
            try {
                $response = (new WorkflowServiceClient)->createWorkflowProxy($body);
                if ($workflowPriority == 'P0') {
                    try {
                        CrossBorderCommonUseCases::sendSlackNotification(
                            $payment->getId(), $payment->getMerchantId(), $workflowPriority, $response['id'], ""
                        );
                    } catch (\Throwable $e) {
                        $this->trace->traceException($e, Trace::ERROR, TraceCode::CROSS_BORDER_INVOICE_WORKFLOW_NOTIFICATION_FAILED, [
                                'payload' => $this->payload,
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::CROSS_BORDER_CREATE_INVOICE_WORKFLOW_FAILED, [
                        'payload' => $this->payload,
                    ]
                );
                $payload = [
                    'action' => CrossBorderCommonUseCases::CREATE_INVOICE_VERIFICATION_WORKFLOW,
                    'body' => $body,
                    'merchant_id' => $payment->getMerchantId(),
                    'payment_id' => $payment->getId(),
                    'priority' => $workflowPriority,
                ];
                CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60, 1000) % 601);
            }
        }
    }

    /*
     * This method is used to save merchant documents for a payment.
     * Used for OPGSP import payments.
     */
    public function updateMerchantDocumentForPayment($id,$input)
    {
        try{
            (new Validator)->validateInput("updateMerchantDocumentDetails", $input);
        }
        catch (\Throwable $e)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILED,null,[
                'error_description' => $e->getMessage(),
                'error_code'        => $e->getCode(),
            ]);
        }

        $response = [];

        if (isset($id) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_NOT_FOUND_TO_UPDATE_MERCHANT_DOC,
                [
                    'payment_id'    => $id,
                    'document_type' => $input["document_type"],
                ]);

            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_PAYMENT_ID);
        }

        $documentId = $input["document_id"];
        $documentType = $input["document_type"];

        $merchant = $this->merchant;

        $mutexKey = InvoiceConstants::MUTEX_MERCHANT_PAYMENT_DOCUMENT_UPLOAD_PREFIX . $documentType . "_" . $id;

        $isDocumentUpdated = $this->mutex->acquireAndRelease($mutexKey,
            function() use ($id,$documentId,$merchant,$documentType)
            {
                $payment = $this->repo->payment->findByIdAndMerchant($id, $merchant);

                if (!isset($payment)) {
                    $this->trace->info(
                        TraceCode::PAYMENT_NOT_FOUND_TO_UPDATE_MERCHANT_DOC,
                        [
                            'payment_id' => $id,
                            'document_type' => $documentType,
                        ]);
                    throw new Exception\BadRequestException(

                        Error\ErrorCode::BAD_REQUEST_INVALID_PAYMENT_ID);
                }

                if((!$payment->merchant->isOpgspImportEnabled()) and
                   ($payment->merchant->isJpmcImportFlowEnabled() === false))
                {
                    throw new Exception\BadRequestException(
                        Error\ErrorCode::BAD_REQUEST_INVALID_PAYMENT_ID);
                }

                if (in_array($payment->getStatus(),
                        [Payment\Status::FAILED, Payment\Status::REFUNDED], true) === true)
                {
                    throw new Exception\BadRequestException(
                        Error\ErrorCode::BAD_REQUEST_PAYMENT_INVALID_STATUS);
                }

                $paymentDocument = (new InvoiceService())->findByPaymentIdDocumentType($id, $documentType);

                if(!isset($paymentDocument))
                {
                    $this->trace->info(
                        TraceCode::PAYMENT_SUPPORTING_DOC_NOT_FOUND,
                        [
                            'payment_id' => $id,
                            'document_type' => $documentType,
                        ]);

                    $message =explode("_", $documentType)[1]. ' number not found.';
                    throw new Exception\BadRequestValidationFailureException(
                        $message, $documentType);
                }

                if(isset($paymentDocument[InvoiceEntity::REF_NUM]))
                {
                    $this->trace->info(
                        TraceCode::PAYMENT_DOCUMENT_UPLOAD_DUPLICATE_REQUEST,
                        [
                            'payment_id' => $id,
                            'document_type' => $documentType,
                        ]);

                    throw new Exception\BadRequestValidationFailureException(
                        'Duplicate request for '.explode("_", $documentType)[1].' upload');
                }

                // this is required as writes can't be done on slave DB
                // and entity id field is only indexed on slave.
                $paymentDocument = $this->repo->invoice->findOrFail($paymentDocument->getId());
                $paymentDocument->setRefNum($documentId);
                $paymentDocument->setStatus(Invoice\Status::PAID);

                $this->repo->invoice->saveOrFail($paymentDocument);

                $this->trace->info(TraceCode::PAYMENT_UPDATED_WITH_MERCHANT_DOC,
                    [
                        'payment_id' => $id,
                        'document_id' => $documentId,
                        'document_type' => $documentType,
                    ]);

                $data = [
                    'merchant_id'   => $merchant->getId(),
                    'action'        => CrossBorderCommonUseCases::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT,
                    'payment_id'    => $id,
                    'mode'          => $this->mode ?? Mode::LIVE,
                ];
                if ($documentType == GenericDocumentConstants::OPGSP_AWB)
                {
                    CrossBorderCommonUseCases::dispatch($data)->delay(rand(60, 1000) % 601);
                }
                return true;
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $response['document_updated'] = $isDocumentUpdated;

        return $response;
    }

    public function uploadPaymentSupportingDocument($input)
    {

        (new Payment\Validator)->validateUploadPaymentSupportingDocument($input);

        $response = [];

        $fileData = $_FILES['file'];
        $fileName = "";

        if (!empty($_FILES['file'])) {
            $fileName = $fileData['name'];
        }

        /* input_file is the invoice file which is locally downloaded from s3 sftp location */
        if (isset($input['input_file'])) {
            $fileName = $input['input_file']->getClientOriginalName();

            // set file and unset input_file params in input for ufh function to utilize it
            $input['file'] = $input['input_file'];
            unset($input['input_file']);
        }

        $documentNumber = substr($fileName, 0, strrpos($fileName, "."));
        $merchant = $this->merchant;

        if((!$merchant->isOpgspImportEnabled()) and
           ($merchant->isJpmcImportFlowEnabled() === false))
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_ACTION);
        }

        $mutexKey = InvoiceConstants::MUTEX_MERCHANT_PAYMENT_DOCUMENT_UPLOAD_PREFIX
            . $merchant->getId() . "_" . $input['purpose'] . "_" . $documentNumber;

        $isDocumentUpdated = $this->mutex->acquireAndRelease($mutexKey,
            function() use ($input, $merchant, $documentNumber)
            {
                $paymentDocument = (new InvoiceService())
                    ->findByMerchantIdDocumentTypeDocumentNumber($merchant->getId(),$input['purpose'], $documentNumber);

                if(!isset($paymentDocument))
                {
                    $this->trace->info(
                        TraceCode::PAYMENT_NOT_FOUND_TO_UPDATE_MERCHANT_DOC,
                        [
                            'fileName'   => $documentNumber,
                            'merchantId' => $merchant->getId(),
                        ]);

                    $message = explode("_", $input['purpose'])[1]. ' number not found.';
                    throw new Exception\BadRequestValidationFailureException(
                        $message, $paymentDocument);
                }
                if (isset($paymentDocument[InvoiceEntity::REF_NUM])) {
                    $this->trace->info(
                        TraceCode::PAYMENT_DOCUMENT_UPLOAD_DUPLICATE_REQUEST,
                        [
                            'payment_id'      => $paymentDocument[InvoiceEntity::ENTITY_ID],
                            'document_type'   => $paymentDocument[InvoiceEntity::TYPE],
                            'document_number' => $paymentDocument[InvoiceEntity::RECEIPT],
                        ]
                    );

                    throw new Exception\BadRequestValidationFailureException(
                        'Duplicate request for '.explode("_", $input['purpose'])[1].' upload'
                    );
                }

                $merchant = $this->merchant;
                $payment = $this->repo->payment->findByIdAndMerchant($paymentDocument[InvoiceEntity::ENTITY_ID], $merchant);

                if (in_array($payment->getStatus(), [Payment\Status::FAILED, Payment\Status::REFUNDED], true)) {
                    throw new Exception\BadRequestException(
                        Error\ErrorCode::BAD_REQUEST_PAYMENT_INVALID_STATUS
                    );
                }

                $fileStorage = 'api/' . $input['file']->getClientOriginalName();
                $uploadResponse = (new UfhService($this->app))->uploadFileAndGetResponse($input['file'], $fileStorage, $input['purpose'], $merchant);
                $paymentDocument = $this->repo->invoice->findOrFail($paymentDocument->getId());
                $paymentDocument->setRefNum(substr($uploadResponse['id'], 5));
                $paymentDocument->setStatus(Invoice\Status::PAID);
                $this->repo->invoice->saveOrFail($paymentDocument);

                $this->trace->info(
                    TraceCode::PAYMENT_UPDATED_WITH_MERCHANT_DOC,
                    [
                        'payment_id'   => $paymentDocument[InvoiceEntity::ENTITY_ID],
                        'document_type' => $paymentDocument[InvoiceEntity::TYPE],
                        'document_id'   => $uploadResponse['id'],
                    ]
                );

                $data = [
                    'merchant_id'   => $merchant->getId(),
                    'action'        => CrossBorderCommonUseCases::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT,
                    'payment_id'    => $paymentDocument[InvoiceEntity::ENTITY_ID],
                    'mode'          => $this->mode ?? Mode::LIVE,
                ];

                if ($paymentDocument[InvoiceEntity::TYPE] == GenericDocumentConstants::OPGSP_AWB)
                {
                    CrossBorderCommonUseCases::dispatch($data)->delay(rand(60, 1000) % 601);
                }

                return true;

            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $response['document_updated'] = $isDocumentUpdated;

        return $response;
    }

    /**
     * uploadPaymentInvoiceDocument Creates payload for invoice upload using merchant features and file downloaded from s3.
     * @throws BadRequestValidationFailureException
     * @throws BadRequestException
     */
    public function uploadPaymentInvoiceDocument(HttpFoundation\File\UploadedFile $file): array
    {
        // Initialize input array with file
        $input = ['input_file' => $file];

        if ($this->merchant->isOpgspImportEnabled())
        {
            $input['purpose'] = GenericDocumentConstants::OPGSP_INVOICE;
        }
        elseif ($this->merchant->isJpmcImportFlowEnabled())
        {
            $input['purpose'] = GenericDocumentConstants::JPMC_INVOICE;
        }
        else
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_ACTION
            );
        }

        return $this->uploadPaymentSupportingDocument($input);
    }

    private function moveTimedoutRecurringNachPaymentTokensToRejectedState($payment)
    {
        $nachToken = $this->repo->token->findByIdAndMerchantId($payment->getTokenId(), $payment->getMerchantId());

        $nachToken->setRecurringStatus(Token\RecurringStatus::REJECTED);

        $nachToken->setRecurringFailureReason(RegisterErrorCodes::NCEX);

        $nachToken->saveOrFail();
    }


    public function markPosPaymentAsCaptured($id) {

        $payment  = $this->repo->payment->findOrFail($id);

        if($payment[Entity::STATUS] !== 'authorized') {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_NOT_AUTHORIZED);
        }
        if($payment[Entity::METHOD] !== Entity::CARD) {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD);
        }

        if($payment[Entity::RECEIVER_TYPE] !== 'pos') {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES);
        }

        if($payment[Entity::GATEWAY] !== 'hdfc_ezetap') {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_GATEWAY);
        }

        $merchant_id = $payment['merchant_id'];

        $merchant = $this->repo->merchant->findByPublicId($merchant_id);

        $input = [
            'amount'        => $payment->getAmount(),
            'currency'      => $payment->getCurrency()
        ];

        return $this->getNewProcessor($merchant)->capture($payment, $input);
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingPaymentDetails($input)
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = $this->getSelfServeActionForFetchingPaymentDetail($input);

        if (isset($segmentProperties[SegmentConstants::SELF_SERVE_ACTION]) === true)
        {
            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $this->merchant, $segmentProperties, $segmentEventName
            );
        }
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingPaymentDetailsFromPaymentId()
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Payment Details Searched';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    private function pushSelfServeSuccessEventsToSegment()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        return [$segmentEventName, $segmentProperties];
    }

    private function getSelfServeActionForFetchingPaymentDetail($input)
    {
        if ((isset($input[Entity::EMAIL]) === true) or
            (isset($input[Entity::NOTES]) === true) or
            (isset($input[Entity::VA_TRANSACTION_ID]) === true))
        {
            return 'Payment Details Searched';
        }

        if ((isset($input[Entity::STATUS]) === true) or
            ((isset($input[Merchant\Constants::FROM]) === true) and
            (isset($input[Merchant\Constants::TO]) === true) and
            ($this->checkDurationInterval($input[Merchant\Constants::FROM], $input[Merchant\Constants::TO]) === true)))
        {
            return 'Payment Details Filtered';
        }
    }

    private function checkDurationInterval($from, $to)
    {
        //By default duration is set as Past 7 days for Payments in the Transactions Tab in PG Merchant Dashboard
        //Timestamp difference for this duration is 691199.
        //We have to trigger the event whenever the duration is changed by the merchant.
        if (($to - $from == '691199') and
            ($to == Carbon::today(Timezone::IST)->endOfDay()->getTimestamp()))
        {
            return false;
        }

        return true;
    }

    public function unSetTokenAttributes(&$entity)
    {
        unset($entity['token']['mrn'], $entity['token']['used_at'], $entity['token']['recurring'], $entity['token']['recurring_details'], $entity['token']['auth_type'], $entity['token']['internal_error_code'], $entity['token']['token']);
        unset($entity['token']['card']['name'], $entity['token']['card']['expiry_year'], $entity['token']['card']['expiry_month'], $entity['token']['card']['flows'], $entity['token']['card']['cobranding_partner'] );
    }

    public function GetPaymentDetailsForCallbackView($payment_id) {
        $mode = $this->repo->determineLiveOrTestModeForEntity($payment_id, 'payment');

        if ($mode === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $payment = $this->core->retrievePaymentById($payment_id);

        $merchant = $payment->merchant;
        $response = [];
        $response["is_international"] = $payment->isInternational();
        $response["method"] = $payment->getMethod();
        $response["library"] = $payment->analytics->library;
        $response["merchant_id"] = $merchant->id;

        return $response;
    }

    public function updateTokenDetails( &$entity, $payment)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_ACTION_ASYNC,
            [
                'payload' => $entity,
                'payment' => $payment
            ]);

        if (($payment->isCard() === true) && ($payment->card->isInternational()) === false && ($entity['token']['status'] === 'active')) {

            $data['id'] = $entity['token_id'];

            $network_token_data = (new Token\Service())->fetchNetworkToken($data);

            $entity['token'] = $network_token_data;
        }
        else if (($payment->isCard() === true) && ($payment->isRecurring() === false) && ( empty($payment->localToken) === false ) && ($payment->localToken->card->isTokenisationCompliant($payment->merchant) === false) && ( ($entity['token']['status'] === 'failed') || ($entity['token']['status'] === null ) )) {

            $errorCode = $payment->localToken->getInternalErrorCode() ?? ErrorCode::BAD_REQUEST_TOKEN_CREATION_FAILED;

            try
            {
                throw new Exception\BadRequestException($errorCode);
            }
            catch (\Throwable $exception)
            {

                $error = $exception->getError();

                $entity['token']['status'] = Token\Constants::FAILED;

                $entity['token']['error_code'] = $error->getPublicErrorCode();

                $entity['token']['error_description'] = $exception->getMessage();
            }

            $this->unSetTokenAttributes($entity);

        }
        else if (($payment->isCard() === true) &&
            ( empty($payment->localToken) === false ) &&
            ($payment->localToken->getErrorDescription() === null))
        {
            $entity['token']['error_code'] = null;
        }

    }

    public function addAuthenticationObject( &$entity, $authenticationData)
    {
        if (isset($authenticationData['protocol_version'])) {
            if($authenticationData['protocol_version'] == '2.1.0' || $authenticationData['protocol_version'] == '2.2.0'){
                $entity['authentication']['version'] = "3DS2";
            }
            else if($authenticationData['protocol_version'] == '1.0.2'){
                $entity['authentication']['version'] = "3DS1";
            }
            else {
                $entity['authentication']['version'] = $authenticationData['protocol_version'];
            }
        }
        if(isset($authenticationData['notes'])){
            $data = json_decode($authenticationData['notes'], true);
            if(isset($data["authentication_channel"])) {
                $entity['authentication']['authentication_channel'] = $data['authentication_channel'];
            }
        }
    }

    public function getPaymentDetailsForMerchantRedirectView(string $paymentId): array
    {
        try
        {
            $payment = $this->repo->payment->findByPublicId($paymentId);

            $merchant = $payment->merchant;

            $library = $this->getLibraryFromPayment($payment);

            return [
                'payment_id'            => $paymentId,
                'library'               => $library,
                'amount'                => $payment->getFormattedAmount(),
                'success'               => $this->isPaymentSuccessful($payment),
                'method'                => $payment->getMethod(),
                'created_at'            => Carbon::createFromTimestamp(
                    $payment->getCreatedAt(),
                    Timezone::IST
                )->format('M d, Y | h:i A'),
                'is_email_less_payment' => empty($payment->getEmail()) || $payment->getEmail() === Entity::DUMMY_EMAIL,
                'merchant'              => [
                    'name' => $merchant->getName(),
                    'rtb'  => (new TrustedBadge\Core())->isTrustedBadgeLiveForMerchant($merchant->getId()),
                ],
            ];
        }
        catch (Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::FETCH_PAYMENT_DETIALS_FOR_MERCHANT_REDIRECT_VIEW_FAILED
            );
        }

        return [];
    }

    /**
     * Checks if email-less checkout experiment is enabled.
     *
     * @param string $merchantId
     *
     * @return bool
     */
    public function isEmailLessCheckoutExperimentEnabled(string $merchantId): bool
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.email_less_checkout_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            return $variant === 'variant_on';
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EMAIL_LESS_CHECKOUT_SPLITZ_EXPERIMENT_ERROR
            );
        }

        return false;
    }

    public function isPaymentSuccessful(Payment\Entity $payment): bool
    {
        return in_array(
            $payment->getStatus(),
            [Payment\Status::CAPTURED, Payment\Status::AUTHORIZED],
            true
        );
    }

    private function canFetchRatesThroughRearch() : bool {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.fetch_flows_api_forex_rates_from_rearch_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $this->merchant->getId(),
                    ]),
            ];

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
                TraceCode::CROSS_BORDER_REARCH_EXPERIMENT_SPILTZ_ERROR
            );
        }

        return false;
    }

    /**
     * @param string $paymentId
     *
     * @return mixed
     * @throws BadRequestException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    public function releaseSubmerchantPayment(string $paymentId): mixed
    {
        $partner = $this->app['basicauth']->getPartnerMerchant();

        $this->trace->info(
            TraceCode::SUBMERCHANT_PAYMENT_RELEASE_REQUEST,
            [
                'payment_id'    => $paymentId,
                'merchant_id'   => $this->merchant->getId(),
                'partner_id'    => $partner->getId()
            ]
        );

        (new PartnerValidator())->validateIsAggregatorOrPurePlatformPartner($partner);

        (new PartnerValidator())->validateIfSubmerchantManualSettlementEnabled($partner, $this->app['basicauth']->getOAuthApplicationId());

        Entity::verifyIdAndSilentlyStripSign($paymentId);

        /** @var Payment\Entity */
        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        $paymentMid = $payment->merchant->getId();

        if ($this->merchant->getId() !== $paymentMid)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PAYMENT_ID,
                $paymentId,
                [
                    'request_mid'   => $this->merchant->getId(),
                    'payment_mid'   => $paymentMid,
                    'partner_id'    => $partner->getId()
                ]);
        }

        (new Payment\Validator())->validatePaymentRelease($payment);

        $isExpEnabled = $this->checkIfTransactionOnholdWriteRemovalEnabled($payment->merchant);

        if($isExpEnabled===false)
        {

            /** @var Transaction\Entity */
            $paymentTrxn = $this->repo->transaction(function () use ($payment) {

                $paymentTrxn = $this->repo->transaction->lockForUpdate($payment->getTransactionId());

                $paymentTrxn->setOnHold(false);

                $this->repo->transaction->saveOrFail($paymentTrxn);

                return $paymentTrxn;
            });
        }
        else
        {
            $paymentTrxn = $this->createVirtualPaymentTxnFromLedger($payment);
            $paymentTrxn->setOnHold(false);
        }

        $accountBalance = $paymentTrxn->accountBalance;

        $settlementBucketCore = (new Settlement\Bucket\Core());

        if ($settlementBucketCore->shouldProcessViaNewService($this->merchant->getId(), $accountBalance) === true)
        {
            $response = $settlementBucketCore->settlementServiceToggleTransactionHold([$paymentTrxn->getId()]);
        }
        else
        {
            $response = (new Transaction\Core)->dispatchForSettlementBucketing($paymentTrxn);
        }

        if ($response['success'] !== true)
        {
            $this->rollbackSubmerchantPaymentRelease($payment);

            throw new Exception\ServerErrorException(
                'Error releasing payment for settlement',
                ErrorCode::SERVER_ERROR_PAYMENT_MANUAL_SETTLEMENT_FAILURE
            );
        }

        $this->trace->info(
            TraceCode::SUBMERCHANT_PAYMENT_RELEASED_FOR_SETTLEMENT,
            [
                'payment_id'        => $paymentId,
                'transaction_id'    => $paymentTrxn->getId(),
                'merchant_id'       => $this->merchant->getId(),
                'partner_id'        => $partner->getId()
            ]
        );

        $paymentArr = $payment->toArray();

        $paymentArr[PaymentsConstants::SETTLEMENT_ONHOLD] = $paymentTrxn->isOnHold();

        return $paymentArr;
    }

    // set the on_hold flag of trxn to true with retry mechanism
    private function rollbackSubmerchantPaymentRelease($payment)
    {
        $maxRetries = 2;
        $attempt = 0;
        $success = false;

        while ($attempt < $maxRetries && !$success)
        {
            try
            {
                $this->repo->transaction(function() use ($payment) {
                    /** @var Transaction\Entity */
                    $paymentTrxn = $this->repo->transaction->lockForUpdate($payment->getTransactionId());

                    $paymentTrxn->setOnHold(true);

                    $this->repo->transaction->saveOrFail($paymentTrxn);
                });

                $this->trace->count(PartnerMetric::SUBMERCHANT_PAYMENT_RELEASE_SUCCESS);

                $success = true;
            }
            catch (\Exception $e)
            {
                $attempt++;

                $this->trace->error("Rollback attempt $attempt failed for submerchant payment release", ['exception' => $e]);

                if ($attempt >= $maxRetries)
                {
                    $this->trace->count(PartnerMetric::SUBMERCHANT_PAYMENT_RELEASE_FAILURE);

                    $this->trace->critical('Failed to rollback trxn for submerchant payment release after multiple attempts',
                        ['payment_id' => $payment->getTransactionId()]
                    );
                }
            }
        }
    }

    public function releaseSubmerchantPaymentsCron(): array
    {
        $last30DaysTimestamp = Carbon::now(Timezone::IST)->subDays(30)->getTimestamp();

        $this->trace->info(
            TraceCode::SUBMERCHANT_PAYMENTS_RELEASE_REQUEST_CRON,
            [
                'timestamp' => Carbon::createFromTimestamp($last30DaysTimestamp, Timezone::IST)->format('d-m-Y H:i:s')
            ]
        );

        $manualSettlementPaymentIdsToUpdate = $this->fetchSubmManualSettlementPaymentsBeforeTimestamp($last30DaysTimestamp);

        $cronSummary = $this->updateOnHoldForPayments($manualSettlementPaymentIdsToUpdate, $last30DaysTimestamp, false);

        return [
            'success' => true,
            'summary' => $cronSummary
        ];
    }

    /**
     * Checks if gateway is enabled for using merchant reference
     * for identifying unexpected payments
     * @param string $gateway
     * @return bool
     */
    private function shouldUseMerchantReferenceForUnexpectedPayment(string $gateway)
    {
        $variant = $this->app->razorx->getTreatment($gateway, Merchant\RazorxTreatment::USE_MERCHANT_REFERENCE_FOR_UNEXPECTED_PAYMENT, Mode::LIVE);

        $this->trace->info(
            TraceCode::UPI_UNEXPECTED_PAYMENT_IDENTIFIER_RAZORX_VARIANT,
            [
                'gateway'           => $gateway,
                'variant'           => $variant
            ]
        );

        if (strtolower($variant) === 'on')
        {
            return true;
        }

        return false;
    }

    public function processGatewayPayerCallBack($gateway, $callbackPayload)
    {
        $successResponse = [
            'success' => true
        ];

        $failureResponse = [
            'success' => false
        ];

        $upiData = $callbackPayload['data'][\RZP\Gateway\Upi\Base\Entity::UPI] ?? null;

        if ($upiData === null)
        {
            return $failureResponse;
        }

        $axisTxnId  = $upiData[\RZP\Gateway\Upi\Base\Entity::NPCI_TXN_ID];
        $statusCode = $upiData[\RZP\Gateway\Upi\Base\Entity::STATUS_CODE];

        [$payment, $mode] = $this->fetchPaymentByGatewayTransactionId(Gateway::UPI_AXISOLIVE, $axisTxnId);

        if ($payment === null)
        {
            $this->trace->error(TraceCode::TURBO_PAYMENT_FETCH_FAILED,
                                [
                                    'reference1' => $axisTxnId
                                ]);

            return $failureResponse;
        }
        $this->trace->info(TraceCode::TURBO_PAYMENT_FETCH_SUCCESSFUL,
            [
                'id'          => $payment->getId(),
                'merchant_id' => $payment->getMerchantId(),
                'status'      => $payment->getStatus(),
                'mode'        => $mode
            ]
        );

        try
        {
            $this->app['basicauth']->setModeAndDbConnection($mode);

            $merchant = $payment->merchant;

            (new Payment\Processor\Processor($merchant))->updateTurboPaymentWithPayerCallBack($payment, $statusCode);

            return $successResponse;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException($exception, Trace::CRITICAL, TraceCode::PAYER_CALLBACK_PROCESSING_FAILED,
                                         [
                                             'gateway' => $gateway,
                                         ]);
        }

        return $failureResponse;
    }

    /**
     * The fetchPaymentByGatewayTransactionId will fetch payment object by the gateway and gateway transaction id
     * As there is no index on the gateway transaction id we will try to fetch the payment Id using gateway transaction id from the cache
     * and if it is not available we will query db with limited range on created_at column.
     *
     * Note: Right now this is used for the UPI Turbo payments.
     */
    private function fetchPaymentByGatewayTransactionId($gateway, $txnId)
    {
        $keyPrefix = PaymentConstants::TURBO_PAYMENT_ID_BY_GATEWAY_TXN_ID_PREFIX;
        $redisKey  = $keyPrefix . $txnId;

        $paymentId = $this->app['cache']->get($redisKey);

        if ($paymentId !== null || empty($paymentId) === false)
        {
            $this->trace->debug(TraceCode::TURBO_PAYMENT_ID_BY_GATEWAY_TXN_KEY_EXIST_IN_REDIS, [
                'message' => 'Turbo payment id by gateway transaction key is exist in the redis'
            ]);
            return $this->repo->payment->fetchPaymentLiveOrTestModeWithGateway($paymentId, $gateway);
        }
        else
        {
            $this->trace->info(TraceCode::TURBO_PAYMENT_ID_BY_GATEWAY_TXN_KEY_IS_NOT_EXIST_IN_REDIS, [
                'action' => 'Analyse the time taken on payment create and payer callback receiving time'
            ]);
            $createdAtRange = Admin\ConfigKey::get(Admin\ConfigKey::UPI_TURBO_PAYMENT_LOOK_UP_CREATED_AT_RANGE, -1);

            if($createdAtRange === null || $createdAtRange === -1) {
                $this->trace->info(TraceCode::TURBO_PAYMENT_LOOK_UP_CREATED_AT_RANGE_NOT_FOUND_IN_CACHE, [
                    'action' => 'Need to add the look up range key upi_turbo_payment_look_up_created_at_range in cache from admin dashboard'
                ]);
                $createdAtRange = P2pPreferences\Constants::TURBO_PAYMENT_LOOK_UP_BACK_SECONDS;
            }

            $createdAtRange = (int)$createdAtRange;

            $currentTime = Carbon::now()->getTimestamp();
            $startTime = $currentTime - $createdAtRange;

            /** @var Payment\Entity $payment */
            return $this->repo->payment->fetchTurboUpiPaymentByReference1($txnId, $startTime, $currentTime);
        }
    }

    /**
     * @param array &$input
     */
    public function addExperimentDetailsInGatewayOtpPostFormData(array &$input): void
    {
        $merchantId = $input["merchant_id"] ?? "";
        $otpUnificationAcsPageVariant = $this->app['razorx']->getTreatment(
            $merchantId,
            RazorxTreatment::OTP_UNIFICATION_ACS_PAGE,
            $this->app['rzp.mode'],
        );

        $input['experiments']['otp_unification_acs_page'] = $otpUnificationAcsPageVariant;
    }

    private function shouldCreateQrPaymentFromUnexpectedUpiPayment(string $gateway)
    {
        $variant = $this->app->razorx->getTreatment($gateway, Merchant\RazorxTreatment::RECON_UNEXPECTED_QR_PAYMENT_VIA_UPI_ROUTE, Mode::LIVE);

        $this->trace->info(
            TraceCode::RECON_UNEXPECTED_QR_PAYMENT_VIA_UPI_ROUTE,
            [
                'gateway'           => $gateway,
                'variant'           => $variant
            ]
        );

        if (strtolower($variant) === 'on')
        {
            return true;
        }

        return false;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function prepareInputForQrFromRecon(array $input): array
    {
        $standardInput['data'] = $input;

        $standardInput['success'] = true;

        $standardInput['data']['payment']['amount_authorized'] = $input['payment']['amount'];

        return $standardInput;
    }

    public function checkIfTransactionOnholdWriteRemovalEnabled($merchant): bool
    {
        $isFeatureAssigned = $merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW);

        if ($isFeatureAssigned === false)
        {
            $this->trace->info(TraceCode::TRANSACTION_ON_HOLD_WRITE_REMOVAL_EXP_CHECK,
                [
                    'merchant'               => $merchant->getId(),
                    'isExperimentEnabled'    => false,
                    'reason'                 => "merchant not on reverse shadow"
                ]);
            return false;
        }

        return true;
    }

    /** showMarkupExperimentEnabled(string $merchantId string $network) checks
     * if show dcc markup experiment is enabled or not this experiment
     * replaces PAYMENT_SHOW_DCC_MARKUP feature flag
     **/
    public function showMarkupExperimentEnabled(string $merchantId, string $network): string
    {
            try
            {
                $experimentIdConfigKey = null;

                if ($network === "Visa") {
                    $experimentIdConfigKey = 'app.show_dcc_markup_visa_experiment_id';
                } elseif ($network === "MasterCard") {
                    $experimentIdConfigKey = 'app.show_dcc_markup_mc_experiment_id';
                }

                if ($experimentIdConfigKey === null) {
                    return false;
                }

                $properties = [
                    'id'            => UniqueIdEntity::generateUniqueId(),
                    'experiment_id' => $this->app['config']->get($experimentIdConfigKey),
                    'request_data'  => json_encode(['merchant_id' => $merchantId]),
                ];
                $response = $this->app['splitzService']->evaluateRequest($properties);
                $versionValue = "";
                $variables = $response['response']['variant']['variables'];
                foreach ($variables as $variable) {
                    $versionValue = $variable['value'];
                }
                return $versionValue;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::SHOW_DCC_EXPERIMENT_SPLITZ_ERROR
                );
            }
        return false;
    }

    protected function shouldTriggerInvoiceWebhooksForRearchPayments(string $merchantId): bool
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.invoice_webhooks_payment_rearch_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            return $variant === 'variant_on';
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException(
                $e,
                null,
                TraceCode::INVOICE_WEBHOOK_PAYMENT_REARCH_SPLITZ_ERROR
            );
        }

        return false;
    }

    /** processes unexpected preprocess logic for rearch payments
     * @param array $input
     * @return void
     * @throws BadRequestException
     */
    protected function processUpsUnexpectedPayment(array $input)
    {
        $customerReference = $input['upi']['npci_reference_id'];

        $gateway = $input['terminal']['gateway'];

        $merchantReference = $input['upi']['merchant_reference'];

        $requiredColumns = [
            UpsConstants::CUSTOMER_REFERENCE,
            UpsConstants::MERCHANT_REFERENCE,
            UpsConstants::PAYMENT_ID,
            UpsConstants::AMOUNT,
            UpsConstants::GATEWAY,
        ];

        $upsEntities = $this->app['upi.payments']->fetchAuthorizeEntityViaRRN($customerReference, $requiredColumns) ?? [];

        if (count($upsEntities) == 0)
        {
            return;
        }

        // filter the records to contain the records with gateway
        $upsEntities = array_values(array_filter($upsEntities, function ($row)  use ($gateway)
        {
            return $row[UpsConstants::GATEWAY] === $gateway;
        }));

        if (($this->shouldUseMerchantReferenceForUnexpectedPayment($gateway) === true) and
            (empty($merchant_reference) === false))
        {
            // filter the records to contain the records with merchant_reference
            $upsEntities = array_values(array_filter($upsEntities, function ($row)  use ($merchantReference)
            {
                return $row[UpsConstants::MERCHANT_REFERENCE] === $merchantReference;
            }));
        }

        if ((empty($upsEntities) === false) and (count($upsEntities) > 1))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    'payment_id'        => null,
                    'npci_reference_id' => $customerReference,
                    'gateway'           => $gateway,
                ],
                'Multiple payments with same RRN'
            );
        }

        if ((empty($upsEntities) === false) and (count($upsEntities) === 1))
        {
            if ($upsEntities[0]['amount'] === (int)($input['payment']['amount']))
            {
                $unexpectedPaymentId = $upsEntities[0][UpsConstants::PAYMENT_ID];

                $payment = null;

                try
                {
                    $payment = $this->repo->payment->findOrFail($unexpectedPaymentId);

                }
                catch (\Throwable $exception){}

                $this->setRefundAtInRecon($payment);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        'payment_id'        => $unexpectedPaymentId,
                        'npci_reference_id' => $customerReference,
                        'gateway'           => $gateway,
                    ],
                    'Duplicate Unexpected payment with same amount'
                );
            }
        }
    }

    /** sets refundAt in recon flow
     * @param Entity $payment
     * @return void
     */
    protected function setRefundAtInRecon(Entity $payment)
    {
        if ((in_array($payment->getMerchantId(), self::$demoAccounts, false) === false) or
            ($payment->isUpi() === false) or
            ($payment->isExternal() === false))
        {
            return;
        }

        if (empty($payment->getRefundAt()) === true)
        {
            $payment->setRefundAt(Carbon::now()->getTimestamp());

            $payment->setAttribute(Entity::REFUND_UNEXPECTED_PAYMENT, true);

            $this->repo->saveOrFail($payment);
        }
    }

    /**
     * Checks if gateway is enabled for create unexpected payment on rearch
     * for identifying unexpected payments
     * @param string $gateway
     * @return bool
     */
    private function shouldProcessUpsUnexpectedPayment(string $gateway): bool
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.ups_unexpected_payment_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'gateway' => $gateway,
                    ]),
            ];

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
                TraceCode::UPS_UNEXPECTED_PAYMENT_SPLITZ_ERROR
            );
        }

        return false;
    }
}
