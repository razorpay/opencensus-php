<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const PAYMENT_ID             = 'payment_id';
    const GATEWAY_AMOUNT         = 'gateway_amount';
    const GATEWAY_CURRENCY       = 'gateway_currency';
    const FOREX_RATE             = 'forex_rate';
    const DCC_OFFERED            = 'dcc_offered';
    const DCC_MARK_UP_PERCENT    = 'dcc_mark_up_percent';
    const ACTION                 = 'action_type';
    const REFERENCE_ID           = 'reference_id';
    const MISMATCH_AMOUNT        = 'mismatch_amount';
    const MISMATCH_AMOUNT_REASON = 'mismatch_amount_reason';
    const MCC_APPLIED            = 'mcc_applied';
    const MCC_FOREX_RATE         = 'mcc_forex_rate';
    const MCC_MARK_DOWN_PERCENT  = 'mcc_mark_down_percent';


    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::PAYMENT_ID,
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
        self::FOREX_RATE,
        self::DCC_OFFERED,
        self::DCC_MARK_UP_PERCENT,
        self::ACTION,
        self::REFERENCE_ID,
        self::MISMATCH_AMOUNT,
        self::MISMATCH_AMOUNT_REASON,
        self::MCC_APPLIED,
        self::MCC_FOREX_RATE,
        self::MCC_MARK_DOWN_PERCENT,
    ];

    protected $public = [
        self::ID,
        self::PAYMENT_ID,
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
        self::FOREX_RATE,
        self::DCC_OFFERED,
        self::DCC_MARK_UP_PERCENT,
        self::ACTION,
        self::REFERENCE_ID,
        self::MISMATCH_AMOUNT,
        self::MISMATCH_AMOUNT_REASON,
        self::MCC_APPLIED,
        self::MCC_FOREX_RATE,
        self::MCC_MARK_DOWN_PERCENT,
    ];

    protected $visible = [
        self::ID,
        self::PAYMENT_ID,
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
        self::FOREX_RATE,
        self::DCC_OFFERED,
        self::DCC_MARK_UP_PERCENT,
        self::ACTION,
        self::REFERENCE_ID,
        self::MISMATCH_AMOUNT,
        self::MISMATCH_AMOUNT_REASON,
        self::MCC_APPLIED,
        self::MCC_FOREX_RATE,
        self::MCC_MARK_DOWN_PERCENT,
    ];

    protected $casts = [
        self::GATEWAY_AMOUNT   => 'int',
        self::DCC_OFFERED      => 'bool',
        self::FOREX_RATE       => 'float',
        self::MCC_FOREX_RATE   => 'float',
        self::MCC_APPLIED      => 'bool',
    ];

    protected $defaults = [
        self::DCC_OFFERED  => false,
        self::FOREX_RATE   => null,
        self::ACTION       => null,
        self::REFERENCE_ID => null,
        self::MCC_APPLIED  => false,
        self::MCC_FOREX_RATE => null,
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
        return $this->getAttribute(self::FOREX_RATE);
    }

    public function isDccOffered()
    {
        return $this->getAttribute(self::DCC_OFFERED);
    }

    public function getDccMarkUpPercent()
    {
        return $this->getAttribute(self::DCC_MARK_UP_PERCENT);
    }

    public function getAction()
    {
        return $this->getAttribute(self::ACTION);
    }

    public function getReferenceId()
    {
        return $this->getAttribute(self::REFERENCE_ID);
    }

    public function getMismatchAmount()
    {
        return $this->getAttribute(self::MISMATCH_AMOUNT);
    }

    public function getMismatchAmountReason()
    {
        return $this->getAttribute(self::MISMATCH_AMOUNT_REASON);
    }

    public function getMccApplied()
    {
        return $this->getAttribute(self::MCC_APPLIED);
    }

    public function getMccMarkDownPercent()
    {
        return $this->getAttribute(self::MCC_MARK_DOWN_PERCENT);
    }

    public function getMccForexRate()
    {
        return $this->getAttribute(self::MCC_FOREX_RATE);
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

    public function setDccMarkUpPercent($dccMarkUpPercent)
    {
        $this->setAttribute(self::DCC_MARK_UP_PERCENT, $dccMarkUpPercent);
    }

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setReferenceId($referenceId)
    {
        $this->setAttribute(self::REFERENCE_ID, $referenceId);
    }

    public function setMismatchAmount($amount)
    {
        $this->setAttribute(self::MISMATCH_AMOUNT, $amount);
    }

    public function setMismatchAmountReason($reason)
    {
        $this->setAttribute(self::MISMATCH_AMOUNT_REASON, $reason);
    }

    public function setMccApplied($mccApplied)
    {
        return $this->setAttribute(self::MCC_APPLIED,$mccApplied);
    }

    public function setMccMarkDownPercent($mccMarkDownPercent)
    {
        return $this->setAttribute(self::MCC_MARK_DOWN_PERCENT,$mccMarkDownPercent);
    }

    public function setMccForexRate($mccForexRate)
    {
        return $this->setAttribute(self::MCC_FOREX_RATE,$mccForexRate);
    }
}
