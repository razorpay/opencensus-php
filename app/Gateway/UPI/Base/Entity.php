<?php

namespace RZP\Gateway\UPI\Base;

use RZP\Constants\Table;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ACTION                = 'action';
    const AMOUNT                = 'amount';
    const BANK                  = 'bank';
    const CONTACT               = 'contact';
    const NAME                  = 'name';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const PAYMENT_ID            = 'payment_id';
    const RECEIVED              = 'received';
    const STATUS_CODE           = 'status_code';
    const VPA                   = 'vpa';

    protected $table = Table::UPI;

    public $incrementing = true;

    protected $entity = 'upi';

    protected $fields = array(
        self::ID,
        self::ACTION,
        self::AMOUNT,
        self::BANK,
        self::CONTACT,
        self::NAME,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::PAYMENT_ID,
        self::RECEIVED,
        self::STATUS_CODE,
        self::VPA,
    );

    protected $fillable = array(
        self::ACTION,
        self::AMOUNT,
        self::BANK,
        self::CONTACT,
        self::NAME,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::PAYMENT_ID,
        self::RECEIVED,
        self::STATUS_CODE,
        self::VPA,
    );

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function getPaymentId()
    {
        return $this->attributes[self::PAYMENT_ID];
    }

    public function getGatewayPaymentId()
    {
        return $this->attributes[self::GATEWAY_PAYMENT_ID];
    }

    public function getMerchantId()
    {
        return $this->attributes[self::GATEWAY_MERCHANT_ID];
    }
}
