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

        throw new LogicException(
            'Gateway does not support OTP resend',
            null,
            ['payment_id' => $id]);
    }

    protected function runOtpResendFlow($gatewayInput, $payment)
    {
        if ($payment['wallet'] === Wallet::FREECHARGE)
        {
            return $this->callGatewayOtpResend($gatewayInput, $payment);
        }

        // For other gateways - otpResend === otpGenerate
        return $this->callGatewayOtpGenerate($gatewayInput, $payment);
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

    protected function callGatewayOtpResend($data, $payment)
    {
        try
        {
            $this->type = 'otp_resend';

            $this->callGatewayFunction('otpResend', $data);

            $payment->incrementOtpCount();
            $payment->save();

            return array(
                'type' => 'otp',
                'request' => [
                    'url' => $this->getOtpSubmitUrl(),
                    'method' => 'post',
                ],
                'version' => 1,
                'payment_id' => $payment->getPublicId(),
                'gateway' => $this->getEncryptedGatewayText($payment->getGateway()),
                // TODO: Return metadata in a better format
                'contact' => $payment->getContact(),
                'amount'  => number_format(($payment->getAmount()/100), 2),
            );
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }
    }
}