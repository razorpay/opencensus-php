<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\RowProcessor;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\Payout;
use RZP\Models\Settlement;

class Base
{
    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    protected $repo;

    protected $mode;

    protected $row;

    protected $version;

    protected $parsedData;

    protected $reconEntityId;

    protected $reconEntity;

    protected $reconciledAt;

    public function __construct($row)
    {
        $this->row = $row;

        $this->mode = \BasicAuth::getMode();

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function process($reconciledAt)
    {
        $this->reconciledAt = $reconciledAt;

        $this->parseRow();

        $this->fetchEntities();

        $this->getEntityStatus();

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

    protected function getEntityStatus()
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
            if (($now < $tenPm) and ($this->mode === Mode::LIVE))
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

        $this->parsedData['failure_reason'] = $failureReason;

        $this->parsedData['status'] = $status;
    }

    protected function updateEntities()
    {
        $oldStatus = $this->reconEntity->getStatus();

        $status = $this->parsedData['status'];

        $entityStatusClass = Entity::getEntityNamespace($this->reconEntity->getEntityName()) . '\\Status';

        if ($oldStatus !== $status)
        {
            // if already processed
            if ($this->reconEntity->isPendingReconciliation() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old and new status not matching. ' .
                    'Old status: ' . $oldStatus . ' New status: ' . $status .
                    'Entity Id: ' . $entity->getPublicId());
            }
        }
    }
}