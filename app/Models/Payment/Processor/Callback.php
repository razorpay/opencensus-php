<?php

namespace RZP\Models\Payment\Processor;

use Mail;

use Razorpay\Trace\Logger;
use RZP\Jobs;
use RZP\Error;
use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Models\Discount;
use RZP\Models\Offer;
use RZP\Models\Emi;
use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use RZP\Models\Customer;
use RZP\Error\ErrorCode;
use RZP\Models\Card\IIN;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use RZP\Models\UpiMandate;
use RZP\Gateway\GooglePay;
use RZP\Models\Transaction;
use RZP\Models\Payment\Status;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\Methods;
use RZP\Models\Plan\Subscription;
use RZP\Exception\BadRequestException;
use RZP\Gateway\Upi\Base\RecurringTrait;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Locale\Core as LocaleCore;

trait Callback
{
    protected $shouldAuthorizePaymentOnCallback = true;

    /**
     * After payment initiation, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies payment action has been successful.
     *
     * @param string $id Payment id
     * @param string $hash
     * @param array  $gatewayInput contains fields provided
     *                             by bank
     *
     * @return Payment\Entity Updated payment entity
     * @throws Exception\BadRequestException
     */
    private function coreCallback($id, $hash, array $gatewayInput)
    {

       $startTime = microtime(true);
        // Axis migs started sending us card number in callback. This is a quickfix to
        // ignore the card number right before the callback is processed.

        unset($gatewayInput['realPan']);
        LocaleCore::setLocale($gatewayInput, $this->merchant->getId());

        $gatewayInputLog = $gatewayInput;

        unset($gatewayInputLog['otp']);
        // Redact PCI/PII fields of PineLabs
        unset($gatewayInputLog['masked_card_number']);
        unset($gatewayInputLog['card_holder_name']);

        if (empty($gatewayInputLog['PaReq']) === false)
        {
            $gatewayInputLog['PaReq'] = '*****redacted**** length: ' . strlen($gatewayInputLog['PaReq']);
        }

        $this->trace->info(
            TraceCode::PAYMENT_CALLBACK_REQUEST,
            [
                'gateway_input' => $gatewayInputLog,
                'payment_id'    => $id,
            ]);

        $payment = $this->retrieve($id);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CALLBACK_INITIATED, $payment);

        $this->app['segment']->trackPayment($payment, TraceCode::PAYMENT_CALLBACK_REQUEST);

        $this->validateOTP($payment, $gatewayInput);

        // For redirect flow
        $this->checkForMerchantCallbackUrl($payment);

        //
        // This field is received back from bank acs.
        // Kinda weird! And it's always null.
        //
        unset($gatewayInput['csrf']);
        $this->verifyHash($hash, $payment->getPublicId());

        $this->verifyCurrency($gatewayInput, $payment);

        $response = $this->acquireLockAndProcessCallback($payment, $gatewayInput);

         $this->logCallbackRequestTime($payment,$startTime);

