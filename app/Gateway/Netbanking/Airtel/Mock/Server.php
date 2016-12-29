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

    public function refund($input)
    {
        parent::refund($input);

        $request = $this->jsonToArray($input);

        $this->validateActionInput($request);

        $response = $this->createRefundResponse($request);

        return $this->makeResponse($response);
    }

    public function verify($input)
    {
        parent::verify($input);

        $request = $this->jsonToArray($input);

        $this->validateActionInput($request);

        $response = $this->getVerifyResponse($request);

        return $this->makeResponse($response);
    }

    protected function createRefundResponse($request)
    {
        $date = Carbon::createFromFormat('dmYHis',
            $request[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        $data = [
            RefundFields::MERCHANT_ID      => $request[RefundFields::MERCHANT_ID],
            RefundFields::ERROR_CODE       => '000',
            RefundFields::AMOUNT           => $request[RefundFields::AMOUNT],
            RefundFields::TRANSACTION_ID   => $request[RefundFields::TRANSACTION_ID],
            RefundFields::TRANSACTION_DATE => $date,
            RefundFields::STATUS           => Status::SUCCESS,
            RefundFields::SESSION_ID       => $request[RefundFields::SESSION_ID],
            RefundFields::MESSAGE_TEXT     => 'Transaction Created Successfully',
            RefundFields::CODE             => '0'
        ];

        $this->content($data);

        $data[RefundFields::HASH] = $this->generateHash($data);

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
            AuthFields::CODE                      => '000',
            AuthFields::MSG                       => 'eCommerce transaction successful',
            AuthFields::TRANSACTION_CURRENCY      => Constants::INDIAN_RUPEE,
        ];

        $response[AuthFields::HASH] = $this->generateHash($response);

        return $response;
    }

    protected function getVerifyResponse($input)
    {
        $date = Carbon::createFromFormat('dmYHis',
            $input[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        $verifyArray = [
            VerifyFields::STATUS                => Status::SUCCESS,
            VerifyFields::TRANSACTION_ID        => mt_rand(111111111, 999999999),
            VerifyFields::TRANSACTION_DATE      => $date,
            VerifyFields::TRANSACTION_AMOUNT    => $input[VerifyFields::AMOUNT],
        ];

        $merchantId = $this->getGatewayInstance()->getMerchantId();

        $hash = $this->generateHash($verifyArray);

        $response =  [
            VerifyFields::TRANSACTION               => array($verifyArray),
            VerifyFields::HASH                      => $hash,
            VerifyFields::MERCHANT_ID               => $merchantId,
            VerifyFields::TRANSACTION_REFERENCE_NO  => $input[VerifyFields::TRANSACTION_REFERENCE_NO],
            VerifyFields::MESSAGE_TEXT              => 'Success',
            VerifyFields::CODE                      => '0',
            VerifyFields::ERROR_CODE                => '000'
        ];

        $this->content($response);

        return json_encode($response);
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
                $content = $this->getCallbackHashArray($content);
                break;

            case Action::VERIFY:
                $content = $this->getVerifyHashArray($content);
                break;

            case Action::REFUND:
                $content = $this->getRefundHashArray($content);
                break;
        }

        $salt = $this->getGatewayInstance()->getSalt();

        array_push($content, $salt);

        return implode($glue, $content);
    }

    protected function getHashOfString($string)
    {
        return hash(HashAlgo::SHA512, $string);
    }

    protected function getCallbackHashArray($input)
    {
        return [
            $input[AuthFields::MERCHANT_ID],
            $input[AuthFields::TRANSACTION_ID],
            $input[AuthFields::TRANSACTION_REFERENCE_NO],
            $input[AuthFields::TRANSACTION_AMOUNT],
            $input[AuthFields::TRANSACTION_DATE],
        ];
    }

    protected function getRefundHashArray($content)
    {
        return [
            $content[RefundFields::MERCHANT_ID],
            $content[RefundFields::ERROR_CODE],
            $content[RefundFields::AMOUNT],
            $content[RefundFields::TRANSACTION_ID],
            $content[RefundFields::TRANSACTION_DATE],
            $content[RefundFields::STATUS]
        ];
    }

    protected function getVerifyHashArray($verifyArray)
    {
        $merchantId = $this->getGatewayInstance()->getMerchantId();

        return [
            $merchantId,
            '['.json_encode($verifyArray).']',
            '000',
        ];
    }
}
