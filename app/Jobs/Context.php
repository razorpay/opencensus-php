<?php

namespace RZP\Jobs;

use RZP\Foundation\Application;

/**
 * Extracts and holds various variables from worker job which is currently executing
 */
class Context
{
    const PREFIX = 'worker:';

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $jobName;

    /**
     * @var string
     */
    protected $mode;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Initialize the context variables for the job which is provided
     *
     * @param Job $job
     */
    public function init(Job $job)
    {
        $this->mode = $job->getMode();

        $this->jobName = self::PREFIX . $job->getJobName();
    }

    /**
     * @return string|null
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return string|null
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * @return bool
     */
    public function isRazorpayXJob() : bool
    {
        $pos = strpos($this->jobName, 'payout');

        return ($pos !== false);
    }
}
