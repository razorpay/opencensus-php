<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\V3;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Constants\Entity;
use RZP\Exception;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Base;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Status;

class RowProcessor extends Base\RowProcessor
{
    protected $source = null;

    public function __construct($row)
    {
        parent::__construct($row);

        $this->version = Attempt\Version::V3;
    }

    public static function isV3($row): bool
    {
        if (($row[Headings::PAYMENT_DETAILS_3] !== null) and
            ($row[Headings::PAYMENT_DETAILS_3] === Attempt\Version::V3))
        {
            return true;
        }
        else if (($row[Headings::PAYMENT_DETAILS_4] !== null) and
            ($row[Headings::PAYMENT_DETAILS_4] === Attempt\Version::V3))
        {
            //
            // This condition is added as a workaround for a bug at Kotak's end.
            // The bug is that Kotak doesn't read the details sent under PAYMENT_DETAILS_4.
            // Also it tracks -
            //      PAYMENT_DETAILS_1 as PAYMENT_DETAILS_2
            //      PAYMENT_DETAILS_2 as PAYMENT_DETAILS_3
            //      PAYMENT_DETAILS_3 as PAYMENT_DETAILS_4
            // Hence even though we send version information in PAYMENT_DETAILS_3,
            // we are trying to read it from PAYMENT_DETAILS_4 here.
            //
            return true;
        }

        return false;
    }

    protected function fetchEntities()
    {
        $this->reconEntity = $this->repo
                                  ->fund_transfer_attempt
                                  ->findWithRelations(
                                        $this->reconEntityId,
                                        ['source', 'source.transaction', 'source.merchant' , 'batchFundTransfer']);

        $this->source = $this->reconEntity->source;
    }

    protected function updateEntities()
    {
        $this->updateReconEntity();

        $this->updateSourceEntity();

        $this->updateTransactionEntity();

        return $this->source;
    }

    protected function updateReconEntity()
    {
        // Get values
        $utr = $this->parsedData['utr'];
        $status = $this->parsedData['status'];
        $bankStatusCode = $this->parsedData['bank_status_code'];

        // Update values
        $this->reconEntity->setUtr($utr);
        $this->reconEntity->setStatus($status);
        $this->reconEntity->setFailureReason($this->parsedData['failure_reason']);
        $this->reconEntity->setRemarks($this->parsedData['remarks']);
        $this->reconEntity->setBankStatusCode($bankStatusCode);
        $this->reconEntity->setDateTime($this->parsedData['date_time']);
        $this->reconEntity->setCmsRefNo($this->parsedData['cms_ref_no']);

        $dirtyAttributes = $this->reconEntity->getDirty();

        // The below conditions check that it's not an upload-level failure
        if (($status === Attempt\Status::FAILED) and
            (empty($utr) === false) and
            (in_array(Attempt\Entity::STATUS, array_keys($dirtyAttributes)) === true) and
            ($bankStatusCode === Status::PROCESSED))
        {
            $this->firstFailure = true;

            // setting merchant hold_funds true temporarily; this will
            // be set for test and live separately afterwards
            $this->reconEntity->merchant->setHoldFunds(true);
        }

        $this->reconEntity->saveOrFail();

        $this->reconEntity->merchant->saveOrFail();
    }

    protected function updateSourceEntity()
    {
        if ($this->source->getBatchFundTransferId() !== $this->reconEntity->getBatchFundTransferId())
        {
            return;
        }

        $sourceStatus = $this->getSourceStatusFromReconEntityStatus();

        $this->source->setStatus($sourceStatus);
        $this->source->setUtr($this->parsedData['utr']);
        $this->source->setRemarks($this->parsedData['remarks']);

        if ($this->source->getEntity() !== Attempt\Type::REFUND)
        {
            $this->source->setFailureReason($this->parsedData['failure_reason']);

            if (($this->parsedData['status'] === Attempt\Status::PROCESSED) and
                (empty($this->parsedData['instrument_date']) === false))
            {
                $settledOn = Carbon::createFromFormat(
                                'd-M-y', $this->parsedData['instrument_date'], Timezone::IST)->getTimestamp();

                $this->source->setSettledOn($settledOn);
            }
        }

        $this->source->saveOrFail();
    }

    protected function updateTransactionEntity()
    {
        $this->source->transaction->setReconciledAt($this->reconciledAt);

        $this->source->transaction->saveOrFail();
    }

    protected function getSourceStatusFromReconEntityStatus(): string
    {
        $sourceEntityName = $this->source->getEntity();

        switch ($sourceEntityName)
        {
            case Entity::SETTLEMENT:
            case Entity::PAYOUT:
            case Entity::REFUND:
                return $this->getStatusForEntity($sourceEntityName);

            default:
                throw new Exception\LogicException('Unrecognized source entity: ' . $sourceEntityName);
        }
    }

    protected function getStatusForEntity(string $sourceEntityName): string
    {
        $entityStatusClass = $this->getEntityStatusNamespace($sourceEntityName);

        $attemptStatus = $this->parsedData['status'];

        switch ($attemptStatus)
        {
            case Attempt\Status::CREATED:
            case Attempt\Status::INITIATED:
                return $this->source->getStatus();

            case Attempt\Status::FAILED:
                return $entityStatusClass::FAILED;

            case Attempt\Status::PROCESSED:
                return $entityStatusClass::PROCESSED;

            default:
                throw new Exception\LogicException('Unrecognized attempt status: ' . $attemptStatus);
        }
    }
}
