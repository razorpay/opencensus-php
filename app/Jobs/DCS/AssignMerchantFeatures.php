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

    protected $entityId;

    protected $variant;

    protected $entityType;

    public function __construct(string $mode, string $variant, $featureName, $entityType, $entityId)
    {
        parent::__construct($mode);

        $this->variant = $variant;
        $this->featureName = $featureName;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $data = [
                Entity::NAME => $this->featureName,
                Entity::ENTITY_ID => $this->entityId,
                Entity::ENTITY_TYPE => $this->entityType,
            ];

            $entity = (new Entity)->build($data);
            $entity->setEntityId($this->entityId);
            $entity->setEntityType($this->entityType);
            app('dcs')->editFeature($entity, $this->variant , true, $this->mode);
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
                'entity_id'    => $this->entityId,
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

