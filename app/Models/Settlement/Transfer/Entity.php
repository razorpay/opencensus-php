<?php

namespace RZP\Models\Settlement\Transfer;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstant;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const SOURCE_MERCHANT_ID        = 'source_merchant_id';
    const SETTLEMENT_ID             = 'settlement_id';
    const SETTLEMENT_TRANSACTION_ID = 'settlement_transaction_id';
    const TRANSACTION_ID            = 'transaction_id';
    const BALANCE_ID                = 'balance_id';
    const CURRENCY                  = 'currency';
    const AMOUNT                    = 'amount';
    const FEE                       = 'fee';
    const TAX                       = 'tax';

    protected $entity = EntityConstant::SETTLEMENT_TRANSFER;

    protected $fillable = [
        self::MERCHANT_ID,
        self::SOURCE_MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::SETTLEMENT_TRANSACTION_ID,
        self::TRANSACTION_ID,
        self::BALANCE_ID,
        self::CURRENCY,
        self::AMOUNT,
        self::FEE,
        self::TAX,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::SOURCE_MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::SETTLEMENT_TRANSACTION_ID,
        self::TRANSACTION_ID,
        self::BALANCE_ID,
        self::CURRENCY,
        self::AMOUNT,
        self::FEE,
        self::TAX,
    ];

    // TODO: decide on public attributes
    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::SOURCE_MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::SETTLEMENT_TRANSACTION_ID,
        self::TRANSACTION_ID,
        self::BALANCE_ID,
        self::CURRENCY,
        self::AMOUNT,
        self::FEE,
        self::TAX,
    ];

    protected $defaults = [
        self::FEE => 0,
        self::TAX => 0,
    ];

    public function merchant()
    {
        return $this->belongsTo(
            'RZP\Models\Merchant\Entity',
            self::MERCHANT_ID);
    }

    public function sourceMerchant()
    {
        return $this->belongsTo(
            'RZP\Models\Merchant\Entity',
            self::SOURCE_MERCHANT_ID);
    }

    public function settlement()
    {
        return $this->belongsTo(
            'RZP\Models\Settlement\Entity',
            self::SETTLEMENT_ID);
    }

    public function settlementTransaction()
    {
        $this->belongsTo(
            'RZP\Models\Transaction\Entity',
            self::SETTLEMENT_TRANSACTION_ID);
    }

    public function transaction()
    {
        return $this->belongsTo(
            'RZP\Models\Transaction\Entity',
            self::TRANSACTION_ID);
    }

    public function balance()
    {
        return $this->belongsTo(
            'RZP\Models\Merchant\Balance\Entity',
            self::BALANCE_ID);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function hasTransaction(): bool
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
    }

    public function getMode()
    {
        return null;
    }
}
