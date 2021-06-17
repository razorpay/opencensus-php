<?php

namespace RZP\Events\Kafka;

use \RZP\Jobs\Job;

class JobProcessed
{
    /**
     * The message instance.
     *
     * @var Job
     */
    public $job;

    /**
     * Create a new event instance.
     *
     * @param Job $job
     * @return void
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
    }
}
