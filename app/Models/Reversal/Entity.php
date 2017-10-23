<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Constants\Entity as EntityConstant;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const NOTES             = 'notes';
    const TRANSACTION_ID    = 'transaction_id';

    // response attribute const
    const TRANSFER_ID       = 'transfer_id';

    protected static $sign = 'rvrsl';

    protected $entity = 'reversal';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::TRANSFER_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::AMOUNT    => 'int',
    ];

    protected $amounts = [
        self::AMOUNT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::TRANSFER_ID,
    ];

    protected $defaults = [
        self::NOTES     => [],
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
        return $this->morphTo('source', 'entity_type', 'entity_id');
    }

    // -------------------- End Relations -----------------------

    // -------------------- Getters -----------------------------
    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    // -------------------- End Getters --------------------------

    // -------------------- Setters ------------------------------

    public function setPublicTransferIdAttribute(array & $array)
    {
       if ($this->getAttribute(self::ENTITY_TYPE) === EntityConstant::TRANSFER)
        {
            $array[self::TRANSFER_ID] = Transfer\Entity::getSignedId(
                                                $this->getAttribute(self::ENTITY_ID));
        }
    }

    // -------------------- End Setters --------------------------
}
