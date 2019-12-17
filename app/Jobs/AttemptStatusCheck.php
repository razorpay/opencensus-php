<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Attempt\Status;

class AttemptStatusCheck extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * Since, UPI status check has a timeout of 180 sec
     * @var int
     */
    public $timeout = 200;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fund_transfer_status_check';
    /**
     * @var string
     */
    protected $ftaId;

    public function __construct(string $mode, string $ftaId)
    {
        parent::__construct($mode);

        $this->ftaId = $ftaId;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $this->traceData(TraceCode::FTA_STATUS_CHECK_INIT);

            $attempt = $this->repoManager
                            ->fund_transfer_attempt
                            ->findByIdWithStatus($this->ftaId, Status::INITIATED);

            if ($attempt === null)
            {
                $this->traceData(TraceCode::FTA_NOT_FOUND);

                return;
            }

            $channel = $attempt->getChannel();

            $allowedChannels = Channel::getApiBasedChannels();

            if (in_array($channel, $allowedChannels, true) === false)
            {
                $this->traceData(TraceCode::FTA_CHANNEL_NOT_SUPPORTED);

                return;
            }

            $attempts = (new PublicCollection)->push($attempt);

            $nameSpace = $reconNamespace = 'RZP\\Models\\FundTransfer\\' . ucwords($channel) . '\\Reconciliation\\Processor';

            $summary = (new $nameSpace)->startReconciliation($attempts);

            $processed = ($summary['unprocessed_count'] === 0);

            $this->traceData(TraceCode::FTA_STATUS_CHECK_PROCESS_STATUS, $processed);

            $this->checkRetry($processed);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTA_STATUS_CHECK_PROCESS_FAILED,
                [
                    'fta_id' => $this->ftaId,
                ]);

            $this->checkRetry(false);
        }
    }

    protected function checkRetry(bool $processed)
    {
        if (($processed === false) and
            ($this->attempts() <= self::MAX_RETRY_ATTEMPT))
        {
            $this->traceData(TraceCode::FTA_STATUS_CHECK_PROCESS_RELEASED, $processed);

            $this->release(self::RETRY_INTERVAL);
        }
    }

    protected function traceData(string $traceCode, bool $status = false)
    {
        $this->trace->info(
            $traceCode,
            [
                'fta_id'        => $this->ftaId,
                'status'        => $status,
                'attempt_count' => $this->attempts()
            ]);
    }
}
