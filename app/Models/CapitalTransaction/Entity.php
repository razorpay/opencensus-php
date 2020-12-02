<?php

namespace RZP\Models\CapitalTransaction;

use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    protected $entity = 'capital_transaction';

    const TYPE             = 'type';
    const MERCHANT_ID      = 'merchant_id';
    const AMOUNT           = 'amount';
    const CURRENCY         = 'currency';
    const TRANSACTION_ID   = 'transaction_id';
    const BALANCE_ID       = 'balance_id';

    protected $fillable = [
        self::ID,
        self::TYPE,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::TRANSACTION_ID,
        self::BALANCE_ID,
    ];

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function hasTransaction(): bool
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
    }

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function balance()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Balance\Entity::class);
    }

    public function transaction()
    {
        return $this->belongsTo(\RZP\Models\Transaction\Entity::class);
    }
}
