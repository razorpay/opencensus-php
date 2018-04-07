<?php

namespace RZP\Models\FundTransfer\Rbl\Reconciliation;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function getAttemptStatus(): array
    {
        $bankStatusCode = $this->fta->getBankStatusCode();

        $failureReason  = null;

        if (strcasecmp(Status::FAILURE, $bankStatusCode) === 0)
        {
            $status = Attempt\Status::FAILED;

            $failureReason = 'Reconciliation';
        }
        else
        {
            $status = Attempt\Status::PROCESSED;

            // This should ideally be the time this request was sent to the bank.
            // Needs to be changed to initiated_at when we have that column.
            $recordDate = Carbon::createFromTimestamp($this->fta->getCreatedAt(), Timezone::IST);

            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $tenTenPm = $recordDate->hour(22)->minute(10)->getTimestamp();

            if (($now < $tenTenPm) and ($this->env !== 'testing'))
            {
                $status = $this->fta->getStatus();
            }
        }

        return [$status, $failureReason];
    }

    protected function isMerchantLevelError(): bool
    {
        return false;
    }
}
