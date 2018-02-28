<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    protected function getAttemptStatus(): array
    {
        $status = $this->fta->getStatus();

        $bankStatusCode = $this->fta->getBankStatusCode();

        $failureReason  = null;

        if (in_array($bankStatusCode, [Status::SETTLED, Status::EXECUTED], true) === true)
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
        else if (in_array($bankStatusCode, [Status::CANCELLED, Status::REJECTED], true) === true)
        {
            $status = Attempt\Status::FAILED;

            $failureReason = 'Reconciliation';
        }

        return [$status, $failureReason];
    }

    protected function isMerchantLevelError(): bool
    {
        return false;
    }
}
