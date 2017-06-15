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
    const EXPIRING_AT               = 'expiring_at';
    const USED                      = 'used';

    protected $entity               = 'credits';

    protected $generateIdOnCreate = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = array(
        self::ID,
        self::PROMOTION_ID,
        self::EXPIRING_AT,
        self::CAMPAIGN,
        self::VALUE,
        self::TYPE,
    );

    protected $visible = array(
        self::ID,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::PROMOTION_ID,
        self::VALUE,
        self::TYPE,
        self::USED,
        self::EXPIRING_AT,
        self::CREATED_AT
    );

    protected $public = array(
        self::ID,
        self::CAMPAIGN,
        self::VALUE,
        self::TYPE,
        self::USED,
        self::EXPIRING_AT,
        self::CREATED_AT
    );

    protected $defaults = array(
        self::VALUE             => 0,
        self::CAMPAIGN          => null,
        self::TYPE              => 'amount',
        self::USED              => 0,
        self::EXPIRING_AT       => null,
    );

    // Casts the attributes to native types
    protected $casts = [
        self::VALUE             => 'integer',
        self::USED              => 'integer',
    ];

    protected static $sign      = 'credits';

// --------------------- Setters ----------------------------------------

    public function setCampaign(string $campaignName)
    {
        $this->setAttribute(self::CAMPAIGN, $campaignName);
    }

    public function setValue(int $value)
    {
        assert (($value >= 0) and ($value <= 1000000));

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

            default:
                return $balance->getAmountCredits();
        }

    }


    public function getUsed()
    {
        return $this->getAttribute(self::USED);
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

// --------------------- End Foreign Key Relations ---------------------------
}
