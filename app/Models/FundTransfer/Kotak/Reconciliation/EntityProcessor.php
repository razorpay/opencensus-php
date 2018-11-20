<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    protected function getAttemptStatus(): array
    {
        $status = Attempt\Status::FAILED;

        $bankStatusCode = $this->fta->getBankStatusCode();

        $failureReason = null;

        if ($bankStatusCode === Status::PROCESSED)
        {
            $remarks = $this->fta->getRemarks();

            if ((empty($remarks) === false) and
                (in_array($remarks, self::SUCCESS_STATUS) === false))
            {
                $status = Attempt\Status::FAILED;

                $failureReason = 'Reconciliation';
            }
            else
            {
                $status = Attempt\Status::PROCESSED;

                // This should ideally be the time request was sent to the bank.
                // Needs to be changed to initiated_at when we have that column.
                $recordDate = Carbon::createFromTimestamp($this->fta->getCreatedAt(), Timezone::IST);

                $now = Carbon::now(Timezone::IST)->getTimestamp();

                $tenTenPm = $recordDate->hour(22)->minute(10)->getTimestamp();

                if (($now < $tenTenPm) and ($this->env !== 'testing'))
                {
                    $status = $this->fta->getStatus();
                }
            }
        }
        else if ($bankStatusCode === Status::CANCELLED)
        {
            $failureReason = 'Reconciliation';
        }
        return [$status, $failureReason];
    }

    protected function isMerchantLevelError(): bool
    {
        $ftaStatus = $this->fta->getStatus();

        $utr = $this->fta->getUtr();

        $bankStatusCode = $this->fta->getBankStatusCode();

        if (($ftaStatus === Attempt\Status::FAILED) and
            (empty($utr) === false) and
            ($bankStatusCode === Status::PROCESSED))
        {
            return true;
        }

        return false;
    }
}
