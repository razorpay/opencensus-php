<?php

namespace RZP\Models\Payment\Processor;

use Config;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Card\IIN\Flow;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Card\IIN;
use RZP\Services\OtpElf;

trait HeadlessOtp
{
    public static $errorCodeToFlow = [
        TraceCode::HEADLESS_OTP_ELF_FAILURE => IIN\Flow::HEADLESS_OTP,
        ErrorCode::GATEWAY_ERROR_IVR_AUTHENTICATION_NOT_AVAILABLE => IIN\Flow::IVR,
    ];

    // Error codes for which payment should not be retried on 3DS flow.
    public static $elfErrorCodeMapping = [
        OtpElf::CARD_BLOCKED             => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_BLOCKED_CARD,
        OtpElf::CARD_INVALID             => ErrorCode::BAD_REQUEST_INVALID_CARD_DETAILS,
        OtpElf::CARD_NOT_ENROLLED        => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENROLLED_FOR_3DSECURE,
        OtpElf::MOBILE_NOT_UPDATED       => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_LINKED_WITH_MOBILE,
        OtpElf::NETWORK_ERROR            => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        OtpElf::BANK_ERROR               => ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
        OtpElf::PAYMENT_TIMEOUT          => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        OtpElf::BANK_SERVICE_DOWN        => ErrorCode::GATEWAY_ERROR_ISSUER_DOWN,
        OtpElf::NO_AVAILABLE_ACTIONS     => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
    ];

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
            ($this->merchant->isHeadlessEnabled() === true) and
            (is_null($payment->card) === false))
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

    protected function runHeadlessOtpFlow(Payment\Entity $payment, $request)
    {
        // This will happen in case of single step payment.
        // Where payment is not to be authenticated
        if ($request === null)
        {
            return;
        }

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_HEADLESS_INITIATED, $payment);

        try
        {
            $response = $this->openHeadlessBrowser($payment, $request);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_HEADLESS_PROCESSED, $payment);

            return $response;
        }
        catch(\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_HEADLESS_PROCESSED, $payment, $ex); //phpcs:ignore

            throw $ex;
        }
    }

    protected function openHeadlessBrowser(Payment\Entity $payment, $request)
    {
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
            'last4'   => $payment->card->getLast4(),
            'type'    => $payment->card->getType()
        ];

        $analytics = $payment->getMetadata('payment_analytics');

        // For private auth we should always fetch it from payment analytics
        if ($this->app['basicauth']->isPrivateAuth() === false)
        {
            if (empty($analytics['ip']) === true)
            {
                $analytics['ip'] = $this->app['request']->ip();
            }

            if (empty($analytics['user_agent']) === true)
            {
                $analytics['user_agent'] = $this->app['request']->header('User-Agent');
            }
        }

        $data = [
            'payment_id'  => $payment->getId(),
            'request'     => $request,
            'card'        => $card,
            'merchant_id' => $payment->getMerchantId(),
            'gateway'     => $payment->getGateway(),
            'client'      => [
                'ip' => $analytics['ip'],
                'ua' => $analytics['user_agent'],
            ]
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
                $payment->setAuthType(Payment\AuthType::HEADLESS_OTP);

                $content = $response['data']['data'];

                return ['url' => $this->getCallbackUrl(), 'content' => $content, 'method' => 'POST'];
            }
        }

        $traceInput = [
            'elf_response' => $response,
            'payment_id'   => $payment->getId(),
            'iin'          => $payment->card->getIin(),
        ];

        $this->handleFailedResponse($response, $payment, $traceInput);

        if ($payment->getAuthType() === Payment\AuthType::OTP)
        {
            $payment->setAuthType(Payment\AuthType::HEADLESS_OTP);

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_OTPELF_FAILURE,
                $response['error']['reason'] ?? $traceCode ?? null,
                'Failed to open Headless Browser',
                $traceInput,
                null,
                Payment\Action::ENROLL,
                true);
        }

        if ($this->isJsonRoute === true)
        {
            $this->headlessError = true;

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_OTPELF_FAILURE,
                null,
                null,
                [],
                null,
                null,
                true
            );
        }

        if ($this->isRupayNetwork($payment) === true)
        {
            throw new Exception\IntegrationException('Unknown error for Rupay transaction',
                ErrorCode::SERVER_ERROR_OTP_ELF_FAILED_FOR_RUPAY);
        }

        /*
         * If elf fail for unknown reason for Non Rupay Transaction we are setting original termurl for fallback
        */

        $request['content']['TermUrl'] = $originalTermUrl;

        return $request;
    }

    protected function submitHeadlessOtp($payment, $gatewayInput)
    {
        $data = [
            'payment_id' => $payment->getId(),
            'gateway'    => $gatewayInput,
            'method'     => $payment->getMethod(),
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
                                'next' => $this->getNextOtpAction($response['data']['data']['next']),
                                'method' => $payment->getMethod()
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

        $traceInput = [
            'elf_response' => $response,
            'payment_id'   => $payment->getId(),
            'iin'          => $payment->card->getIin(),
        ];

        $this->handleFailedResponse($response, $payment, $traceInput);

        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED,null,null,
            ['method' => $payment->getMethod()]
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

        $traceInput = [
            'elf_response' => $response,
            'payment_id'   => $payment->getId(),
            'iin'          => $payment->card->getIin(),
        ];

        $this->handleFailedResponse($response, $payment, $traceInput);

        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        );
    }

    /**
     * This function sets TermUrl to a dummy URL, so that payment is
     * not processed and we get the response
     */
    protected function setHeadlessDummyCallbackUrl(&$content)
    {
        $content['TermUrl'] = 'https://api.razorpay.com';
    }

    protected function handleFailedResponse($response, $payment, $traceInput)
    {
        $traceCode = TraceCode::HEADLESS_OTP_ELF_UNKNOWN_RESPONSE;

        if ((empty($response) === false) and
            ($response['success'] === false) and
            (isset($response['error']['reason']) === true))
        {
            $traceCode = TraceCode::HEADLESS_OTP_ELF_UNKNOWN_FAILURE;

            if ((isset($response['error']['fatal']) === true) and
                ($response['error']['fatal'] === true))
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

            if (array_key_exists($response['error']['reason'], self::$elfErrorCodeMapping) === true)
            {
                $payment->setAuthType(Payment\AuthType::HEADLESS_OTP);

                $errorCode = self::$elfErrorCodeMapping[$response['error']['reason']];

                throw new Exception\GatewayErrorException(
                    $errorCode, null, null,
                    ['method' => $payment->getMethod()]
                );
            }
        }

        $this->trace->critical($traceCode, $traceInput);
    }

    protected function disableIinFlowIfApplicable($payment, $code)
    {
        if ((($this->mode === Mode::TEST) and ($this->app->environment(Environment::PRODUCTION) === true))  or  (app()->isEnvironmentQA() === true))
        {
            return;
        }

        if ($payment->hasCard() === false)
        {
            return;
        }

        if (empty(self::$errorCodeToFlow[$code]) === true)
        {
            return;
        }

        $flow = self::$errorCodeToFlow[$code];

        $iin = $payment->card->getIin();

        $this->trace->info(
            TraceCode::IIN_FLOW_DISABLE,
            [
                'iin' => $iin,
                'flow'  => $flow,
            ]);

        if ($flow === Payment\AuthType::IVR)
        {
            $this->app['slack']->queue(
                'IIN disable notification',
                [
                    'iin'        => $iin,
                    'flow'       => $flow,
                    'payment_id' => $payment->getPublicId()
                ],
                [
                    'channel'  => Config::get('slack.channels.ivr_alerts'),
                    'username'              => 'alerts',
                    'icon'                  => ':x:'
                ]
            );
        }

        $iinEntity = $this->app['repo']->iin->find($iin);

        if ($flow === Payment\AuthType::HEADLESS_OTP)
        {
            $this->app['diag']->trackIINEvent(
                EventCode::BIN_HEADLESS_DISABLED,
                $iinEntity,
                null,
                [
                    'iin' => $iin,
                    'payment_id' => $payment->getPublicId(),
                    'disable_reason' => $code
                ]);
        }


        (new IIN\Service)->disableIinFlow($iin, $flow);
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

    protected function isHeadlessRetryableException($exception)
    {
        $internalErrorCode = $exception->getError()->getInternalErrorCode();

        $errorCodes = array_values(self::$elfErrorCodeMapping);

        if (in_array($internalErrorCode, $errorCodes, true) === true)
        {
            return false;
        }

        return true;
    }
}
