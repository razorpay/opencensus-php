<?php

namespace RZP\Models\Payment\Processor;

use Mail;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Emi;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Payment\Status;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;

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
        $this->trace->info(
            TraceCode::PAYMENT_CALLBACK_REQUEST,
            [
                'gateway_input' => $gatewayInput,
                'payment_id'    => $id,
            ]);

        $payment = $this->retrieve($id);

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

        $this->mutex->acquireAndRelease(
            $this->getCallbackMutexResource($payment),
            function() use ($payment, $gatewayInput)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                $isCorporatePayment = $payment->terminal->isCorporate();

                // In case of non - corporate payments, this case is fine.
                // In case of corporate and payment already having been authorized
                if ((($payment->isCreated() === false) and
                    ($isCorporatePayment === false)) or
                    (($isCorporatePayment === true) and
                    ($payment->hasBeenAuthorized() === true)))
                {
                    $this->app['segment']->trackPayment(
                        $payment, ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
                }

                $isS2sCallback = true;

                $this->processPaymentCallback($payment, $gatewayInput, $isS2sCallback);

                $this->autoCapturePaymentIfApplicable($payment);
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return ['success' => true];
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

        throw new Exception\LogicException('Should not have been hit.');
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

        $diff = time() - $payment->getCreatedAt();

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

        if ($payment->getGlobalTokenId() !== null)
        {
            $token = $this->repo->token->getGlobalOrLocalTokenEntityOfPayment($payment);
            $input['token'] = $token->toArray();
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

    protected function acquireLockAndProcessCallback($payment, $gatewayInput)
    {
        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment, $gatewayInput)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                $isCorporatePayment = $payment->terminal->isCorporate();

                // In case of non - corporate payments, this case is fine.
                // In case of corporate and payment already having been authorized
                if ((($payment->isCreated() === false) and
                    ($isCorporatePayment === false)) or
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

    protected function callGatewayCallback($input)
    {
        // TODO: Refactor
        if ((isset($input['gateway']['type'])) and
            ($input['gateway']['type'] === 'otp'))
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
        $diff = time() - $payment->getCreatedAt();

        if (($payment->isFailed()) and
            ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
        {
            $this->rethrowFailedPaymentErrorException($payment);
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

            $this->app['segment']->trackPayment($payment, TraceCode::OTP_POSTPROCESSING, ['is_customer_set' => true]);
        }

        if (isset($data['token']) === true)
        {
            $token = $this->createOrUpdateToken($input, $data);

            $payment->globalToken()->associate($token);

            $input['token'] = $token->toArray();

            $this->app['segment']->trackPayment($payment, TraceCode::OTP_POSTPROCESSING, ['is_token_set' => true]);
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

        $payment = $this->payment;

        $status = $payment->getStatus();

        $isCorporatePayment = $payment->terminal->isCorporate();

        // In case of corporate payments, process this.
        if (($status !== Status::CREATED) and
            ($isCorporatePayment === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
                null,
                [
                    'payment_id' => $this->payment->getId(),
                    'status' => $status
                ]);
        }

        $internalErrorCode = $e->getError()->getInternalErrorCode();

        $this->setTwoFactorAuthAfterCallbackException($e);

        $this->logRiskFailureForGateway($this->payment, $internalErrorCode);

        if (Error\Error::hasAction($internalErrorCode) === false)
        {
            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);
        }
        else
        {
            $this->setPaymentError($e, TraceCode::PAYMENT_AUTH_PENDING);
        }

        switch ($internalErrorCode)
        {
            case ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT:
                $payment->incrementOtpAttempts();

                $this->repo->saveOrFail($payment);

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
}
