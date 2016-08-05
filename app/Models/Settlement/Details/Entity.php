<?php

namespace RZP\Models\Settlement\Details;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Constants;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const MERCHANT_ID   = 'merchant_id';
    const SETTLEMENT_ID = 'settlement_id';
    const TYPE          = 'type';
    const TYPEXYZ       = 'typexyz';
    const COUNT         = 'count';
    const AMOUNT        = 'amount';
    const DESCRIPTION   = 'description';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    protected $table = Constants\Table::SETTLEMENT_DETAIL;

    protected $entity = 'settlement_details';

    protected static $sign = '';

    protected static $delimiter = '';

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::TYPE,
        self::TYPEXYZ,
        self::COUNT,
        self::AMOUNT,
        self::DESCRIPTION
    );

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::TYPE,
        self::TYPEXYZ,
        self::COUNT,
        self::AMOUNT,
        self::DESCRIPTION
    );

    protected $public = array(
        self::TYPE,
        self::TYPEXYZ,
        self::COUNT,
        self::AMOUNT
    );

    protected $casts = array(
        self::COUNT     => 'int',
        self::AMOUNT    => 'int'
    );

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function settlement()
    {
        return $this->belongsTo('RZP\Models\Settlement\Entity');
    }

    public function getCount()
    {
        return $this->getAttribute(self::COUNT);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    protected function setCount($count)
    {
        $this->setAttribute(self::COUNT, $count);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }
}