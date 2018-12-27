<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Transfer\Traits\LinkedAccountNotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use HasBalance;
    use LinkedAccountNotesTrait;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const BALANCE_ID            = 'balance_id';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const NOTES                 = 'notes';
    const TRANSACTION_ID        = 'transaction_id';
    const TRANSFER              = 'transfer';
    const CHANNEL               = 'channel';

    // Input attribute const
    const LINKED_ACCOUNT_NOTES  = 'linked_account_notes';

    // Response attribute const
    const TRANSFER_ID           = 'transfer_id';

    protected static $sign = 'rvrsl';

    protected $entity = 'reversal';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::CHANNEL,
        self::LINKED_ACCOUNT_NOTES,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::CHANNEL,
        self::BALANCE_ID,
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
        self::LINKED_ACCOUNT_NOTES,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $amounts = [
        self::AMOUNT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::TRANSFER_ID,
        self::LINKED_ACCOUNT_NOTES
    ];

    protected $defaults = [
        self::NOTES => [],
    ];

    // -------------------- Relations ---------------------------

    public function transaction()
    {
        return $this->belongsTo(Transaction\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function entity()
    {
        return $this->morphTo();
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

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    // -------------------- End Getters --------------------------

    // -------------------- Setters ------------------------------

    public function setPublicTransferIdAttribute(array & $array)
    {
        if ($this->getAttribute(self::ENTITY_TYPE) === E::TRANSFER)
        {
            $array[self::TRANSFER_ID] = Transfer\Entity::getSignedId($this->getAttribute(self::ENTITY_ID));
        }
    }

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    // -------------------- End Setters --------------------------
}
