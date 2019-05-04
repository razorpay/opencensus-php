<?php

namespace RZP\Gateway\Paytm;

use RZP\Constants;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const REQUEST_TYPE      = 'request_type';
    const CUST_ID           = 'cust_id';
    const CHANNEL_ID        = 'channel_id';
    const PAYMENT_MODE_ONLY = 'payment_mode_only';
    const AUTH_MODE         = 'auth_mode';
    const BANK_CODE         = 'bank_code';
    const PAYMENT_TYPE_ID   = 'payment_type_id';
    const INDUSTRY_TYPE_ID  = 'industry_type_id';
    const ORDERID           = 'orderid';
    const TXN_AMOUNT        = 'txn_amount';
    const TXNAMOUNT         = 'txnamount';
    const REFUNDAMOUNT      = 'refundamount';
    const TXNID             = 'txnid';
    const TXNTYPE           = 'txntype';
    const BANKTXNID         = 'banktxnid';
    const STATUS            = 'status';
    const RESPCODE          = 'respcode';
    const RESPMSG           = 'respmsg';
    const TXNDATE           = 'txndate';
    const GATEWAYNAME       = 'gatewayname';
    const BANKNAME          = 'bankname';
    const PAYMENTMODE       = 'paymentmode';

    const METHOD            = 'method';

    protected $entity = Constants\Entity::PAYTM;

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::ACTION,
        self::RECEIVED,
        self::REQUEST_TYPE,
        self::CUST_ID,
        self::CHANNEL_ID,
        self::PAYMENT_MODE_ONLY,
        self::AUTH_MODE,
        self::BANK_CODE,
        self::PAYMENT_TYPE_ID,
        self::INDUSTRY_TYPE_ID,
        self::ORDERID,
        self::TXN_AMOUNT,
        self::TXNAMOUNT,
        self::REFUNDAMOUNT,
        self::TXNID,
        self::TXNTYPE,
        self::BANKTXNID,
        self::STATUS,
        self::RESPCODE,
        self::RESPMSG,
        self::TXNDATE,
        self::GATEWAYNAME,
        self::BANKNAME,
        self::PAYMENTMODE,
    ];

    protected $fillable = [
        self::ID,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::ACTION,
        self::RECEIVED,
        self::REQUEST_TYPE,
        self::CUST_ID,
        self::CHANNEL_ID,
        self::PAYMENT_MODE_ONLY,
        self::AUTH_MODE,
        self::BANK_CODE,
        self::PAYMENT_TYPE_ID,
        self::INDUSTRY_TYPE_ID,
        self::ORDERID,
        self::TXN_AMOUNT,
        self::TXNAMOUNT,
        self::REFUNDAMOUNT,
        self::TXNID,
        self::TXNTYPE,
        self::BANKTXNID,
        self::STATUS,
        self::RESPCODE,
        self::RESPMSG,
        self::TXNDATE,
        self::GATEWAYNAME,
        self::BANKNAME,
        self::PAYMENTMODE,
    ];

    public function setMethod($method)
    {
        $this->setAttribute(self::METHOD, $method);
    }

    public function getGatewayTransactionId()
    {
        return $this->getAttribute(self::TXNID);
    }
}
