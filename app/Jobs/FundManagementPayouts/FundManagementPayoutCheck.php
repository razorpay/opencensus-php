<?php

namespace RZP\Jobs\FundManagementPayouts;

use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job as Job;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Metric;
use RZP\Models\Payout\Entity;
use RZP\Exception\LogicException;
use RZP\Models\Payout\Core as PayoutCore;

class FundManagementPayoutCheck extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'fund_management_payout_check';

    /**
     * @var array
     *
     * Sample Params:
     * {
     *     "channel": "rbl",
     *     "merchant_id": "1cXSLlUU8V9sXl",
     *     "thresholds" : [
     *          "neft_threshold": 50000000,           // In paisa
     *          "lite_balance_threshold": 30000000,   // In paisa
     *          "lite_deficit_allowed": 500,          // Percentage
     *          "fmp_consideration_threshold": 14400, // In secs
     *          "total_amount_threshold":20000000,    // In paisa
     *     ],
     * }
     *
     */
    protected $params;

    /**
     * Default timeout value for a job is 60s. Changing it to 300s
     * @var integer
     */
    public $timeout = 300;

    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        $workerStartTime = microtime(true);

        try
        {
            parent::handle();

            $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_INIT, [$this->params]);

            (new PayoutCore)->initiateFundManagementPayoutIfRequired($this->params);

            $workerEndTime = microtime(true);

            $workerCompletionTotalTime = $workerEndTime - $workerStartTime;

            $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_SUCCESS, [
                'params'            => $this->params,
                'response_time'     => $workerCompletionTotalTime,
            ]);

            $dimensions = [
                'worker_class' => $this->getJobName(),
                'merchant_id'  => $this->params[Entity::MERCHANT_ID],
                'channel'      => $this->params[Entity::CHANNEL],
            ];

            $this->trace->histogram(
                Metric::FUND_MANAGEMENT_PAYOUT_CHECK_COMPLETED_DURATION_SECONDS, $workerCompletionTotalTime, $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILED, $this->params);

            if ($e instanceof LogicException)
            {
                $errorMessage = $e->getMessage();
            }
            else
            {
                $errorMessage = $e->getCode();
            }

            $this->trace->count(Metric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, [
                Entity::CHANNEL => $this->params[Entity::CHANNEL],
                'error_message' => $errorMessage,
            ]);
        }

        $this->delete();
    }

    public function getParams()
    {
        return $this->params;
    }
}
