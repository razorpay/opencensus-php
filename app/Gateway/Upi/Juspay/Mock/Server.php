<?php

namespace RZP\Gateway\Upi\Juspay\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Upi\Juspay\Fields;

class Server extends Base\Mock\Server
{
    public function getCallbackRequest($attributes = [])
    {
        $url = '/callback/upi_juspay';

        $method = 'post';
        $server = [
            'CONTENT_TYPE'                      => 'application/json',
            'HTTP_X-Merchant-Payload-Signature' => 'signature'
        ];

        $raw = json_encode($attributes);

        return [
            'url'       => $url,
            'method'    => $method,
            'raw'       => $raw,
            'server'    => $server,
        ];
    }

    public function getDirectCallback($terminal, $attributes = [])
    {
        $default = $this->getDefaultPayload();

        $default[Fields::MERCHANT_ID]         = $terminal['gateway_merchant_id'];
        $default[Fields::MERCHANT_CHANNEL_ID] = $terminal['gateway_merchant_id2'];

        $attributes = array_merge($default, $attributes);

        return $this->getCallbackRequest($attributes);
    }

    public function getCallback($payment, $attributes = [])
    {
        $default = $this->getDefaultPayload();

        $default[Fields::AMOUNT]              = $this->getStringFormattedAmount($payment['amount']);
        $default[Fields::MERCHANT_REQUEST_ID] = $payment['id'];
        $default[Fields::PAYER_VPA]           = $payment['vpa'];

        $attributes = array_merge($default, $attributes);

        return $this->getCallbackRequest($attributes);
    }

    protected function getStringFormattedAmount(int $amount)
    {
        $amountStr = (string)$amount;

        $amountStr = substr_replace($amountStr, '.', -2, 0);

        return $amountStr;
    }

    protected function getDefaultPayload()
    {
        $default = [
            Fields::AMOUNT                    => '10.00',
            Fields::CUSTOM_RESPONSE           => '{}',
            Fields::GATEWAY_REFERENCE_ID      => '034520388334',
            Fields::GATEWAY_RESPONSE_CODE     => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE  => 'Your transaction is successful',
            Fields::GATEWAY_TRANSACTION_ID    => 'APP34749005b22e45bfa1e9a38e668fc43c',
            Fields::MERCHANT_CHANNEL_ID       => 'MERCHANT_CHANNEL_ID',
            Fields::MERCHANT_ID               => 'MERCHANT_ID',
            Fields::MERCHANT_REQUEST_ID       =>  random_alphanum_string(32),
            Fields::PAYEE_MCC                 => '5674',
            Fields::PAYEE_VPA                 => 'test@bajaj',
            Fields::PAYER_NAME                => 'PAYER_NAME',
            Fields::PAYER_VPA                 => 'customer@abfspay',
            Fields::REF_URL                   => 'https://example.com',
            Fields::TRANSACTION_TIMESTAMP     => '2020-12-10T20:29:40+05:30',
            Fields::TYPE                      => 'MERCHANT_CREDITED_VIA_PAY',
            Fields::UDF_PARAMETERS            => '{}',
        ];

        return $default;
    }
}
