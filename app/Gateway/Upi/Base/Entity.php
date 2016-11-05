<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;
use RZP\Constants\Table;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ACTION                = 'action';
    const NAME                  = 'name';
    const AMOUNT                = 'amount';
    const BANK                  = 'bank';
    const EMAIL                 = 'email';
    const CONTACT               = 'contact';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const PAYMENT_ID            = 'payment_id';
    const RECEIVED              = 'received';
    const STATUS_CODE           = 'status_code';
    const VPA                   = 'vpa';

    public $incrementing = true;

    protected $entity = 'upi';

    protected $fields = array(
        self::ID,
        self::ACTION,
        self::AMOUNT,
        self::BANK,
        self::CONTACT,
        self::EMAIL,
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
        self::EMAIL,
        self::NAME,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::PAYMENT_ID,
        self::RECEIVED,
        self::STATUS_CODE,
        self::VPA,
    );

    protected $casts = array(
        'amount'  =>  'int'
    );

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getGatewayPaymentId()
    {
        return $this->getAttribute(self::GATEWAY_PAYMENT_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID);
    }
}
