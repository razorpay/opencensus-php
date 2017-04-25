<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

use RZP\Constants\Entity;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Status;

class RowProcessor extends BaseCore
{
    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    protected $row;

    protected $version;

    protected $parsedData;

    protected $reconEntityId;

    protected $reconEntity;

    protected $reconciledAt;

    public function __construct($row)
    {
        parent::__construct();

        $this->row = $row;
    }

    public function process($reconciledAt)
    {
        $this->reconciledAt = $reconciledAt;

        $this->parseRow();

        $this->fetchEntities();

        $this->getReconciliationStatus();

        return $this->updateEntities();
    }

    protected function parseRow()
    {
        $this->parsedData = [
            'payment_ref_no'    => trim($this->row[Headings::PAYMENT_REF_NO] ?? null),
            'utr'               => trim($this->row[Headings::UTR_NUMBER] ?? null),
            'bank_status_code'  => trim($this->row[Headings::STATUS_OF_TRANSACTION] ?? null),
            'remarks'           => trim($this->row[Headings::REMARKS] ?? null),
            'payment_date'      => trim($this->row[Headings::PAYMENT_DATE] ?? null),
            'date_time'         => trim($this->row[Headings::DATE_TIME] ?? null),
            'cms_ref_no'        => trim($this->row[Headings::CMS_REF_NO] ?? null),
        ];

        $this->reconEntityId = $this->parsedData['payment_ref_no'];
    }

    protected function getReconciliationStatus()
    {
        $recordDate = Carbon::createFromFormat('d-M-y', $this->parsedData['payment_date'], 'Asia/Kolkata');

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $tenPm = $recordDate->hour(22)->timestamp;

        $failureReason = null;

        $class = Entity::getEntityNamespace($this->reconEntity->getEntityName()) . '\\Status';
        $status = $class::FAILED;

        if ($this->parsedData['bank_status_code'] === Status::PROCESSED)
        {
            $remarks = $this->parsedData['remarks'];

            // If current time is before 10 pm, dont mark the settlement as
            // processed and update only the utr
            if (($now < $tenPm) and ($this->env !== 'testing'))
            {
                $status = $entity->getStatus();
            }
            else if ((empty($remarks) === true) or
                (in_array($remarks, self::SUCCESS_STATUS) === true))
            {
                $status = $class::PROCESSED;
            }
            else
            {
                $status = $class::FAILED;

                $failureReason = 'Reconciliation';
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
        }

        $this->parsedData['failure_reason'] = $failureReason;

        $this->parsedData['status'] = $status;
    }
}