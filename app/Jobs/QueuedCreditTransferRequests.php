<?php


namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\CreditTransfer;

class QueuedCreditTransferRequests extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;

    // the delay is in seconds
    // used in an exponential backoff manner
    const MIN_RETRY_DELAY = 3;

    /**
     * @var string
     */
    protected $queueConfigKey = 'queued_credit_transfer_requests';

    protected $trace;

    /**
     * @var array
     */
    protected $params;

    public function __construct(string $mode, array $params)
    {
        parent::__construct($mode);

        $this->params = $params;
    }

    public function handle()
    {
        parent::handle();

        $traceData = [ "credit_transfer_request" => $this->params ];

        $this->trace->info(TraceCode::CREDIT_TRANSFER_QUEUE_PROCESSING_INITIATE,
            $traceData + [
                'attempt' => $this->attempts(),
            ]);

        try
        {
            (new CreditTransfer\Core)->createCreditTransfer($this->params, true);

            $this->trace->info(TraceCode::CREDIT_TRANSFER_QUEUE_PROCESSING_SUCCESS, $traceData);

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::CREDIT_TRANSFER_QUEUE_PROCESSING_ATTEMPT_FAILED,
                $traceData + [
                    'attempt' => $this->attempts(),
                ]);

            if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->trace->info(TraceCode::CREDIT_TRANSFER_QUEUE_PROCESSING_FAILURE_EXCEPTION, $traceData);

                try
                {
                    (new CreditTransfer\Core)->moveCreditTransferToFailed($this->params);

                    $this->trace->info(TraceCode::CREDIT_TRANSFER_MOVED_TO_FAILED, $traceData);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->info(TraceCode::CREDIT_TRANSFER_FAILED_STATUS_UPDATE_FAILURE, $traceData);
                }

                $this->delete();
            }
            else
            {
                $noOfAttempts = $this->attempts();

                $this->release(self::MIN_RETRY_DELAY * pow(2, ($noOfAttempts - 1)));
            }
        }
    }
}
