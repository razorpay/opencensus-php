<?php

namespace Models\Adjustment;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const CHANNEL           = 'channel';
    const DESCRIPTION       = 'description';
    const TRANSACTION_ID    = 'transaction_id';

    protected $table = \Constants\Table::ADJUSTMENT;

    protected static $sign = 'adj';

    protected $entity = 'adjustment';

    protected $genereateIdOnCreate = true;

    protected $fillable = array(
        self::MERCHANT_ID,
        self::CHANNEL,
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

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('Models\Transaction\Entity');
    }
}
