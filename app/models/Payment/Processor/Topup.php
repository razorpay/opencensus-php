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
        $gatewayInput['gateway'] = $input;

        try
        {
            $this->prePaymentTopupProcessing($payment, $gatewayInput);

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
    }

    protected function prePaymentTopupProcessing($payment, array & $gatewayInput)
    {
        (new TerminalPicker)->selectTerminal($payment, $this->mode);

        $canTopup = $this->callGatewayFunction('canTopup', []);

        if ($canTopup === false)
        {
            throw new Exception\BaseException(

                );
        }
        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['customer'] = $payment->customer->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();

        if ($payment->order)
        {
            $gatewayInput['order'] = $payment->order->toArray();
        }
    }
}