<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Fraud\WebsiteChecker\Job as WebCheckerJob;
use RZP\Models\Merchant\Fraud\WebsiteChecker\Constants as WebCheckerConstants;

class RiskWebsiteChecker extends Job
{
    protected $queueConfigKey = "risk_website_checker";

    protected $params;

    public function __construct(string $mode, array $params)
    {
        parent::__construct($mode);

        $this->params = $params;
    }

    public function handle()
    {
        parent::handle();

        $params = $this->params;

        $this->trace->info(
            TraceCode::WESBITE_CHECKER_JOB_PROCESSING_START,
            [
                'params' => $params,
            ]
        );

        $jobType = $params['job_type'];

        $jobDetails = $params['job_details'] ?? [];

        $merchantId = $params['merchant_id'];

        $wcJob = new WebCheckerJob();

        try
        {
            if ($jobType === WebCheckerConstants::PERFORM_WEBSITE_CHECK_JOB)
            {
                $retryCount = $jobDetails[WebCheckerConstants::RETRY_COUNT_KEY] ?? 0;

                $eventType = $jobDetails[WebCheckerConstants::EVENT_TYPE] ?? WebCheckerConstants::PERIODIC_CHECKER_EVENT;

                $wcJob->performRiskCheck($merchantId, $eventType, $retryCount);
            }
            else if ($jobType === WebCheckerConstants::SEND_REMINDER_TO_MERCHANT_JOB)
            {
                $wcJob->remindMerchantIfApplicable($merchantId);
            }
            else
            {
                $this->trace->error(TraceCode::WESBITE_CHECKER_JOB_PROCESSING_INVALID_JOB_TYPE, [
                    'params' => $params,
                ]);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::WESBITE_CHECKER_JOB_PROCESSING_FAILED,
                [
                    'params' => $params,
                ]
            );
        }

        $this->trace->info(
            TraceCode::WESBITE_CHECKER_JOB_PROCESSING_END,
            [
                'params' => $params,
            ]
        );
    }
}
