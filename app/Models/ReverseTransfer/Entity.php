<?php

namespace RZP\Models\ReverseTransfer;

use RZP\Models\Base;
use RZP\Models\Transfer;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const TRANSFER_ID       = 'transfer_id';
    const MERCHANT_ID       = 'merchant_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const TRANSACTION_ID    = 'transaction_id';

    protected static $sign = 'revtrf';

    protected $entity = 'reverse_transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSFER_ID,
        self::TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::TRANSFER_ID,
        self::TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::AMOUNT    => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::TRANSFER_ID,
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
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function setPublicTransferIdAttribute(array & $attributes)
    {
        $transferId = $this->getAttribute(self::TRANSFER_ID);

        if ($transferId !== null)
        {
            $attributes[self::TRANSFER_ID] = Transfer\Entity::getSignedId($transferId);
        }
    }
}
