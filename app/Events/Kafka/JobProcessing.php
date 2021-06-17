<?php

namespace RZP\Events\Kafka;

use \RZP\Jobs\Job;

class JobProcessing
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
     * @param string $jobName
     * @param Job $job
     * @return void
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
    }
}
