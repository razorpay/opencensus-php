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
    const RESPONSE             = 'RESPONSE';
    const RESPONSE_HEADER      = 'RESPONSE_HEADER';
    const API_MSG              = 'API_MSG';
    const CHECKPAYMENTSTATUS   = 'CHECKPAYMENTSTATUS';
    const GETREQUESTSTATUS     = 'GETREQUESTSTATUS';
    const STATUS               = 'STATUS';
    const TXN_STATUS           = 'TXN_STATUS';
    const REFUND_AMOUNT        = 'REFUND_AMOUNT';
    const JM_TRAN_REF_NO       = 'JM_TRAN_REF_NO';
    const TXN_TIME_STAMP       = 'TXN_TIME_STAMP';
    const TXN_AMOUNT           = 'TXN_AMOUNT';
    /**
     * This provides the list of fields returned for callback and
     * refund gateway response.
     */
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
            self::CHECKSUM,
        ];
    }
}
