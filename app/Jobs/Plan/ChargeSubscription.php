<?php

namespace RZP\Jobs\Plan;

use RZP\Jobs\Job;
use RZP\Models\Plan\Subscription;

/**
 * This queue job represents periodic charge of a subscription.
 *
 * A cron job runs which finds all subscription to be charged now
 * and pushes this job onto a queue.
 *
 */
class ChargeSubscription extends Job
{
    /**
     * Queue payload data
     *
     * @var array
     */
    protected $payload;

    /**
     *
     * @param array $payload
     *
     */
    public function __construct(array $payload)
    {
        parent::__construct();

        $this->payload = $payload;
    }

    /**
     * Queue job handler
     */
    public function handle()
    {
        parent::handle();

        try
        {
            (new Subscription\Charge)->fireCharge($this->payload);
        }
        finally
        {
            $this->delete();
        }
    }
}
