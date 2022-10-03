<?php

namespace RZP\Services\NbPlus;

use App;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Models\Customer\Token;
use RZP\Models\Base\PublicEntity;

class Emandate extends Service
{
    const REGISTER = 'register';
    const DEBIT    = 'debit';
    const BANK_REFERENCE_ID = 'bank_reference_id';

    protected $transactionType = self::REGISTER;

    public function action(string $method, string $gateway, string $action, array $input)
    {
        $this->action = $action;

        $this->gateway = $gateway;

        $this->input = $input;

        if ((empty($input) === false) and
            (isset($input[Entity::PAYMENT]) === true) and
            (isset($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE]) === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::AUTO))
        {
            $this->transactionType = self::DEBIT;
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

        $content = [
            Request::ACTION  => $action,
            Request::GATEWAY => $gateway,
            Request::INPUT   => $input
        ];

        $response = $this->sendRequest('POST', 'action/' . $action . '/' . $method, $content);

        return $this->processResponse($response);
    }

    protected function processResponse($response)
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $returnData = $this->getAuthorizeResponseData($response);
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

            case Action::PREPROCESS_CALLBACK:
                $returnData = $response[Response::PAYMENT_ID];
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

        return $this->getRecurringData($response);
    }

    protected function getAuthorizeResponseData($response)
    {
        if ($this->transactionType === self::DEBIT)
        {
            $debitResponse = [
                'acquirer' => [
                    Payment\Entity::REFERENCE1 => $response[Response::DATA][Response::BANK_REFERENCE_ID],
                ]
            ];

            if (isset($response[Response::DATA][Response::GATEWAY_PAYMENT_STATUS]) === true)
            {
                $debitResponse['additional_data'] = [
                    'gateway_payment_status' => $response[Response::DATA][Response::GATEWAY_PAYMENT_STATUS] ?? null,
                ];
            }

            return $debitResponse;
        }

        return $response[Response::DATA][Response::NEXT][Response::REDIRECT];
    }

    protected function getRecurringData($response): array
    {
        if ($this->transactionType === self::DEBIT)
        {
            $debitResponse = [
                'acquirer' => [
                    Payment\Entity::REFERENCE1 => $response[Response::DATA][Response::BANK_REFERENCE_ID] ?? null,
                ]
            ];

            if (isset($response[Response::DATA][Response::GATEWAY_PAYMENT_STATUS]) === true)
            {
                $debitResponse['additional_data'] = [
                    'gateway_payment_status' => $response[Response::DATA][Response::GATEWAY_PAYMENT_STATUS] ?? null,
                ];
            }

            return $debitResponse;
        }

        $reference = $response[Response::DATA][Response::BANK_REFERENCE_ID] ?? null;

        $returnData = [
            Token\Entity::RECURRING_STATUS         => $response[Response::DATA][Response::RECURRING_STATUS],
            Token\Entity::GATEWAY_TOKEN            => $response[Response::DATA][Response::GATEWAY_TOKEN],
            Token\Entity::RECURRING_FAILURE_REASON => $response[Response::DATA][Response::RECURRING_FAILURE_REASON] ?? null,
        ];

        if (empty($reference) === false)
        {
            $returnData['acquirer'] = [
                Payment\Entity::REFERENCE1 => $reference,
            ];
        }

        return $returnData;
    }

    protected function getCallbackResponseData($response): array
    {
        $callbackResponseData = $this->getRecurringData($response);

        $callbackResponseData[Payment\Entity::TWO_FACTOR_AUTH] = Payment\TwoFactorAuth::UNAVAILABLE;

        return $callbackResponseData;
    }
}
