<?php

namespace RZP\Services\NbPlus;

use Illuminate\Support\Arr;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Models\Base\PublicEntity;

class AppMethod extends Service
{

    // gateway entity attributes
    const GATEWAY_REFERENCE_NUMBER       = 'gateway_reference_number';
    const PROVIDER_REFERENCE_NUMBER      = 'provider_transaction_number';
    const GATEWAY_STATUS                 = 'gateway_status';

    public function action(string $method, string $gateway, string $action, array $input)
    {
        $this->action = $action;

        $this->gateway = $gateway;

        $this->input = $input;

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

        if ($action !== Action::AUTHORIZE)
        {
            unset($input['payment']['billing_address']);
        }

        $content = [
            Request::ACTION  => $action,
            Request::GATEWAY => $gateway,
            Request::INPUT   => $input
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
                $returnData = $response[Response::DATA][Response::NEXT][Response::REDIRECT];
                break;

            case Action::CALLBACK:
                $returnData = $this->getCallbackResponseData($response);
                break;

            case Action::VERIFY:
                $returnData = $this->processVerifyResponse($response);
                break;

            case Action::AUTHORIZE_FAILED:
                $returnData = $this->processAuthorizeFailedFlow($response);
                break;

            default:
                throw new Exception\InvalidArgumentException(
                    'Not a valid action',
                    ['action' => $this->action]
                );
        }

        return $returnData;
    }

    // ----------------------- Verify ---------------------------------------------

    protected function processVerifyResponse($response)
    {
        $verify = $this->verifyPayment($response);

        return $verify->getDataToTrace();
    }

    protected function checkApiSuccess(Verify &$verify)
    {
        $verify->apiSuccess = true;

        // If payment status is either failed or created, this is an api failure
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

    // ----------------------- Authorize Failed ---------------------------------------------

    protected function processAuthorizeFailedFlow($response)
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
                    'message'    => 'Payment verification failed. Now converting to authorized',
                    'payment_id' => $this->input[Entity::PAYMENT][Payment\Entity::ID]
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

        return $this->getAcquirerData($response);
    }

    protected function getAcquirerData($response)
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $response[Response::DATA][Response::GATEWAY_REFERENCE_NUMBER]
            ]
        ];
    }

    protected function getCallbackResponseData($response)
    {
        return $this->getAcquirerData($response);
    }
}
