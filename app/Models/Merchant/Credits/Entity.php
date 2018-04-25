<?php

namespace RZP\Models\Merchant\Credits;

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

    protected $entity               = 'credits';

    protected $generateIdOnCreate = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = array(
        self::ID,
        self::EXPIRED_AT,
        self::CAMPAIGN,
        self::VALUE,
        self::TYPE,
    );

    protected $visible = array(
        self::ID,
        self::ENTITY,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::PROMOTION_ID,
        self::VALUE,
        self::TYPE,
        self::USED,
        self::EXPIRED_AT,
        self::CREATED_AT
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::CAMPAIGN,
        self::VALUE,
        self::TYPE,
        self::USED,
        self::EXPIRED_AT,
        self::CREATED_AT
    );

    protected $defaults = array(
        self::VALUE             => 0,
        self::CAMPAIGN          => null,
        self::TYPE              => Type::AMOUNT,
        self::USED              => 0,
        self::EXPIRED_AT        => null,
    );

    // Casts the attributes to native types
    protected $casts = [
        self::VALUE             => 'integer',
        self::USED              => 'integer',
    ];

    protected $dates = [
        self::EXPIRED_AT,
    ];

    protected static $sign      = 'credits';

// --------------------- Setters ----------------------------------------

    public function setCampaign(string $campaignName)
    {
        $this->setAttribute(self::CAMPAIGN, $campaignName);
    }

    public function setValue(int $value)
    {
        assert (($value >= $this->getUsed()) and ($value <= 100000000));

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

// --------------------- End Setters -------------------------------------

// --------------------- Getters -----------------------------------------

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

        $balance = $this->merchant->balance;

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

// --------------------- End Modifiers ---------------------------------------

// --------------------- Foreign Key Relations -------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

     public function promotion()
    {
        return $this->belongsTo('RZP\Models\Promotion\Entity');
    }

// --------------------- End Foreign Key Relations ---------------------------
}
