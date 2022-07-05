<?php

namespace RZP\Models\Payment;

use FuzzyWuzzy\Process;
use Mail;
use Crypt;
use Config;
use Carbon\Carbon;
use RZP\Base\Luhn;
use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\Timezone;
use RZP\Constants\Mode;
use RZP\Base\RuntimeManager;

use RZP\Jobs;
use RZP\Diag\EventCode;
use RZP\Models\Risk;
use RZP\Exception;
use RZP\Error;
use RZP\Mail\Merchant\AuthorizedPaymentsReminder as AuthorizedPaymentsReminderMail;
use RZP\Models\Base;
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
use RZP\Models\CardMandate\CardMandateNotification;
use RZP\Models\Payment\Verify\Result as VerifyResult;
use RZP\Models\Payment\Processor\Constants as PaymentConstants;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Payment\PaymentMeta;
use RZP\Models\Payment\Fraud;
use RZP\Constants\Shield as ShieldConstants;
use RZP\Models\Batch\Processor\Nach\ErrorCodes\RegisterErrorCodes;

class Service extends Base\Service
{
    use FraudDetector;

    protected $merchant;

    protected $core;

    protected $slack;

    protected $mutex;

    protected $redis;

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

        $variant = $this->app->razorx->getTreatment(
                $this->merchant->getId(),
                Merchant\RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1,
                $this->mode
        );

        if (strtolower($variant) === RefundConstants::RAZORX_VARIANT_ON)
        {
            // Route refund creation to scrooge
            return (new Payment\Refund\Service())->scroogeRefundCreate($id, $input);
        }

        $refund = $this->getNewProcessor()->refundPaymentViaMerchant($id, $input);

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

