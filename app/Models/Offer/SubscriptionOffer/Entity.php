<?php

namespace RZP\Models\Offer\SubscriptionOffer;

use RZP\Models\Base;
use RZP\Constants\Table;

//
// Added for future use in retrieving the
// status, soft deletes, etc, for pivot table
//
class Entity extends Base\PublicEntity
{
    protected $entity      = 'subscription_offers_master';

    const OFFER_ID               = 'offer_id';
    const APPLICABLE_ON          = 'applicable_on';
    const REDEMPTION_TYPE        = 'redemption_type';
    const NO_OF_CYCLES           = 'no_of_cycles';
    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';

    protected $table = Table::SUBSCRIPTION_OFFERS_MASTER;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::OFFER_ID,
        self::APPLICABLE_ON,
        self::REDEMPTION_TYPE,
        self::NO_OF_CYCLES,
    ];

    protected $visible = [
        self::OFFER_ID,
        self::APPLICABLE_ON,
        self::REDEMPTION_TYPE,
        self::NO_OF_CYCLES,
    ];

    protected $proxy = [
        self::APPLICABLE_ON,
        self::REDEMPTION_TYPE,
        self::NO_OF_CYCLES,
    ];

    public function getOfferId()
    {
        return $this->getAttribute(self::OFFER_ID);
    }

    public function getApplicableOn()
    {
        return $this->getAttribute(self::APPLICABLE_ON);
    }

    public function getRedemptionType()
    {
        return $this->getAttribute(self::REDEMPTION_TYPE);
    }

    public function getNoOfCycles()
    {
        return $this->getAttribute(self::NO_OF_CYCLES);
    }
}
