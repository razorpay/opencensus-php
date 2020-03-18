<?php

namespace RZP\Jobs;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\VirtualAccount;
use RZP\Exception\LogicException;

/**
 * Class VirtualAccountMigrate
 *
 * @package RZP\Jobs
 */
class VirtualAccountMigrate extends Job
{
    const MAX_JOB_ATTEMPTS   = 1;
    const JOB_RELEASE_WAIT   = 30;
    const DEFAULT_BATCH_SIZE = 1000;

    public $timeout = 4000;

    protected $queueConfigKey = 'capture';

    /**
     * @var string
     */
    private $afterId;

    /**
     * @var int
     */
    private $fromTime;

    /**
     * @var int
     */
    private $toTime;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var array
     */
    private $merchantIds;

    public function __construct(
        string $mode,
        string $afterId,
        int $fromTime,
        int $toTime,
        int $batchSize,
        array $merchantIds = [])
    {
        parent::__construct($mode);

        $this->afterId = $afterId;

        $this->fromTime = $fromTime;
        $this->toTime   = $toTime;

        $this->batchSize   = $batchSize;
        $this->merchantIds = $merchantIds;
    }

    public function handle()
    {
        parent::handle();

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'after_id'     => $this->afterId,
            'from_time'    => $this->fromTime,
            'to_time'      => $this->toTime,
            'batch_size'   => $this->batchSize,
            'merchant_ids' => $this->merchantIds,
        ];

        $this->trace->debug(TraceCode::VA_MIGRATE_JOB_TRIGGERED, $tracePayload);

        try
        {
            $this->migrate();

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::VA_MIGRATE_JOB_FAILED, $tracePayload);

            // If it's logical error or maximum number of retries has happened
            // just delete the job, else retry the job after a wait.

             $this->delete();
        }
    }

    private function migrate()
    {
        (new VirtualAccount\Core)->migrateYesbankVirtualAccounts(
            $this->afterId,
            $this->fromTime,
            $this->toTime,
            $this->batchSize,
            $this->merchantIds);
    }
}
