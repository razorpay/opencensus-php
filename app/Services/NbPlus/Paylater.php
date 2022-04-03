<?php

namespace RZP\Services\NbPlus;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Jobs\Capture;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use Illuminate\Support\Arr;
use RZP\Gateway\Base\Verify;
use RZP\Models\Base\PublicEntity;

class Paylater extends Service
{

    // gateway entity attributes
    const GATEWAY_REFERENCE_NUMBER    = 'gateway_reference_number';
    const PROVIDER_REFERENCE_NUMBER   = 'provider_reference_number';
    const ADDITIONAL_DATA             = 'additional_data';
    const TOKEN                       = 'token';


    public function action(string $method, string $gateway, string $action, array $input)
    {
        $this->gateway = $gateway;

        $this->action = $action;

        $this->input = $input;

        if ($this->action === Action::AUTHORIZE_FAILED) {
            $action = Action::VERIFY;
        }

        if ($this->action === Action::FORCE_AUTHORIZE_FAILED) {
            if ($this->app['api.route']->getCurrentRouteName() === 'payment_force_authorize') {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ACTION);
            }

            return true;
        }

        if (empty($input[Entity::TERMINAL]) === false) {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();
        }

        foreach ($input as $key => $data) {
            if ((is_object($data) === true) and ($data instanceof PublicEntity)) {
                $input[$key] = $data->toArray();
            }
        }

        $input = $this->convertEmptyArrayToNull($input);

        $content = [
            Request::ACTION => $action,
            Request::GATEWAY => $gateway,
            Request::INPUT => $input,
        ];

        $response = $this->sendRequest('POST', 'action/' . $action . '/' . $method, $content);

        return $this->processResponse($response);
    }

    protected function convertEmptyArrayToNull($input)
    {
        // Empty arrays which are actually key-value pairs fail during json decoding on NBPlus so we set these specific keys to null if they are []

        $keyArray = [
            'gateway',
        ];

        foreach ($keyArray as $srcPath) {
            $value = Arr::get($input, $srcPath);

            if ((is_array($value) === true) and (empty($value))) {
                Arr::set($input, $srcPath, null);
            }
        }

        return $input;
    }

    protected function processResponse($response)
    {
        $returnData = null;

        switch ($this->action)
        {
            case Action::CHECK_ACCOUNT:
                $returnData = null;
                break;

            case Action::AUTHORIZE:
                $returnData = $this->processAuthorizeResponse($response);
                break;

            case Action::CALLBACK:
                $returnData = $this->getCallbackResponseData($response);
                break;

            case Action::VERIFY:
                $returnData = $this->processVerifyResponse($response);
                break;

            case Action::AUTHORIZE_FAILED:
                $returnData = $this->processAuthorizedFailedFlow($response);
                break;

            case Action::OTP_RESEND:
                $returnData = $this->processOtpResendResponse($response);
                break;

            default:
                throw new Exception\InvalidArgumentException(
                    'Not a valid action',
                    ['action' => $this->action]
                );
        }

        return $returnData;
    }

    // ---------------------- verify --------------------------------------

    protected function processAuthorizeResponse($response)
    {
        if (isset($response[RESPONSE::DATA]) === false)
        {
            return null;
        }
        if (isset($response[RESPONSE::DATA][RESPONSE::NEXT]) === true)
        {
            return $response[RESPONSE::DATA][RESPONSE::NEXT][RESPONSE::REDIRECT];
        }

        // for s2s flows
        return [
            "url"       => $response["data"][RESPONSE::OTP_SUBMIT_URL],
            "method"    => "post",
            'content'   => [
                            'next' => [
                                'resend_otp',
                                'submit_otp'
                            ]
            ]
        ];
    }

    protected function processOtpResendResponse($response)
    {
        return [
            "url"       => $response["data"][RESPONSE::OTP_SUBMIT_URL],
            "method"    => "post",
            'content'   => [
                            'next' => [
                                'resend_otp',
                                'submit_otp'
                            ]
            ]
        ];
    }

    protected function processVerifyResponse($response)
    {
        $verify = $this->verifyPayment($response);

        return $verify->getDataToTrace();
    }

    protected function checkApiSuccess(Verify &$verify)
    {

        $verify->apiSuccess = true;

        // If payment status is either failed or created then this is an api failure
        if (($this->input[Entity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($this->input[Entity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::CREATED)) {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify &$verify)
    {
        $verify->gatewaySuccess = $verify->verifyResponseContent[Response::GATEWAY_STATUS] ?? false;
    }

    // ---------------------- Authorized Failed ----------------------------

    protected function processAuthorizedFailedFlow($response)
    {
        $e = null;

        try {
            $this->verifyPayment($response);
        } catch (Exception\PaymentVerificationException $e) {
            $this->trace->info(
                TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
                [
                    'message' => 'Payment verification failed. Now converting to authorized',
                    'payment_id' => $this->input[Entity::PAYMENT][Payment\Entity::ID]
                ]);
        }

        if ($e === null) {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not',
                null,
                $this->input[Entity::PAYMENT]);
        }

        $acquirerData = $this->getAcquirerData($response);

        $additionData = $this->getAdditionalData($response);

        return array_merge($acquirerData, $additionData);
    }

    // ---------------------- Callback -------------------------------------

    protected function getCallbackResponseData($response)
    {
        $acquirerData = $this->getAcquirerData($response);

        $additionData = $this->getAdditionalData($response);

        $tokenData = $this->getTokenData($response);

        return array_merge($acquirerData, $additionData, $tokenData);
    }

    protected function getAdditionalData($response): array
    {
        if (isset($response[Response::DATA][self::ADDITIONAL_DATA]) === true) {
            return [
                'additional_data' => $response[Response::DATA][self::ADDITIONAL_DATA]
            ];
        }
        return [];
    }

    protected function getTokenData($response): array
    {
        if (isset($response[Response::DATA][self::TOKEN]) === true) {
            return [
                'token' => $response[Response::DATA][self::TOKEN]
            ];
        }
        return [];
    }

    protected function getAcquirerData($response): array
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $response[Response::DATA][Response::GATEWAY_REFERENCE_NUMBER]
            ]
        ];
    }
}

