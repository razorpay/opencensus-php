<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Transfer;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                = 'id';
    const TRANSFER_ID       = 'transfer_id';
    const MERCHANT_ID       = 'merchant_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const NOTES             = 'notes';
    const TRANSACTION_ID    = 'transaction_id';

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
        self::TRANSFER_ID,
        self::TRANSACTION_ID,
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
        self::AMOUNT        => 'int',
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

    public function transfer()
    {
        return $this->belongsTo('RZP\Models\Transfer\Entity');
    }

    // -------------------- End Relations -----------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getTransferId()
    {
        return $this->getAttribute(self::TRANSFER_ID);
    }

    public function setPublicTransferIdAttribute(array & $attributes)
    {
        $transferId = $this->getAttribute(self::TRANSFER_ID);

        $attributes[self::TRANSFER_ID] = Transfer\Entity::getSignedId($transferId);
    }
}
