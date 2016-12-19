<?php

namespace RZP\Gateway\Wallet\Jiomoney;

class ResponseFields
{
    const STATUS_CODE          = 'status_code';
    const CLIENT_ID            = 'client_id';
    const MERCHANT_ID          = 'merchant_id';
    const CUSTOMER_ID          = 'customer_id';
    const PAYMENT_ID           = 'payment_id';
    const GATEWAY_PAYMENT_ID   = 'gateway_payment_id';
    const AMOUNT               = 'amount';
    const RESPONSE_CODE        = 'response_code';
    const RESPONSE_DESCRIPTION = 'response_description';
    const DATE                 = 'date';
    const CARD_NUMBER          = 'card_number';
    const CARD_TYPE            = 'card_type';
    const CARD_NETWORK         = 'card_network';
    const CHECKSUM             = 'checksum';

    public static function getResponseFieldsArray()
    {
        return [
            self::STATUS_CODE,
            self::CLIENT_ID,
            self::MERCHANT_ID,
            self::CUSTOMER_ID,
            self::PAYMENT_ID,
            self::GATEWAY_PAYMENT_ID,
            self::AMOUNT,
            self::RESPONSE_CODE,
            self::RESPONSE_DESCRIPTION,
            self::DATE,
            self::CARD_NUMBER,
            self::CARD_TYPE,
            self::CARD_NETWORK,
            self::CHECKSUM
        ];
    }
}
