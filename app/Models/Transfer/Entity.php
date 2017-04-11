<?php

namespace RZP\Models\Transfer;

use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Transaction;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

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
    const NOTES                 = 'notes';
    const ON_HOLD               = 'on_hold';
    const ON_HOLD_UNTIL         = 'on_hold_until';
    const TRANSACTION_ID        = 'transaction_id';

    // Public Attribute keys for SOURCE_ID and TO_ID
    const SOURCE                = 'source';
    const RECIPIENT             = 'recipient';

    protected static $sign = 'trf';

    protected $entity = 'transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
    ];

    protected $visible = [
        self::ID,
        self::TO_TYPE,
        self::TO_ID,
        self::SOURCE_TYPE,
        self::SOURCE_ID,
        self::SOURCE,
        self::RECIPIENT,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_REVERSED,
        self::NOTES,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
        self::RECIPIENT,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_REVERSED,
        self::NOTES,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::SOURCE,
        self::RECIPIENT,
        self::TRANSACTION_ID,
        self::ENTITY,
    ];

    protected $casts = [
        self::AMOUNT                 => 'int',
        self::AMOUNT_REVERSED        => 'int',
        self::ON_HOLD                => 'bool',
        self::ON_HOLD_UNTIL          => 'int',
    ];

    protected $defaults = [
        self::AMOUNT_REVERSED   => 0,
        self::NOTES             => [],
        self::ON_HOLD           => 0,
        self::ON_HOLD_UNTIL     => null,
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

    public function source()
    {
        return $this->morphTo();
    }

    public function to()
    {
        return $this->morphTo();
    }

    // -------------------- End Relations -----------------------

    // -------------------- Getters -----------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE_TYPE);
    }

    public function getToType()
    {
        return $this->getAttribute(self::TO_TYPE);
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
        return ($this->getAmount() - $this->getAmountReversed());
    }

    public function getOnHold()
    {
        return $this->getAttribute(self::ON_HOLD);
    }

    public function getOnHoldUntil()
    {
        return $this->getAttribute(self::ON_HOLD_UNTIL);
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

    public function setOnHoldUntil($holdUntil)
    {
        $this->setAttribute(self::ON_HOLD_UNTIL, $holdUntil);
    }

    // -------------------- End Setters ---------------------------

    public function reverseAmount(int $amount)
    {
        $amountUnreversed = $this->getAmountUnreversed();

        if ($amount > $amountUnreversed)
        {
            throw new Exception\LogicException(
                'Transfer reversal amount should be less than or equal to amount not refunded yet',
                'amount_reversed',
                [
                    'amount'                => $amount,
                    'amount_unreversed'     => $amountUnreversed,
                ]);
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

    public function setPublicRecipientAttribute(array & $attributes)
    {
        $toId = $this->getAttribute(self::TO_ID);

        $toType = $this->getAttribute(self::TO_TYPE);

        $entity = E::getEntityClass($toType);

        if ($toType === 'merchant')
        {
            $entity = 'RZP\Models\Merchant\AccountEntity';
        }

        $attributes[self::RECIPIENT] = $entity::getSignedId($toId);
    }

    public function setPublicSourceAttribute(array & $attributes)
    {
        $sourceId = $this->getAttribute(self::SOURCE_ID);

        $sourceType = $this->getAttribute(self::SOURCE_TYPE);

        $entity = E::getEntityClass($sourceType);

        if ($sourceType === 'merchant')
        {
            $entity = 'RZP\Models\Merchant\AccountEntity';
        }

        $attributes[self::SOURCE] = $entity::getSignedId($sourceId);
    }
}
