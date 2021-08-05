<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Fraud\HealthChecker\Job as HealthCheckerJob;
use RZP\Models\Merchant\Fraud\HealthChecker\Constants as HealthCheckerConstants;

class RiskHealthChecker extends Job
{
    protected $queueConfigKey;

    protected $params;

    public function __construct(string $mode, array $params, string $checkerType)
    {
        parent::__construct($mode);

        $this->params = $params;


        // separate queue for app checker
        $this->queueConfigKey = HealthCheckerConstants::QUEUE_MAP[$checkerType];
    }

    public function handle()
    {
        parent::handle();

        $params = $this->params;

        $this->trace->info(
            TraceCode::HEALTH_CHECKER_JOB_PROCESSING_START,
            [
                'params' => $params,
            ]
        );

        $jobType = $params['job_type'];

        $jobDetails = $params['job_details'] ?? [];

        $merchantId = $params['merchant_id'];

        $wcJob = new HealthCheckerJob();

        try
        {
            $checkerType = $jobDetails[HealthCheckerConstants::CHECKER_TYPE] ?? HealthCheckerConstants::WEBSITE_CHECKER;

            if ($jobType === HealthCheckerConstants::PERFORM_HEALTH_CHECK_JOB)
            {
                $retryCount = $jobDetails[HealthCheckerConstants::RETRY_COUNT_KEY] ?? 0;

                $eventType = $jobDetails[HealthCheckerConstants::EVENT_TYPE] ?? HealthCheckerConstants::PERIODIC_CHECKER_EVENT;

                $wcJob->performRiskCheck($merchantId, $eventType, $checkerType, $retryCount);
            }
            else if ($jobType === HealthCheckerConstants::SEND_REMINDER_TO_MERCHANT_JOB)
            {
                $wcJob->remindMerchantIfApplicable($merchantId, $checkerType);
            }
            else
            {
                $this->trace->error(TraceCode::HEALTH_CHECKER_JOB_PROCESSING_INVALID_JOB_TYPE, [
                    'params' => $params,
                ]);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::HEALTH_CHECKER_JOB_PROCESSING_FAILED,
                [
                    'params' => $params,
                ]
            );
        }

        $this->trace->info(
            TraceCode::HEALTH_CHECKER_JOB_PROCESSING_END,
            [
                'params' => $params,
            ]
        );
    }
}
