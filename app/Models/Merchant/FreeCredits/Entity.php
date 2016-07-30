<?php

namespace RZP\Models\Merchant\FreeCredits;

use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                        = 'id';
    const CAMPAIGN                  = 'campaign';
    const MERCHANT_ID               = 'merchant_id';
    const CREDITS                   = 'credits';
    const NOTES                     = 'notes';

    protected $entity               = 'free_credits';

    protected $table                = \RZP\Constants\Table::FREE_CREDITS;

    protected $fillable = array(
        self::ID,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::CREDITS,
        self::NOTES,
    );

    protected $visible = array(
        self::ID,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::CREDITS,
        self::NOTES,
    );

    protected $public = array(
        self::ID,
        self::CAMPAIGN,
        self::MERCHANT_ID,
        self::CREDITS,
        self::NOTES,
    );

    protected $guarded = array(self::ID);

    protected $defaults = array(
        self::NOTES             => [],
        self::CREDITS           => 0,
        self::CAMPAIGN          => null,
    );

// --------------------- Mutators -------------------------------------------

// --------------------- End Mutators ----------------------------------------

// --------------------- Setters ---------------------------------------------

    public function setCampaign(string $campaignName)
    {
        $this->setAttribute(self::CAMPAIGN, $campaignName);
    }

    public function setCredits(int $credits)
    {
        $this->setAttribute(self::CREDITS, $credits);
    }

// --------------------- End Setters -----------------------------------------

// --------------------- Foreign Key Relations -------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

// --------------------- End Foreign Key Relations ---------------------------
}
