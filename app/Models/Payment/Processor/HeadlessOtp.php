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
use RZP\Services\OtpElf;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Analytics\Metadata;

trait HeadlessOtp
{
    protected function getNextOtpAction(array $actions)
    {
        $map = [
            'resend_otp' => 'otp_resend',
        ];

        $newActions = [
            'otp_submit'
        ];

        foreach ((array) $actions as $action)
        {
            if (isset($map[$action]) === true)
            {
                $newActions[] = $map[$action];
            }
        }

        return $newActions;
    }

    protected function canRunHeadlessOtpFlow($payment)
    {
        if (($payment->isMethodCardOrEmi() === true) and
            ($this->isAuthTypeOtp($payment) === true) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::HEADLESS) === true))
        {
            if ((Payment\Gateway::supportsHeadlessBrowser($payment->getGateway()) === true) and
                ($payment->card->iinRelation !== null) and
                ($payment->card->iinRelation->supports(IIN\Flow::HEADLESS_OTP) === true))
            {
                return true;
            }
        }

        return false;
    }

    protected function isAuthTypeOtp(Payment\Entity $payment)
    {
        if (($payment->getAuthType() === Payment\AuthType::OTP) or
            (in_array(Payment\AuthType::OTP, $payment->getMetadata(Payment\Entity::PREFERRED_AUTH, []), true) === true))
        {
            return true;
        }

        return false;
    }

    protected function openHeadlessBrowser(Payment\Entity $payment, $request)
    {
        //
        // This will happen in case of single step payment.
        // Where payment is not to be authenticated
        if ($request === null)
        {
            return;
        }

        $originalTermUrl = $request['content']['TermUrl'];

        $this->setHeadlessDummyCallbackUrl($request['content']);

        $data = [
            'payment_id' => $payment->getId(),
            'request'    => $request,
        ];

        $response = $this->app['card.otpelf']->otpSend($data);

        if ((empty($response) === false) and
            ($response['success'] === true))
        {
            if (($response['data']['action'] === 'page_resolved') and
               ($response['data']['data']['type'] === 'otp'))
            {
                $payment->setAuthType(Payment\AuthType::HEADLESS_OTP);

                $content = $response['data']['data'];

                return ['url' => $this->getOtpSubmitUrl(), 'content' => $content, 'method' => 'POST'];
            }

            if ($response['data']['action'] === 'submit_otp')
            {
                $content = $response['data']['data'];

                return ['url' => $this->getCallbackUrl(), 'content' => $content, 'method' => 'POST'];
            }
        }

        $traceInput = [
            'elf_response' => $response,
            'payment_id'   => $payment->getId(),
            'iin'          => $payment->card->getIin(),
        ];

        $traceCode = TraceCode::HEADLESS_OTP_ELF_UNKNOWN_RESPONSE;

        if ((empty($response) === false) and
            ($response['success'] === false) and
            (isset($response['error']['reason']) === true))
        {
            $traceCode = TraceCode::HEADLESS_OTP_ELF_UNKNOWN_FAILURE;

            if (in_array($response['error']['reason'], OtpElf::$otpElfErrors, true) === true)
            {
                $this->disableHeadlessFlow($payment);

                $traceInput['disable_iin'] = true;
                $traceCode = TraceCode::HEADLESS_OTP_ELF_FAILURE;
            }
        }

        $this->trace->critical($traceCode, $traceInput);

        if ($payment->getAuthType() === Payment\AuthType::OTP)
        {
            throw new Exception\GatewayRequestException(
                'Failed to open Headless Browser',
                null,
                true);
        }

        /*
         * If elf fail for unknow reason we are setting original termurl for fallback
        */
        $request['content']['TermUrl'] = $originalTermUrl;

        return $request;
    }

    protected function submitHeadlessOtp($payment, $gatewayInput)
    {
        $data = [
            'payment_id' => $payment->getId(),
            'gateway'    => $gatewayInput,
        ];

        $response = $this->app['card.otpelf']->otpSubmit($data);

        if ($response['success'] === true)
        {
            switch ($response['data']['action'])
            {
                case 'submit_otp':
                    return $response['data']['data'];
                    break;

                case 'page_resolved':

                    if ($response['error']['reason'] === OtpElf::ERROR_INVALID_OTP)
                    {
                        $data = [];

                        if (isset($response['data']['data']['next']) === true)
                        {
                            $data = [
                                'next' => $this->getNextOtpAction($response['data']['data']['next'])
                            ];
                        }

                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                            null,
                            $data
                        );
                    }
                    break;
            }

            return [];
        }

        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        );
    }

    protected function resendHeadlessOtp($payment, $gatewayInput)
    {
        $data = [
            'payment_id' => $payment->getId(),
            'gateway'    => $gatewayInput,
        ];

        $response = $this->app['card.otpelf']->otpResend($data);

        if ($response['success'] === true)
        {
            $content = $response['data']['data'];

            return ['url' => $this->getOtpSubmitUrl(), 'content' => $content, 'method' => 'POST'];
        }
    }

    /**
     * This function sets TermUrl to a dummy URL, so that payment is
     * not processed and we get the response
     */
    protected function setHeadlessDummyCallbackUrl(&$content)
    {
        $content['TermUrl'] = 'https://api.razorpay.com';
    }

    protected function disableHeadlessFlow($payment)
    {
        $iin = $payment->card->getIin();

        $this->trace->info(TraceCode::IIN_FLOW_DISABLE, [
            'iin' => $iin,
            'flow'  => 'headless_otp',
        ]);

        (new IIN\Service)->disableIinFlow($iin, 'headless_otp');
    }
}
