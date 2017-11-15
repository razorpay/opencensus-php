<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Models\VirtualAccount\Provider;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const PROVIDER                  = 'provider';
    const ENTITY_ID                 = 'entity_id';
    const ENTITY_TYPE               = 'entity_type';
    const AMOUNT                    = 'amount';
    const QR_STRING                 = 'qr_string';
    const SHORT_URL                 = 'short_url';

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
        self::SHORT_URL,
        self::QR_STRING,
        self::CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::SHORT_URL,
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

    public function files()
    {
        return $this->morphMany(FileStore\Entity::class, 'entity');
    }

    // --------------------- END RELATIONS ---------------------

    // --------------------- GETTERS ---------------------

    /**
     * Gets the most recent qrcode file
     *
     * @return FileStore\Entity
     */
    public function qrCodeFile(): FileStore\Entity
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, '=', FileStore\Type::QR_CODE_IMAGES)
                    ->latest()
                    ->first();
    }

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

    /**
     * Returns string to be used a qrcode file path in s3 store.
     * Format: qrcode/{qrcodeid}_{epoch}
     *
     * @return string
     */
    public function getQrCodeFilename(): string
    {
        return 'qrcode/'. $this->getId() . '_' . time();
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- SETTERS ---------------------

    public function setShortUrl(string $shortUrl)
    {
        $this->setAttribute(self::SHORT_URL, $shortUrl);
    }

    public function setQrString(string $qrString)
    {
        $this->setAttribute(self::QR_STRING, $qrString);
    }

    public function generateQrString()
    {
        $qrString = (new Provider)->generateQrString($this);

        $this->setQrString($qrString);

        return $this;
    }

    // --------------------- END SETTERS ---------------------
}
