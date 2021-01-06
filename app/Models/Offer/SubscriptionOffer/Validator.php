<?php

namespace RZP\Models\Offer\SubscriptionOffer;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{

    protected static $createRules = [
        Entity::OFFER_ID               => 'filled|string|min:14|max:20',
        Entity::APPLICABLE_ON          => 'required|string|in:plan,addon,both',
        Entity::REDEMPTION_TYPE        => 'required|string|in:single,cycle,forever',
        Entity::NO_OF_CYCLES           => 'required_if:redemption_type,cycle|integer|min:1',
    ];

    public function validateNoOfCycles()
    {
        $subOffer = $this->entity;

        if ($subOffer->getRedemptionType() !== 'cycle' and
            $subOffer->getNoOfCycles() !== null)
        {
            throw new BadRequestValidationFailureException(
                Entity::NO_OF_CYCLES . ' is required only when ' .
                Entity::REDEMPTION_TYPE . ' is cycle');
        }
    }

}
