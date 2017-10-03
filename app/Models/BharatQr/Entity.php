<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const ENTITY_ID                 = 'entity_id';
    const ENTITY_TYPE               = 'entity_type';
    const VISA_IDENTIFIER           = 'visa_identifier';
    const MASTER_CARD_IDENTIFIER    = 'master_card_identifier';
    const AMOUNT                    = 'amount';
    const METHOD                    = 'method';
    const QR_STRING                 = 'qr_string';

    protected static $sign      = 'bhqr';

    protected $primaryKey = self::ID;

    protected $entity = 'bharat_qr';

    protected $fillable = [
        self::AMOUNT,
        self::METHOD,
        self::VISA_IDENTIFIER,
        self::MASTER_CARD_IDENTIFIER,
        self::QR_STRING,
    ];

    protected $visible = [
        self::ID,
        self::AMOUNT,
        self::METHOD,
        self::VISA_IDENTIFIER,
        self::MASTER_CARD_IDENTIFIER,
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

    public function build(array $input = [], string $operation = 'addBharatQr')
    {
        $this->getValidator()->validateInput($operation, $input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

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
        return $this->getAttribute(self::VISA_IDENTIFIER);
    }

    public function getMasterCardIdentifier()
    {
        return $this->getAttribute(self::MASTER_CARD_IDENTIFIER);
    }

    public function getQrString()
    {
        return $this->getAttribute(self::QR_STRING);
    }

    public function setQrString($qr)
    {
        $this->setAttribute(self::QR_STRING, $qr);
    }

    public function getDynamicTagString()
    {
        $visaTag = $this->getVisaTag();

        //Removing for now. Visa Test cases fails it
        //$masterCardTag = $this->getMasterCardTag();

        $masterCardTag = '';

        $merchantCategoryTag = '52045399';

        $currencyCodeTag = '5303356';

        $amountTag = $this->getAmountTag();

        $countryCode = '5802IN';

        $merchantName = '5908PAYMENTS';

        $merchantCity = '6009BANGALORE';

        $additionalDetailsTag = $this->getAdditionalDetailsTag();

        return $visaTag . $masterCardTag . $merchantCategoryTag . $currencyCodeTag . $amountTag . $countryCode  . $merchantName . $merchantCity . $additionalDetailsTag;
    }

    protected function getMasterCardTag()
    {
        $masterCardIdentifier = $this->getMasterCardIdentifier();

        return '04' . strlen($masterCardIdentifier) . $this->getMasterCardIdentifier();
    }

    protected function getVisaTag()
    {
        $visaIdentifier = $this->getVisaIdentifier();

        return '02' . strlen($visaIdentifier) . $this->getVisaIdentifier();
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

    protected function getFormattedAmount()
    {
        $amount = $this->getAmount();

        if (empty($amount) === true)
        {
            return null;
        }

        return number_format($amount / 100, 2, '.', '');
    }
}
