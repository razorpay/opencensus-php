<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\PennyTesting;
use RZP\Models\FundAccount\Validation\Entity as ValidationEntity;

class FundAccountValidation extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    protected $queueConfigKey = 'fund_account_validation';

    protected $fundAccountValidationId;

    public function __construct(string $mode, string $fundAccountValidationId)
    {
        parent::__construct($mode);

        $this->fundAccountValidationId = $fundAccountValidationId;
    }

    public function handle()
    {
        parent::handle();

        // Trace payload should include all necessary info for debugging.

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'id'           => $this->fundAccountValidationId,
        ];

        try
        {
            $this->trace->debug(TraceCode::FUND_ACCOUNT_VALIDATION_JOB_REQUEST, $tracePayload);

            ValidationEntity::verifyIdAndSilentlyStripSign($this->fundAccountValidationId);

            $validationEntity = $this->repoManager->fund_account_validation->findOrFail($this->fundAccountValidationId);

            (new PennyTesting())->handlePennyTestingEvent($validationEntity);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_JOB_ERROR,
                [
                    'mode' => $this->mode,
                    'id'   => $this->fundAccountValidationId,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_JOB_DELETE, [
                'id'           => $this->fundAccountValidationId,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
