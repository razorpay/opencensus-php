<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const CAMPAIGN                  = 'campaign';
    const MERCHANT_ID               = 'merchant_id';
    const VALUE                     = 'value';
    const FEE_CREDITS               = 'fee_credits';
    const TYPE                      = 'type';
    const IS_ADMIN                  = 'is_admin';

    protected $entity               = 'credits';

    protected $table                = \RZP\Constants\Table::CREDITS;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::CAMPAIGN,
        self::VALUE,
        self::FEE_CREDITS,
        self::TYPE,
        self::IS_ADMIN,
    );

    protected $visible = array(
        self::ID,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::VALUE,
        self::FEE_CREDITS,
        self::TYPE,
        self::IS_ADMIN,
        self::CREATED_AT
    );

    protected $public = array(
        self::ID,
        self::CAMPAIGN,
        self::VALUE,
        self::FEE_CREDITS,
        self::CREATED_AT
    );

    protected $defaults = array(
        self::VALUE             => 0,
        self::CAMPAIGN          => null,
        self::FEE_CREDITS       => 0,
        self::TYPE              => 'amount',
    );

    // Casts the attributes to native types
    protected $casts = [
        self::VALUE             => 'integer',
        self::FEE_CREDITS       => 'integer',
        self::IS_ADMIN          => 'boolean',
    ];

    protected static $sign      = 'credits';

// --------------------- Setters ----------------------------------------

    public function setCampaign($campaignName)
    {
        $this->setAttribute(self::CAMPAIGN, $campaignName);
    }

    public function setValue($value)
    {
        assert (($value >= 0) and ($value <= 1000000));

        $this->setAttribute(self::VALUE, $value);
    }

    public function setFeeCredits($value)
    {
        assert (($value >= 0) and ($value <= 20000));

        $this->setAttribute(self::FEE_CREDITS, $value);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setIsAdmin($isAdmin)
    {
        $this->setAttribute(self::IS_ADMIN, $isAdmin);
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

    public function getFeeCredits()
    {
        return $this->getAttribute(self::FEE_CREDITS);
    }

    public function getIsAdmin()
    {
        return $this->getAttribute(self::IS_ADMIN);
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

// --------------------- End Modifiers ---------------------------------------

// --------------------- Foreign Key Relations -------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

// --------------------- End Foreign Key Relations ---------------------------
}
