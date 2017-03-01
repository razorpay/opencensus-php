<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Constants\Table;

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
    const BLOCK                     = 'block';
    const PERCENT_RATE              = 'percent_rate';
    const MIN_AMOUNT                = 'min_amount';
    const MAX_CASHBACK              = 'max_cashback';
    const FLAT_CASHBACK             = 'flat_cashback';
    const PAYMENT_COUNT             = 'payment_count';
    const PROCESSING_TIME           = 'processing_time';
    const STARTS_AT                 = 'starts_at';
    const ENDS_AT                   = 'ends_at';
    const DISPLAY_TEXT              = 'display_text';
    const TERMS                     = 'terms';

    // Offer types
    const INSTANT  = 'instant';
    const DEFERRED = 'deferred';

    //Attribute lengths
    const NAME_LENGTH                      = 50;
    const PAYMENT_METHOD_LENGTH            = 10;
    const PAYMENT_METHOD_TYPE_LENTH        = 10;
    const PAYMENT_NETWORK_LENGTH           = 20;
    const ISSUER_LENGTH                    = 10;
    const DISPLAY_TEXT_LENGTH              = 255;

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
        self::TYPE,
        self::PERCENT_RATE,
        self::MIN_AMOUNT,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::PAYMENT_COUNT,
        self::PROCESSING_TIME,
        self::ACTIVE,
        self::BLOCK,
        self::STARTS_AT,
        self::ENDS_AT,
        self::DISPLAY_TEXT,
        self::TERMS,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::TYPE,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::MIN_AMOUNT,
        self::PAYMENT_COUNT,
        self::PROCESSING_TIME,
        self::ACTIVE,
        self::BLOCK,
        self::STARTS_AT,
        self::ENDS_AT,
        self::DISPLAY_TEXT,
        self::TERMS,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::TYPE,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::MIN_AMOUNT,
        self::PAYMENT_COUNT,
        self::PROCESSING_TIME,
        self::STARTS_AT,
        self::ENDS_AT,
        self::DISPLAY_TEXT,
        self::ACTIVE,
        self::BLOCK,
        self::TERMS,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::ACTIVE => 1,
        self::BLOCK  => 1,
        self::TYPE   => self::DEFERRED
    ];

    protected $casts = [
        self::IINS            => 'array',
        self::ACTIVE          => 'boolean',
        self::BLOCK           => 'boolean',
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

    public function shouldBlock()
    {
        return $this->getAttribute(self::BLOCK);
    }

    public function getDisplayText()
    {
        return $this->getAttribute(self::DISPLAY_TEXT);
    }

    public function getTerms()
    {
        return $this->getAttribute(self::TERMS);
    }

// ----------------------- Setters ---------------------------------------------

    public function deactivate()
    {
        $this->setAttribute(self::ACTIVE, false);
    }

// ----------------------- Mutators --------------------------------------------
    /**
     * Since wallet validation is not case sensitive, we convert to lowercase
     * and set in entity
     *
     * @param string $paymentNetwork Input payment networl
     */
    public function setPaymentNetworkAttribute(string $paymentNetwork)
    {
        if ($this->getPaymentMethod() === Payment\Method::WALLET)
        {
            $paymentNetwork = strtolower($paymentNetwork);
        }

        $this->attributes[self::PAYMENT_NETWORK] = $paymentNetwork;
    }

    protected function setIinsAttribute(array $iins)
    {
        $existingIins = $this->getAttribute(self::IINS);

        if ($existingIins !== null)
        {
            $iins = array_unique(array_merge($existingIins, $iins));
        }

        $this->attributes[self::IINS] = json_encode(array_values($iins));
    }
}
