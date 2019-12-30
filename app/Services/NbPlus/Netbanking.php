<?php

namespace RZP\Services\NbPlus;

use App;
use RZP\Exception;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Models\Base\PublicEntity;

class Netbanking extends Service
{
    public function action(string $gateway, string $action, array $input)
    {
        $this->action = $action;

        $this->gateway = $gateway;

        $this->input = $input;

        if ($this->action === self::AUTHORIZE)
        {
            $input[self::GATEWAY]['features']['tpv'] = $input[Entity::MERCHANT]->isTPVRequired();
        }

        if ($this->action === self::AUTHORIZE_FAILED)
        {
            $action = self::VERIFY;
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

        $input = $this->addTransactionType($input);

        $content = [
            self::ACTION  => $action,
            self::GATEWAY => $gateway,
            self::INPUT   => $input
        ];

        $method = $input[Entity::PAYMENT][Payment\Entity::METHOD];

        $response = $this->sendRequest('POST', 'action/' . $action . '/' . $method, $content);

        return $response;
    }

    protected function processResponse($response, $method)
    {
        $code = $response->status_code;

        $responseBody = $this->jsonToArray($response->body);

        if ($this->action === self::AUTHORIZE_FAILED)
        {
            return $this->processAuthorizeFailedFlow($responseBody);
        }

        if ($this->isSuccessResponse($code, $responseBody) and ($this->action === self::VERIFY))
        {
            return $this->processVerifyResponse($responseBody);
        }

        return [$responseBody, $code];
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
        $verify->gatewaySuccess = $verify->verifyResponseContent['gateway_success'];
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

        return $response;
    }

    // TODO Add more logic for this
    protected function addTransactionType($input)
    {
        $input[self::METHOD_DATA] = ['transaction_type' => 'retail'];

        return $input;
    }

    protected function getAcquirerData($response)
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $response['data']['gateway_reference_number']
            ]
        ];
    }

    protected function getCallbackResponseData($response)
    {
        $callbackResponseData = $this->getAcquirerData($response);

        $callbackResponseData[Payment\Entity::TWO_FACTOR_AUTH] = Payment\TwoFactorAuth::UNAVAILABLE;

        return $callbackResponseData;
    }

}
