<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const CAMPAIGN                  = 'campaign';
    const MERCHANT_ID               = 'merchant_id';
    const VALUE                     = 'value';
    const TYPE                      = 'type';

    protected $entity               = 'credits';

    protected $table                = \RZP\Constants\Table::CREDITS;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::CAMPAIGN,
        self::VALUE,
        self::TYPE,
    );

    protected $visible = array(
        self::ID,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::VALUE,
        self::TYPE,
        self::CREATED_AT
    );

    protected $public = array(
        self::ID,
        self::CAMPAIGN,
        self::VALUE,
        self::TYPE,
        self::CREATED_AT
    );

    protected $defaults = array(
        self::VALUE             => 0,
        self::CAMPAIGN          => null,
        self::TYPE              => 'amount',
    );

    // Casts the attributes to native types
    protected $casts = [
        self::VALUE             => 'integer',
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
