<?php

namespace Models\Payment\Processor;

use Models\Merchant;
use Models\Payment;
use Trace\TraceCode;

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

        assert(false, 'Shouldn\'t reach here.');
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

        return [];
    }

    protected function prePaymentTopupProcessing($payment, $input, array & $gatewayInput)
    {
        if ($payment->customer === null)
        {
            throw new Exception(

                );
        }

        $canTopup = $this->callGatewayFunction('canTopup', []);

        if ($canTopup === false)
        {
            throw new Exception\BaseException(

                );
        }

        (new TerminalPicker)->selectTerminal($payment, $this->mode);

        //
        // Call gateway input
        //
        $gatewayInput['gateway'] = $input;

        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['customer'] = $payment->customer->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
    }
}