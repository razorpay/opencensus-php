<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\Service as PayoutService;

class PayoutAttachmentEmail extends Job
{
    protected $mode;

    protected $queueConfigKey = 'payout_attachment_email';

    public $data;

    const MAX_RETRY_ATTEMPT = 5;

    const RELEASE_WAIT_SECS = 120;

    const JOB_DELETED       = 'job_deleted';

    const JOB_RELEASED      = 'job_released';

    /**
     * Create a new job instance.
     * @param string $mode
     * @param array $input
     */
    public function __construct(
        string $mode,
        array $input
    )
    {
        parent::__construct($mode);

        $this->mode = $mode;
        $this->data = $input;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            (new PayoutService)->processPayoutAttachmentEmail($this->data);

            $this->delete();
        }
        catch (\Exception $e)
        {
            $this->handleException($e);
        }
    }

    /**
     * When an exception occurs, the job gets deleted if it has
     * exceeded the maximum attempts. Otherwise it is released back
     * into the queue after the set release wait time
     *
     * @param \Exception $e
     */
    protected function handleException(\Exception $e)
    {
        $jobAction = self::JOB_DELETED;

        if ($this->attempts() >= self::MAX_RETRY_ATTEMPT)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }

        $this->trace->traceException(
            $e,
            Trace::ERROR,
            TraceCode::PAYOUT_ATTACHMENT_EMAIL_FAILED,
            [
                'job_action' => $jobAction,
            ]);
    }
}
