<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Plan;
use RZP\Trace\TraceCode;

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

        $queued = $failed = 0;
        $failures = [];

        foreach ($subscriptionsToCharge as $subscription)
        {
            try
            {
                $this->core->charge($subscription);
                $queued += 1;
            }
            catch (\Exception $ex)
            {
                $failed += 1;
                $failures[] = $subscription->getId();

                $this->trace->traceException($ex);

                $this->trace->error(
                    TraceCode::SUBSCRIPTION_CHARGE_QUEUE_FAILED,
                    [
                        'subscription_id' => $subscription->getId(),
                    ]);
            }
        }

        $summary = [
            'total' => $subscriptionsToCharge->count(),
            'queued' => $queued,
            'failed' => $failed,
            'failure_subscriptions' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CHARGE_QUEUE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function retryAuthSubscription()
    {
        $subscriptionsToRetry = $this->repo->subscription->getSubscriptionsToRetry();

        $queued = $failed = 0;
        $failures = [];

        foreach ($subscriptionsToRetry as $subscription)
        {
            try
            {
                $this->core->retry($subscription);
                $queued += 1;
            }
            catch (\Exception $ex)
            {
                $failed += 1;
                $failures[] = $subscription->getId();

                $this->trace->traceException($ex);

                $this->trace->error(
                    TraceCode::SUBSCRIPTION_RETRY_QUEUE_FAILED,
                    [
                        'subscription_id' => $subscription->getId(),
                    ]);
            }
        }

        $summary = [
            'total' => $subscriptionsToRetry->count(),
            'queued' => $queued,
            'failed' => $failed,
            'failure_subscriptions' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_RETRY_QUEUE_SUMMARY,
            $summary
        );

        return $summary;
    }
}