    public function verify($id)
    {
        $payment = $this->repo->payment->findOrFailByPublicIdWithParams($id, []);

        if ($payment->isExternal() === true)
        {
            $response = $this->app['pg_router']->paymentVerify($id, true);

            return $this->buildVerifyResponse($response);
        }

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        return $this->getNewProcessor($merchant)->verify($payment);
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

        if ($payment->card->isRecurringSupported(true, $payment->hasSubscription()) === false)
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
        if ($payment->getGateway() !== Gateway::PAYSECURE)
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

        $key = $payment->getPaymentResponseCacheKey();

        $payload = Crypt::encrypt($data);

        $this->app['cache']->put($key, $payload, Processor\Processor::REDIRECT_CACHE_RESPONSE_TTL);
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

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $data = $this->getNewProcessor($merchant)
                     ->forceAuthorizeFailedPayment($payment, $input);

        return $data;
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
        $variant = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),
            RefundConstants::RAZORX_KEY_REFUND_FETCH_BY_ID_AND_PAYMENT_FROM_SCROOGE,
            $this->mode
        );

        if ($variant === RefundConstants::RAZORX_VARIANT_ON)
        {
            return $this->app['scrooge']->refundsFetchByIdAndPayment($paymentId, $rfndId);
        }

        Payment\Entity::verifyIdAndStripSign($paymentId);
        Refund\Entity::verifyIdAndStripSign($rfndId);

        $refund = $this->repo->refund->fetchByIdPaymentIdMerchantId(
                                    $rfndId,
                                    $paymentId,
                                    $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function getCardForPayment($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->hasCard() === false)
        {
            throw new Exception\BadRequestException(Error\ErrorCode::BAD_REQUEST_NOT_CARD_PAYMENT);
        }

        $card = $this->repo->card->fetchForPayment($payment);

        unset($card[Card\Entity::NAME]);

        return $card->toArrayPublic();
    }

    public function retrieveRefundsForPayment($id, array $input = [])
    {
        $experiment = false;
        $scroogeRefundsArray = [];

        if ($this->app['basicauth']->isStrictPrivateAuth() === true)
        {
                $variant = $this->app->razorx->getTreatment($id,
                    RefundConstants::RAZORX_KEY_REFUND_FETCH_BY_PAYMENT_FROM_SCROOGE,
                    $this->mode
                );

                if ($variant === RefundConstants::RAZORX_VARIANT_ON)
                {
                    return $this->app['scrooge']->refundsFetchByPayment($id, $input);
                }
        }

        else
        {
            $variant = $this->app->razorx->getTreatment($id,
                RefundConstants::RAZORX_KEY_REFUND_FETCH_BY_PAYMENT_FROM_SCROOGE_PROXY,
                $this->mode
            );

            if ($variant === RefundConstants::RAZORX_VARIANT_ON) {

                $experiment = true;

                // keeping in shadow mode for non-private auth requests
                try
                {
                    $scroogeRefundsArray = $this->app['scrooge']->refundsFetchByPayment($id, $input);
                }
                catch (\Throwable $e)
                {
                    $this->trace->info(
                        TraceCode::REFUNDS_FETCH_BY_PAYMENT_SCROOGE_EXCEPTION,
                        [
                            'error_code' => $e->getCode(),
                            'error_message' => $e->getMessage(),
                        ]);
                }
            }
        }

        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $refunds = $this->repo->refund->findForPaymentAndMerchant($payment, $this->merchant);

        $refundsArray = $refunds->toArrayPublic();

        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            (new Payment\Refund\Service())->addModeAndPublicStatus($refundsArray);
        }

        if ($experiment === true)
        {
            (new Payment\Refund\Service())->compareRefundsAndLogDifference($refundsArray['items'], $scroogeRefundsArray['items'] ?? []);
        }

        return $refundsArray;
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
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

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

            $paymentMap = $this->app['pg_router']->paymentCapture($id, $input, true);

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
            $payment = $this->getNewProcessor()->capture($payment, $input);
        }

        if ($payment->getMethod() === Entity::CARD)
        {
            unset ($payment['card'][Card\Entity::NAME]);
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

        return $transfers->toArrayPublic();
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

            $payments = $this->repo->payment->findMany($paymentIds);
        }
        else
        {
            $from = Carbon::today(Timezone::IST)->subDays(8)->getTimestamp();
            $to = Carbon::today(Timezone::IST)->subDays(3)->getTimestamp();

            $payments = $this->repo->payment->fetchPendingCapturePaymentsBetweenTimestamps($from, $to, $limit);
        }

        $total = $payments->count();
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

        return $this->getNewProcessor($this->merchant)->mandatePause($input, $upiMandate);
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

        return $this->getNewProcessor($this->merchant)->mandateCancelViaCallback($input, $upiMandate);
    }

    public function unexpectedCallback(array $input, string $referenceId, string $gateway)
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

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        return $this->getNewProcessor($merchant)
                    ->authorizePush($input, $referenceId, $data, $terminal);
    }

    public function fetchMultiple(array $input)
    {
        $merchantId = $this->merchant->getId();

        $this->addInputTrace($input);

        $this->modifyInputForVATransaction($input);

        $payments = $this->repo->payment->fetchPaymentWithForceIndex($input, $merchantId);

        $paymentsArrayPublic = $payments->toArrayPublic();

        foreach ($paymentsArrayPublic['items'] as &$payment)
        {
            if (isset($payment['card']))
            {
                unset($payment['card'][Card\Entity::NAME]);
            }
        }

        return $paymentsArrayPublic;
    }

    private function modifyInputForVATransaction(&$input)
    {
        if (isset($input[Entity::VA_TRANSACTION_ID]) === true)
        {
            unset($input[Entity::VIRTUAL_ACCOUNT]);
        }
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

    public function fetch(string $id, array $input = []): array
    {
        $id = Entity::stripSignWithoutValidation($id);

        $payment = $this->repo
                        ->payment
                        ->findOrFailByPublicIdWithParams($id, $input);

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

        if(isset($entity['token']))
        {
            if($payment->card->isInternational() === false)
            {
                $data['id'] = $entity['token_id'];
                $network_token_data = (new Token\Service())->fetchNetworkToken($data);
                $entity['token'] = $network_token_data;
            }
        }

        if ($payment->getMethod() === Entity::CARD)
        {
            unset($entity['card'][Card\Entity::NAME]);
        }

        return $entity;
    }

    public function fetchById(string $id, array $input = []): array
    {
        $id = Entity::stripSignWithoutValidation($id);

        $payment = $this->repo
                        ->payment
                        ->findOrFailByPublicIdWithParams($id, $input);

        $paymentMerchantId = $payment->getMerchantId();

        $entity = $payment->toArrayPublicWithExpand();

        if ($entity['order_id'] != null)
        {
            $order = $this->repo
                ->order
                ->findByPublicId($entity['order_id']);

            $entity['order'] = $order->toArrayPublic();
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

        $iin = $payment->card->iinRelation;

        $this->updateDccDataIfApplicable($payment, $iin, $merchant,$dccInfo);

        $route = $this->app['api.route'];

        $data['type'] = 'dcc';

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

        $library = $this->getLibraryFromPayment($payment);
        //Check if Address is required for DCC transaction
        $data['avs_required'] = $this->isAddressRequired($library, $iin, $merchant);

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

        return $data;
    }

    public function updateAndRedirectToAuthorize($id, $input)
    {
        return $this->redirectToAuthorize($id, $input);
    }

    public function getPaymentFlows(array $input)
    {
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

        $this->updateDccDataIfApplicable($input, $iinEntity, $merchant,$data);

        $library = isset($input['source']) === true ? $input['source']: "";

        $data['avs_required'] = $this->isAddressRequired($library, $iinEntity, $merchant);

        $data['address_name_required'] = $this->isAddressWithNameRequired($input, $merchant);

        $this->updateCurrencyWrapperIfApplicable($input, $merchant, $data);

        $this->updateCurrencyWrapperForAppsIfApplicable($input, $merchant, $data);

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

        if ((isset($input['currency']) === true) and
            (isset($input['amount']) === true))
        {
            $amount = $input['amount'];
            $currency = $input['currency'];

            /* conditions for showing dcc:
                1. iin is dcc enabled and international(i.e country != IN)
                2. merchant's currency != card currency
            */
            if (($this->isDccEnabledIIN($iinEntity) === true)
                and ($currency !== $iinEntity->getIinCurrency()))
            {
                $dccInfo = $this->getDCCInfo($amount, $currency, $merchant->getDccMarkupPercentage());

                $dccInfo['card_currency'] = $iinEntity->getIinCurrency() ?? Currency\Currency::USD;

                $dccInfo['show_markup'] = $merchant->isDCCMarkupVisible();

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
                $currencyInfo = $this->getDCCInfo($amount, $currency, 5);

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

            // For Method APP Default DCC Markup is set as 6
            $currencyInfo = $this->getDCCInfo($amount, $currency, $merchant->getDccMarkupPercentageForApps());

            // First Currency in Currency Map is set as default currency for an app.
            $currencyInfo['app_currency'] = $enabledCurrencyList[0];

            $currencyInfo['all_currencies'] = array_intersect_key(
                                                          $currencyInfo['all_currencies'],
                                                          array_flip($enabledCurrencyList));

            $data = array_merge($data, $currencyInfo);
        }
    }


    public function isDccEnabledIIN($iinEntity): bool
    {
        if (($iinEntity !== null) and
            ($iinEntity->isInternational() === true) and
            (Card\Network::isDCCSupportedNetwork($iinEntity->getNetworkCode())) === true)
        {
            return true;
        }

        return false;
    }

    private function getDCCInfo($baseAmount, $baseCurrency, $markupPercent)
    {
        $dccInfo = [];

        $currencyRequestId = UniqueIdEntity::generateUniqueId();

        $dccInfo['all_currencies'] = (new Currency\DCC\Service)->getConvertedCurrencies($baseCurrency, $baseAmount, $currencyRequestId, $markupPercent);

        $dccInfo['currency_request_id'] = $currencyRequestId;

        return $dccInfo;
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

        // Fetch all the authorized payments whose refund_at is on or before current time.
        $payments = $this->repo->payment->getAuthorizedPaymentsToBeRefundedUsingRefundAt($ts, $limit);

        // Counts for determining the unsetting refund_at work and rejections
        $initialCount = $payments->count();
        $updatedRefundAt = 0;

        /**
         * Rejecting all the disputed payments
         * as the query only depends on refund_at column
         */
        try
        {
            $payments = $payments->reject(function ($payment) use (&$updatedRefundAt)
            {
                /**
                 * @var $payment Payment\Entity
                 */
                if ($payment->isDisputed() === true)
                {
                    return true;
                }

                $isRefundRequired = $this->isRefundRequiredForPayment($payment);

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
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::INFO, TraceCode::REFUND_EXCEPTION, ["message" => "mutex lock not acquired"]);
        }

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
     * and source transfer with on_hold_until less than today's
     *
     * @param array $input
     * @return array
     */
    public function updateOnHold(array $input): array
    {
        $timestamp = Carbon::today(Timezone::IST)->getTimestamp();

        $paymentsToUpdate = $this->repo->payment->getPaymentsOnHoldBeforeTimestamp($timestamp);

        $this->trace->debug(
            TraceCode::PAYMENT_UPDATE_HOLD_CRON,
            [
                'step'          => 'fetch_payments',
                'ids_fetched'   => $paymentsToUpdate->getIds(),
                'timestamp'     => Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d-m-Y H:i:s')
            ]
        );

        $cronSummary = [
            'total_count' => $paymentsToUpdate->count(),
            'failed_ids'  => []
        ];

        $mapForSettlementService = [];

        foreach ($paymentsToUpdate as $payment)
        {
            try
            {
                $txn = $this->repo->transaction(
                    function() use ($payment)
                    {
                       return $this->setHoldFalse($payment);
                    });

                $mid = $txn->getMerchantId();

                $bucketCore = New Settlement\Bucket\Core;

                if(isset($mapForSettlementService[$mid]) === false)
                {
                    $mapForSettlementService[$mid] = false;

                    $balance = $txn->accountBalance;

                    if($bucketCore->shouldProcessViaNewService($mid, $balance) === true)
                    {
                        $mapForSettlementService[$mid] = true;
                    }
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
            catch (\Exception $e)
            {
                $cronSummary['failed_ids'][] = $payment->getId();

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

        return [
            'success'   => true,
            'summary'   => $cronSummary
        ];
    }

    public function updateOnHoldBulkUpdate(array $input)
    {
       (new Payment\Validator)->validateInput('payment_onhold_bulk_update', $input);

       $onHold = $input['on_hold'];

       $paymentsToUpdate = $this->repo->payment->findManyByPublicIds($input['payment_ids']);

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

        $data = $this->getNewProcessor($merchant)->validateVpa($input);

        return $data;
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

    protected function setHoldFalse(Payment\Entity $payment)
    {
        $this->repo->payment->lockForUpdateAndReload($payment);

        $payment->setOnHold(false);

        $payment->setOnHoldUntil(null);

        $this->repo->saveOrFail($payment);

        $txn = $this->repo->transaction->lockForUpdate($payment->getTransactionId());

        $txn->setOnHold(false);

        $this->repo->saveOrFail($txn);

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
        // Fail if no associated transfer. @todo - Remove this when payment hold is added\
        else
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

    /**
     * Sends the authorized payments reminder email
     *
     * @param  string  $merchantId
     * @param  array   $payments
     * @param  boolean $final Whether this is the final payment reminder
     */
    protected function sendAuthorizedPaymentsReminderMail($merchantId, $payments, $final)
    {
        $merchant = (new Merchant\Entity)->findOrFail($merchantId);

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

    public function generateAndSaveOneTimeTokenWithContact($input)
    {
        $cacheTtl = 15 * 60; // multiplying by 60 since put() expects in seconds

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
            $payment = $this->repo->payment->find($paymentId);

            $card = $this->repo->card->find($cardId);

            $updated = (new Token\Core)->updatePaymentToken($payment, $card);

            if ($updated === true)
            {
                $this->repo->saveOrFail($payment);
            }
        }

        return $updated;
    }

    public function updateMerchantBalance(string $paymentId)
    {
        $payment = $this->repo->payment->find($paymentId);
        $transaction = $payment->transaction;

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

        $this->getNewProcessor($payment->merchant)->updateMerchantBalance($payment, $transaction);
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

            $paidOfferSubscription = $this->repo->offer->fetchSubscriptionOfferById($paidOffer->getId());

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
        $payment = $this->repo->payment->fetchByIdandSubscriptionId($paymentId, $subscriptionId);

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

    public function getAuthenticationEntityForAcquirerData($id)
    {
        return $this->app['card.payments']->fetchEntity('authentication', $id);
    }

    public function getAuthorizationEntity($id)
    {
        $paymentId = Payment\Entity::verifyIdAndStripSign($id);

        $response = $this->app['card.payments']->fetchEntity('authorization', $paymentId);

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

        $merchant =  $this->repo->merchant->findByPublicId($payment->getMerchantId());

        $payment->merchant()->associate($merchant);

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
            (new Notify($payment))->trigger($event);
        }
    }

    public function internalPricingFetchForPayment($id, $input)
    {
        if (isset($id) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Payment Id is a required field");
        }

        $payment = $this->repo->payment->findByPublicId($id);

        [$fee, $tax, $feeSplit] = (new Fee())->calculateMerchantFees($payment);

        $response = [
            "fee" => $fee,
            "tax" => $tax,
            "fee_split" => $feeSplit,
        ];

        $esInput['payment_ids'] = array($id);

        $this->paymentsCardEsSyncCron($esInput);

        return $response;
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

        (new Fraud\Notify())->notifyOpsIfNeeded($merchant, $riskData[ShieldConstants::TRIGGERED_RULES]);

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

        $payment =  $this->repo->payment->find($id);

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

        if((in_array($payment->getGateway(),Payment\Gateway::$fileBasedEMandateDebitGateways)=== true) and
            ($payment->getRecurringType() === Payment\RecurringType::AUTO))
        {
            $this->traceRetryVerifyFalse($id, TraceCode::PAYMENT_VERIFY_STOPPED_FOR_FILE_BASED_DEBITS, $data);

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

                $this->trace->info(
                    TraceCode::VERIFY_NEW_ROUTE_RESULT,
                    [
                        'payment_id' => $id,
                        'result'     => $result,
                    ]);

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

        $payment = $this->repo->payment->find($paymentId);

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

            $this->repo->transaction(function () use ($payment, & $data, $extraProperties)
            {
                $this->repo->payment->lockForUpdateAndReload($payment);

                try
                {
                    $this->getNewProcessor($payment->merchant)
                        ->setPayment($payment)
                        ->timeoutPayment();

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
        }

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
                $payment = $this->repo->payment->find($id);

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
            if (($iinEntity !== null) and ($iinEntity->isInternational() === true)
                and (empty($iinEntity->getCountry()) === false)
                and ($iinEntity->getCountry() !== null)
                and (Country::isAddressRequiredCountry($iinEntity->getCountry()))
            ) {
                return true;
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
         *  way to fetch Data from Druid table payments
        */
        $query1 = "select payments_reference1, payments_id, payments_merchant_id  from druid.payments_fact  where payments_reference1 in ('" . implode("','", $allArn) . "')";

        $druidService = $this->app['druid.service'];

        $content1 = [
            'query' => $query1
        ];

        // extract data from druid service
        list($error1, $data1) = $druidService->getDataFromDruid($content1);

        $this->trace->info(TraceCode::DISPUTE_CHARGEBACK_DRUID_RESPONSE, [
            'error1'    => $error1,
            'data1'      => $data1,
        ]);

        if (empty($error1) === false)
        {
            $this->trace->info(TraceCode::DRUID_REQUEST_FAILURE, [
                'query'   => $query1,
                'message' => $error1
            ]);
        }
        else
        {
            if (isset($data1) === false || array_key_exists(0, $data1) === false)
            {
                $this->trace->info(TraceCode::PAYMENT_DATA_NOT_FOUND_ON_DRUID, [
                    'arn-data' => $allArn,
                    'data'     => $data1,
                ]);
            }
        }

        foreach ($data1 as $item)
        {
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
         *  way to fetch Data from Druid table authorization
        */
        $query2 = "select authorization_rrn, authorization_payment_id, payments_merchant_id  from druid.payments_fact where authorization_rrn in ('" . implode("','", $requiredRrn) . "')";

        $content2 = [
            'query' => $query2
        ];

        list($error2, $data2) = $druidService->getDataFromDruid($content2);

        $this->trace->info(TraceCode::DISPUTE_CHARGEBACK_DRUID_RESPONSE, [
            'error2'    => $error2,
            'data2'     => $data2,
        ]);

        if (empty($error2) === false)
        {
            $this->trace->info(TraceCode::DRUID_REQUEST_FAILURE, [
                'query'   => $query2,
                'message' => $error2
            ]);
        }
        else
        {
            if (isset($data2) === false || array_key_exists(0, $data2) === false)
            {
                $this->trace->info(TraceCode::PAYMENT_DATA_NOT_FOUND_ON_DRUID, [
                    'rrn-data' => $requiredRrn,
                    'data'     => $data2,
                ]);
            }
        }

        $rrnVsPaymentData =  [];

        foreach ($data2 as $dataItem)
        {
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

        $variant = $this->app->razorx->getTreatment(
                $mid,
                Merchant\RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1,
                $this->mode
        );

        return (strtolower($variant) === RefundConstants::RAZORX_VARIANT_ON);
    }

    /**
     * Used in two places [GetFlows , PaymentCreate]
     * @param $iinEntity
     * @param Merchant\Entity $merchant
     * @return bool
     */
    public function isAddressWithNameRequired($input, Merchant\Entity $merchant): bool
    {
        if (($merchant !== null) and ($merchant->isInternational() === true) and
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

                $response = $this->unexpectedCallback($input, $input['upi']['merchant_reference'], $gateway);

                if (empty($response['payment_id']) === false)
                {
                    $unexpectedPaymentId = $response['payment_id'];

                    $this->trace->info(
                        TraceCode::UPI_UNEXPECTED_PAYMENT_CREATED,
                        [
                            'payment_id'            => $unexpectedPaymentId,
                            'npci_reference_id'     => $npciReferenceId,
                            'gateway'               => $gateway,

                        ]);
                }
                else
                {
                    $this->trace->info(
                        TraceCode::UPI_UNEXPECTED_PAYMENT_FAILED,
                        [
                            'npci_reference_id'     => $npciReferenceId,
                            'gateway'               => $gateway,

                        ]);
                }

            return [
                'payment_Id' => $unexpectedPaymentId,
                'success' => (empty($unexpectedPaymentId) === false),
            ];
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

    public function reviveOrderViaPL($id)
    {
        $payment_id = Payment\Entity::getSign().Payment\Entity::getDelimiter().$id;

        try {
            $payment = $this->repo->payment->findByPublicId($payment_id);
        } catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_PAYMENT_PL_CREATION_FAILED,
                [
                    'payment_id'         => $payment_id
                ]
            );
            return false;
        }

        if ($payment->hasOrder() == false || $payment->order->isPaid() == true)
        {
            return false;
        }

        return $this->core->createPaymentLinkToReviveOrder($payment);
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
        (new Payment\Validator)->validateInput('authorize_failed_upi_payment', $input);

        $paymentId = $input['payment']['id'];

        $gateway = $input['upi']['gateway'];

        $payment = $this->repo->payment->findOrFail($paymentId);

        if (($input['meta']['force_auth_payment'] === true) and
            ($this->isForceAuthAllowed($gateway) ===true))
        {
            return $this->forceAuthorizeUpiPayment($payment, $input);
        }
        else
        {
            return $this->verifyAuthorizeFailedPayment($payment, $input);
        }

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
            'success'        => ($payment->getStatus() === Payment\Status::AUTHORIZED),
            'payment_Id'     => $payment->getId(),
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
            // Try to make it authorized
            $verifyResponse = $this->verifyPayment($payment);

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_RESPONSE,
                [
                    'payment_id'    => $payment->getId(),
                    'amount'        => $payment->getAmount(),
                    'gateway'       => $payment->getGateway(),
                    'verify_status' => $verifyResponse,
                ]);

            if ($verifyResponse === VerifyResult::AUTHORIZED)
            {
                $this->verifyPaymentTransaction($payment->getId());
            }

            return [
                'success'        => ($payment->getStatus() === Payment\Status::AUTHORIZED),
                'payment_Id'     => $payment->getId(),
                'amount'         => $payment->getAmount(),
                'status'         => $payment->getStatus(),
                'rrn'            => $payment->getReference16(),
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
     * @param string $paymentId
     * @throws \Throwable
     */
    protected function verifyPaymentTransaction(string $paymentId)
    {
        $payment = $this->repo->payment->findOrFail($paymentId);

        if ($payment->hasTransaction() === true)
        {
            return;
        }

        try
        {
            $this->repo->transaction(function () use ($payment)
            {
                list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentAuthorized($payment);

                $this->repo->saveOrFail($txn);
                // This is required to save the association of the transaction with the payment.
                $this->repo->saveOrFail($payment);
            });
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
        return [
                Payment\Entity::VPA         => $input['upi']['vpa'],
                Payment\Entity::REFERENCE16 => $input['upi']['npci_reference_id'],
        ];
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

    private function moveTimedoutRecurringNachPaymentTokensToRejectedState($payment)
    {
        $nachToken = $this->repo->token->findByIdAndMerchantId($payment->getTokenId(), $payment->getMerchantId());

        $nachToken->setRecurringStatus(Token\RecurringStatus::REJECTED);

        $nachToken->setRecurringFailureReason(RegisterErrorCodes::NCEX);

        $nachToken->saveOrFail();
    }

}
