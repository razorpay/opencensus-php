<?php

namespace RZP\Services\NbPlus;

use App;
use RZP\Exception;

use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Models\Base\PublicEntity;

class Netbanking extends Service
{
    const RETAIL = 'retail';
    const TPV    = 'TPV';

    // gateway entity attributes
    const GATEWAY_TRANSACTION_ID = 'gateway_transaction_id';
    const BANK_TRANSACTION_ID    = 'bank_transaction_id';

    protected $transactionType = self::RETAIL;

    public function action(string $gateway, string $action, array $input)
    {
        $this->action = $action;

        $this->gateway = $gateway;

        $this->input = $input;

        if (($this->action === Action::AUTHORIZE) and ($input[Entity::MERCHANT]->isTPVRequired() === true))
        {
            $this->transactionType = self::TPV;
        }

        if ($this->action === Action::AUTHORIZE_FAILED)
        {
            $action = Action::VERIFY;
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
            Request::ACTION  => $action,
            Request::GATEWAY => $gateway,
            Request::INPUT   => $input
        ];

        $method = $input[Entity::PAYMENT][Payment\Entity::METHOD];

        $response = $this->sendRequest('POST', 'action/' . $action . '/' . $method, $content);

        return $this->processResponse($response);
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
        $verify->gatewaySuccess = $verify->verifyResponseContent[Response::GATEWAY_STATUS];
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

    protected function addTransactionType($input)
    {
        $input[Request::METHOD_DATA] = [Request::TRANSACTION_TYPE => $this->transactionType];

        return $input;
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
        $callbackResponseData = $this->getAcquirerData($response);

        $callbackResponseData[Payment\Entity::TWO_FACTOR_AUTH] = Payment\TwoFactorAuth::UNAVAILABLE;

        return $callbackResponseData;
    }

    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);
        unset($request['content'][Request::INPUT]['gateway_config']);
        unset($request['content'][Request::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD]);
        unset($request['content'][Request::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2]);
        unset($request['content'][Request::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_SECURE_SECRET]);
        unset($request['content'][Request::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_SECURE_SECRET2]);

        $this->trace->info(TraceCode::NBPLUS_PAYMENT_SERVICE_REQUEST, $request);
    }
}
