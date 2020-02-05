<?php

namespace RZP\Gateway\Worldline;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const MID              = 'mid';
    const REF_NO           = 'ref_no';
    const AUTH_CODE        = 'auth_code';
    const BANK_CODE        = 'bank_code';
    const TXN_AMOUNT       = 'txn_amount';
    const PRIMARY_ID       = 'primary_id';
    const CUSTOMER_VPA     = 'customer_vpa';
    const SECONDARY_ID     = 'secondary_id';
    const TXN_CURRENCY     = 'txn_currency';
    const AGGREGATOR_ID    = 'aggregator_id';
    const TRANSACTION_TYPE = 'transaction_type';
    const GATEWAY_UTR      = 'gateway_utr';

    protected $entity = 'worldline';

    protected $fillable = [
        self::MID,
        self::TXN_CURRENCY,
        self::TXN_AMOUNT,
        self::AUTH_CODE,
        self::REF_NO,
        self::GATEWAY_UTR,
        self::TRANSACTION_TYPE,
        self::BANK_CODE,
        self::AGGREGATOR_ID,
        self::CUSTOMER_VPA,
        self::PRIMARY_ID,
        self::SECONDARY_ID,
    ];

    public function getAuthCode()
    {
        return $this->getAttribute(self::AUTH_CODE);
    }

    public function getGatewayUtr()
    {
        return $this->getAttribute(self::GATEWAY_UTR);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::TXN_AMOUNT, $amount);
    }

    public function setCurrency($currency)
    {
        $this->setAttribute(self::TXN_CURRENCY, $currency);
    }

    public function setAuthCode($authCode)
    {
        $this->setAttribute(self::AUTH_CODE, $authCode);
    }

    public function setGatewayUtr($utr)
    {
        $this->setAttribute(self::GATEWAY_UTR, $utr);
    }
}
