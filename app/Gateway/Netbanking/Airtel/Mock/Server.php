<?php

namespace RZP\Gateway\Netbanking\Airtel\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Airtel\Status;
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

        $content = $this->createCallbackResponseArray($input);

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

    protected function createCallbackResponseArray($input)
    {
        $response = [
            AuthFields::MERCHANT_ID               => $input[AuthFields::MERCHANT_ID],
            AuthFields::TRANSACTION_ID            => mt_rand(11111111, 99999999),
            AuthFields::TRANSACTION_REFERENCE_NO  => $input[AuthFields::TRANSACTION_REFERENCE_NO],
            AuthFields::TRANSACTION_AMOUNT        => $input[AuthFields::AMOUNT],
            AuthFields::TRANSACTION_DATE          => $input[AuthFields::DATE],
            AuthFields::STATUS                    => Status::SUCCESS,
            AuthFields::CODE                      => Constants::CODE,
            AuthFields::MSG                       => Constants::SUCCESS_MSG,
            AuthFields::TRANSACTION_CURRENCY      => Constants::INDIAN_RUPEE,
        ];

        $response[AuthFields::HASH] = $this->generateHash($response);

        return $response;
    }

    protected function generateHash($content)
    {
        $hashString = $this->getStringToHash($content, '#');

        return $this->getHashOfString($hashString);
    }

    protected function getStringToHash($content, $glue = '')
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
            {
                $content = $this->getCallbackHashArray($input);
            }

            case Action::VERIFY:
            {
                $content = $this->getVerifyHashArray($input);
            }
        }

        return implode($glue, $content);
    }

    protected function getHashOfString($string)
    {
        return hash(HashAlgo::SHA512, $string);
    }

    protected function getCallbackHashArray($input)
    {
        return [
            AuthFields::MERCHANT_ID               => $input[AuthFields::MERCHANT_ID],
            AuthFields::TRANSACTION_ID            => $input[AuthFields::TRANSACTION_ID],
            AuthFields::TRANSACTION_REFERENCE_NO  => $input[AuthFields::TRANSACTION_REFERENCE_NO],
            AuthFields::TRANSACTION_AMOUNT        => $input[AuthFields::TRANSACTION_AMOUNT],
            AuthFields::TRANSACTION_DATE          => $input[AuthFields::TRANSACTION_DATE],
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
            RefundFields::STATUS                   => Status::SUCCESS
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
        $response =  $this->createVerifyResponseArray($input, $verifyArray);

        return json_encode($response);
    }

    protected function createVerifyResponseArray($input, $verifyArray)
    {
        $date = Carbon::createFromFormat('dmYHms',
            $input[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        $verifyArray = [
            VerifyFields::STATUS                => Status::SUCCESS,
            VerifyFields::TRANSACTION_ID        => mt_rand(111111111, 999999999),
            VerifyFields::TRANSACTION_DATE      => $date,
            VerifyFields::TRANSACTION_AMOUNT    => $input[VerifyFields::AMOUNT],
        ];

        $this->content($verifyArray);

        $merchantId = $this->getGatewayInstance()->getMerchantId();

        $hash = $this->generateHash($verifyArray);

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

    protected function getVerifyHashArray($verifyArray)
    {
        $merchantId = $this->getGatewayInstance()->getMerchantId();

        return [
            VerifyFields::MERCHANT_ID    => $merchantId,
            VerifyFields::TRANSACTION    => '['.json_encode($verifyArray).']',
            VerifyFields::ERROR_CODE     => Constants::CODE,
        ];
    }
}
