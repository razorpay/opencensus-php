<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const TO_ID             = 'to_id';
    const TO_TYPE           = 'to_type';
    const SOURCE_ID         = 'source_id';
    const SOURCE_TYPE       = 'source_type';
    const AMOUNT            = 'amount';
    const TRANSACTION_ID    = 'transaction_id';

    protected static $sign = 'trf';

    protected $entity = 'transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::TO_ID,
        self::TO_TYPE,
        self::AMOUNT,
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
        self::AMOUNT    => 'int',
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

        // @todo: check for a better way
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
