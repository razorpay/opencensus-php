<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const NAME                      = 'name';
    const MERCHANT_ID               = 'merchant_id';
    const PAYMENT_METHOD            = 'payment_method';
    const PAYMENT_METHOD_TYPE       = 'payment_method_type';
    const PAYMENT_NETWORK           = 'payment_network';
    const ISSUER                    = 'issuer';
    const ACTIVE                    = 'active';
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

    protected $entity             = 'offer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::PERCENT_RATE,
        self::MIN_AMOUNT,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::PAYMENT_COUNT,
        self::PROCESSING_TIME,
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
        self::CUSTOM_LONG_DISPLAY_TEXT
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
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
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::ACTIVE                    => 1
    ];

    protected $publicSetters = [
        Entity::CUSTOM_LONG_DISPLAY_TEXT,
        Entity::CUSTOM_SHORT_DISPLAY_TEXT
    ];

    protected $casts = [
        self::ACTIVE          => 'boolean',
        self::PERCENT_RATE    => 'integer',
        self::MIN_AMOUNT      => 'integer',
        self::MAX_CASHBACK    => 'integer',
        self::FLAT_CASHBACK   => 'integer',
        self::PAYMENT_COUNT   => 'integer',
        self::PROCESSING_TIME => 'integer',
        self::STARTS_AT       => 'integer',
        self::ENDS_AT         => 'integer'
    ];

    public function coupons()
    {
        return $this->hasMany('RZP\Models\Offer\Coupon\Entity', Coupon\Entity::OFFER_ID);
    }

    public function merchants()
    {
        return $this->belongsToMany('RZP\Models\Merchant\Entity', Table::MERCHANT_OFFER);
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
     * Processing time is converted from days to seconds and stored in db
     */
    public function setProcessingTimeAttribute(string $processingTime)
    {
        $this->attributes[self::PROCESSING_TIME] = strtotime($processingTime . ' day', 0);
    }

// -----------------------Mutators end==----------------------------------------

// -----------------------Accessors begin---------------------------------------

    protected function getPercentRateAttribute($percentRate)
    {
        return $percentRate / 100;
    }

    protected function getAdditionalDetailsAttribute($additionalDetails)
    {
        if ($additionalDetails === null)
        {
            $additionalDetails = '';
        }

        return $additionalDetails;
    }
}
