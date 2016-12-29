<?php

namespace RZP\Gateway\Netbanking\Airtel\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Airtel\Constants;
use RZP\Gateway\Netbanking\Airtel\AuthFields;
use RZP\Gateway\Netbanking\Airtel\VerifyFields;
use RZP\Gateway\Netbanking\Airtel\RefundFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = $this->createCallbackResponse($input);

        $this->content($content);

        $callbackUrl = $input[AuthFields::SUCCESS_URL] . '?' .
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

    public function refund($input)
    {
        parent::refund($input);

        $request = (array) json_decode($input);

        $this->validateActionInput($request);

        $response = $this->createRefundResponse($request);

        return $this->makeResponse($response);
    }

    protected function createCallbackResponse($input)
    {
        // Creating a success response
        $response = $this->createAuthResponseArray($input);

        return $response;
    }

    protected function createRefundResponse($request)
    {
        $hashArray = $this->getRefundHashArray($request);

        $this->content($hashArray);

        $hash = $this->generateHash($hashArray);

        $hashArray[RefundFields::HASH] = $hash;

        $data = $this->getAdditionalRefundData($request);

        $data =  array_merge($hashArray, $data);

        return json_encode($data);
    }

    protected function createAuthResponseArray($input)
    {
        $hashArray = $this->getAuthResponseHashArray($input);

        $hash = $this->generateHash($hashArray);

        $response = $this->getAdditionalAuthData($hash);

        $response = array_merge($hashArray, $response);

        return $response;
    }

    protected function getAuthResponseHashArray($input)
    {
        return [
            AuthFields::MERCHANT_ID               => $input[AuthFields::MERCHANT_ID],
            AuthFields::TRANSACTION_ID            => mt_rand(11111111, 99999999),
            AuthFields::TRANSACTION_REFERENCE_NO  => $input[AuthFields::TRANSACTION_REFERENCE_NO],
            AuthFields::TRANSACTION_AMOUNT        => $input[AuthFields::AMOUNT],
            AuthFields::TRANSACTION_DATE          => $input[AuthFields::DATE],
        ];
    }

    protected function getAdditionalAuthData($hash)
    {
        return [
            AuthFields::STATUS                    => Constants::SUCCESS,
            AuthFields::CODE                      => Constants::CODE,
            AuthFields::MSG                       => Constants::SUCCESS_MSG,
            AuthFields::TRANSACTION_CURRENCY      => Constants::INDIAN_RUPEE,
            AuthFields::HASH                      => $hash
        ];
    }

    protected function getRefundHashArray($input)
    {
        $date = Carbon::createFromFormat('dmYHms',
            $input[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        return [
            RefundFields::MERCHANT_ID              => $input[RefundFields::MERCHANT_ID],
            RefundFields::ERROR_CODE               => Constants::CODE,
            RefundFields::AMOUNT                   => $input[RefundFields::AMOUNT],
            RefundFields::TRANSACTION_ID           => $input[RefundFields::TRANSACTION_ID],
            RefundFields::TRANSACTION_DATE         => $date,
            RefundFields::STATUS                   => Constants::SUCCESS
        ];
    }

    protected function getAdditionalRefundData($request)
    {
        return [
            RefundFields::SESSION_ID    => $request[RefundFields::SESSION_ID],
            RefundFields::MESSAGE_TEXT  => 'Transaction Created Successfully',
            RefundFields::CODE          => '0'
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
        $verifyArray = $this->createDefaultVerifyArray($input);

        $this->content($verifyArray);

        return $this->createVerifyResponseArray($input, $verifyArray);
    }

    protected function createDefaultVerifyArray($input)
    {
        $date = Carbon::createFromFormat('dmYHms',
            $input[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        return [
            VerifyFields::STATUS                => Constants::SUCCESS,
            VerifyFields::TRANSACTION_ID        => mt_rand(111111111, 999999999),
            VerifyFields::TRANSACTION_DATE      => $date,
            VerifyFields::TRANSACTION_AMOUNT    => $input[VerifyFields::AMOUNT],
        ];
    }

    protected function createVerifyResponseArray($input, $verifyArray)
    {
        $merchantId = $this->getGatewayInstance()->getMerchantId();

        $hashArray = $this->getVerifyHashArray($verifyArray, $input);

        $hash = $this->generateHash($hashArray);

        return [
            VerifyFields::TRANSACTION               => array($verifyArray),
            VerifyFields::HASH                      => $hash,
            VerifyFields::MERCHANT_ID               => $merchantId,
            VerifyFields::TRANSACTION_REFERENCE_NO  => $input[VerifyFields::TRANSACTION_REFERENCE_NO],
            VerifyFields::MESSAGE_TEXT              => 'Success',
            VerifyFields::CODE                      => '0',
            VerifyFields::ERROR_CODE                => Constants::CODE
        ];
    }

    protected function getVerifyHashArray($verifyArray, $input)
    {
        return [
            VerifyFields::MERCHANT_ID    => $input[VerifyFields::MERCHANT_ID],
            VerifyFields::TRANSACTION    => '['.json_encode($verifyArray).']',
            VerifyFields::ERROR_CODE     => Constants::CODE,
        ];
    }
}
