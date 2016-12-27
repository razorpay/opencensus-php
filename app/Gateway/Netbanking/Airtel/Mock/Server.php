<?php

namespace RZP\Gateway\Netbanking\Airtel\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Airtel\Constants;
use RZP\Gateway\Netbanking\Airtel\VerifyFields;
use RZP\Gateway\Netbanking\Airtel\RequestFields;
use RZP\Gateway\Netbanking\Airtel\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = $this->createCallbackResponse($input);

        $this->content($content);

        $callbackUrl = $input[RequestFields::SUCCESS_URL] . '?' .
                        http_build_query($content);

        return $callbackUrl;
    }

    public function verify($input)
    {
        parent::verify($input);

        $request = $this->getVerifyRequestArray($input);

        $this->validateActionInput($request);

        $response = $this->getVerifyResponse($request);

        return $this->makeResponse($response);
    }

    protected function createCallbackResponse($input)
    {
        $hashArray = $this->getHashArray($input);

        $hash = $this->getHash($hashArray);

        if ($hash !== $input[RequestFields::HASH])
        {
            // Error in authorize request - invalid hash
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_HASH);
        }

        // Creating a success response
        $response = $this->createResponseArray($input);

        return $response;
    }

    protected function createResponseArray($input)
    {
        $hashArray = [
            ResponseFields::MERCHANT_ID               => $input[RequestFields::MERCHANT_ID],
            ResponseFields::TRANSACTION_REFERENCE_NO  => $input[RequestFields::TRANSACTION_REFERENCE_NO],
            ResponseFields::TRANSACTION_ID            => mt_rand(11111111, 99999999),
            ResponseFields::TRANSACTION_AMOUNT        => $input[RequestFields::AMOUNT],
            ResponseFields::TRANSACTION_DATE          => $input[RequestFields::DATE],
        ];

        $hash = $this->getHash($hashArray);

        $response = [
            ResponseFields::STATUS                    => Constants::SUCCESS,
            ResponseFields::CODE                      => Constants::CODE,
            ResponseFields::MSG                       => Constants::SUCCESS_MSG,
            ResponseFields::TRANSACTION_CURRENCY      => Constants::INDIAN_RUPEE,
            ResponseFields::HASH                      => $hash
        ];

        $response = array_merge($hashArray, $response);

        return $response;
    }

    protected function getHash($hashArray)
    {
        $salt = $this->getGatewayInstance()->getSalt();

        array_push($hashArray, $salt);

        $hashString = implode('#', $hashArray);

        return hash(Constants::HASH_ALGORITHM, $hashString);
    }

    protected function getHashArray($input)
    {
        return [
            $input[RequestFields::MERCHANT_ID],
            $input[RequestFields::TRANSACTION_REFERENCE_NO],
            $input[RequestFields::AMOUNT],
            $input[RequestFields::DATE],
            Constants::NETBANKING,
        ];
    }

    protected function getVerifyRequestArray($input)
    {
        return (array) json_decode($input);
    }

    protected function getVerifyResponse($input)
    {
        $response = $this->getVerifyResponseArray($input);

        return json_encode($response);
    }

    protected function getVerifyResponseArray($input)
    {
        $date = Carbon::createFromFormat('dmYHms',
            $input[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        $verifyArray = [
            VerifyFields::STATUS                => Constants::SUCCESS,
            VerifyFields::TRANSACTION_ID        => mt_rand(111111111, 999999999),
            VerifyFields::TRANSACTION_DATE      => $date,
            VerifyFields::TRANSACTION_AMOUNT    => $input[VerifyFields::AMOUNT],
        ];

        // hash generation is incorrect
        $hash = $this->getGatewayInstance()->getHash($verifyArray);

        $merchantId = $this->getGatewayInstance()->getMerchantId();

        return [
            VerifyFields::TRANSACTION               => array($verifyArray),
            VerifyFields::HASH                      => $hash,
            VerifyFields::MERCHANT_ID               => $merchantId,
            VerifyFields::TRANSACTION_REFERENCE_NO  => $input[VerifyFields::TRANSACTION_REFERENCE_NO],
            VerifyFields::MESSAGE_TEXT              => Constants::VERIFY_SUCCESS,
            VerifyFields::CODE                      => Constants::VERIFY_CODE,
            VerifyFields::ERROR_CODE                => Constants::CODE
        ];
    }
}
