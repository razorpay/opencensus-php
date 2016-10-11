<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Plan;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input, $planId)
    {
        $customerId = $input[Entity::CUSTOMER_ID];
        $tokenId = $input[Entity::TOKEN_ID];

        Customer\Entity::verifyIdAndStripSign($customerId);
        Token\Entity::verifyIdAndStripSign($tokenId);
        Plan\Entity::verifyIdAndStripSign($planId);

        $customer = $this->repo->customer->findByIdAndMerchant($customerId, $this->merchant);
        $plan = $this->repo->plan->findByIdAndMerchant($planId, $this->merchant);
        $token = $this->repo->token->findByIdAndCustomer($tokenId, $customer);

        $subscription = $this->core->create($input, $plan, $token);

        return $subscription->toArrayPublic();
    }

    public function chargeSubscriptions()
    {
        $subscriptionsToCharge = $this->repo->subscription->getSubscriptionsToCharge();

        $payments = [];

        foreach ($subscriptionsToCharge as $subscription)
        {
            $payments[] = $this->core->charge($subscription);
        }

        return $payments;
    }
}