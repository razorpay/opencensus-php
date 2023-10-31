<?php

namespace RZP\Jobs\DCS;

use RZP\Jobs\Job;
use RZP\Models\Feature\Entity;
use RZP\Trace\TraceCode;

class AssignMerchantFeatures extends Job
{
    const RETRY_INTERVAL    = 30;
    const MAX_RETRY_ATTEMPT = 3;

    protected $featureName;

    protected $entityIds;

    protected $variant;

    protected $entityType;

    public function __construct(string $mode, string $variant, $featureName, $entityType,array $entityIds)
    {
        parent::__construct($mode);

        $this->variant = $variant;
        $this->featureName = $featureName;
        $this->entityType = $entityType;
        $this->entityIds = $entityIds;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            app('dcs')->editFeatureInBulk($this->entityIds,$this->entityType,$this->featureName, $this->variant , true, $this->mode);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::DCS_EDIT_FEATURE_MERCHANT_JOB_FAILED,
                [

                ]);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::DCS_EDIT_FEATURE_QUEUE_DELETE, [
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.',
                'feature_name' => $this->featureName,
                'entity_id'    => $this->entityIds,
                'mode'         => $this->mode,
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

}

