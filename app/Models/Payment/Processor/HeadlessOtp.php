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
        if (($payment->isCardOrEmi() === true) and
            (Payment\Flow::isFeatureBasedFlowEnabled(Payment\Flow::HEADLESS_OTP) === true))
        {
            if ((Payment\Gateway::supportsHeadlessBrowser($payment->getGateway()) === true) and
                ($payment->card->iin->supports(IIN\Flow::HEADLESS_OTP) === true))
            {
                return true;
            }
        }

        return false;
    }

    protected function openHeadlessBrowser($payment, $request)
    {
        //
        // This will happen in case of single step payment.
        // Where payment is not to be authenticated
        if ($request === null)
        {
            return false;
        }

        $this->setHeadlessDummyCallbackUrl($request['content']);

        $data = [
            'payment_id' => $payment->getId(),
            'request'    => $request,
        ];

        $response = $this->app['card.otpelf']->otpSend($data);

        if ((empty($response) === false) and
            ($response['success'] === true) and
            ($response['data']['action'] === 'page_resolved') and
            ($response['data']['data']['type'] === 'otp'))
        {
            $payment->setFlow(Payment\Flow::HEADLESS_OTP);

            return ['url' => $this->getOtpSubmitUrl(), 'method' => 'POST'];
        }

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

        // Handle error codes
        return [];
    }

    protected function getHeadlessDummyCallbackUrl(&$content)
    {
        $content['TermUrl'] = 'https://api.razorpay.com';
    }
}
