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

        if ($payment->isSigned())
        {
            // If payment is signed, then we capture it in this step only.
            $payment = $this->capturePayment($payment, $payment->getAmount());
        }

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    /**
     * This means the payment has already been processed but
     * we are hitting callabck again. This could be due to
     * browser refresh by the customer or s2s callback notification being
     * delivered by the gateway before browser hits the callback route etc.
     */
    protected function processPaymentCallbackSecondTime($payment)
    {
        $diff = time() - $payment->getCreatedAt();

        // If it was authorized recently then send back authorized again.
        if (($payment->isAuthorized()) and
            ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
        {
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
        // Return if payments is signed to allow for payments to be captured
        // which come signed via shopify route.
        if ($payment->isSigned())
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
            $data = $this->callGatewayCallback($payment, $input);
        }
        catch (Exception\BaseException $e)
        {
            $this->processPaymentCallbackException($e);
        }

        $this->updateTokenOnAuthorized();

        $this->updateAndNotifyPaymentAuthorized();
    }

    protected function callGatewayCallback($payment, $input)
    {
        // TODO: Refactor
        if ((isset($input['gateway']['type'])) and
            ($input['gateway']['type'] === 'otp'))
        {
            // TODO: Better name suggestions
            $data = $this->callGatewayFunction('callbackOtpSubmit', $input);

            $this->postPaymentOtpCallbackProcessing($input, $data);

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

            $sharedAccount = (new Merchant\Repository)->getSharedAccount();

            $customer = (new Customer\Repository)->findByContactAndMerchant(
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

            $payment->setGlobalToken($token->getToken());

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

        if (Error\Error::hasAction($code) === false)
        {
            $this->updatePaymentFailed(
                $e->getError(),
                TraceCode::PAYMENT_AUTH_FAILURE);
        }
        else
        {
            $this->setPaymentError($e->getError());
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
}
