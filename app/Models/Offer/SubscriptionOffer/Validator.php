<?php

namespace RZP\Models\Offer\SubscriptionOffer;

use RZP\Base;

class Validator extends Base\Validator
{

    protected static $createRules = [
        Entity::OFFER_ID               => 'filled|string|min:14|max:20',
        Entity::APPLICABLE_ON          => 'required|string|in:plan,addon,both',
        Entity::REDEMPTION_TYPE        => 'required|string|in:single,cycle,forever',
        Entity::NO_OF_CYCLES           => 'required_only_if:redemption_type,cycle|integer|min:1',
    ];

}
