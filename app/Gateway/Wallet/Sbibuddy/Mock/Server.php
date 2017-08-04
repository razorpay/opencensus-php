<?php

namespace RZP\Gateway\Wallet\Sbibuddy\Mock;

use RZP\Gateway\Base;
use phpseclib\Crypt\AES;
use RZP\Gateway\Wallet\Sbibuddy\RequestFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseCodeMap;
use RZP\Gateway\Wallet\Sbibuddy\Encryptor;


class Server extends Base\Mock\Server
{

    public function authorize($input)
    {
        $data = $this->parseEncryptedInput($input);

        $redirectUrl = $data[RequestFields::CALLBACK_URL];

        $content = $this->prepareAuthorizeResponse($data, $input[RequestFields::MERCHANT_ID]);

        $params = http_build_query($content);

        return \Redirect::to($redirectUrl . '?' . $params);
    }

    public function refund($input)
    {
        parent::refund($input);

        $data = $this->parseEncryptedInput($input);

        $content = $this->prepareRefundResponse($data, $input[RequestFields::MERCHANT_ID]);

        $this->content($content, 'refund');

        return $this->makeResponse($content);
    }

    protected function parseEncryptedInput($input)
    {
        $encryptor = $this->getGatewayInstance()->getEncryptor();

        $decryptedInput = $encryptor->decryptString($input[RequestFields::ENCRYPTED_DATA]);

        $data = [];

        parse_str($decryptedInput, $data);

        return $data;
    }

    protected function prepareAuthorizeResponse($input, $merchantId)
    {
        $content = [
            ResponseFields::EXTERNAL_TRANSACTION_ID => $input[RequestFields::EXTERNAL_TRANSACTION_ID],
            ResponseFields::ORDER_ID                => $input[RequestFields::ORDER_ID],
            ResponseFields::TRANSACTION_ID          => 123,
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            ResponseFields::FEE                     => 0.00,
            ResponseFields::STATUS_CODE             => ResponseCodeMap::SUCCESS_CODE,
            ResponseFields::PROCESSOR_ID            => "ALL",
        ];

        $encryptor = $this->getGatewayInstance()->getEncryptor();

        $encodedData = http_build_query($content);

        $encryptedData = $encryptor->encryptString($encodedData);

        return [
            ResponseFields::MERCHANT_ID     => $merchantId,
            ResponseFields::ENCRYPTED_DATA  => $encryptedData
        ];
    }

    protected function prepareRefundResponse($input, $merchantId)
    {
        $content = [
            ResponseFields::EXTERNAL_TRANSACTION_ID => $input[RequestFields::ORDER_ID],
            ResponseFields::TRANSACTION_ID          => $input[RequestFields::TRANSACTION_ID],
            ResponseFields::TRACKING_ID             => 123,
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            ResponseFields::FEE                     => 0.00,
            ResponseFields::STATUS_CODE             => ResponseCodeMap::SUCCESS_CODE,
            ResponseFields::REFUND_ID               => 234,
            ResponseFields::REFUNDED_AMOUNT         => 456
        ];

        $encryptor = $this->getGatewayInstance()->getEncryptor();

        $encodedData = http_build_query($content);

        $encryptedData = $encryptor->encryptString($encodedData);

        return [
            ResponseFields::MERCHANT_ID     => $merchantId,
            ResponseFields::ENCRYPTED_DATA  => $encryptedData
        ];
    }
}

