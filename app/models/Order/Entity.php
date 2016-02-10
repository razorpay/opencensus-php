<?php

namespace Models\Order;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const MERCHANT_ID = 'merchant_id';
    const AMOUNT      = 'amount';
    const CURRENCY    = 'currency';
    const ATTEMPTS    = 'attempts';
    const STATUS      = 'status';
    const RECEIPT     = 'receipt';
    // const METHOD      = 'method';
    // const ACCOUNT_ID  = 'account_id';
    // const CREATED_AT  = 'created_at';
    // const VALIDITY    = 'validity';
    // const VALID_TILL  = 'valid_till';

    // Auto capture if set
    // const CAPTURE     = 'capture';

    protected $fillable = array(
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT);

    protected $table = \Constants\Table::ORDER;

    protected $genereateIdOnCreate = true;

    protected $defaults = array(
        self::ATTEMPTS  => 0);

    protected $public = array(
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT,
        self::ATTEMPTS,
        self::CREATED_AT);

    protected static $sign = '';

    protected static $delimiter = '_';

    public function merchant()
    {
        return $this->belongsTo('Models\Order\Entity');
    }

    public function setStatus($status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAmount()
    {
        return $this->getAmountAttribute();
    }

    protected function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }
}
