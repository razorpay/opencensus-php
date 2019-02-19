<?php

namespace RZP\Gateway\Isg;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const MERCHANT_REFERENCE                    = 'merchant_reference';
    const SECONDARY_ID                          = 'secondary_id';
    const AMOUNT                                = 'amount';
    const BANK_REFERENCE_NUMBER                 = 'bank_reference_no';
    const TRANSACTION_DATE_TIME                 = 'transaction_date_time';
    const AUTH_CODE                             = 'auth_code';
    const RRN                                   = 'rrn';
    const TIP_AMOUNT                            = 'tip_amount';
    const STATUS_CODE                           = 'status_code';
    const STATUS_DESC                           = 'status_desc';
    const MERCHANT_PAN                          = 'merchant_pan';

    protected $entity = 'isg';

    protected $fillable = [
        self::MERCHANT_REFERENCE,
        self::SECONDARY_ID,
        self::BANK_REFERENCE_NUMBER,
        self::TRANSACTION_DATE_TIME,
        self::AUTH_CODE,
        self::RRN,
        self::TIP_AMOUNT,
        self::STATUS_DESC,
        self::STATUS_CODE,
        self::AMOUNT,
        self::MERCHANT_PAN,
        self::RECEIVED,
    ];

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function getGatewayTransactionId()
    {
        return $this->getAttribute(self::BANK_REFERENCE_NUMBER);
    }

    public function setGatewayTransactionId($gatewayTransactionId)
    {
        $this->setAttribute(self::BANK_REFERENCE_NUMBER, $gatewayTransactionId);
    }
}
