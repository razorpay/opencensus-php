<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;

class Entity extends Base\PublicEntity
{
    const NAME                      = 'name';
    const MERCHANT_ID               = 'merchant_id';
    const PAYMENT_METHOD            = 'payment_method';
    const PAYMENT_METHOD_TYPE       = 'payment_method_type';
    const IINS                      = 'iins';
    const PAYMENT_NETWORK           = 'payment_network';
    const ISSUER                    = 'issuer';
    const ACTIVE                    = 'active';
    const TYPE                      = 'type';
    const PERCENT_RATE              = 'percent_rate';
    const MIN_AMOUNT                = 'min_amount';
    const MAX_CASHBACK              = 'max_cashback';
    const FLAT_CASHBACK             = 'flat_cashback';
    const PAYMENT_COUNT             = 'payment_count';
    const PROCESSING_TIME           = 'processing_time';
    const STARTS_AT                 = 'starts_at';
    const ENDS_AT                   = 'ends_at';
    const ADDITIONAL_DETAILS        = 'additional_details';
    const CUSTOM_SHORT_DISPLAY_TEXT = 'custom_short_display_text';
    const CUSTOM_LONG_DISPLAY_TEXT  = 'custom_long_display_text';

    // Offer types
    const INSTANT  = 'instant';
    const DEFERRED = 'deferred';

    //Attribute lengths
    const NAME_LENGRH                      = 25;
    const PAYMENT_METHOD_LENGTH            = 10;
    const PAYMENT_METHOD_TYPE_LENTH        = 6;
    const PAYMENT_NETWORK_LENGTH           = 20;
    const ISSUER_LENGTH                    = 10;
    const CUSTOM_SHORT_DISPLAY_TEXT_LENGTH = 50;
    const CUSTOM_LONG_DISPLAY_TEXT_LENGTH  = 200;



    protected $entity      = 'offer';

    protected static $sign = 'offer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::PERCENT_RATE,
        self::MIN_AMOUNT,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::PAYMENT_COUNT,
        self::PROCESSING_TIME,
        self::ACTIVE,
        self::TYPE,
        self::STARTS_AT,
        self::ENDS_AT,
        self::ADDITIONAL_DETAILS,
        self::CUSTOM_SHORT_DISPLAY_TEXT,
        self::CUSTOM_LONG_DISPLAY_TEXT
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::MIN_AMOUNT,
        self::PAYMENT_COUNT,
        self::PROCESSING_TIME,
        self::ACTIVE,
        self::TYPE,
        self::STARTS_AT,
        self::ENDS_AT,
        self::ADDITIONAL_DETAILS,
        self::CUSTOM_SHORT_DISPLAY_TEXT,
        self::CUSTOM_LONG_DISPLAY_TEXT
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::MIN_AMOUNT,
        self::PAYMENT_COUNT,
        self::PROCESSING_TIME,
        self::STARTS_AT,
        self::ENDS_AT,
        self::ADDITIONAL_DETAILS,
        self::CUSTOM_SHORT_DISPLAY_TEXT,
        self::CUSTOM_LONG_DISPLAY_TEXT,
        self::ACTIVE,
        self::TYPE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::ACTIVE                    => 1
    ];

    protected $publicSetters = [
        self::ID,
        self::CUSTOM_LONG_DISPLAY_TEXT,
        self::CUSTOM_SHORT_DISPLAY_TEXT
    ];

    protected $casts = [
        self::IINS            => 'array',
        self::ACTIVE          => 'boolean',
        self::PROCESSING_TIME => 'int',
        self::PERCENT_RATE    => 'int',
        self::MAX_CASHBACK    => 'int',
        self::FLAT_CASHBACK   => 'int',
        self::MIN_AMOUNT      => 'int',
        self::STARTS_AT       => 'int',
        self::ENDS_AT         => 'int',
        self::PAYMENT_COUNT   => 'int'
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function orders()
    {
        return $this->hasMany('RZP\Models\Order\Entity');
    }

    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    public function getPercentRate()
    {
        return $this->getAttribute(self::PERCENT_RATE);
    }

    public function getFlatCashback()
    {
        return $this->getAttribute(self::FLAT_CASHBACK);
    }

    public function getMaxCashback()
    {
        return $this->getAttribute(self::MAX_CASHBACK);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getPaymentNetwork()
    {
        return $this->getAttribute(self::PAYMENT_NETWORK);
    }

    public function getPaymentMethodType()
    {
        return $this->getAttribute(self::PAYMENT_METHOD_TYPE);
    }

    public function getPaymentMethod()
    {
        return $this->getAttribute(self::PAYMENT_METHOD);
    }

    public function getPaymentCount()
    {
        return $this->getAttribute(self::PAYMENT_COUNT);
    }

    public function getMinAmount()
    {
        return $this->getAttribute(self::MIN_AMOUNT);
    }

    public function getProcessingTime()
    {
        return $this->getAttribute(self::PROCESSING_TIME);
    }

    public function getAdditionalDetails()
    {
        return $this->getAttribute(self::ADDITIONAL_DETAILS);
    }

    public function getIins()
    {
        return $this->getAttribute(self::IINS);
    }

    public function getStartsAt()
    {
        return $this->getAttribute(self::STARTS_AT);
    }

    public function getEndsAt()
    {
        return $this->getAttribute(self::ENDS_AT);
    }

    public function deactivate()
    {
        $this->active = false;
    }

// -----------------------Mutators begin----------------------------------------
    /**
     * Sets the custom_long_display_text value if present else generates a long
     * description from the offer attributes
     */
    public function setPublicCustomLongDisPlayTextAttribute(array & $array)
    {
        $customLongDisplayText = $this->getAttribute(self::CUSTOM_LONG_DISPLAY_TEXT);

        if ($customLongDisplayText === null)
        {
            $generator = new Generator($this);

            $customLongDisplayText = $generator->generateLongDescription();
        }

        $array[self::CUSTOM_LONG_DISPLAY_TEXT] = $customLongDisplayText;
    }

    /**
     * Sets the custom_long_display_text value if present else generates a short
     * description from the offer attributes
     */
    public function setPublicCustomShortDisplayTextAttribute(array & $array)
    {
        $customShortDisplayText = $this->getAttribute(self::CUSTOM_SHORT_DISPLAY_TEXT);

        if ($customShortDisplayText === null)
        {
            $generator = new Generator($this);

            $customShortDisplayText = $generator->generateShortDescription();
        }

        $array[self::CUSTOM_SHORT_DISPLAY_TEXT] = $customShortDisplayText;
    }

    /**
     * Since wallet validation is not case sensitiove, we convert to loewercase
     * and set in entity
     *
     * @param string $paymentNetwork Input payment networl
     */
    public function setPaymentNetworkAttribute(string $paymentNetwork)
    {
        if ($this->getPaymentMethod() === Payment\Method::WALLET)
        {
            $this->attributes[self::PAYMENT_NETWORK] = strtolower($paymentNetwork);
        }
    }

    public function addIins(array $newIins)
    {
        $this->getValidator()->validateIins($newIins);

        $existingIins = $this->getAttribute(self::IINS);

        $this->iins = array_unique(array_merge($existingIins, $newIins));
    }

// -----------------------Mutators end------------------------------------------

// -----------------------Accessors begin---------------------------------------

    protected function getAdditionalDetailsAttribute($additionalDetails)
    {
        if ($additionalDetails === null)
        {
            $additionalDetails = '';
        }

        return $additionalDetails;
    }
}
