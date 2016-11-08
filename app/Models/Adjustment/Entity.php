<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const CHANNEL           = 'channel';
    const DESCRIPTION       = 'description';
    const TRANSACTION_ID    = 'transaction_id';

    protected static $sign = 'adj';

    protected $entity = 'adjustment';

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::AMOUNT,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY);

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::DESCRIPTION,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::DESCRIPTION,
        self::TRANSACTION_ID,
        self::CREATED_AT);

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    protected function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }
}
