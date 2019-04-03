<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Models\Base;
use RZP\Constants\Entity as E;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const CUSTOMER_ID       = 'customer_id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
    const TYPE              = 'type';
    const STATUS            = 'status';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const CREDIT            = 'credit';
    const DEBIT             = 'debit';
    const BALANCE           = 'balance';
    const DESCRIPTION       = 'description';
    const RECONCILED_AT     = 'reconciled_at';

    // Public
    const SOURCE            = 'source';

    protected static $sign = 'ctxn';

    protected $entity = 'customer_transaction';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::TYPE,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::DESCRIPTION,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
        self::STATUS,
        self::TYPE,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::DESCRIPTION,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SOURCE
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
        self::CREDIT        => 'int',
        self::DEBIT         => 'int',
        self::BALANCE       => 'int',
    ];

    protected $ignoredRelations = [
        self::ENTITY
    ];

    // -------------------- Relations ---------------------------

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // @codingStandardsIgnoreStart
    public function entity()
    {
        return $this->morphTo();
    }
    // @codingStandardsIgnoreEnd

    // -------------------- End Relations -----------------------

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }

    public function getDebit()
    {
        return $this->getAttribute(self::DEBIT);
    }

    public function getCredit()
    {
        return $this->getAttribute(self::CREDIT);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getReconciledAt()
    {
        return $this->getAttribute(self::RECONCILED_AT);
    }

    public function setBalance($amount)
    {
        $this->setAttribute(self::BALANCE, $amount);
    }

    public function setDebit($amount)
    {
        $this->setAttribute(self::DEBIT, $amount);
    }

    public function setCredit($amount)
    {
        $this->setAttribute(self::CREDIT, $amount);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setDescription(string $description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    public function setEntityType($type)
    {
        $this->setAttribute(self::ENTITY_TYPE, $type);
    }

    public function setEntityId($id)
    {
        $this->setAttribute(self::ENTITY_ID, $id);
    }

    public function setReconciledAt($timestamp)
    {
        $this->setAttribute(self::RECONCILED_AT, $timestamp);
    }

    /**
     * Adding dummy function to set reconciled type as db doesn't have this column currently.
     * Payout recon internally calls this function.
     *
     * @param $reconciledType
     */
    public function setReconciledType($reconciledType)
    {

    }

    public function setPublicSourceAttribute(array & $attributes)
    {
        $sourceId = $this->getAttribute(self::ENTITY_ID);

        $sourceType = $this->getAttribute(self::ENTITY_TYPE);

        $entity = E::getEntityClass($sourceType);

        $attributes[self::SOURCE] = $entity::getSignedId($sourceId);
    }
}
