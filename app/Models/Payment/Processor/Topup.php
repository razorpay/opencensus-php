<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Payment;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

trait Topup
{
    public function topup($id, $input)
    {
        $payment = $this->retrieve($id);

        $gatewayInput = [];

        $this->validateTopupFlow($payment, $input);

        $this->fillTopupGatewayInput($payment, $input, $gatewayInput);

        try
        {
            return $this->callGatewayTopup($payment, $gatewayInput);
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed($e, TraceCode::PAYMENT_TOPUP_FAILURE);

            throw $e;
        }
    }

    protected function callGatewayTopup($payment, array $data)
    {
        $request = $this->callGatewayFunction(
                                        Payment\Action::TOPUP,
                                        $data);

        if ($request !== null)
        {
            return $this->getFirstPaymentCreatedResponse($request, $payment);
        }

        assertTrue(false, 'Should not reach here.');
    }

    protected function validateTopupFlow($payment, $input)
    {
        $gateway = $payment->getGateway();

        if (Payment\Gateway::canGatewayTopup($gateway) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_CANNOT_TOPUP);
        }

        if ($payment->isCreated() === false)
        {
            // If it failed recently, then return the failure directly.
            $this->checkForRecentFailedPayment($payment);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
        }

        //
        // Sharp gateway will execute in test mode and won't have global customer and
        // Slight hack for mobikwik as we are falling back on traditional redirection
        // flow for mobikwik as we are not using their topup flow right now
        //

        if (($gateway !== Payment\Gateway::SHARP) and
            (in_array($payment->getWallet(), [Wallet::MOBIKWIK, Wallet::BAJAJPAY]) === false) and
            ($payment->getGlobalCustomerId() === null))
        {
            throw new Exception\LogicException(
                'Customer does not exist', null, $input);
        }

        if (($gateway !== Payment\Gateway::SHARP) and
            (in_array($payment->getWallet(), [Wallet::MOBIKWIK, Wallet::BAJAJPAY]) === false) and
            ($payment->getGlobalTokenId() === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TOPUP_INVALID_WALLET_TOKEN);
        }
    }

    protected function fillTopupGatewayInput($payment, $input, array & $gatewayInput)
    {
        //
        // Call gateway input
        //
        $gatewayInput['gateway']  = $input;

        $gatewayInput['payment']  = $payment->toArray();

        $gatewayInput['customer'] = $payment->globalCustomer;

        if ($payment->getGlobalTokenId() !== null)
        {
            $token = $this->repo->token->getGlobalOrLocalTokenEntityOfPayment($payment);

            $gatewayInput['token'] = $token->toArray();
        }

        if ($payment->analytics !== null)
        {
            $gatewayInput['analytics'] = $payment->analytics->toArray();
        }

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
    }
}
