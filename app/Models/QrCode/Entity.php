<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\VirtualAccount\Provider;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    //
    // Reference will always be equal to id in case
    // qr code is generated before payment happens.
    // If qr code is generated after payment is done
    // we set the reference equal to the reference sent
    // by bank.
    //
    // There is no unique db constraint on reference.
    // This is so because if a virtual account associated
    // with a qr code is closed and we again get payment
    // notification on same reference we will generate qr code
    // again with same reference.
    //
    const REFERENCE                 = 'reference';
    const PROVIDER                  = 'provider';
    const ENTITY_ID                 = 'entity_id';
    const ENTITY_TYPE               = 'entity_type';
    const AMOUNT                    = 'amount';
    const QR_STRING                 = 'qr_string';
    const SHORT_URL                 = 'short_url';

    protected static $sign = 'qr';

    protected $entity = 'qr_code';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::PROVIDER,
        self::REFERENCE,
        self::QR_STRING,
    ];

    protected $visible = [
        self::ID,
        self::REFERENCE,
        self::AMOUNT,
        self::PROVIDER,
        self::SHORT_URL,
        self::QR_STRING,
        self::CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::REFERENCE,
        self::SHORT_URL,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected static $generators = [
        self::ID,
        self::REFERENCE,
    ];

    protected $ignoredRelations = [
        'source'
    ];

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

    public function payments()
    {
        return $this->morphMany(Payment\Entity::class, 'source');
    }

    // --------------------- END RELATIONS ---------------------

    public function generateReference($input)
    {
        if (isset($input[self::REFERENCE]) === false)
        {
            $this->setReference(strtoupper($this->getId()));
        }
    }

    // --------------------- GETTERS ---------------------

    /**
     * This function is used in case of polymorphic relations where we associate one entity
     * with multiple other entities using (entity_type and entity_id). It determines the string that
     * will be stored for entity_type when the association is with the QrCode entity.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->entity;
    }

    /**
     * Gets the most recent qrcode file
     *
     * @return FileStore\Entity
     */
    public function qrCodeFile(): FileStore\Entity
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, '=', FileStore\Type::QR_CODE_IMAGE)
                    ->firstOrFail();
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getReference()
    {
        return $this->getAttribute(self::REFERENCE);
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
        return 'qrcodes/'. $this->getId();
    }

    public function isGeneratedByMerchant()
    {
        return ($this->getReference()) !== strtoupper($this->getId());
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- SETTERS ---------------------

    public function setReference(string $reference)
    {
        $this->setAttribute(self::REFERENCE, $reference);
    }

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
