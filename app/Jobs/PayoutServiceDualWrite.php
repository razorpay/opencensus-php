<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\Core;
use Razorpay\Trace\Logger as Trace;

class PayoutServiceDualWrite extends Job
{
    const MAX_RETRY_ATTEMPT = 5;

    const MAX_RETRY_DELAY = 10;

    const MAX_ATTEMPTS_FOR_DUAL_WRITE = 3;

    /**
     * @var string
     */
    protected $queueConfigKey = 'payout_service_dual_write';

    /**
     * @var array
     */
    protected $params;

    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_INIT,
                $this->params
            );

            (new Core)->processDualWrite($this->params);

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_COMPLETE,
                $this->params);

            $this->delete();
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_FAILURE,
                $this->params);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_ATTEMPTS_FOR_DUAL_WRITE)
        {
            $this->trace->info(
                TraceCode::PAYOUTS_DUAL_WRITE_JOB_RELEASE,
                $this->params);

            $this->release(self::MAX_RETRY_DELAY);
        }
        else
        {
            $this->trace->info(
                TraceCode::PAYOUTS_DUAL_WRITE_JOB_DELETE,
                $this->params);

            $this->delete();
        }
    }
}
