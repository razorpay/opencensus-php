<?php

namespace RZP\Services\NbPlus;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use Illuminate\Support\Arr;
use RZP\Gateway\Base\Verify;
use RZP\Models\Base\PublicEntity;

class CardlessEmi extends Service
{

    // gateway entity attributes
    const GATEWAY_REFERENCE_NUMBER    = 'gateway_reference_number';
    const PROVIDER_REFERENCE_NUMBER   = 'provider_reference_number';
    const ADDITIONAL_DATA             = 'additional_data';

    public function action(string $method, string $gateway, string $action, array $input)
    {

        $this->gateway = $gateway;

        $this->action = $action;

        $this->input = $input;


        if($input['payment']['wallet'] === \RZP\Models\Payment\Processor\CardlessEmi::ZESTMONEY && $action === "capture")
        {
            return ;
        }


        if ($this->action === Action::AUTHORIZE_FAILED)
        {
            $action = Action::VERIFY;
        }

        if ($this->action === Action::FORCE_AUTHORIZE_FAILED)
        {
            if ($this->app['api.route']->getCurrentRouteName() === 'payment_force_authorize')
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ACTION);
            }

            return true;
        }

        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();
        }

        foreach ($input as $key => $data)
        {
            if ((is_object($data) === true) and ($data instanceof PublicEntity))
            {
                $input[$key] = $data->toArray();
            }
        }

        $input = $this->convertEmptyArrayToNull($input);

        $content = [
            Request::ACTION     => $action,
            Request::GATEWAY    => $gateway,
            Request::INPUT      => $input,
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

        foreach ($keyArray as $srcPath)
        {
            $value = Arr::get($input, $srcPath);

            if ((is_array($value) === true) and (empty($value)))
            {
                Arr::set($input, $srcPath, null);
            }
        }

        return $input;
    }

    protected function processResponse($response)
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $returnData = $response[RESPONSE::DATA][RESPONSE::NEXT][RESPONSE::REDIRECT];
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

            default:
                throw new Exception\InvalidArgumentException(
                    'Not a valid action',
                    ['action' => $this->action]
                );
        }

        return $returnData;
    }

    // ---------------------- verify --------------------------------------

    protected function processVerifyResponse($response): array
    {
        $verify = $this->verifyPayment($response);

        return $verify->getDataToTrace();
    }

    protected function checkApiSuccess(Verify &$verify){

        $verify->apiSuccess = true;

        // If payment status is either failed or created then this is an api failure
        if (($this->input[Entity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($this->input[Entity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify &$verify)
    {
        $verify->gatewaySuccess = $verify->verifyResponseContent[Response::GATEWAY_STATUS] ?? false;
    }

    // ---------------------- Authorized Failed ----------------------------

    protected function processAuthorizedFailedFlow($response): array
    {
        $e = null;

        try
        {
            $this->verifyPayment($response);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $this->trace->info(
                TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
                [
                    'message'       => 'Payment verification failed. Now converting to authorized',
                    'payment_id'    => $this->input[Entity::PAYMENT][Payment\Entity::ID]
                ]);
        }

        if ($e === null)
        {
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

    protected function getCallbackResponseData($response): array
    {
        $acquirerData = $this->getAcquirerData($response);

        $additionData = $this->getAdditionalData($response);

        return array_merge($acquirerData, $additionData);
    }

    protected function getAcquirerData($response): array
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $response[Response::DATA][Response::GATEWAY_REFERENCE_NUMBER]
            ]
        ];
    }

    protected function getAdditionalData($response): array
    {
        if(isset($response[Response::DATA][self::ADDITIONAL_DATA]) === true)
        {
            return [
                'additional_data' =>  $response[Response::DATA][self::ADDITIONAL_DATA]
            ];
        }
       return [];
    }

}
