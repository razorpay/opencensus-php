<?php

namespace RZP\Gateway\Wallet\Sbibuddy\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Sbibuddy\RequestFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseCodeMap;
use RZP\Gateway\Wallet\Sbibuddy\Encryptor;


class Server extends Base\Mock\Server
{

    public function authorize($input)
    {
        $data = $this->parseAuthorizeInput($input);

        $redirectUrl = $data[RequestFields::CALLBACK_URL];

        $content = $this->prepareAuthorizeResponse($data, $input[RequestFields::MERCHANT_ID]);

        $params = http_build_query($content);

        return \Redirect::to($redirectUrl . '?' . $params);
    }

    protected function parseAuthorizeInput($input)
    {
        $encryptor = $this->getEncryptor();

        $decryptedInput = $encryptor->decrypt($input[RequestFields::ENCRYPTED_DATA]);

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

        $encryptor = $this->getEncryptor();

        $encodedData = http_build_query($content);

        $encryptedData = $encryptor->encrypt($encodedData);

        return [
            ResponseFields::MERCHANT_ID     => $merchantId,
            ResponseFields::ENCRYPTED_DATA  => $encryptedData
        ];
    }

    protected function getEncryptor()
    {
        $secret = $this->getSecret();

        return new Encryptor($secret);
    }

    protected function getAuthorizeResponse(array $input)
    {
        $date = $this->getFormattedTimeStamp(
                        Carbon::now('Asia/Kolkata')->timestamp,
                        self::TXN_DATE_FORMAT);

        $paymentId = $input[RequestFields::getFormatted(RequestFields::TRANSACTION, RequestFields::PAYMENT_ID)];

        $amount = $input[RequestFields::getFormatted(RequestFields::TRANSACTION, RequestFields::AMOUNT)];

        return [
            ResponseFields::STATUS_CODE             => StatusCode::SUCCESS,
            ResponseFields::CLIENT_ID               => $input[RequestFields::CLIENT_ID],
            ResponseFields::MERCHANT_ID             => $input[RequestFields::MERCHANT_ID],
            ResponseFields::CUSTOMER_ID             => 'NA',
            ResponseFields::PAYMENT_ID              => $paymentId,
            ResponseFields::GATEWAY_PAYMENT_ID      => $this->getJioMoneyTxnId(),
            ResponseFields::AMOUNT                  => $amount,
            ResponseFields::RESPONSE_CODE           => 'SUCCESS',
            ResponseFields::RESPONSE_DESCRIPTION    => 'APPROVED',
            ResponseFields::DATE                    => $date,
            ResponseFields::CARD_NUMBER             => 'NA',
            ResponseFields::CARD_TYPE               => 'JM',
            ResponseFields::CARD_NETWORK            => 'NA'
        ];
    }
}

