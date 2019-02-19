<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Models\Card;
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

    protected function canRunHeadlessOtpFlow($payment, $gatewayInput)
    {
        if (empty($gatewayInput['auth_type']) === false)
        {
            if ($gatewayInput['auth_type'] === Payment\AuthType::HEADLESS_OTP)
            {
                return true;
            }

            return false;
        }

        if (($payment->isMethodCardOrEmi() === true) and
            ($this->isAuthTypeOtp($payment) === true) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::HEADLESS) === true))
        {
            $iin = $payment->card->iinRelation;

            if (($iin !== null) and
                (Payment\Gateway::supportsHeadlessBrowser($payment->getGateway(), $iin->getNetworkCode()) === true) and
                ($iin->supports(IIN\Flow::HEADLESS_OTP) === true) and
                ((isset($gatewayInput['authenticate']['auth_type']) === false) or
                 ($gatewayInput['authenticate']['auth_type'] === '3ds')))
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

        $originalTermUrl = null;

        if (($this->isRupayNetwork($payment) === false) and
            (isset($request['content']['TermUrl']) === true))
        {
            $originalTermUrl = $request['content']['TermUrl'];

            $this->setHeadlessDummyCallbackUrl($request['content']);
        }

        $card = [
            'iin'     => $payment->card->getIin(),
            'issuer'  => $payment->card->getIssuer(),
            'network' => $payment->card->getNetwork(),
            'type'    => $payment->card->getType()
        ];

        $data = [
            'payment_id' => $payment->getId(),
            'request'    => $request,
            'card'       => $card
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
                $this->disableIinFlowIfApplicable($payment, TraceCode::HEADLESS_OTP_ELF_FAILURE);

                $traceInput['disable_iin'] = true;
                $traceCode = TraceCode::HEADLESS_OTP_ELF_FAILURE;
            }

            if ($response['error']['reason'] === OtpElf::ERROR_TIMEOUT)
            {
                $payment->setAuthType(Payment\AuthType::HEADLESS_OTP);

                throw new Exception\GatewayTimeoutException(
                    ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
                    null,
                    false);
            }
        }

        $this->trace->critical($traceCode, $traceInput);

        if ($payment->getAuthType() === Payment\AuthType::OTP)
        {
            $payment->setAuthType(Payment\AuthType::HEADLESS_OTP);

            throw new Exception\GatewayRequestException(
                'Failed to open Headless Browser',
                null,
                false);
        }

        /*
         * If elf fail for unknow reason we are setting original termurl for fallback
        */
        if ($originalTermUrl !== null)
        {
            $request['content']['TermUrl'] = $originalTermUrl;
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

        if ((isset($response['success']) === true) and
            ($response['success'] === true))
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

    protected function disableIinFlowIfApplicable($payment, $code)
    {
        if ($code !== TraceCode::HEADLESS_OTP_ELF_FAILURE)
        {
            return;
        }

        $iin = $payment->card->getIin();

        $this->trace->info(TraceCode::IIN_FLOW_DISABLE, [
            'iin' => $iin,
            'flow'  => 'headless_otp',
        ]);

        (new IIN\Service)->disableIinFlow($iin, 'headless_otp');
    }

    protected function isRupayNetwork($payment)
    {
        $iinRelation = $payment->card->iinRelation;

        if (($iinRelation !== null) and
            ($iinRelation->getNetworkCode() === Card\Network::RUPAY))
        {
            return true;
        }

        return false;
    }
}
