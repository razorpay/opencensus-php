<?php

namespace Models\Payment\Processor;

use Models\Payment;
use Models\Merchant;
use Trace\TraceCode;
use EE\Exception\LogicException;

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
            $data = $this->runOtpPaymentFlow($gatewayInput, $payment);

            $payment->resetOtpAttempts();
            $payment->saveOrFail();

            return $data;
        }

        throw new LogicException(
            'Gateway doesn\'t support OTP resend',
            ['payment_id' => $id]);
    }

    protected function prePaymentOtpResendProcessing($payment, $input, array & $gatewayInput)
    {
        $this->verifyPaymentMethodEnabled($payment, $input);

        (new TerminalPicker)->selectTerminal($payment, $this->mode);

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
    }
}