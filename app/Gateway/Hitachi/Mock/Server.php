<?php

namespace RZP\Gateway\Hitachi\Mock;

use Str;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Gateway\Hitachi\RequestFields;
use RZP\Gateway\Hitachi\ResponseFields;
use RZP\Gateway\Hitachi\TransactionType;
use RZP\Gateway\Hitachi\TerminalFields;
class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        return $this->callback($input);
    }

    public function callback($input)
    {
        $content = json_decode($input, true);

        $this->request($content, __FUNCTION__);

        $this->validateAuthorizeInput($content);

        $response = $this->getAuthorizeResponse($content);

        $json = json_encode($response);

        $this->content($json, __FUNCTION__);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    public function advice($input)
    {
        $content = json_decode($input, true);

        $this->request($content, __FUNCTION__);

        $this->validateActionInput($content, 'advice');

        $response = $this->getAuthorizeResponse($content);

        $json = json_encode($response);

        $this->content($json, __FUNCTION__);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;

    }

    public function getBharatQrCallback($qrCodeId, $ref = null, $input = [])
    {
        $data = [
            'F002'       => '525783XXXXXX3413',
            'F003'       => '26000',
            'F004'       => '000000000200',
            'F011'       => 'abc123',
            'F012'       => '120000',
            'F013'       => '1212',
            'F037'       => 'somethingabc',
            'F038'       => 'random',
            'F039'       => '00',
            'F041'       => 'abcd_hitachi_bharat',
            'F042'       => 'abcd_hitachi_bharat',
            'F043'       => 'RazorpayBangalore',
            'F102'       => 'paymentId',
            'PurchaseID' => $qrCodeId,
            'SenderName' => 'Random Name',
        ];

        if (empty($input) === false)
        {
            $data = array_merge($data, $input);
        }

        if ($ref !== null)
        {
            $data['F037'] = $ref;
        }

        $this->content($data, 'callback');

        $hash = $this->getGatewayInstance()->getStringToHashForBharatQr($data);

        $data['CheckSum'] = $this->getGatewayInstance()->getHashOfString($hash);

        return $data;
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

        $this->content($content, 'validateRefund');

        $response = $this->getRefundResponse($content);

        $this->content($response, __FUNCTION__);

        return $this->makeResponse($response);
    }

    public function capture($input)
    {
        $content = json_decode($input, true);

        // For all RuPay transactions, use the callback response
        if ($content['pTranType'] === 'RU')
        {
            return $this->callback($input);
        }

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

    public function getBharatQrCallbackForRecon($qrCodeId)
    {
        return $this->getBharatQrCallback($qrCodeId, 123456789012);
    }

    public function merchantOnboard($input)
    {
        $content = json_decode($input,true);

        $this->validateActionInput($content, Base\Terminal::MERCHANT_ONBOARD);

        $response = $this->getOnboardResponse($content);

        $this->content($response, Base\Terminal::MERCHANT_ONBOARD);

        return $this->makeResponse($response);
    }

    protected function getAuthorizeResponse(array $input)
    {
        $response = [
            ResponseFields::TRANSACTION_TYPE    => $input[RequestFields::TRANSACTION_TYPE],
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
            ResponseFields::STATUS              => 'Success',
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

    protected function getOnboardResponse(array $input)
    {
        $response = [
            TerminalFields::S_NO                => $input[TerminalFields::S_NO],
            TerminalFields::GATEWAY_TID         => $input[TerminalFields::TID],
            TerminalFields::GATEWAY_MID         => $input[TerminalFields::MID],
            TerminalFields::RESPONSE_CODE       => '00',
            TerminalFields::RESPONSE_DESC       => 'Success',
        ];

        $this->content($response, Terminal::MERCHANT_ONBOARD);

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
