<?php

namespace RZP\Services\NbPlus;

use App;
use Carbon\Carbon;
use Illuminate\Support\Arr;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Models\Customer\Token;
use RZP\Models\Base\PublicEntity;

class Wallet extends Service
{

    // gateway entity attributes
    const WALLET_TRANSACTION_ID  = 'wallet_transaction_id';
    const ADDITIONAL_DATA        = 'additional_data';

    // attributes which are part of additional data in wallet entity
    const CREDIT_ACCOUNT_NUMBER  = 'credit_account_number';
    const CUSTOMER_ID            = 'customer_id';
    const OTP_ATTEMPTS           = 'otp_attempts';
    const TOKEN                  = 'token';

    public function action(string $method, string $gateway, string $action, array $input)
    {

        $this->action = $action;

        $this->gateway = $gateway;

        $this->input = $input;

        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();
        }

        if (isset($input[Entity::MERCHANT]) && is_null($input[Entity::MERCHANT]) === false)
        {
            $input['merchant']['features'] = $input[Entity::MERCHANT]->features;
        }

        foreach ($input as $key => $data)
        {
            if ((is_object($data) === true) and ($data instanceof PublicEntity))
            {
                $input[$key] = $data->toArray();
            }
        }

        if (($this->action === Action::CALLBACK) && (isset($input[Entity::PAYMENT])) && ($input[Entity::PAYMENT][self::OTP_ATTEMPTS] === null))
        {
            $input[Entity::PAYMENT][self::OTP_ATTEMPTS] = 0;
        }

        if ($this->action === Action::AUTHORIZE_FAILED)
        {
            $action = Action::VERIFY;
        }

        $input = $this->convertEmptyArrayToNull($input);

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
        $returnData = null;

        switch ($this->action)
        {
            case Action::AUTHORIZE:
                if(isset($response[Response::DATA][Response::NEXT]))
                {
                    $returnData = $response[Response::DATA][Response::NEXT][Response::REDIRECT];
                }
                if ($this->input['wallet']['flow'] === Action::INTENT)
                {
                    $returnData = [
                        'data' => [
                            'intent_url' => $response[Response::DATA][Response::NEXT][Response::REDIRECT][Response::INTENT_URL],
                        ],
                    ];
                }
                break;
            case Action::CALLBACK:
                $returnData = $this->getCallbackResponseData($response);
                break;
            case Action::OTP_RESEND:
            case Action::TOPUP:
                $returnData = $response[Response::DATA][Response::NEXT][Response::REDIRECT];
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

    // ----------------------- Callback ---------------------------------------------

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

        $callbackResponseData[Payment\Entity::TWO_FACTOR_AUTH] = Payment\TwoFactorAuth::PASSED;

        $tokenData = $this->getTokenData($response);

        return array_merge($callbackResponseData, $tokenData);
    }

    protected function getTokenData($response): array
    {
        if (isset($response[Response::DATA][self::TOKEN]) === true)
        {
            return [
                'token' => $response[Response::DATA][self::TOKEN]
            ];
        }
        return [];
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

    // ----------------------- Authorize failed  -----------------------

    protected function processAuthorizeFailedFlow($response) {

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
}
