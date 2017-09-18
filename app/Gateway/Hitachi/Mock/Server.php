<?php

namespace RZP\Gateway\Hitachi\Mock;

use Str;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Gateway\Hitachi\RequestFields;
use RZP\Gateway\Hitachi\ResponseFields;
use RZP\Gateway\Hitachi\TransactionType;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        return $this->callback($input);
    }

    public function callback($input)
    {
        $content = json_decode($input, true);

        $this->validateAuthorizeInput($content);

        $response = $this->getAuthorizeResponse($content);

        $this->content($response, __FUNCTION__);

        return $this->makeJsonResponse($response);
    }

    public function verify($input)
    {
        $content = json_decode($input, true);

        $this->validateActionInput($content, 'verify');

        $response = $this->getVerifyResponse($content);

        $this->content($response, __FUNCTION__);

        return $this->makeResponse($response);
    }

    public function refund($input)
    {
        $content = json_decode($input, true);

        $this->validateActionInput($content, __FUNCTION__);

        $response = $this->getRefundResponse($content);

        $this->content($response, __FUNCTION__);

        return $this->makeResponse($response);
    }

    public function capture($input)
    {
        $content = json_decode($input, true);

        $this->validateActionInput($content, __FUNCTION__);

        $response = $this->getCaptureResponse($content);

        $this->content($response, __FUNCTION__);

        return $this->makeResponse($response);
    }

    public function reverse($input)
    {
        $content = json_decode($input, true);

        $this->validateActionInput($content, __FUNCTION__);

        $response = $this->getReverseResponse($content);

        $this->content($response, __FUNCTION__);

        return $this->makeResponse($response);
    }

    protected function getAuthorizeResponse(array $input)
    {
        $response = [
            ResponseFields::TRANSACTION_TYPE    => TransactionType::AUTH,
            ResponseFields::TRANSACTION_AMOUNT  => $input[RequestFields::TRANSACTION_AMOUNT],
            ResponseFields::MERCHANT_ID         => $input[RequestFields::MERCHANT_ID],
            ResponseFields::MERCHANT_REF_NUMBER => $input[RequestFields::MERCHANT_REF_NUMBER],
            ResponseFields::AUTH_ID             => Str::random(6),
            ResponseFields::RETRIEVAL_REF_NUM   => Str::random(12),
            ResponseFields::RESPONSE_CODE       => '00',
        ];

        return $response;
    }

    protected function getVerifyResponse(array $input)
    {
        $response = [
            ResponseFields::TRANSACTION_TYPE    => $input[RequestFields::TRANSACTION_TYPE],
            ResponseFields::REQUEST_ID          => $input[RequestFields::REQUEST_ID],
            ResponseFields::TRANSACTION_AMOUNT  => $input[RequestFields::TRANSACTION_AMOUNT],
            ResponseFields::MERCHANT_ID         => $input[RequestFields::MERCHANT_ID],
            ResponseFields::MERCHANT_REF_NUMBER => $input[RequestFields::MERCHANT_REF_NUMBER],
            ResponseFields::RETRIEVAL_REF_NUM   => Str::random(12),
            ResponseFields::RESPONSE_CODE       => '00',
            ResponseFields::STATUS              => 'S',
        ];

        return $response;
    }

    protected function getRefundResponse(array $input)
    {
        $response = $this->getDefaultRefundReverseResponse($input);

        $response[ResponseFields::REQUEST_ID] = $input[RequestFields::REQUEST_ID];

        return $response;
    }

    protected function getReverseResponse(array $input)
    {
        return $this->getDefaultRefundReverseResponse($input);
    }

    protected function getDefaultRefundReverseResponse(array $input)
    {
        $response = [
            ResponseFields::TRANSACTION_TYPE    => $input[RequestFields::TRANSACTION_TYPE],
            ResponseFields::TRANSACTION_AMOUNT  => $input[RequestFields::TRANSACTION_AMOUNT],
            ResponseFields::MERCHANT_ID         => $input[RequestFields::MERCHANT_ID],
            ResponseFields::MERCHANT_REF_NUMBER => $input[RequestFields::MERCHANT_REF_NUMBER],
            ResponseFields::RETRIEVAL_REF_NUM   => $input[RequestFields::RETRIEVAL_REF_NUM],
            ResponseFields::RESPONSE_CODE       => '00',
        ];

        return $response;
    }

    protected function getCaptureResponse(array $input)
    {
        $response = [
            ResponseFields::TRANSACTION_TYPE    => $input[RequestFields::TRANSACTION_TYPE],
            ResponseFields::REQUEST_ID          => $input[RequestFields::REQUEST_ID],
            ResponseFields::TRANSACTION_AMOUNT  => $input[RequestFields::TRANSACTION_AMOUNT],
            ResponseFields::MERCHANT_ID         => $input[RequestFields::MERCHANT_ID],
            ResponseFields::MERCHANT_REF_NUMBER => $input[RequestFields::MERCHANT_REF_NUMBER],
            ResponseFields::RETRIEVAL_REF_NUM   => $input[RequestFields::RETRIEVAL_REF_NUM],
            ResponseFields::RESPONSE_CODE       => '00',
        ];

        return $response;
    }

    protected function makeJsonResponse(array $content)
    {
        $json = json_encode($content);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }
}
