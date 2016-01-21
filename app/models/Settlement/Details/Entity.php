<?php

namespace Models\Settlement\Details;

use Models\Base;
use EE\Exception;

class Entity extends Base\PublicEntity
{
    const ID                    =      'id';
    const MERCHANT_ID           =      'merchant_id';
    const SETTLEMENT_ID         =      'settlement_id';
    const TYPE                  =      'type';
    const COUNT                 =      'count';
    const AMOUNT                =      'amount';
    const DESCRIPTION           =      'description';
    const CREATED_AT            =      'created_at';
    const UPDATED_AT            =      'updated_at';

    protected $table = \Constants\Table::SETTLEMENT_DETAIL;

    protected $entity = 'settlement_detail';

    protected static $sign = '';

    protected static $delimiter = '';

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::TYPE,
        self::COUNT,
        self::AMOUNT,
        self::DESCRIPTION
    );

    protected $public = array(
        self::ID,
        self::MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::TYPE,
        self::COUNT,
        self::AMOUNT,
        self::DESCRIPTION,
        self::CREATED_AT
    );

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function settlement()
    {
        return $this->belongsTo('Models\Settlement\Entity');
    }

    public function getCount()
    {
        return (int) $this->getAttribute(self::COUNT);
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    //get Attribute
    public function getCountAttribute()
    {
        return (int) $this->attributes[self::COUNT];
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    //setter
    public function setCount($count)
    {
        $this->setAttribute(self::COUNT, $count);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }
}