<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Models\Risk;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Card\IIN;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Analytics\Metadata;

trait HeadlessOtp
{
    protected function canRunHeadlessOtpFlow($payment)
    {
        if (($payment->merchant->isFeatureEnabled(Feature\Constants::OTPELF) === true) and
            ($payment->isCard() === true))
        {
            if ((Payment\Gateway::supportsHeadlessOtp($payment->getGateway()) === true) and
                ($payment->card->iin->supports(IIN\Flow::HEADLESS_OTP) === true))
            {
                return true;
            }
        }

        return false;
    }

    protected function openHeadlessBrowser($payment, $request)
    {
        if ($request === null)
        {
            return null;
        }

        // todo: move it to a dummy one
        $request['content']['TermUrl'] = 'https://api.razorpay.com';

        $data = [
            'payment_id' => $payment->getId(),
            'request'    => $request,
        ];

        $response = $this->app['card.otpelf']->otpSend($data);

        if (($response['success'] === true) and
            ($response['data']['action'] === 'page_resolved') and
            ($response['data']['data']['type'] === 'otp'))
        {
            $payment->setFlow('headless_otp');

            return ['url' => $this->getOtpSubmitUrl(), 'method' => 'POST'];
        }

        // Returning the same request as a fallback
        return $request;
    }

    protected function submitHeadlessOtp($payment, $gatewayInput)
    {
        $data = [
            'payment_id' => $payment->getId(),
            'gateway'    => $gatewayInput,
        ];

        $response = $this->app['card.otpelf']->otpSubmit($data);

        if (($response['success'] === true) and
            ($response['data']['action'] === 'submit_otp'))
        {
            return $response['data']['data'];
        }

        // Returning the same request as a fallback
        return [];
    }
}
