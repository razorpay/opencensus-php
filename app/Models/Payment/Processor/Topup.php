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
            return $this->getPaymentGatewayRequestData($request, $payment);
        }

        rzpAssert(false, 'Should not reach here.');
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
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCCESSED);
        }

        //
        // Sharp gateway will execute in test mode and won't have global customer and
        // Slight hack for mobikwik as we are falling back on traditional redirection
        // flow for mobikwik as we are not using their topup flow right now
        //

        if (($gateway !== Payment\Gateway::SHARP) and
            ($payment->getWallet() !== Wallet::MOBIKWIK) and
            ($payment->globalCustomer === null))
        {
            throw new Exception\LogicException(
                'Customer does not exist', null, $input);
        }
    }

    protected function fillTopupGatewayInput($payment, $input, array & $gatewayInput)
    {
        $gateway = $payment->getGateway();

        //
        // Call gateway input
        //
        $gatewayInput['gateway']  = $input;

        $gatewayInput['payment']  = $payment->toArray();

        $gatewayInput['customer'] = $payment->globalCustomer;

        if ($payment->analytics !== null)
        {
            $gatewayInput['analytics'] = $payment->analytics->toArray();
        }

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
    }
}
