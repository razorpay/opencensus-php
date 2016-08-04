<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                        = 'id';
    const CAMPAIGN                  = 'campaign';
    const MERCHANT_ID               = 'merchant_id';
    const VALUE                     = 'value';
    const NOTES                     = 'notes';

    protected $entity               = 'credits';

    protected $table                = \RZP\Constants\Table::CREDITS;

    protected $fillable = array(
        self::ID,
        self::CAMPAIGN,
        self::VALUE,
        self::NOTES,
    );

    protected $visible = array(
        self::ID,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::VALUE,
        self::NOTES,
    );

    protected $public = array(
        self::CAMPAIGN,
        self::VALUE,
        self::NOTES,
    );

    protected $guarded = array(self::ID);

    protected $defaults = array(
        self::NOTES             => [],
        self::VALUE             => 0,
        self::CAMPAIGN          => null,
    );

    // Casts the attributes to native types
    protected $casts = [
        'value'                 => 'integer',
        'campaign'              => 'string',
    ];

// --------------------- Setters -------------------------------------------

    public function setCampaign($campaignName)
    {
        $this->setAttribute(self::CAMPAIGN, $campaignName);
    }

    public function setValue($value)
    {
        $this->setAttribute(self::VALUE, $value);
    }

// --------------------- End Setters -----------------------------------------

// --------------------- Getters -----------------------------------------

    public function getValue()
    {
        return $this->getAttribute(self::VALUE);
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
