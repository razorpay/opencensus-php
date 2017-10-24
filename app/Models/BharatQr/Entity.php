<?php

namespace RZP\Models\BharatQr;

use Config;
use RZP\Base\Luhn;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const ENTITY_ID                 = 'entity_id';
    const ENTITY_TYPE               = 'entity_type';
    const IDENTIFIER_PADDING        = 'identifier_padding';
    const AMOUNT                    = 'amount';
    const METHOD                    = 'method';
    const QR_STRING                 = 'qr_string';

    protected static $sign = 'bhqr';

    protected $entity = 'bharat_qr';

    protected $fillable = [
        self::AMOUNT,
        self::METHOD,
        self::QR_STRING,
    ];

    protected $visible = [
        self::ID,
        self::AMOUNT,
        self::METHOD,
        self::QR_STRING,
        self::CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::AMOUNT,
        self::METHOD,
        self::QR_STRING,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $generateIdOnCreate = true;

    public function source()
    {
        return $this->morphTo('source', self::ENTITY_TYPE, self::ENTITY_ID);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getVisaIdentifier()
    {
        return $this->generateMerchantIdentifier('visa');
    }

    public function getMasterCardIdentifier()
    {
        return $this->generateMerchantIdentifier('mastercard');
    }

    public function getQrString()
    {
        return $this->getAttribute(self::QR_STRING);
    }

    public function setQrString(string $qr)
    {
        $this->setAttribute(self::QR_STRING, $qr);
    }

    public function getDynamicTagString()
    {
        $tagArray = [
            $this->getVisaTag(),
            $this->getMasterCardTag(),
            Constants::MERCHANT_CATEGORY_TAG,
            Constants::CURRENCY_CODE_TAG,
            $this->getAmountTag(),
            Constants::COUNTRY_CODE_TAG,
            Constants::MERCHANT_NAME_TAG,
            Constants::MERCHANT_CITY_TAG,
            $this->getAdditionalDetailsTag(),
        ];

        return implode('', $tagArray);
    }

    protected function getMasterCardTag()
    {
        $masterCardIdentifier = $this->getMasterCardIdentifier();

        return '04' . strlen($masterCardIdentifier) . $masterCardIdentifier;
    }

    protected function getVisaTag()
    {
        $visaIdentifier = $this->getVisaIdentifier();

        return '02' . strlen($visaIdentifier) . $visaIdentifier;
    }

    protected function getAdditionalDetailsTag()
    {
        $idTag = '0514' . $this->getId();

        $additionalDetailsString = $idTag;

        return '62' . strlen($additionalDetailsString) . $additionalDetailsString;
    }

    protected function getAmountTag()
    {
        $amount = (string)($this->getFormattedAmount());

        if (empty($amount) === true)
        {
            return '';
        }

        return '54' . str_pad(strlen($amount), 2, '0', STR_PAD_LEFT) . $amount;
    }

    /**
     * This will generate merchant identifier using network
     * network could be visa , mastercard or rupay
     *r
     * @param string $network
     * @return string
     */
    protected function generateMerchantIdentifier(string $network)
    {
        $acquirerCode = $this->getAcquirerCode($network);

        $identifierPadding = $this->getAttribute(self::IDENTIFIER_PADDING);

        $identifier  = $acquirerCode . '0' . str_pad(strlen($identifierPadding), 8, '0', STR_PAD_LEFT);

        return $identifier . Luhn::computeCheckDigit($identifier);
    }

    protected function getAcquirerCode(string $network)
    {
        return Config::get('gateway.bharat_qr.' . $network . '_' . 'code');
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
}
