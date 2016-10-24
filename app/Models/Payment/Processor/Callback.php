<?php

namespace RZP\Models\Payment\Processor;

use RZP\Constants\Mode;
use RZP\Http\Route;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Emi;
use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Transaction;
use RZP\Models\Order;
use RZP\Exception;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use Mail;

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

        // For redirect flow
        $this->checkForMerchantCallbackUrl($payment);

        //
        // This field is received back from bank acs.
        // Kinda weird! And it's always null.
        //
        unset($gatewayInput['csrf']);
        $this->verifyHash($hash, $payment->getPublicId());

        if ($payment->isCreated() === false)
        {
            return $this->processPaymentCallbackSecondTime($payment);
        }

        $this->processPaymentCallback($payment, $gatewayInput);

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    public function redirectCallback($id)
    {
        $payment = $this->retrieve($id);

        if ($payment->isCreated() === false)
        {
            return $this->processPaymentCallbackSecondTime($payment);
        }

        throw new Exception\LogicException('Should not have been hit.');
    }

    /**
     * This means the payment has already been processed but
     * we are hitting callabck again. This could be due to
     * browser refresh by the customer or s2s callback notification being
     * delivered by the gateway before browser hits the callback route etc.
     */
    protected function processPaymentCallbackSecondTime($payment)
    {
        $this->trace->info(TraceCode::PAYMENT_CALLBACK_RETRY);

        $diff = time() - $payment->getCreatedAt();

        // If it was authorized recently then send back authorized again.
        if ((($payment->isAuthorized() === true) or
             (($payment->isCaptured() === true) and
              ($payment->getAutoCaptured() === true))) and
            ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
        {
            $this->trace->info(TraceCode::PAYMENT_CALLBACK_RETRY_SUCCESS);

            return $this->postPaymentAuthorizeProcessing($payment);
        }

        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCCESSED);
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

        if (in_array($gateway, Payment\Gateway::$s2sCallbackGateways) === false)
        {
            throw new Exception\LogicException(
                'Invalid gateway provided: ' . $gateway);
        }

        if ($payment->isCreated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCCESSED);
        }

        $this->processPaymentCallback($payment, $gatewayInput);

        $this->autoCapturePaymentIfApplicable($payment);

        return ['success' => true];
    }

    protected function processPaymentCallback($payment, $gatewayInput)
    {
        $input['payment'] = $payment->toArray();
        $input['gateway'] = $gatewayInput;

        if ($payment->globalCustomer !== null)
        {
            $input['customer'] = $payment->globalCustomer;
        }

        if ($payment->card !== null)
        {
            $input['card'] = $payment->card->toArray();
        }

        try
        {
            $data = $this->callGatewayCallback($input);

            $twoFactorAuth = null;

            if (isset($data[Payment\Entity::TWO_FACTOR_AUTH]) === true)
            {
                $twoFactorAuth = $data[Payment\Entity::TWO_FACTOR_AUTH];
            }

            $payment->setTwoFactorAuth($twoFactorAuth);


            $this->repo->saveOrFail($payment);
        }
        catch (Exception\BaseException $e)
        {
            $this->processPaymentCallbackException($e);
        }

        $this->updateAndNotifyPaymentAuthorized();
    }

    protected function callGatewayCallback($input)
    {
        // TODO: Refactor
        if ((isset($input['gateway']['type'])) and
            ($input['gateway']['type'] === 'otp'))
        {
            // TODO: Better name suggestions
            $data = $this->callGatewayFunction('callbackOtpSubmit', $input);

            $this->postPaymentOtpCallbackProcessing($input, $data);

            // Send a request to topup if balance is insufficient
            $this->callGatewayFunction('checkBalance', $input);
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
        $diff = time() - $payment->getUpdatedAt();

        if (($payment->isFailed()) and
            ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
        {
            $this->rethrowFailedPaymentErrorException($payment);
        }
    }

    protected function postPaymentOtpCallbackProcessing($input, $data)
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

        if ($status !== Status::CREATED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCCESSED);
        }

        $code = $e->getError()->getInternalErrorCode();

        $this->setTwoFactorAuthAfterCallbackException($this->payment, $e);

        if (Error\Error::hasAction($code) === false)
        {
            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);
        }
        else
        {
            $this->setPaymentError($e);
        }

        switch ($code)
        {
            case ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT:
                $payment->incrementOtpAttempts();
                $this->repo->saveOrFail($payment);
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

        //
        // If it has reached here, then an edge case occurred, for which
        // a suitable exception was not found and which must be handled.
        // So, we trace an error message, ringing alerts to our devs.
        //

        $this->trace->error(
            TraceCode::PAYMENT_CALLBACK_FAILURE,
            [
                'payment_id' => $payment->getPublicId(),
                'public_error_code' => $publicErrorCode,
                'internal_error_code' => $internalErrorCode,
                'error_description' => $errorDesc,
                'message' => 'Failed to convert error code to the appropriate exception'
            ]);

        // If no appropriate exception mapping was found then show
        // the usual message that payment already processed.

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCCESSED);
    }

    protected function checkForMerchantCallbackUrl($payment)
    {
        if ($payment->getCallbackUrl() !== null)
        {
            $this->app['rzp.merchant_callback_url'] = $payment->getCallbackUrl();
        }
    }
}
