<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const TO_ID                 = 'to_id';
    const TO_TYPE               = 'to_type';
    const SOURCE_ID             = 'source_id';
    const SOURCE_TYPE           = 'source_type';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const BASE_AMOUNT           = 'base_amount';
    const AMOUNT_REVERSED       = 'amount_reversed';
    const BASE_AMOUNT_REVERSED  = 'base_amount_reversed';
    const TRANSACTION_ID        = 'transaction_id';

    protected static $sign = 'trf';

    protected $entity = 'transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::TO_ID,
        self::TO_TYPE,
        self::AMOUNT,
        self::CURRENCY,
        self::SOURCE_ID,
        self::SOURCE_TYPE
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TO_ID,
        self::TO_TYPE,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::CURRENCY,
        self::AMOUNT_REVERSED,
        self::BASE_AMOUNT_REVERSED,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::SOURCE_ID,
        self::TO_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_REVERSED,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::TRANSACTION_ID,
        self::TO_ID,
        self::SOURCE_ID,
    ];

    protected $casts = [
        self::AMOUNT                 => 'int',
        self::BASE_AMOUNT            => 'int',
        self::AMOUNT_REVERSED        => 'int',
        self::BASE_AMOUNT_REVERSED   => 'int',
    ];

    protected $defaults = [
        self::AMOUNT_REVERSED   => 0,
    ];

    // -------------------- Relations ---------------------------

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function transfers()
    {
        return $this->morphMany('RZP\Models\Transfer\Entity', 'entity');
    }

    // -------------------- End Relations -----------------------

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getBaseAmount()
    {
        return $this->GetAttribute(self::BASE_AMOUNT);
    }

    public function getToId()
    {
        return $this->getAttribute(self::TO_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getAmountReversed()
    {
        return $this->getAttribute(self::AMOUNT_REVERSED);
    }

    public function getAmountUnreversed()
    {
        return $this->getAmount() - $this->getAmountReversed();
    }

    public function getBaseAmountReversed()
    {
        return $this->getAttribute(self::BASE_AMOUNT_REVERSED);
    }

    public function getBaseAmountUnreversed()
    {
        return $this->getBaseAmount() - $this->getBaseAmountReversed();
    }

    /**
     * Get the rate at which currency conversion was applied to
     * the transfer amount
     */
    public function getCurrencyConversionRate()
    {
        $baseAmount = $this->getBaseAmount();

        $transferAmount = $this->getAmount();

        return $baseAmount / $transferAmount;
    }

    public function setBaseAmount(int $amount)
    {
        $this->setAttribute(self::BASE_AMOUNT, $amount);
    }

    public function setAmountReversed(int $amount)
    {
        $this->setAttribute(self::AMOUNT_REVERSED, $amount);
    }

    public function reverseAmount(int $amount, int $baseAmount)
    {
        $amountUnreversed = $this->getAmountUnreversed();

        if ($amount > $amountUnreversed)
        {
            throw new Exception\LogicException(
                'Transfer refund amount should be less than or equal to amount not refunded yet');
        }

        $amountReversed = $this->getAmountReversed() + $amount;

        $baseAmountReversed = $this->getBaseAmountReversed() + $baseAmount;

        $this->setAttribute(self::AMOUNT_REVERSED, $amountReversed);

        $this->setAttribute(self::BASE_AMOUNT_REVERSED, $baseAmountReversed);

    }

    public function setPublicTransactionIdAttribute(array & $attributes)
    {
        $txnId = $this->getAttribute(self::TRANSACTION_ID);

        if ($txnId !== null)
        {
            $attributes[self::TRANSACTION_ID] = Transaction\Entity::getSignedId($txnId);
        }
    }

    public function setPublicToIdAttribute(array & $attributes)
    {
        $toId = $this->getAttribute(self::TO_ID);

        $toType = $this->getAttribute(self::TO_TYPE);

        $entity = E::getEntityClass($toType);

        if ($toType === 'merchant')
        {
            $entity = 'RZP\Models\Merchant\AccountEntity';
        }

        if ($toId !== null)
        {
            $attributes[self::TO_ID] = $entity::getSignedId($toId);
        }
    }

    public function setPublicSourceIdAttribute(array & $attributes)
    {
        $sourceId = $this->getAttribute(self::SOURCE_ID);

        $sourceType = $this->getAttribute(self::SOURCE_TYPE);

        $entity = E::getEntityClass($sourceType);

        if ($sourceId !== null)
        {
            $attributes[self::SOURCE_ID] = $entity::getSignedId($sourceId);
        }
    }
}
