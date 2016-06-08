<?php

namespace Models\Payment\Processor;

use EE\Exception;
use Models\Payment;
use Models\Customer;
use Models\Merchant;
use Trace\TraceCode;
use EE\Error\ErrorCode;

trait Topup
{
    public function topup($id, $input)
    {
        $payment = $this->retrieve($id);

        $gatewayInput = [];

        try
        {
            $this->prePaymentTopupProcessing($payment, $input, $gatewayInput);

            return $this->callGatewayTopup($payment, $gatewayInput);
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_TOPUP_FAILURE);

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

        assert(false, 'Should not reach here.');
    }

    protected function prePaymentTopupProcessing($payment, $input, array & $gatewayInput)
    {
        //
        // Slight hack for mobikwik as we are falling back on traditional redirection
        // flow for mobikwik as we are not using their topup flow right now
        //
        if (($payment->getWallet() !== Wallet::MOBIKWIK) and
            ($payment->globalCustomer === null))
        {
            throw new Exception\BaseException(
                'Customer does not exist');
        }

        $canTopup = $this->callGatewayFunction('canTopup', []);

        if ($canTopup === false)
        {
            throw new Exception\BaseException(
                'Gateway doesn\'t support topup');
        }

        //
        // Call gateway input
        //
        $gatewayInput['gateway']  = $input;

        $gatewayInput['payment']  = $payment->toArray();

        $gatewayInput['customer'] = $payment->globalCustomer;

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
    }
}