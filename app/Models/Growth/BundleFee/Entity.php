<?php

namespace RZP\Models\Growth\BundleFee;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    protected static $sign = 'bundfee';

    protected $entity = 'bundle_fee';

    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const BASE_AMOUNT            = 'base_amount';
    const IS_REVERSAL            = 'is_reversal';

    protected $fillable = [
        self::AMOUNT,
        self::ID,
        self::CURRENCY,
        self::MERCHANT_ID,
        self::BASE_AMOUNT,
        self::IS_REVERSAL,
    ];

    protected $casts = [
        self::AMOUNT           => 'int',
        self::BASE_AMOUNT      => 'int',
        self::IS_REVERSAL      => 'bool',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function isReversal()
    {
        return boolval($this->getAttribute(self::IS_REVERSAL));
    }

    /**
     * This function is used in case of polymorphic relations where we associate one entity
     * with multiple other entities using (entity_type and entity_id). It determines the string that
     * will be stored for entity_type when the association is with the QrCode entity.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->entity;
    }
}
