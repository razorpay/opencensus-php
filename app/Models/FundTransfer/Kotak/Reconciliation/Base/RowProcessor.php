<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Exception;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Status;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

abstract class RowProcessor extends BaseRowProcessor
{
    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    protected function parseRow()
    {
        $utr = trim($this->row[Headings::UTR_NUMBER]);

        if (empty($utr) === true)
        {
            $utr = null;
        }

        $this->parsedData = [
            'payment_ref_no'    => trim($this->row[Headings::PAYMENT_REF_NO] ?? null),
            'utr'               => $utr,
            'bank_status_code'  => trim($this->row[Headings::STATUS_OF_TRANSACTION] ?? null),
            'remarks'           => trim($this->row[Headings::REMARKS] ?? null),
            'payment_date'      => trim($this->row[Headings::PAYMENT_DATE] ?? null),
            'instrument_date'   => trim($this->row[Headings::INSTRUMENT_DATE] ?? null),
            'date_time'         => trim($this->row[Headings::DATE_TIME] ?? null),
            'cms_ref_no'        => trim($this->row[Headings::CMS_REF_NO] ?? null),
        ];

        $this->reconEntityId = $this->parsedData['payment_ref_no'];
    }

    protected function getReconciliationStatus()
    {
        $recordDate = Carbon::createFromFormat('d-M-y', $this->parsedData['payment_date'], Timezone::IST);

        $now = Carbon::now()->getTimestamp();

        $eightFiftyPm = $recordDate->hour(20)->minute(50)->getTimestamp();

        $failureReason = null;

        $class = $this->getEntityStatusNamespace($this->reconEntity->getEntityName());

        $status = $class::FAILED;

        if ($this->parsedData['bank_status_code'] === Status::PROCESSED)
        {
            $remarks = $this->parsedData['remarks'];

            if ((empty($remarks) === false) and
                (in_array($remarks, self::SUCCESS_STATUS) === false))
            {
                $status = $class::FAILED;

                $failureReason = 'Reconciliation';
            }
            else
            {
                $status = $class::PROCESSED;

                // If current time is before 10 pm, dont mark the settlement as
                // processed and update only the utr
                if (($now < $eightFiftyPm) and ($this->env !== 'testing'))
                {
                    $status = $this->reconEntity->getStatus();
                }
            }
        }

        // Verify status
        $oldStatus = $this->reconEntity->getStatus();

        if ($oldStatus !== $status)
        {
            // if already processed
            if ($this->reconEntity->isPendingReconciliation() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old and new status not matching. ' .
                    'Old status: ' . $oldStatus . ' New status: ' . $status .
                    'Entity Id: ' . $this->reconEntity->getPublicId());
            }

            //
            // Set the fire webhook flag as true only for the settlements that
            // have to be updated in the current settlement cycle. For the settlements
            // that have been settled in the previous cycles, this flag stays false.
            //
            $this->fireWebhook = true;
        }

        $this->parsedData['failure_reason'] = $failureReason;

        $this->parsedData['status'] = $status;
    }
}
