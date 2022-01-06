<?php

namespace RZP\Models\Merchant\Credits;

use Carbon\Carbon;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const CAMPAIGN                  = 'campaign';
    const MERCHANT_ID               = 'merchant_id';
    const PROMOTION_ID              = 'promotion_id';
    const VALUE                     = 'value';
    const TYPE                      = 'type';
    const EXPIRED_AT                = 'expired_at';
    const USED                      = 'used';
    const BATCH_ID                  = 'batch_id';
    const BALANCE_ID                = 'balance_id';
    const IDEMPOTENCY_KEY           = 'idempotency_key';
    const CREDIT_SOURCE             = 'credit_source';
    const CREDIT_VALUE_TYPE         = 'credit_value_type';
    const CREATOR_NAME              = 'creator_name';
    const REMARKS                   = 'remarks';
    const PRODUCT                   = 'product';
    const INPUT                     = 'input';

    const BANKING                   = 'banking';
    const CREDITS                   = 'credits';
    const DEAFULT                   = 'default';
    const FETCH_EXPIRED             = 'fetch_expired';
    const IS_PROMOTION              = 'is_promotion';

    protected $entity               = 'credits';

    protected static $sign      = 'credits';

    protected $generateIdOnCreate = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::ID,
        self::EXPIRED_AT,
        self::CAMPAIGN,
        self::VALUE,
        self::TYPE,
        self::REMARKS,
        self::CREATOR_NAME,
        self::PRODUCT,
        self::BATCH_ID,
        self::IDEMPOTENCY_KEY,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::PROMOTION_ID,
        self::VALUE,
        self::TYPE,
        self::USED,
        self::PRODUCT,
        self::CREATOR_NAME,
        self::IDEMPOTENCY_KEY,
        self::BATCH_ID,
        self::EXPIRED_AT,
        self::CREATED_AT,
        self::MERCHANT_ID,
        self::REMARKS,
        self::BALANCE_ID,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CAMPAIGN,
        self::VALUE,
        self::TYPE,
        self::USED,
        self::PRODUCT,
        self::CREATOR_NAME,
        self::IDEMPOTENCY_KEY,
        self::BATCH_ID,
        self::EXPIRED_AT,
        self::CREATED_AT,
        self::MERCHANT_ID,
        self::REMARKS,
        self::BALANCE_ID,
    ];

    protected $publicSetters = [
        self::ID,
        self::VALUE,
    ];

    protected $defaults = [
        self::VALUE             => 0,
        self::CAMPAIGN          => null,
        self::TYPE              => Type::AMOUNT,
        self::USED              => 0,
        self::EXPIRED_AT        => null,
    ];

    // Casts the attributes to native types
    protected $casts = [
        self::VALUE             => 'integer',
        self::USED              => 'integer',
    ];

    protected $dates = [
        self::EXPIRED_AT,
    ];

    protected static $modifiers = [
        self::EXPIRED_AT,
    ];
// --------------------- Setters ----------------------------------------

    public function setCampaign(string $campaignName)
    {
        $this->setAttribute(self::CAMPAIGN, $campaignName);
    }

    public function setValue(int $value)
    {
        // removing assert of upper limit of value
        // because of the Covid-19 situation which increased refunds.
        // and merchants are issuing huge amounts of refunds
        assertTrue (($value >= $this->getUsed()));

        $this->setAttribute(self::VALUE, $value);
    }

    public function setType(string $type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setUsed(int $used)
    {
        $this->setAttribute(self::USED, $used);
    }

    public function setPublicValueAttribute(array &$attributes)
    {
        $attributes[self::VALUE] = (new Core)->getCreditInAmount($this->getValue(), $this->getProduct());
    }

    public function setExpiredAt(int $timestamp)
    {
        $this->setAttribute(self::EXPIRED_AT, $timestamp);
    }

// --------------------- End Setters -------------------------------------

// --------------------- Getters -----------------------------------------

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getValue()
    {
        return $this->getAttribute(self::VALUE);
    }

    public function getCampaign()
    {
        return $this->getAttribute(self::CAMPAIGN);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getMerchantCredits()
    {
        if ($this->getMerchantId() === null)
        {
            return null;
        }

        $balance = $this->merchant->primaryBalance;

        switch ($this->getType())
        {
            case Type::AMOUNT:
                return $balance->getAmountCredits();

            case Type::FEE:
                return $balance->getFeeCredits();

           case Type::REFUND:
                return $balance->getRefundCredits();

            default:
                return $balance->getAmountCredits();
        }

    }

    public function getUsed()
    {
        return $this->getAttribute(self::USED);
    }

    public function getUnusedCredits()
    {
        return $this->getValue() - $this->getUsed();
    }

    public function getProduct()
    {
       return $this->getAttribute(self::PRODUCT);
    }

    public function getExpiredAt()
    {
        return $this->getAttribute(self::EXPIRED_AT);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }
// --------------------- End Getters -----------------------------------------

// --------------------- Modifiers -------------------------------------------

    public function addCredits($credits)
    {
        $credits = $this->getValue() + $credits;

        $this->setValue($credits);
    }

    public function deductCredits($credits)
    {
        $credits = $this->getValue() - $credits;

        $this->setValue($credits);
    }

    public function updateUsed(int $usedCount)
    {
        $creditsUsed = $this->getUsed() + $usedCount;

        $this->setUsed($creditsUsed);
    }

    protected function modifyExpiredAt(& $input)
    {
        // default type is amount that's why we are setting expiry as today + 92 days
        if ((isset($input[self::TYPE]) === false or $input[self::TYPE] === Type::AMOUNT) &&
            (empty($input[self::EXPIRED_AT]) === true))
        {
            $input[self::EXPIRED_AT] = Carbon::now()->addDays(92)->getTimestamp();
        }
    }

// --------------------- End Modifiers ---------------------------------------

// --------------------- Foreign Key Relations -------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function balance()
    {
        return $this->belongsTo(Balance\Entity::class);
    }

    public function promotion()
    {
        return $this->belongsTo('RZP\Models\Promotion\Entity');
    }
// --------------------- End Foreign Key Relations ---------------------------

    public function isValid()
    {
        $expiredAt = $this->getExpiredAt();

        if ($expiredAt > time() or $expiredAt === null)
        {
            return true;
        }

        return false;
    }

}
