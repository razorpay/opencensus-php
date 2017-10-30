<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const PROVIDER                  = 'provider';
    const ENTITY_ID                 = 'entity_id';
    const ENTITY_TYPE               = 'entity_type';
    const AMOUNT                    = 'amount';
    const QR_STRING                 = 'qr_string';

    protected static $sign = 'qr';

    protected $entity = 'qr_code';

    protected $fillable = [
        self::AMOUNT,
        self::PROVIDER,
        self::QR_STRING,
    ];

    protected $visible = [
        self::ID,
        self::AMOUNT,
        self::PROVIDER,
        self::QR_STRING,
        self::CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::AMOUNT,
        self::PROVIDER,
        self::QR_STRING,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $generateIdOnCreate = true;

    // --------------------- RELATIONS ---------------------

    public function source()
    {
        return $this->morphTo('source', self::ENTITY_TYPE, self::ENTITY_ID);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // --------------------- END RELATIONS ---------------------

    // --------------------- GETTERS ---------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getQrString()
    {
        return $this->getAttribute(self::QR_STRING);
    }

    public function getProvider()
    {
        return $this->getAttribute(self::PROVIDER);
    }

    public function getFormattedAmount()
    {
        $amount = $this->getAmount();

        if (empty($amount) === true)
        {
            return null;
        }

        return number_format($amount / 100, 2, '.', '');
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- SETTERS ---------------------

    public function setQrString(string $qrString)
    {
        $this->setAttribute(self::QR_STRING, $qrString);
    }

    // --------------------- END SETTERS ---------------------
}
