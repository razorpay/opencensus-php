<?php

namespace RZP\Models\FundAccount\Validation\Traits;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use Illuminate\Support\Arr;
use RZP\Constants\Timezone;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\Constants;

trait FtaStatus
{
    protected function updateValidationAfterFtaProcessed(array $input)
    {
        $this->markValidationAsCompleted(AccountStatus::ACTIVE);

        if ($this->validation->getRegisteredName() === null)
        {
            $traceArray = [
                'input'             => $input,
            ];

            $this->trace->warn(TraceCode::BENEFICIARY_NAME_NOT_PRESENT, $traceArray);
        }
    }

    protected function updateValidationAfterFtaFailed(array $input)
    {
        if ($input['internal_error'] === false)
        {
            $this->markValidationAsCompleted(AccountStatus::INVALID);

            return;
        }

        $traceArray = [
            'input'             => $input,
            'validation_status' => $this->validation->getStatus(),
        ];

        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_FAILED_CRITICAL_ERROR, $traceArray);

        // We need to retry after some time.
        // This will be done by creating another FTA from retry CRON.
        $this->setRetryAt();
    }

    protected function setRetryAt()
    {
        // Calculate Retry At value
        $nextAttempt = $this->validation->getAttempts() + 1;

        $retryAfter = Arr::get(self::$attemptToRetryAfterSecondsMap, $nextAttempt);

        if ($retryAfter == null)
        {
            $retryAfter = end(self::$attemptToRetryAfterSecondsMap);
        }

        $retryAt = Carbon::now(Timezone::IST)->addSeconds($retryAfter)->getTimestamp();

        $this->validation->setRetryAt($retryAt);

        $this->repo->saveOrFail($this->validation);
    }
}
