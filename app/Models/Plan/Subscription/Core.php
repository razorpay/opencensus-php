<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Plan;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;

class Core extends Base\Core
{
    public function create(array $input, Plan\Entity $plan, Token\Entity $token)
    {
        $subscription = (new Entity)->build($input);

        $this->associateEntitiesToSubscription($subscription, $plan, $token);

        $this->repo->saveOrFail($subscription);

        return $subscription;
    }

    public function charge(Entity $subscription)
    {
        // TODO: Add charging
    }

    protected function associateEntitiesToSubscription(Entity $subscription, Plan\Entity $plan, Token\Entity $token)
    {
        $customer = $token->customer;
        $merchant = $customer->merchant;

        $subscription->merchant()->associate($merchant);
        $subscription->plan()->associate($plan);
        $subscription->customer()->associate($customer);
        $subscription->token()->associate($token);
    }
}