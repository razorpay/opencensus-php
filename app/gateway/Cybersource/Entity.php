<?php

namespace Gateway\Cybersource;

use Gateway\Base;
use Constants;

class Entity extends Base\Entity
{
    const ID                   = 'id';
    const COMMERCE_INDICATOR   = 'commerce_indicator';
    const COLLECTION_INDICATOR = 'collection_indicator';
    const AMOUNT               = 'amount';
    const STATUS               = 'status';
    const ECI                  = 'eci';
    const CAVV                 = 'cavv';
    const AUTH_DATA            = 'auth_data';
    const REF                  = 'ref';
    const CAPTURE_REF          = 'capture_ref';
    const XID                  = 'xid';
    const PARES_STATUS         = 'pares_status';
    const ERROR_CODE           = 'error_code';
    const CREATED_AT           = 'created_at';
    const UPDATED_AT           = 'updated_at';

    protected $fields = array(
        self::ID,
        self::PAYMENT_ID,
        self::RECEIVED,
        self::REFUND_ID,
        self::ACTION,
        self::AMOUNT,
        self::STATUS,
        self::ECI,
        self::CAVV,
        self::AUTH_DATA,
        self::REF,
        self::CAPTURE_REF,
        self::COMMERCE_INDICATOR,
        self::XID,
        self::PARES_STATUS,
        self::ERROR_CODE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::COLLECTION_INDICATOR,
    );

    protected $fillable = array(
        self::PAYMENT_ID,
        self::RECEIVED,
        self::REFUND_ID,
        self::ACTION,
        self::AMOUNT,
        self::STATUS,
        self::ECI,
        self::CAVV,
        self::AUTH_DATA,
        self::REF,
        self::CAPTURE_REF,
        self::COMMERCE_INDICATOR,
        self::XID,
        self::PARES_STATUS,
        self::ERROR_CODE,
        self::COLLECTION_INDICATOR,
    );

    protected $table = Constants\Table::CYBERSOURCE;

    protected $primaryKey = self::ID;

    protected $entity = Constants\Table::CYBERSOURCE;

    public $incrementing = true;

    protected $guarded = array();

    public function payment()
    {
        return $this->belongsTo('Models\Payment\Entity', self::PAYMENT_ID, self::ID);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getCommerceIndicator()
    {
        return $this->getAttribute(self::COMMERCE_INDICATOR);
    }

    public function getCollectionIndicator()
    {
        return $this->getAttribute(self::COLLECTION_INDICATOR);
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

    public function getAuthData()
    {
        return $this->getAttribute(self::AUTH_DATA);
    }

    public function getRef()
    {
        return $this->getAttribute(self::REF);
    }

    public function getCaptureRef()
    {
        return $this->getAttribute(self::CAPTURE_REF);
    }

    public function getXid()
    {
        return $this->getAttribute(self::XID);
    }

    public function getParesStatus()
    {
        return $this->getAttribute(self::PARES_STATUS);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }
}
