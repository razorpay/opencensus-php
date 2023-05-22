<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Metric as PartnerMetric;

class PartnerMigrationAuditJob extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 3;

    protected $queueConfigKey = 'commission';

    protected $merchantId;
    protected $actorDetails;
    protected $oldPartnerType;

    public function __construct(string $merchantId, array $actorDetails, string $oldPartnerType)
    {
        parent::__construct();

        $this->merchantId = $merchantId;
        $this->actorDetails  = $actorDetails;
        $this->oldPartnerType = $oldPartnerType;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $traceInfo = ['merchant_id' => $this->merchantId, 'actor_details'=> $this->actorDetails, 'old_partner_type'=> $this->oldPartnerType];
            $this->trace->info(
                TraceCode::PARTNER_MIGRATION_AUDIT_JOB_REQUEST,
                $traceInfo
            );
            $core = new Partner\Core();
            $core->auditPartnerMigration($this->merchantId, $this->actorDetails, $this->oldPartnerType);
            $this->delete();
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PARTNER_MIGRATION_AUDIT_JOB_FAILED,
                [
                    'merchant_id'     => $this->merchantId,
                    'actor_details'   => $this->actorDetails,
                    'old_partner_type'=> $this->oldPartnerType,
                    'message'         => $e->getMessage(),
                ]
            );
            $this->checkRetry($e);
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::PARTNER_MIGRATION_AUDIT_JOB_DELETE, [
                'mode'         => $this->mode,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();

            $this->trace->count(PartnerMetric::PARTNER_MIGRATION_AUDIT_JOB_FAILURE_TOTAL);
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
