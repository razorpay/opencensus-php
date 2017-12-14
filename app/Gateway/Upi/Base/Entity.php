<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ACTION                = 'action';
    const TYPE                  = 'type';
    const NAME                  = 'name';
    const AMOUNT                = 'amount';
    const ACQUIRER              = 'acquirer';
    const BANK                  = 'bank';
    const PROVIDER              = 'provider';
    const EMAIL                 = 'email';
    const CONTACT               = 'contact';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const NPCI_REFERENCE_ID     = 'npci_reference_id';
    const PAYMENT_ID            = 'payment_id';
    const REFUND_ID             = 'refund_id';
    const EXPIRY_TIME           = 'expiry_time';
    const RECEIVED              = 'received';
    const STATUS_CODE           = 'status_code';
    const VPA                   = 'vpa';

    public $incrementing = true;

    protected $entity = 'upi';

    protected $fields = [
        self::ID,
        self::ACTION,
        self::AMOUNT,
        self::TYPE,
        self::ACQUIRER,
        self::BANK,
        self::PROVIDER,
        self::CONTACT,
        self::EMAIL,
        self::NAME,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::NPCI_REFERENCE_ID,
        self::REFUND_ID,
        self::PAYMENT_ID,
        self::RECEIVED,
        self::STATUS_CODE,
        self::VPA,
        self::EXPIRY_TIME,
    ];

    protected $fillable = [
        self::ACTION,
        self::TYPE,
        self::AMOUNT,
        self::ACQUIRER,
        self::BANK,
        self::PROVIDER,
        self::CONTACT,
        self::EMAIL,
        self::NAME,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::NPCI_REFERENCE_ID,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::RECEIVED,
        self::STATUS_CODE,
        self::VPA,
        self::EXPIRY_TIME,
    ];

    protected $casts = [
        'amount' => 'int'
    ];

    protected static $generators = [
        self::PROVIDER,
        self::BANK,
    ];

    public function setAcquirer($acquirer)
    {
        $this->setAttribute(self::ACQUIRER, $acquirer);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setProvider($provider)
    {
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

    public function getNpciReferenceId()
    {
        return $this->getAttribute(self::NPCI_REFERENCE_ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getVpa()
    {
        return $this->getAttribute(self::VPA);
    }

    public function getRefundId()
    {
        return $this->getAttribute(self::REFUND_ID);
    }

    public function extractProviderFromVpa()
    {
        $vpa = $this->getAttribute(self::VPA);

        $vpaParts = explode('@', $vpa);

        return $vpaParts[1] ?? null;
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID);
    }

    protected function generateProvider(array &$input)
    {
        if (isset($input[self::VPA]) === false)
        {
            return;
        }

        $vpa = $input[self::VPA];

        $vpaParts = explode('@', $vpa);

        $provider = $vpaParts[1];

        $this->setAttribute(self::PROVIDER, $provider);
    }

    protected function generateBank($input)
    {
        $provider = $this->getAttribute(self::PROVIDER);

        if (isset($provider) === false)
        {
            return;
        }

        $bank = ProviderCode::getBankCode($provider);

        $this->setAttribute(self::BANK, $bank);
    }
}
