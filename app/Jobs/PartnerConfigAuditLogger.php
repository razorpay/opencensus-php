<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner\Config\Core as PartnerConfigCore;
use RZP\Models\Partner\Metric as PartnerMetric;

class PartnerConfigAuditLogger extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    public $timeout = 180;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $params;

    public function __construct(array $params, string $mode = null)
    {
        parent::__construct($mode);

        $this->params = $params;
    }

    public function handle()
    {
        $startTime = millitime();

        parent::handle();

        try
        {
            (new PartnerConfigCore())->auditPartnerConfig($this->params);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PARTNER_CONFIG_AUDIT_JOB_ERROR,
                [
                    'params'  => $this->params,
                    'message' => $e->getMessage(),
                ]
            );
            $this->checkRetry();
        }

        $timeTaken = millitime() - $startTime;
        $this->trace->histogram(PartnerMetric::PARTNER_CONFIG_AUDIT_LATENCY_IN_MS, $timeTaken);
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::PARTNER_CONFIG_AUDIT_QUEUE_DELETE, [
                'mode'         => $this->mode,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();

            $this->trace->count(PartnerMetric::PARTNER_CONFIG_AUDIT_LOGGER_JOB_FAILURE_TOTAL);
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
