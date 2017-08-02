<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use RZP\Gateway\Wallet\Base;
use RZP\Models\Payment;

class Gateway extends Base\Gateway
{
    protected $gateway = 'wallet_sbibuddy';

    public function authorize(array $input)
    {
        $request = $this->getAuthRequest($input);
        return $request;
    }

    protected function getAuthRequest($input)
    {
        $payment = $input['payment'];

        $request = $this->getPayloadForAuth($payment, $input['callbackUrl']);

        return $request;
    }

    protected function getPayloadForAuth($payment, $callbackUrl)
    {
        $data = [
            RequestFields::EXTERNAL_TRANSACTION_ID => $payment['id'],
            RequestFields::ORDER_ID => $payment['id'],
            RequestFields::AMOUNT => $payment[Payment\Entity::AMOUNT],
            RequestFields::CURRENCY => $payment[Payment\Entity::CURRENCY],
            RequestFields::CALLBACK_URL => $callbackUrl,
            RequestFields::BACK_URL => $callbackUrl,
            RequestFields::DESCRIPTION => "Test description",
            // RequestFields::CATEGORY => 'Cat 1',
            // RequestFields::SUBCATEGORY => 'Cat 2',
            RequestFields::PROCESSOR_ID => 'ALL',
        ];

        $encodedData = utf8_encode(http_build_query($data));

        $secret = $this->getSecret();

        $cryptor = new Encryptor($secret);

        $encrypted = $cryptor->encrypt($encodedData);

        $request = [
            'content' => [
                'merchantId'    => '123',
                'encryptedData' => $encrypted
            ]
        ];

        return $request;
    }
}
