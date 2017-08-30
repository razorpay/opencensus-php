<?php

namespace RZP\Gateway\Blade;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                     = 'id';
    const AMOUNT                 = 'amount';
    const PARES_STATUS           = 'pares_status';
    const STATUS                 = 'status';
    const CAVV                   = 'cavv';
    const ECI                    = 'eci';
    const XID                    = 'xid';
    const VERES_ENROLLED         = 'veresEnrolled';
    const CURRENCY               = 'currency';

    protected $fields = [
        self::ID,
        self::VERES_ENROLLED,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::ECI,
        self::PARES_STATUS,
        self::CAVV,
        self::XID,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $fillable = [
        self::VERES_ENROLLED,
        self::ECI,
        self::PARES_STATUS,
        self::CAVV,
        self::PARES_STATUS,
        self::XID,
        self::STATUS,
        self::RECEIVED
    ];

    protected $casts = [
        self::AMOUNT      => 'int'
    ];

    protected $entity = Constants\Entity::BLADE;

    public $incrementing = true;

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity', self::PAYMENT_ID, self::ID);
    }

    public function refund()
    {
        return $this->belongsTo('RZP\Models\Refund\Entity', self::REFUND_ID, self::ID);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getEci()
    {
        return $this->getAttribute(self::ECI);
    }

    public function getCavv()
    {
        return $this->getAttribute(self::CAVV);
    }

    public function getXid()
    {
        return $this->getAttribute(self::XID);
    }

    public function getParesStatus()
    {
        return $this->getAttribute(self::PARES_STATUS);
    }

    public function getVeresEnrolled()
    {
        return $this->getAttribute(self::VERES_ENROLLED);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setCurrency($currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setAcquirer($acquirer)
    {
        $this->setAttribute(self::ACQUIRER, $acquirer);
    }
}
