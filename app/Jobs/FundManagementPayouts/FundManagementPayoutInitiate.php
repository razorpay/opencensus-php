<?php

namespace RZP\Jobs\FundManagementPayouts;

use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job as Job;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Metric;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Service as PayoutService;

class FundManagementPayoutInitiate extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'fund_management_payout_initiate';

    /**
     * @var array
     *
     * Sample Params:
     * {
     *   "payout_create_input": [
     *       "balance_id": "RcTSyQlye8V3sZ2",
     *       "amount": 1000000,
     *       "currency": "INR",
     *       "mode": "NEFT",
     *       "purpose": "RZP Fund Management",
     *       "fund_account": {
     *           "account_type": "bank_account",
     *           "bank_account": {
     *           "name": "xyz",
     *           "ifsc": "HDFC0001234",
     *           "account_number": "1121431121541121"
     *       },
     *       "contact": {
     *           "name": "xyz",
     *           "type": "self",
     *       }
     *   ],
     *   "merchant_id": "1cXSLlUU8V9sXl",
     *   "channel": "rbl",
     *   "fmp_unique_identifier": "LRX3L6UUWW9sYd",
     * }
     *
     */
    protected $params;

    /**
     * Default timeout value for a job is 60s. Changing it to 100s
     * @var integer
     */
    public $timeout = 100;

    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    public function getParams()
    {
        return $this->params;
    }

    public function handle()
    {
        $workerStartTime = microtime(true);

        try
        {
            parent::handle();

            $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_INIT, [$this->params]);

            (new PayoutService())->createFundManagementPayout($this->params);

            $workerEndTime = microtime(true);

            $workerCompletionTotalTime = $workerEndTime - $workerStartTime;

            $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_SUCCESS, [
                'params'            => $this->params,
                'response_time'     => $workerCompletionTotalTime,
            ]);

            $dimensions = [
                'worker_class' => $this->getJobName(),
                'merchant_id'  => $this->params[Entity::MERCHANT_ID],
                'channel'      => $this->params[Entity::CHANNEL],
            ];

            $this->trace->histogram(
                Metric::FUND_MANAGEMENT_PAYOUT_INITIATED_COMPLETED_DURATION_SECONDS, $workerCompletionTotalTime, $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_FAILED, $this->params);

            $this->trace->count(Metric::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_FAILURES_COUNT, [
                'channel' => $this->params[Entity::CHANNEL],
            ]);
        }

        $this->delete();
    }
}
