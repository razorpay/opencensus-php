<?php

namespace RZP\Models\Transfer;

use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Models\Base;
use RZP\Models\Transaction;

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
    const REVERSAL_STATUS       = 'reversal_status';
    const AMOUNT_REVERSED       = 'amount_reversed';
    const ON_HOLD               = 'on_hold';
    const HOLD_UNTIL            = 'hold_until';
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
        self::SOURCE_TYPE,
        self::ON_HOLD,
        self::HOLD_UNTIL,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TO_ID,
        self::TO_TYPE,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_REVERSED,
        self::ON_HOLD,
        self::HOLD_UNTIL,
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
        self::ON_HOLD,
        self::HOLD_UNTIL,
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
        self::AMOUNT_REVERSED        => 'int',
        self::ON_HOLD                => 'bool',
        self::HOLD_UNTIL             => 'int',
    ];

    protected $defaults = [
        self::AMOUNT_REVERSED   => 0,
        self::ON_HOLD           => 0,
        self::HOLD_UNTIL        => null,
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

    // -------------------- Getters -----------------------------

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getSourceId()
    {
        return $this->getAttribute(self::SOURCE_ID);
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

    public function getOnHold()
    {
        return $this->getAttribute(self::ON_HOLD);
    }

    public function getHoldUntil()
    {
        return $this->getAttribute(self::HOLD_UNTIL);
    }

    // -------------------- End Getters ---------------------------

    // -------------------- Setters ---------------------------

    public function setAmountReversed(int $amount)
    {
        $this->setAttribute(self::AMOUNT_REVERSED, $amount);
    }

    public function setOnHold(bool $onHold)
    {
        $this->setAttribute(self::ON_HOLD, $onHold);
    }

    public function setHoldUntil($holdUntil)
    {
        $this->setAttribute(self::HOLD_UNTIL, $holdUntil);
    }

    // -------------------- End Setters ---------------------------

    public function reverseAmount(int $amount)
    {
        $amountUnreversed = $this->getAmountUnreversed();

        if ($amount > $amountUnreversed)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Transfer reversal amount should be less than or equal to amount not refunded yet');
        }

        $amountReversed = $this->getAmountReversed() + $amount;

        $this->setAttribute(self::AMOUNT_REVERSED, $amountReversed);
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
