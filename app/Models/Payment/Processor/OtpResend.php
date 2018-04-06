<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;

trait OtpResend
{
    public function otpResend($id, array $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $payment = $this->retrieve($id);

        $this->trace->info(
            TraceCode::PAYMENT_OTP_RESEND_REQUEST,
            [
                'input'         => $input,
                'payment_id'    => $payment->getId(),
                'gateway'       => $payment->getGateway(),
            ]);

        $this->validatePaymentStatus($payment);

        $gatewayInput = [];

        $this->prePaymentOtpResendProcessing($payment, $input, $gatewayInput);

        if ($this->canRunOtpPaymentFlow($payment) === true)
        {
            $data = $this->runOtpResendFlow($gatewayInput, $payment);

            $payment->resetOtpAttempts();
            $payment->saveOrFail();

            $this->app['segment']->trackPayment($payment, TraceCode::OTP_RESEND);

            return $data;
        }

        $this->app['segment']->trackPayment($payment, TraceCode::OTP_RESEND_EXCEPTION);

        throw new Exception\LogicException(
            'Gateway does not support OTP resend',
            null,
            ['payment_id' => $id]);
    }

    protected function runOtpResendFlow($gatewayInput, $payment)
    {
        return $this->callGatewayOtpGenerate($gatewayInput, $payment, true);
    }

    protected function validatePaymentStatus($payment)
    {
        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        if ($payment->isCreated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
        }
    }

    protected function prePaymentOtpResendProcessing($payment, $input, array & $gatewayInput)
    {
        $this->verifyPaymentMethodEnabled($payment);

        // Set metadata in payment
        $payment->setMetadata($input);

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();

        $gatewayInput['otpSubmitUrl'] = $this->getOtpSubmitUrl();
    }
}