        return $response;
    }

    /**
     * Wrap logic of `coreCallback` with tracing instrumentation
     *
     * @param string $id Payment id
     * @param string $hash
     * @param array  $gatewayInput contains fields provided
     *                             by bank
     *
     * @return Payment\Entity Updated payment entity
     * @throws Exception\BadRequestException
     */
    public function callback($id, $hash, array $gatewayInput)
    {
        $response = Tracer::inSpan(['name' => 'payment.callback'],
            function() use ($id, $hash, $gatewayInput){
                return $this->coreCallback($id, $hash, $gatewayInput);
            });
        return $response;
    }

    public function s2sCallback($payment, array $gatewayInput)
    {
        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_S2S_CALLBACK_INITIATED, $payment);

        // Return if payment is auto captured
        if ($payment->getAutoCaptured())
        {
            return ['success' => false];
        }

        $this->setPayment($payment);

        $this->performSkippedValidations($gatewayInput, $payment);

        $gateway = $payment->getGateway();

        if (in_array($gateway, Payment\Gateway::$s2sCallbackGateways, true) === false)
        {
            throw new Exception\LogicException(
                'Invalid gateway provided',
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'gateway'       => $gateway
                ]);
        }

        $this->mutex->acquireAndRelease(
            $this->getCallbackMutexResource($payment),
            function() use ($payment, $gatewayInput)
            {
                try
                {
                    // Adding the order id mutex for solving multiple captured payment on same order
                    // If payment has order id then resource will contain order id else payment id
                    $orderMutex = $this->getCallbackOrderMutexResource($payment);

                    $this->mutex->acquireAndRelease($orderMutex,
                        function() use ($payment, $gatewayInput)
                        {
                            // Reload in case it's processed by another thread.
                            $this->repo->reload($payment);

                            // In case of non - corporate payments, this case is fine.
                            // In case of corporate and payment already having been authorized
                            if ($this->shouldProcessSecondS2sCallback($payment) === false) {
                                $this->app['segment']->trackPayment(
                                    $payment, ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);

                                throw new Exception\BadRequestException(
                                    ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
                                    null,
                                    [
                                        'payment_id' => $payment->getId(),
                                        'gateway' => $payment->getGateway(),
                                        'status' => $payment->getStatus(),
                                    ]);
                            }

                            $isS2sCallback = true;

                            $this->processPaymentCallback($payment, $gatewayInput, $isS2sCallback);

                            // For this is firstUpiRecurringPayment and it is not authorized
                            // We will not follow the further steps.
                            // We do not just want to rely on payment status being authorized
                            // Note: Later if needed, one can add another payment check
                            if (($payment->isUpiRecurring() === true) and
                                ($payment->hasBeenAuthorized() === false)) {
                                if ($this->shouldHitDebitOnRecurringForUpi($payment) === true) {
                                    $this->processRecurringDebitForUpi($payment);
                                }

                                // Since the payment is UPI recurring and not authorized, we can
                                // skip the further steps on offers and auto capture
                                return;
                            }

                            // If gateways like Payu did not retun a terminal status
                            // for emandate payment from webhooks, then we skip all post processing.
                            if (($payment->isEmandateAutoRecurring() === true) and
                                ($payment->hasBeenAuthorized() === false) and
                                (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === true)) {
                                $this->trace->info(TraceCode::SKIP_S2S_CALLBACK_POST_PROCESSING,
                                    [
                                        'payment_id' => $payment->getId(),
                                        'gateway' => $payment->getGateway(),
                                        'status' => $payment->getStatus(),
                                        'method' => $payment->getMethod(),
                                    ]);

                                return;
                            }

                            $this->postPaymentAuthorizeOfferProcessing($payment);

                            $this->autoCapturePaymentIfApplicable($payment);
                        },
                        60,
                        ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                        20, 1000, 2000
                    );
                }
                catch (\Exception $ex)
                {
                    //this is a hack to send success response to PhonePe as they expect us to acknowledge the webhook with a 200 status else they keep on sending webhook's
                    if ($payment->getGateway() !== Payment\Gateway::WALLET_PHONEPE)
                    {
                        throw $ex;
                    }
                }
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return ['success' => true];
    }

    protected function shouldProcessSecondS2sCallback($payment)
    {
        $isCorporatePayment = $payment->isCorporateNetbanking();

        $method = $payment->getMethod();

        $result = true;

        $shouldActuallySkip = false;

        switch ($method)
        {
            case Payment\Method::NETBANKING:
                if ((($payment->isCreated() == false) and ($isCorporatePayment === false)) or
                (($isCorporatePayment === true) and ($payment->hasBeenAuthorized() === true)))
                {
                    $result = false;
                }
                break;

            case Payment\Method::UPI:
                if ($payment->hasBeenAuthorized() === true)
                {
                    $result = false;
                }
                break;

            case Payment\Method::EMANDATE:
                $acceptEmandateWebhook = $this->emandateS2SCallbackCheck($payment);

                if (($acceptEmandateWebhook === false) and
                    ($payment->getGateway() === Gateway::PAYU))
                {
                    $shouldActuallySkip = true;
                }

                break;

            default :
                if ($payment->isCreated() === false)
                {
                    $result = false;
                }
        }

        // This is to accept webhooks for gateways even if the payment is marked as failed.
        if ((Gateway::isWebhookEnabledGateway($payment->getGateway())) and ($payment->hasBeenAuthorized() === false))
        {
            $result = true;
        }

        // For some cases we dont want webhooks to be consumed, like if webhooks comes before callback.
        if ($shouldActuallySkip === true)
        {
            $this->trace->info(TraceCode::SKIP_WEBHOOK_FLOW,
                [
                    'method'        => $method,
                    'payment_id'    => $payment->getId(),
                ]);

            $result = false;
        }

        return $result;
    }

    protected function emandateS2SCallbackCheck($payment): bool
    {
        $result = false;

        if (empty($payment) === true)
        {
            return $result;
        }

        // Following conditions should be true to accept webhooks
        // 1. gateway can accept webhooks
        // 2. gateway is an async status gateway
        // 3. token and payment status check
        // 4. did gateway send webhook too soon
        if ((Gateway::isWebhookEnabledGateway($payment->getGateway())) and
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway())) and
            (   // if authorized and token not updated
                (($payment->hasBeenAuthorized() === true) and
                 ($payment->isRecurringTypeInitial() === true) and
                 ($payment->isCaptured() === false)) or
                // if second recurring and async payment update flow
                (($payment->hasBeenAuthorized() === false) and
                 ($payment->isCreated() === true) and
                 ($payment->isSecondRecurring() === true)) or
                // if initial or auto payment is failed
                ($payment->isFailed() === true)) and
            // if gateways send callback before NBPlus has saved
            // entities, skip webhook consumption
            ($this->checkCallbackTooSoon($payment) === false))
        {
            $result = true;
        }

        return $result;
    }

    public function redirectCallback($id)
    {
        $payment = $this->retrieve($id);

        // For redirect flow
        $this->checkForMerchantCallbackUrl($payment);

        if ($payment->isCreated() === false)
        {
            return $this->processPaymentCallbackSecondTime($payment);
        }

        throw new Exception\LogicException(
            'Should not have been hit.',
            null,
            [
                'payment_id' => $payment->getId(),
                'status'     => $payment->getStatus(),
                'order_id'   => $payment->getApiOrderId(),
                'gateway'    => $payment->getGateway(),
            ]);
    }

    /**
     * This means the payment has already been processed but
     * we are hitting callback again.
     *
     * This could be due to browser refresh by the customer or
     * s2s callback notification being delivered by the gateway before
     * browser hits the callback route etc.
     */
    protected function processPaymentCallbackSecondTime($payment)
    {
        $this->trace->info(TraceCode::PAYMENT_CALLBACK_RETRY);

        $diff = Carbon::now()->getTimestamp() - $payment->getCreatedAt();

        // If it was authorized recently then send back authorized again.
        if (($payment->hasBeenAuthorized() === true) and
            ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
        {
            $this->trace->info(TraceCode::PAYMENT_CALLBACK_RETRY_SUCCESS);

            $this->app['segment']->trackPayment($payment, TraceCode::PAYMENT_CALLBACK_RETRY_SUCCESS);

            if($payment->getInternalErrorCode()===ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BY_AVS)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BY_AVS);
            }

            return $this->processAuthorizeResponse($payment);
        }

        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        $this->app['segment']->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED, null, ['method' => $payment->getMethod()]);
    }

    protected function processPaymentCallback(Payment\Entity $payment, $gatewayInput, $s2sCallback = false)
    {
        $input['payment'] = $payment->toArrayGateway();
        $input['gateway'] = $gatewayInput;

        if ($payment->getGlobalCustomerId() !== null)
        {
            $input['customer'] = $this->repo->customer->getGlobalCustomerForPayment($payment);
        }

        $token = $this->repo->token->getGlobalOrLocalTokenEntityOfPayment($payment);

        if ($token !== null)
        {
            $input['token'] = $token;
        }

        if ($payment->hasCard())
        {
            $card = $this->repo->card->fetchForPayment($payment);

            $input['card'] = $card->toArray();
        }

        // Upi for OTM and Recurring need to send extra information in callback
        $this->modifyGatewayInputForUpi($payment, $input);

        // In case of axis corporate payments, the s2s call back return unencrypted
        // data, however, the normal callback return parameters which are encrypted.
        if ($s2sCallback === true)
        {
            $input['s2s'] = true;
        }

        if ((empty($input['gateway']) === true) and
            ($input['payment']['method'] === Payment\Method::CARD) and
            (Gateway::isGatewayCallbackEmpty($input['payment']['gateway']) === false))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_GATEWAY_EMPTY_CALLBACK,null,null,
                ['method'=>$payment->getMethod()]);
        }

        try
        {
            $this->preProcessGatewayCallback($input);
        }
        catch (Exception\BaseException $e)
        {
            $this->processHeadlessExceptionIfApplicable($e);
        }

        try
        {
            $data = $this->callGatewayCallback($input);

            //For Cred and walnut369 we receive the discount in the callback event.
            $this->addDiscountToPaymentIfApplicable($payment, $data);

            // set auth ref no. in case of a Rupay card
            $this->setAuthenticationReferenceNumberIfApplicable($payment->card, $data);

            if (isset($data[Payment\Entity::TWO_FACTOR_AUTH]) === true)
            {
                $twoFactorAuth = $data[Payment\Entity::TWO_FACTOR_AUTH];

                $payment->setTwoFactorAuth($twoFactorAuth);

                $this->repo->saveOrFail($payment);
            }

            // The gateways which allow amount difference in authorized will send
            // amount_authorized field explicitly in response
            if ((isset($data[Payment\Entity::AMOUNT_AUTHORIZED]) === true) and
                (isset($data[Payment\Entity::CURRENCY]) === true))
            {
                $currency           = $data[Payment\Entity::CURRENCY];
                $amountAuthorized   = $data[Payment\Entity::AMOUNT_AUTHORIZED];

                if ($this->processGatewayAmountAuthorized($payment, $currency, $amountAuthorized) === false)
                {
                    // We are still throwing the same exception for amount mismatch cases which is being
                    // thrown by the Base\Gateway, it will be caught later as BaseException
                    throw new Exception\LogicException(
                        'Amount tampering found.',
                        ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
                        [
                            'expected'  => $payment->getAmount(),
                            'actual'    => $amountAuthorized,
                        ]);
                }
            }
        }
        catch (Exception\BaseException $e)
        {
            if ($this->payment->isUpiAutoRecurring() and
                $e->getError()->getGatewayErrorCode() != null)
            {
                $canRetry = $this->checkUpiAutopayIncreaseDebitRetry($this->payment->getId(), $this->payment->getMerchantId());

                if ($canRetry === true)
                {
                    $this->trace->info(
                        TraceCode::UPI_AUTOPAY_CALLBACK_FAILURE,
                        [
                            'payment_id' => $this->payment->getPublicId(),
                            'error_code' => $e->getError()->getGatewayErrorCode()
                        ]);

                    $exception = new Exception\GatewayErrorException($e->getError()->getInternalErrorCode(),
                        $e->getError()->getGatewayErrorCode(),
                        $e->getError()->getGatewayErrorDesc(),
                        $e->getData()
                    );

                    $this->processDebitGatewayFailure($this->payment, $exception);

                    return;
                }
            }

            $this->processPaymentCallbackException($e);
        }

        if ($this->shouldCallPayAction($input, $data) === true)
        {
            try
            {
                $payData = $this->callGatewayPay($input);

                //For Cred we receive the discount in the callback event.
                $this->addDiscountToPaymentIfApplicable($payment, $payData);

                if (isset($payData[Payment\Entity::TWO_FACTOR_AUTH]) === true)
                {
                    $twoFactorAuth = $payData[Payment\Entity::TWO_FACTOR_AUTH];

                    $payment->setTwoFactorAuth($twoFactorAuth);

                    $this->repo->saveOrFail($payment);
                }

                $data = $payData;
            }
            catch (Exception\BaseException $e)
            {
                $traceData = [
                    'callbackData' => $data,
                    'payment_id'   => $payment->getId(),
                    'gateway'      => $payment->getGateway(),
                ];

                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::CARD_PAYMENT_SERVICE_PAY_ERROR,
                    $traceData);

                $this->processPaymentPayException($e);
            }
        }
        else if ($this->isAuthSplitPayment($input, $data) === true)
        {
            $this->updateAndNotifyPaymentAuthenticated($data);

            return $data;
        }

        $shouldLateAuthorize = false;

        // This condition has been added for upi recurring payments.For upi recurring payments we get two callbacks,
        // one for mandate approval and one for first debit. If upi mandate was confirmed late via verify, then in that
        // case we need to mark the payment as late authorized.
        if ((isset($input['upi_mandate']) === true) and
            ($input['upi_mandate']['late_confirmed']) === true)
        {
            $shouldLateAuthorize = true;
        }

        if ((Gateway::isWebhookEnabledGateway($payment->getGateway())) and ($payment->getStatus() === Payment\Status::FAILED) and ($s2sCallback === true))
        {
            $shouldLateAuthorize = true;
        }

        $this->updateAndNotifyPaymentAuthorized($data, $shouldLateAuthorize);
    }

    protected function shouldCallPayAction($input, $data)
    {
        if ($this->isRoutedThroughCardPayments(Payment\Action::PAY, $input) and
            (isset($data['status']) === true) and
            ($data['status'] === Payment\Status::AUTHENTICATED))
        {
            if ($this->merchant->isFeatureEnabled(Feature\Constants::AUTH_SPLIT) === true)
            {
                return false;
            }

            return true;
        }

        return false;
    }

    protected function isAuthSplitPayment($input, $data)
    {
        if ($this->isRoutedThroughCardPayments(Payment\Action::PAY, $input) and
            (isset($data['status']) === true) and
            ($data['status'] === Payment\Status::AUTHENTICATED))
        {
            if ($this->merchant->isFeatureEnabled(Feature\Constants::AUTH_SPLIT) === true)
            {
                return true;
            }

            return false;
        }

        return false;
    }

    // headless exception handling
    protected function processHeadlessExceptionIfApplicable($exception)
    {
        if ($this->isHeadlessRetryableException($exception) === true)
        {
            throw $exception;
        }

        $this->processPaymentCallbackException($exception);
    }

    protected function acquireLockAndProcessCallback(Payment\Entity $payment, $gatewayInput)
    {
        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment, $gatewayInput)
            {
                // Adding the order id mutex for solving multiple captured payment on same order
                // If payment has order id then resource will contain order id else payment id
                $orderMutex = $this->getCallbackOrderMutexResource($payment);

                return $this->mutex->acquireAndRelease($orderMutex,
                    function() use ($payment, $gatewayInput)
                    {
                        // Reload in case it's processed by another thread.
                        $this->repo->reload($payment);

                        $isCorporatePayment = $payment->isCorporateNetbanking();

                        $this->setSubscriptionForCallback($payment);

                        //
                        // In case of non - corporate payments, this case is fine.
                        // In case of corporate and payment already having been authorized
                        //
                        if ((($isCorporatePayment === false) and
                                ($payment->isCreated() === false)) or
                            (($isCorporatePayment === true) and
                                ($payment->hasBeenAuthorized() === true))) {
                            return $this->processPaymentCallbackSecondTime($payment);
                        }

                        $callbackData = $this->processPaymentCallback($payment, $gatewayInput);

                        if ($payment->getStatus() === Payment\Status::AUTHENTICATED) {
                            return $this->postPaymentAuthenticateProcessing($payment);
                        }

                        return $this->postPaymentAuthorizeProcessing($payment, $callbackData);
                    },
                    60,
                    ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                    20, 1000, 2000
                );
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }

    protected function setSubscriptionForCallback(Payment\Entity $payment)
    {
        if ($payment->hasSubscription() === false)
        {
            return;
        }

        $subscriptionId = Subscription\Entity::getSignedId($payment->getSubscriptionId());

        $this->subscription = $this->app['module']
                                   ->subscription
                                   ->fetchSubscriptionInfo(
                                    [
                                        Payment\Entity::AMOUNT          => $payment->getAmount(),
                                        Payment\Entity::SUBSCRIPTION_ID => $subscriptionId,
                                        Payment\Entity::METHOD          => $payment->getMethod(),
                                    ],
                                    $payment->merchant,
                                    $callback = true);
    }

    protected function callGatewayPay($input)
    {
        return $this->callGatewayFunction(Payment\Action::PAY, $input);
    }

    protected function callGatewayCallback($input)
    {
        // TODO: Refactor
        if ((isset($input['gateway']['type'])) and
            ($input['gateway']['type'] === 'otp') and
            $input['payment'][Payment\Entity::CPS_ROUTE] !== Payment\Entity::CARD_PAYMENT_SERVICE and
            $input['payment'][Payment\Entity::CPS_ROUTE] !== Payment\Entity::NB_PLUS_SERVICE)
        {
            $this->validateCallbackInputIfApplicable($input);

            // TODO: Better name suggestions
            $data = $this->callGatewayFunction(Payment\Action::CALLBACK_OTP_SUBMIT, $input);

            $this->postPaymentOtpCallbackProcessing($input, $data);

            // Send a request to topup if balance is insufficient
            $this->callGatewayFunction(Payment\Action::CHECK_BALANCE, $input);
        }
        else if ((Payment\Gateway::canRunOtpFlowViaNbPlus($input['payment'])) and
            (isset($input['gateway']['type'])) and
            ($input['gateway']['type'] === 'otp') and
            $input['payment'][Payment\Entity::CPS_ROUTE] !== Payment\Entity::CARD_PAYMENT_SERVICE)
        {
            $this->validateCallbackInputIfApplicable($input);

            $data = $this->callGatewayFunction(Payment\Action::CALLBACK, $input);

            $this->postPaymentOtpCallbackProcessing($input, $data);

            if(Payment\Gateway::shouldSkipDebit($input['payment']) !== true)
            {
                $this->callGatewayFunction(Payment\Action::AUTHORIZE, $input);
            }
        }
        else
        {
            $data = $this->callGatewayFunction(Payment\Action::CALLBACK, $input);
        }

        // Debit for UPI will be called separately and not from here
        if ($input['payment']['method'] !== Payment\Method::UPI)
        {
            $this->callGatewayFunction(Payment\Action::DEBIT, $input);
        }

        return $data;
    }

    protected function checkForRecentFailedPayment($payment)
    {
        // Difference should be less than 30 minutes
        $diff = Carbon::now()->getTimestamp() - $payment->getCreatedAt();

        if (($payment->isFailed()) and
            ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
        {
            $this->rethrowFailedPaymentErrorException($payment);
        }
    }
    protected function preProcessGatewayCallback(array &$input)
    {
        $payment = $this->payment;

        if ($payment->isMethodCardOrEmi() === true)
        {
            $pa = $this->repo->payment_analytics->findLatestByPayment($payment->getId());

            $input['payment_analytics'] = $pa ? $pa->toArray() : null;

            // It is observed when s2s merchants send multiple redirect requests for headless payments.
            // This causes failure on otp_elf resulting in reset of payment auth type
            // but customer has submitted otp on native otp page opened during first redirect request.
            if ((isset($input['gateway']['type']) === true) and
                ($input['gateway']['type'] === 'otp') and
                (($payment->getAuthType() === null) or
                 ($payment->getAuthType() === Payment\AuthType::_3DS)))
            {
                if (in_array($payment->getGateway(), Payment\Gateway::$otpPostFormSubmitGateways, true) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_OTP_SUBMIT_FOR_3DS_AUTH,null,
                        ['method'=>$payment->getMethod()]);
                }
            }
        }

        if (($payment->isMethodCardOrEmi() === true) and
            ($payment->getAuthType() === Payment\AuthType::HEADLESS_OTP) and
            ($payment->getCpsRoute() !== Payment\Entity::CARD_PAYMENT_SERVICE))
        {
            $input['gateway'] = $this->submitHeadlessOtp($payment, $input['gateway']);
        }

        if(($payment->isMethod(Payment\Method::EMI) === true) and
            (in_array($payment->getGateway(), Payment\Gateway::$otpPostFormSubmitGateways, true) === true))
        {
            $input['emi'] = $this->repo->emi_plan->handleFindOrFail($payment->getEmiPlanId());

            $input['payment_analytics'] = $this->repo->payment_analytics->findForPayment($payment->getId())[0];

            $card = $this->repo->card->findOrFail($payment->getCardId());

            $input['card'] = array();

            $input['card']['number'] = $this->getCardNumber($card, $payment->getGateway());
            $input['emi_plan'] = $payment->emi;
        }
    }

    protected function postPaymentOtpCallbackProcessing(array &$input, $data)
    {
        $payment = $this->payment;

        if (isset($input['customer']) === false)
        {
            $contact = $this->parseContact($input['payment']['contact']);

            $sharedAccount = $this->repo->merchant->getSharedAccount();

            $customer = $this->repo->customer->findByContactAndMerchant(
                                    $contact->format(), $sharedAccount);

            if ($customer === null)
            {
                $customerAttributes = array(
                    'contact' => $contact->format(),
                    'email'   => $input['payment']['email']
                );

                $customer = (new Customer\Core)
                                    ->createGlobalCustomer($customerAttributes);
            }

            $input['customer'] = $customer;

            $payment->globalCustomer()->associate($customer);
        }

        if (isset($data['token']) === true)
        {
            $token = $this->createOrUpdateToken($input, $data);

            $payment->globalToken()->associate($token);

            $input['token'] = $token->toArray();
        }

        $this->repo->saveOrFail($payment);
    }

    protected function processPaymentException($e)
    {
        // Refresh and check that payment is in created state only
        // This is because significant time has elapsed during
        // gateway request and we need to refresh it to take into
        // account race conditions.
        $this->lockForUpdateAndReload($this->payment);

        $this->migrateCardDataIfApplicable($this->payment);

        $payment = $this->payment;

        $status = $payment->getStatus();

        $isCorporatePayment = $payment->isCorporateNetbanking();

        // In case of corporate payments, process this.
        if (($status !== Status::CREATED) and
            ($status !== Status::AUTHENTICATED) and
            ($isCorporatePayment === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
                null,
                [
                    'payment_id'  => $this->payment->getPublicId(),
                    'order_id'    => $this->payment->getPublicOrderId(),
                    'method'      => $this->payment->getMethod(),
                    'status'      => $status
                ]);
        }

        $internalErrorCode = $e->getError()->getInternalErrorCode();

        $this->setTwoFactorAuthAfterCallbackException($e);

        $this->logRiskFailureForGateway($this->payment, $internalErrorCode);

        switch ($internalErrorCode)
        {
            case ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT:
            case ErrorCode::BAD_REQUEST_PAYMENTS_INVALID_OTP_TRY_NEW:
                $this->payment->incrementOtpAttempts();

                $this->app['segment']->trackPayment($payment,
                    ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT);

                break;

            case ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE:
                $this->trace->info(TraceCode::PAYMENT_WALLET_LOW_BALANCE, [
                    'id'     => $payment->getId(),
                    'wallet' => $payment->getWallet(),
                    'amount' => $payment->getAmount()
                ]);
                break;

            case ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED:
                if ($payment->isInAppUPI() === true)
                {
                    $this->trace->error(TraceCode::TURBO_UPI_GATEWAY_CALLBACK_CHECKSUM_MISMATCH,
                                        [
                                            'payment_id' => $payment->getId(),
                                            'status'     => $payment->getStatus(),
                                            'gateway'    => $payment->getGateway(),
                                        ]);
                }
                break;
        }
    }

    protected function processPaymentPayException($e)
    {
        $this->processPaymentException($e);

        $this->updatePaymentOnExceptionAndThrow($e, 'authorization');
    }

    protected function processPaymentCallbackException($e)
    {
        $this->processPaymentException($e);

        $this->updatePaymentOnExceptionAndThrow($e);
    }

    protected function updatePaymentOnExceptionAndThrow($e, $step = 'authorization')
    {
        $internalErrorCode = $e->getError()->getInternalErrorCode();

        $previousExceptionData = $e->getData() ?? [];

        if (($internalErrorCode === ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED) and
            ($this->payment->isInAppUPI() === true))
        {
            throw $e;
        }

        if(($this->payment->isEmandateRecurring() === true) and
            ($this->payment->isRecurringTypeInitial() === true) and
            ($this->payment->isGateway(Payment\Gateway::ENACH_NPCI_NETBANKING) === true) and
            ($this->isNpciFeedbackPopupAllowed() === true))
        {
            $this->trace->info(
                TraceCode::EMANDATE_NPCI_PAYMENT_FAILURE_CALLBACK,
                [
                    'payment_id'            => $this->payment->getPublicId(),
                ]);
            $e->setData(['payment_id'  => $this->payment->getPublicId(),
                'order_id'    => $this->payment->getPublicOrderId(),
                'method'      => $this->payment->getMethod(),
                'recurring_type'      => $this->payment->getRecurringType(),
                'gateway'      => $this->payment->getGateway(),
                'merchant_id'      => $this->payment->getMerchantId(),
                'application' => $this->payment->getAuthenticationGateway()]);
        }
        else
        {
            $e->setData(['payment_id'  => $this->payment->getPublicId(),
                'order_id'    => $this->payment->getPublicOrderId(),
                'method'      => $this->payment->getMethod(),
                'application' => $this->payment->getAuthenticationGateway()]);
        }

        if (Error\Error::hasAction($internalErrorCode) === false)
        {
            if ($step === 'authentication')
            {
                $this->updatePaymentAuthenticationFailed($e);
            }
            else
            {
                $this->updatePaymentAuthFailed($e);

                $this->processUpiRecurringFailureIfApplicable($this->payment, $previousExceptionData);

                $this->addBackupMethodForRetry($this->payment, $this->merchant, $e);
            }
        }
        else
        {
            $this->setPaymentError($e, TraceCode::PAYMENT_AUTH_PENDING);
        }

        throw $e;
    }

    protected function rethrowFailedPaymentErrorException($payment)
    {
        $internalErrorCode = $payment->getInternalErrorCode();
        $publicErrorCode = $payment->getErrorCode();
        $errorDesc = $payment->getErrorDescription();
        $data = [
            'payment_id'  => $this->payment->getPublicId(),
            'order_id'    => $this->payment->getPublicOrderId(),
            'method'      => $this->payment->getMethod()];

        Error\Map::throwExceptionFromErrorDetails(
            $publicErrorCode, $internalErrorCode, $errorDesc, $data);

        $errors = [
            'payment_id' => $payment->getPublicId(),
            'public_error_code'     => $publicErrorCode,
            'internal_error_code'   => $internalErrorCode,
            'error_description'     => $errorDesc,
            'message'               => 'Failed to convert error code to the appropriate exception'
        ];

        //
        // If it has reached here, then an edge case occurred, for which
        // a suitable exception was not found and which must be handled.
        // So, we trace an error message, ringing alerts to our devs.
        //

        $this->trace->error(TraceCode::PAYMENT_CALLBACK_FAILURE, $errors);

        // If no appropriate exception mapping was found then show
        // the usual message that payment already processed.

        $this->app['segment']->trackPayment($payment, TraceCode::PAYMENT_CALLBACK_FAILURE, $errors);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
    }

    protected function validateCallbackInputIfApplicable(array $input)
    {
        if ((isset($input['gateway']['type']) === true) and
            ($input['gateway']['type'] === 'otp'))
        {
            if (empty($input['gateway']['otp']) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Please enter a valid OTP.', 'otp', $input['gateway']);
            }
        }
    }

    protected function checkForMerchantCallbackUrl($payment)
    {
        if ($payment->getCallbackUrl() !== null)
        {
            $this->app['rzp.merchant_callback_url'] = $payment->getCallbackUrl();
        }
    }

    protected function getRedirectMutexResource(Payment\Entity $payment): string
    {
        return 'redirect_' . $payment->getId();
    }

    protected function getCardNumber($card , $gateway=null)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken,$card->toArray(),$gateway);

        return $cardNumber;
    }

    public function performSkippedValidations($data, $payment)
    {
        if ((isset($payment['authentication_gateway']) === true) and
            ($payment['authentication_gateway'] === 'google_pay'))
        {
            $this->performMethodRelatedChecksAndUpdates($data, $payment);
        }
    }

    protected function performMethodRelatedChecksAndUpdates($data, $payment)
    {

        $method = (new GooglePay\Gateway())->fetchPaymentMethod($data, $payment->getGateway());

        switch ($method)
        {
            case Payment\Method::CARD:

                $this->createAndAssociateCard($data, $payment);

                $this->createAndAssociateTerminal($payment);

                $this->runInternationalChecks($payment);

                $this->runFraudChecksIfApplicable($payment);

                break;

            case Payment\Method::UPI:

                $this->updateGooglePayUpiPayment($payment);

                break;
        }
    }

    protected function addDiscountToPaymentIfApplicable(Payment\Entity $payment, $callbackData)
    {
        if ($payment->isAppCred() === true)
        {
            $this->addDiscountToCred($payment, $callbackData);
        }

        if ($payment->isCardlessEmiWalnut369() === true)
        {
            $this->addDiscountToWalnut369($payment, $callbackData);
        }
    }

    protected function addDiscountToCred($payment, $callbackData)
    {
        if ((isset($callbackData['data']['credCoins']) === true) and
            ($callbackData['data']['credCoins'] !== 0))
        {
            $discountAmount = $this->getValidatedCredCoins($callbackData['data']['credCoins'], $payment);
            $discountInput = [Discount\Entity::AMOUNT => $discountAmount,];
            (new Discount\Service)->create($discountInput, $payment, null);
        }
    }

    protected function addDiscountToWalnut369($payment, $callbackData) {

        if ($this->merchant->isFeatureEnabled(Feature\Constants::SOURCED_BY_WALNUT369) === true)
        {
            // apply discount
            // get mdr and subvention from callback data
            $mdr        = (float) $callbackData['additional_data']['mdr'];
            $subvention = (float) $callbackData['additional_data']['subvention'];

            // Removing this check as there is use case for insurance merchants when they can have 0% mdr and
            // such merchants are charged with the fee later at the end of the month
            // edge case: for SOURCED_BY_WALNUT369 we remove the fee during transaction enitity creation,
            // hence after removing this check it will not be possible to detect human errors untill finops figures it out
            // at the end of the month
            /* if(($mdr === 0.0) and ($subvention === 0.0))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_RESPONSE_BODY);
            }*/

            $discountAmount = 0;

            if(empty($mdr) === false)
            {
                $discountAmount = (($payment->getAmount() * ($mdr * 100)) / 10000);
            }

            if(empty($subvention) === false)
            {
                $discountAmount = (($payment->getAmount() * ($subvention * 100)) / 10000);
            }

            $discountAmount = (int) $discountAmount;

            $discountInput = [Discount\Entity::AMOUNT => $discountAmount];
            (new Discount\Service)->create($discountInput, $payment, null);
        }
    }

    protected function getValidatedCredCoins($coins, $payment)
    {
        //1 coin = 1 Re, in the CRED API response
        $coins *= 100;
        if ($payment->getAmount() < $coins)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DISCOUNT_GREATER_THAN_BASE_AMOUNT);
        }

        return $coins;
    }

    protected function createAndAssociateCard($data, $payment)
    {
        $cardType           = strtolower($data[GooglePay\RequestFields::CARD_TYPE]);
        $cardNetwork        = $data[GooglePay\RequestFields::CARD_NETWORK];

        $cardNumber         = $data[GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::METHOD_DETAILS][GooglePay\RequestFields::CARD_NUMBER];
        $expirationMonth    = $data[GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::METHOD_DETAILS][GooglePay\RequestFields::CARD_EXPIRY_MONTH];
        $expirationYear     = $data[GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::METHOD_DETAILS][GooglePay\RequestFields::CARD_EXPIRY_YEAR];

        $merchantId         = $data[GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::MERCHANT_ID];
        $merchant           = (new Merchant\Repository)->findOrFail($merchantId);

        $cardInput          = [
            Card\Entity::NUMBER       => $cardNumber,
            Card\Entity::EXPIRY_MONTH => $expirationMonth,
            Card\Entity::EXPIRY_YEAR  => $expirationYear,
            Card\Entity::CVV          => Card\Entity::DUMMY_CVV,
            Card\Entity::NAME         => Card\Entity::DUMMY_NAME,
            Card\Entity::IS_TOKENIZED_CARD  => true,
        ];

        $this->repo->transaction(function() use ($cardInput, $payment, $merchant, $cardType, $cardNetwork)
        {
            $card = (new Card\Core)->create($cardInput, $merchant, $payment->isRecurring());
            $card->setType($cardType);
            $card->setNetwork($cardNetwork);
            $this->repo->saveOrFail($card);

            $this->payment->card()->associate($card);
            $this->payment->setMethod(Payment\Method::CARD);
            $this->repo->saveOrFail($payment);
        });
    }

    /**
     * @param $payment
     */
    protected function createAndAssociateTerminal($payment): void
    {
        $payment->setApplication(Payment\Gateway::GOOGLE_PAY);
        $terminals = (new TerminalProcessor)->getTerminalsForPayment($payment);
        $payment->associateTerminal($terminals[0]);
        $this->repo->saveOrFail($payment);
    }

    protected function updateGooglePayUpiPayment($payment)
    {
        $payment->setMethod(Payment\Method::UPI);

        $payment->saveOrFail();
    }

    protected function checkCallbackTooSoon($payment)
    {
        if (empty($payment) === true)
        {
            return true;
        }

        $diff = Carbon::now()->getTimestamp() - $payment->getCreatedAt();

        $this->trace->info(TraceCode::PAYMENT_CALLBACK_RETRY,
                [
                    'payment_id'         => $payment->getId(),
                    'current_timestamp'  => Carbon::now()->getTimestamp(),
                    'payment_created_at' => $payment->getCreatedAt(),
                    'delta'              => $diff,
                ]);

        if ($diff > self::CALLBACK_PROCESS_AGAIN_DURATION * 10)
        {
            $this->trace->info(TraceCode::PAYMENT_CALLBACK_RETRY_SUCCESS);

            return false;
        }

        return true;
    }

    protected function setAuthenticationReferenceNumberIfApplicable($card, $callbackData)
    {
        if (isset($card) === false)
        {
            return;
        }

        if ($card->isRupay() === false)
        {
            return;
        }

        if (isset($callbackData['authentication_reference_number']) === true)
        {
            $authReferenceNumber = $callbackData['authentication_reference_number'];

            $card->setReference4($authReferenceNumber);

            $this->repo->saveOrFail($card);
        }
    }

    protected function isNpciFeedbackPopupAllowed(): bool
    {
        try
        {
            $variantFlag = $this->app['razorx']->getTreatment($this->payment->getMerchantId(),
                RazorxTreatment::ALLOW_NPCI_FEEDBACK_POPUP_EMANDATE_FAILURE,
                $this->app['rzp.mode']);

            $this->trace->info(
                TraceCode::EMANDATE_ALLOW_NPCI_FEEDBACK_RAZORX_SUCCESS,
                [
                    'variant' => $variantFlag
                ]);

            return (strtolower($variantFlag) === 'on');
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::EMANDATE_ALLOW_NPCI_FEEDBACK_RAZORX_FAILURE,
                [
                    'error' => $e,
                ]);

            return false;
        }
    }
}
