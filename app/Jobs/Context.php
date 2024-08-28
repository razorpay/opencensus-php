<?php

namespace RZP\Jobs;

use Carbon\Carbon;
use Illuminate\Queue\Jobs\SyncJob;

use RZP\Constants\Timezone;
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

    protected $uniqueJobId;

    protected $jobUuid;

    protected $ledgerDualWriteFlow;

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

        $this->uniqueJobId = bin2hex(random_bytes(16)) . '_' . Carbon::now(Timezone::IST)->getTimestamp();

        $internalJob = $job->job;

        if ($internalJob instanceof SyncJob)
        {
            $this->jobUuid = optional($internalJob)->payload()['uuid'] ?? null;
        }
        else
        {
            $this->jobUuid = optional($internalJob)->uuid();
        }
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

    /**
     * @return string|null
     */
    public function fetchUniqueJobId()
    {
        return $this->uniqueJobId;
    }

    /**
     * @return string|null
     */
    public function fetchJobUuid()
    {
        return $this->jobUuid;
    }

    public function setLedgerDualWriteFlow($ledgerDualWriteFlow)
    {
        $this->ledgerDualWriteFlow = $ledgerDualWriteFlow;
    }

    public function getLedgerDualWriteFlow()
    {
        return $this->ledgerDualWriteFlow;
    }
}
