<?php

namespace RZP\Jobs\Plan\Subscription;

use App;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Plan\Subscription;

/**
 * This queue job represents periodic charge of a subscription.
 *
 * A cron job runs which finds all subscription to be charged now
 * and pushes this job onto a queue.
 *
 */
class Charge extends Job implements ShouldQueue
{
    use InteractsWithQueue;

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
        $this->payload = $payload;
    }

    /**
     * Queue job handler
     */
    public function handle()
    {
        try
        {
            (new Subscription\Charge)->fireCharge($this->payload);
        }
        finally
        {
            $job->delete();
        }
    }
}
