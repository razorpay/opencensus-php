<?php

namespace RZP\Models\Merchant\Credits\Balance;

use RZP\Base\BuilderEx;
use RZP\Models\Merchant\Credits;
use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    const BALANCE           = 'balance';
    const MERCHANT_ID       = 'merchant_id';
    const TYPE              = 'type';
    const PRODUCT           = 'product';
    const EXPIRED_AT        = 'expired_at';
    const REFERENCE1        = 'reference1';
    const REFERENCE2        = 'reference2';
    const REFERENCE3        = 'reference3';
    const REFERENCE4        = 'reference4';

    protected $entity = 'credit_balance';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::BALANCE,
        self::TYPE,
        self::PRODUCT,
        self::EXPIRED_AT
    ];

    protected $visible = [
        self::BALANCE,
        self::TYPE,
        self::PRODUCT,
        self::MERCHANT_ID,
        self::EXPIRED_AT,
    ];

    protected $public = [
        self::BALANCE,
        self::TYPE,
        self::PRODUCT,
        self::MERCHANT_ID,
        self::EXPIRED_AT,
    ];

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }

    public function incrementBalance($credits)
    {
        $this->increment(self::BALANCE, $credits);
    }

    public function decrementBalance($credits)
    {
        $this->decrement(self::BALANCE, $credits);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }
}
