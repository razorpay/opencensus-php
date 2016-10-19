<?php

namespace RZP\Models\Payment\Processor;

use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Exception\LogicException;

trait OtpResend
{
    public function otpResend($id, array $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $payment = $this->retrieve($id);

        $gatewayInput = [];

        $this->prePaymentOtpResendProcessing($payment, $input, $gatewayInput);

        if ($this->canRunOtpPaymentFlow($payment, $input))
        {
            $data = $this->runOtpResendFlow($gatewayInput, $payment);

            $payment->resetOtpAttempts();
            $payment->saveOrFail();

            return $data;
        }

        $this->app['segment']->trackPayment($payment, TraceCode::SEGMENT_OTP_RESEND_EXCEPTION);

        throw new LogicException(
            'Gateway does not support OTP resend',
            null,
            ['payment_id' => $id]);
    }

    protected function runOtpResendFlow($gatewayInput, $payment)
    {
        return $this->callGatewayOtpGenerate($gatewayInput, $payment, true);
    }

    protected function prePaymentOtpResendProcessing($payment, $input, array & $gatewayInput)
    {
        $this->verifyPaymentMethodEnabled($payment, $input);

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
    }
}
