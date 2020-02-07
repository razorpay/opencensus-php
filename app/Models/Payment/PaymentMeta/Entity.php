<?php

namespace RZP\Models\Payment\PaymentMeta;


class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const PAYMENT_ID             = 'payment_id';
    const GATEWAY_AMOUNT         = 'gateway_amount';
    const GATEWAY_CURRENCY       = 'gateway_currency';
    const FOREX_RATE             = 'forex_rate';
    const DCC_OFFERED            = 'dcc_offered';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::PAYMENT_ID,
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
        self::FOREX_RATE,
        self::IS_DCC_OFFERED,
    ];

    protected $public = [
        self::ID,
        self::PAYMENT_ID,
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
        self::FOREX_RATE,
        self::IS_DCC_OFFERED,
    ];

    protected $visible = [
        self::ID,
        self::PAYMENT_ID,
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
        self::FOREX_RATE,
        self::IS_DCC_OFFERED,
    ];

    protected $casts = [
        self::GATEWAY_AMOUNT   => 'int',
        self::IS_DCC_OFFERED   => 'bool',
    ];

    protected $entity = 'payment_meta';

    // --------------------------- Relations -----------------------------------

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    // -------------------------- Getters --------------------------------------

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getGatewayAmount()
    {
        return $this->getAttribute(self::GATEWAY_AMOUNT);
    }

    public function getGatewayCurrency()
    {
        return $this->getAttribute(self::GATEWAY_CURRENCY);
    }

    public function getForexRate()
    {
        return $this->getAttrubute(self::FOREX_RATE);
    }

    public function isDccOffered()
    {
        return $this->getAttribute(self::DCC_OFFERED);
    }

    // ----------------------- Setters ---------------------------------------

    public function setPaymentId($paymentId)
    {
        $this->setAttribute(self::PAYMENT_ID, $paymentId);
    }

    public function setGatewayAmount($gatewayAmount)
    {
        $this->setAttribute(self::GATEWAY_AMOUNT, $gatewayAmount);
    }

    public function setGatewayCurrency($gatewayCurrency)
    {
        $this->setAttribute(self::GATEWAY_CURRENCY, $gatewayCurrency);
    }

    public function setForexRate($forexRate)
    {
        $this->setAttribute(self::FOREX_RATE, $forexRate);
    }

    public function setDccOffered($dccOffered)
    {
        $this->setAttribute(self::DCC_OFFERED, $dccOffered);
    }

}
