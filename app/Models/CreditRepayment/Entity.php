<?php

namespace RZP\Models\CreditRepayment;

use RZP\Constants\Entity as E;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    protected static $sign = 'repay';

    protected $entity = E::CREDIT_REPAYMENT;

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

    public static function create(array $input): Entity
    {
        $creditRepayment = (new Entity);
        $creditRepayment->setAttribute(self::ID, $input['id']);
        $creditRepayment->setAttribute(self::AMOUNT, $input['amount']);
        $creditRepayment->setAttribute(self::CURRENCY, $input['currency']);
        $creditRepayment->setAttribute(self::MERCHANT_ID, $input['merchant_id']);
        $creditRepayment->setAttribute(self::TRANSACTION_ID, $input['transaction_id']);
        return $creditRepayment;
    }
}
