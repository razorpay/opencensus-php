<?php

namespace RZP\Models\Payment\Processor;

use Mail;

use RZP\Jobs;
use RZP\Error;
use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Models\Emi;
use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Customer;
use RZP\Error\ErrorCode;
use RZP\Models\Card\IIN;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Gateway\GooglePay;
use RZP\Models\Transaction;
use RZP\Models\Payment\Status;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\Methods;
use RZP\Models\Plan\Subscription;

trait Callback
{

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
    public function callback($id, $hash, array $gatewayInput)
    {
        $gatewayInputLog = $gatewayInput;

        unset($gatewayInputLog['otp']);
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

        // For redirect flow
        $this->checkForMerchantCallbackUrl($payment);

        //
        // This field is received back from bank acs.
        // Kinda weird! And it's always null.
        //
        unset($gatewayInput['csrf']);
        $this->verifyHash($hash, $payment->getPublicId());

        $response = $this->acquireLockAndProcessCallback($payment, $gatewayInput);

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

        $this->performSkippedValidations($gatewayInput, $payment);

        $this->mutex->acquireAndRelease(
            $this->getCallbackMutexResource($payment),
            function() use ($payment, $gatewayInput)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                // In case of non - corporate payments, this case is fine.
                // In case of corporate and payment already having been authorized
                if ($this->shouldProcessSecondS2sCallback($payment) === false)
                {
                    $this->app['segment']->trackPayment(
                        $payment, ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
                        null,
                        [
                            'payment_id' => $payment->getId(),
                            'gateway'    => $payment->getGateway(),
                            'status'     => $payment->getStatus(),
                        ]);
                }

                $isS2sCallback = true;

                $this->processPaymentCallback($payment, $gatewayInput, $isS2sCallback);

                $this->postPaymentAuthorizeOfferProcessing($payment);

                $this->autoCapturePaymentIfApplicable($payment);
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

            default :
                if ($payment->isCreated() === false)
                {
                    $result = false;
                }
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

            return $this->postPaymentAuthorizeProcessing($payment);
        }

        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        $this->app['segment']->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
    }

    protected function processPaymentCallback($payment, $gatewayInput, $s2sCallback = false)
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

        // In case of axis corporate payments, the s2s call back return unencrypted
        // data, however, the normal callback return parameters which are encrypted.
        if ($s2sCallback === true)
        {
            $input['s2s'] = true;
        }

        if ((empty($input['gateway']) === true) and
            ($input['payment']['method'] === Payment\Method::CARD))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_GATEWAY_EMPTY_CALLBACK);
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

            if (isset($data[Payment\Entity::TWO_FACTOR_AUTH]) === true)
            {
                $twoFactorAuth = $data[Payment\Entity::TWO_FACTOR_AUTH];

                $payment->setTwoFactorAuth($twoFactorAuth);

                $this->repo->saveOrFail($payment);
            }
        }
        catch (Exception\BaseException $e)
        {
            $this->processPaymentCallbackException($e);
        }

        $this->updateAndNotifyPaymentAuthorized($data);
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

    protected function acquireLockAndProcessCallback($payment, $gatewayInput)
    {
        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
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
                     ($payment->hasBeenAuthorized() === true)))
                {
                    return $this->processPaymentCallbackSecondTime($payment);
                }

                $this->processPaymentCallback($payment, $gatewayInput);

                return $this->postPaymentAuthorizeProcessing($payment);
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
                                    ],
                                    $payment->merchant,
                                    $callback = true);
    }

    protected function callGatewayCallback($input)
    {
        // TODO: Refactor
        if ((isset($input['gateway']['type'])) and
            ($input['gateway']['type'] === 'otp') and
            $input['payment'][Payment\Entity::CPS_ROUTE] !== Payment\Entity::CARD_PAYMENT_SERVICE)
        {
            $this->validateCallbackInputIfApplicable($input);

            // TODO: Better name suggestions
            $data = $this->callGatewayFunction(Payment\Action::CALLBACK_OTP_SUBMIT, $input);

            $this->postPaymentOtpCallbackProcessing($input, $data);

            // Send a request to topup if balance is insufficient
            $this->callGatewayFunction(Payment\Action::CHECK_BALANCE, $input);
        }
        else
        {
            $data = $this->callGatewayFunction(Payment\Action::CALLBACK, $input);
        }

        $this->callGatewayFunction(Payment\Action::DEBIT, $input);

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
            $input['emi'] = $this->repo->emi_plan->findOrFail($payment->getEmiPlanId());

            $input['payment_analytics'] = $this->repo->payment_analytics->findForPayment($payment->getId())[0];

            $card = $this->repo->card->findOrFail($payment->getCardId());

            $input['card'] = array();

            $input['card']['number'] = $this->getCardNumber($card);
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

    protected function processPaymentCallbackException($e)
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
        }

        $this->updatePaymentOnExceptionAndThrow($e);
    }

    protected function updatePaymentOnExceptionAndThrow($e)
    {
        $internalErrorCode = $e->getError()->getInternalErrorCode();

        $e->setData(['payment_id'  => $this->payment->getPublicId(),
                     'order_id'    => $this->payment->getPublicOrderId(),
                     'method'      => $this->payment->getMethod()]);

        if (Error\Error::hasAction($internalErrorCode) === false)
        {
            $this->updatePaymentAuthFailed($e);
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

        Error\Map::throwExceptionFromErrorDetails(
            $publicErrorCode, $internalErrorCode, $errorDesc);

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

    protected function getCallbackMutexResource(Payment\Entity $payment): string
    {
        return 'callback_' . $payment->getId();
    }

    protected function getCardNumber($card)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken);

        return $cardNumber;
    }

    public function performSkippedValidations($data, $payment)
    {
        if ((isset($payment['authentication_gateway']) === true) and
            ($payment['authentication_gateway'] === 'google_pay'))
        {
            $this->createAndAssociateCard($data, $payment);

            $this->runInternationalChecks($payment);

            $this->runFraudChecksIfApplicable($payment);
        }
    }

    protected function createAndAssociateCard($data, $payment)
    {
        $cardType           = $data[GooglePay\RequestFields::CARD_TYPE];
        $cardNetwork        = $data[GooglePay\RequestFields::CARD_NETWORK];

        $cardNumber         = $data[GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::METHOD_DETAILS][GooglePay\RequestFields::CARD_NUMBER];
        $expirationMonth    = $data[GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::METHOD_DETAILS][GooglePay\RequestFields::CARD_EXPIRY_MONTH];
        $expirationYear     = $data[GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::METHOD_DETAILS][GooglePay\RequestFields::CARD_EXPIRT_YEAR];

        $merchantId         = $data[GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::MERCHANT_ID];
        $merchant           = (new Merchant\Repository)->findOrFail($merchantId);

        $cardInput          = [
            Card\Entity::NUMBER       => $cardNumber,
            Card\Entity::EXPIRY_MONTH => $expirationMonth,
            Card\Entity::EXPIRY_YEAR  => $expirationYear,
            Card\Entity::CVV          => Card\Entity::DUMMY_CVV,
            Card\Entity::NAME         => Card\Entity::DUMMY_NAME,
        ];

        $this->repo->transaction(function() use ($cardInput, $payment, $merchant, $cardType, $cardNetwork)
        {
            $card = (new Card\Core)->create($cardInput, $merchant, $payment->isRecurring());
            $card->setType($cardType);
            $card->setNetwork($cardNetwork);
            $this->repo->saveOrFail($card);

            $this->payment->card()->associate($card);
            $this->repo->saveOrFail($payment);
        });
    }
}
