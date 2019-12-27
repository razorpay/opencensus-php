<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;

trait OtpResend
{
    public function otpResend($id, array $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $this->trace->info(
            TraceCode::PAYMENT_OTP_RESEND_REQUEST,
            [
                'input'         => $input,
                'payment_id'    => $id
            ]);

        try
        {
            $payment = $this->retrieve($id);

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_AUTHENTICATION_OTP_RESEND_INITIATED, $payment);
        
            $this->validatePaymentStatus($payment);

            $gatewayInput = [];

            $this->prePaymentOtpResendProcessing($payment, $input, $gatewayInput);

            if ($this->canRunOtpPaymentFlow($payment) === true)
            {
                $data = $this->runOtpResendFlow($gatewayInput, $payment);

                $payment->resetOtpAttempts();
                
                $this->repo->saveOrFail($payment);

                $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_AUTHENTICATION_OTP_RESEND_PROCESSED, $payment);

                return $data;
            }
            else
            {
                throw new Exception\LogicException(
                    'Gateway does not support OTP resend',
                    null,
                    [
                        'payment_id' => $id
                    ]);
            }
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_AUTHENTICATION_OTP_RESEND_PROCESSED, $payment, $ex);
         
            $this->app['segment']->trackPayment($payment, TraceCode::OTP_RESEND_EXCEPTION);

            throw $ex;
        }
    }

    protected function runOtpResendFlow($gatewayInput, $payment)
    {
        // Checking if the resend is called for headless otp
        if (($payment->isMethodCardOrEmi() === true) and
            ($payment->getAuthType() === Payment\AuthType::HEADLESS_OTP))
        {
            $request = $this->resendHeadlessOtp($payment, $gatewayInput);

            return $this->getOtpPaymentCreatedResponse($request, $payment);
        }

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
