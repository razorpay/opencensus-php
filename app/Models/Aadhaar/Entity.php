<?php

namespace RZP\Models\Aadhaar;

use RZP\Models\Base;
use RZP\Exception;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const NUMBER            = 'number';
    const MERCHANT_ID       = 'merchant_id';
    const BANK              = 'bank';
    const PAYMENT_ID        = 'payment_id';

    /**
     * Fingerprint data are never saved in the database
     * but are referenced at various points
     * and the values are held in-memory.
     */
    const FINGERPRINT       = 'fingerprint';

    protected static $sign = 'adhr';

    protected $entity = 'aadhaar2';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::NUMBER,
        self::BANK,
    ];

    protected $visible = [
        self::ID,
        self::NUMBER,
        self::BANK,
    ];

    protected $public = [
        self::ID,
        self::NUMBER,
        self::BANK,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }
}
