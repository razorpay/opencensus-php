<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Plan;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input, Plan\Entity $plan, Token\Entity $token)
    {
        $subscription = (new Entity)->build($input);

        $this->associateEntitiesToSubscription($subscription, $plan, $token);

        $this->repo->saveOrFail($subscription);

        return $subscription;
    }

    public function charge(Entity $subscription)
    {
        $this->mutex->acquireAndRelease(
            $subscription->getId(),
            function() use($subscription) {
                $recurringPayload = $this->constructRecurringPayload($subscription);

                $queuePayload = [
                    'recurring_payload' => $recurringPayload,
                    'subscription_id'   => $subscription->getId(),
                    'key_id'            => $this->app['basicauth']->getPublicKey(),
                ];

                $this->app['queue']->push(Charge::class . '@fireCharge', $queuePayload);
            });
    }

    protected function constructRecurringPayload(Entity $subscription)
    {
        $subscriptionAmount = $subscription->getChargeableAmount();
        $customer = $subscription->customer;
        $tokenId = $subscription->token->getPublicId();

        $recurringPayload = [
            Payment\Entity::AMOUNT      => $subscriptionAmount,
            Payment\Entity::CURRENCY    => Payment\Entity::DEFAULT_CURRENCY,
            Payment\Entity::RECURRING   => "1",
            Payment\Entity::TOKEN       => $tokenId,
            Payment\Entity::CUSTOMER_ID => $customer->getPublicId(),
            Payment\Entity::EMAIL       => $customer->getEmail(),
            Payment\Entity::CONTACT     => $customer->getContact(),
            Payment\Entity::DESCRIPTION => 'Recurring Payment via Subscription'
        ];

        return $recurringPayload;
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