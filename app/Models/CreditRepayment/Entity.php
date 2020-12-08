<?php

namespace RZP\Models\CreditRepayment;

use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    protected static $sign = 'repay';

    protected $entity = 'credit_repayment';

    const MERCHANT_ID      = 'merchant_id';
    const AMOUNT           = 'amount';
    const CURRENCY         = 'currency';
    const TRANSACTION_ID   = 'transaction_id';

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::TRANSACTION_ID,
    ];

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function hasTransaction(): bool
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
    }

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function transaction()
    {
        return $this->belongsTo(\RZP\Models\Transaction\Entity::class);
    }
}
