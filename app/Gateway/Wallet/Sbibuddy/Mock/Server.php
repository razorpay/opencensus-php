<?php

namespace RZP\Gateway\Wallet\Sbibuddy\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Base\Entity as Wallet;
use RZP\Gateway\Wallet\Sbibuddy\Encryptor;
use RZP\Gateway\Wallet\Sbibuddy\RequestFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseCodeMap;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $data = $this->parseEncryptedInput($input);

        $this->validateActionInput($data, 'authorize');

        $redirectUrl = $data[RequestFields::CALLBACK_URL];

        $content = $this->prepareAuthorizeResponse($data, $input[RequestFields::MERCHANT_ID]);

        $params = http_build_query($content);

        return \Redirect::to($redirectUrl . '?' . $params);
    }

    public function refund($input)
    {
        parent::refund($input);

        $data = $this->parseEncryptedInput($input);

        $this->validateActionInput($data, 'refund');

        $this->content($data, 'validateRefund');

        $content = $this->prepareRefundResponse($data, $input[RequestFields::MERCHANT_ID]);

        $content = http_build_query($content);

        return $this->makeResponse($content);
    }

    public function verify($input)
    {
        parent::verify($input);

        $data = $this->parseEncryptedInput($input);

        $this->validateActionInput($data, 'verify');

        $content = $this->prepareVerifyResponse($data, $input[RequestFields::MERCHANT_ID]);

        $content = http_build_query($content);

        return $this->makeResponse($content);
    }


    //------------------Authorize Helper methods----------------------
    protected function prepareAuthorizeResponse($input, $merchantId)
    {
        $content = [
            ResponseFields::EXTERNAL_TRANSACTION_ID => $input[RequestFields::EXTERNAL_TRANSACTION_ID],
            ResponseFields::ORDER_ID                => $input[RequestFields::ORDER_ID],
            ResponseFields::TRANSACTION_ID          => '987654321',
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            ResponseFields::FEE                     => 0.00,
            ResponseFields::STATUS_CODE             => ResponseCodeMap::SUCCESS_CODE,
            ResponseFields::PROCESSOR_ID            => 'ALL',
        ];

        $this->content($content, 'authorize');

        $encryptor = $this->getGatewayInstance()->getEncryptor();

        $encodedData = http_build_query($content);

        $encryptedData = $encryptor->encryptString($encodedData);

        return [
            ResponseFields::MERCHANT_ID     => $merchantId,
            ResponseFields::ENCRYPTED_DATA  => $encryptedData
        ];
    }

    //-----------------Authorize Helper methods end---------------

    protected function prepareVerifyResponse($input, $merchantId)
    {
        $content = $this->getVerifyResponseContent($input[RequestFields::ORDER_ID]);

        $this->content($content, 'verify');

        $encryptedData = $this->getGatewayInstance()->getEncryptedStringFromData($content);

        return [
            ResponseFields::MERCHANT_ID     => $merchantId,
            ResponseFields::ENCRYPTED_DATA  => $encryptedData
        ];
    }

    protected function getVerifyResponseContent($paymentId)
    {
        $wallet = $this->repo->wallet->fetchWalletByPaymentId($paymentId);

        $amount = $this->getGatewayInstance()->formatAmount($wallet[Wallet::AMOUNT]);

        $content = [
            ResponseFields::EXTERNAL_TRANSACTION_ID => $paymentId,
            ResponseFields::TRANSACTION_ID          => $wallet[Wallet::GATEWAY_PAYMENT_ID],
            ResponseFields::TRACKING_ID             => 123,
            ResponseFields::AMOUNT                  => $amount,
            ResponseFields::FEE                     => "0.00",
            ResponseFields::STATUS_CODE             => ResponseCodeMap::SUCCESS_CODE,
            ResponseFields::REFUND_ID               => "123,234,345",
            ResponseFields::REFUNDED_AMOUNT         => "80.00",
        ];

        return $content;
    }

    //-----------------Refund Helper methods----------------------

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

        $this->content($content, 'refund');

        $encryptedData = $this->getGatewayInstance()->getEncryptedStringFromData($content);

        return [
            ResponseFields::MERCHANT_ID     => $merchantId,
            ResponseFields::ENCRYPTED_DATA  => $encryptedData
        ];
    }

    //------------Refund Helper methods end----------------

    //------------General Helper methods----------------------

    protected function parseEncryptedInput($input)
    {
        $encryptor = $this->getGatewayInstance()->getEncryptor();

        $decryptedInput = $encryptor->decryptString($input[RequestFields::ENCRYPTED_DATA]);

        $data = [];

        parse_str($decryptedInput, $data);

        return $data;
    }
}
