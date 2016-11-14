<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ACTION                = 'action';
    const NAME                  = 'name';
    const AMOUNT                = 'amount';
    const BANK                  = 'bank';
    const PROVIDER              = 'provider';
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
        self::PROVIDER,
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
        self::PROVIDER,
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

    public function setProvider($provider) {
        $this->setAttribute(self::PROVIDER, $provider);
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

    public function getProviderFromVpa() {
        $vpa = $this->getAttribute(self::VPA);

        $vpaParts = explode('@', $vpa);

//        if (count($vpaParts) < 2) {
//            return;
//        }

        return $vpaParts[1] ?? NULL;
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID);
    }
}
