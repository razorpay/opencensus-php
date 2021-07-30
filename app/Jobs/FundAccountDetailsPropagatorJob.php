<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundAccount\DetailsPropagator\Core as DetailsPropagator;

/***
 * Class FundAccountDetailsPropagatorJob
 * @package RZP\Jobs
 */
class FundAccountDetailsPropagatorJob extends Job
{

    const MAX_RETRIES = 3;

    const MAX_RETRY_DELAY = 300;

    protected $queueConfigKey = 'fund_account_details_propagator';

    protected $fundAccountId;

    public function __construct(string $mode, string $fundAccountId)
    {
        parent::__construct($mode);

        $this->fundAccountId = $fundAccountId;
    }

    public function handle()
    {
        parent::handle();

        $context = [
            DetailsPropagator::FUND_ACCOUNT_ID  => $this->fundAccountId
        ];

        try
        {
            $fundAccount = $this->repoManager
                ->fund_account
                ->findByPublicId($this->fundAccountId);

            $this->trace->info(
                TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_JOB,
                $context
            );

            DetailsPropagator::update($fundAccount, $this->mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_JOB_FAILED,
                $context
            );

            if ($this->attempts() < self::MAX_RETRIES)
            {
                $this->trace->info(TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_JOB_RELEASE,
                    $context);

                $this->release(self::MAX_RETRY_DELAY);
            }
        }
    }
}
