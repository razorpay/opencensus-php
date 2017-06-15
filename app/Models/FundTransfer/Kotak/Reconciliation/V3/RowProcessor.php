<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\V3;

use Carbon\Carbon;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Base;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Status;

class RowProcessor extends Base\RowProcessor
{
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
    }

    protected function updateEntities()
    {
        $utr = $this->parsedData['utr'];
        $this->reconEntity->setUtr($utr);

        $status = $this->parsedData['status'];
        $this->reconEntity->setStatus($status);

        $this->reconEntity->setFailureReason($this->parsedData['failure_reason']);
        $this->reconEntity->setRemarks($this->parsedData['remarks']);

        $bankStatusCode = $this->parsedData['bank_status_code'];
        $this->reconEntity->setBankStatusCode($bankStatusCode);

        $processedAtDate = $this->parsedData['date_time'];
        $processedAtTimestamp = Carbon::createFromFormat('d/m/Y H:i:s', $processedAtDate)->timestamp;

        $this->reconEntity->setDateTime($processedAtDate);

        $this->reconEntity->setCmsRefNo($this->parsedData['cms_ref_no']);

        $dirtyAttributes = $this->reconEntity->getDirty();

        // The below conditions check that it's not an upload-level failure
        if (($status === Attempt\Status::FAILED) and
            (empty($utr) === false) and
            (in_array(Attempt\Entity::STATUS, array_keys($dirtyAttributes)) === true) and
            ($bankStatusCode === Status::PROCESSED))
        {
            $this->firstFailure = true;
        }

        $this->reconEntity->saveOrFail();

        $source = $this->reconEntity->source;

        $source->setUtr($utr);
        $source->setFailureReason($this->parsedData['failure_reason']);
        $source->setStatus($status);
        $source->setRemarks($this->parsedData['remarks']);
        $source->setProcessedAt($processedAtTimestamp);

        if ($status === Attempt\Status::PROCESSED)
        {
            $settledOn = Carbon::createFromFormat(
                            'd-M-y', $this->parsedData['instrument_date'])->format('d/m/Y');

            $source->setSettledOn($settledOn);
        }

        $source->saveOrFail();

        $source->transaction->setReconciledAt($this->reconciledAt);
        $source->transaction->saveOrFail();

        return $source;
    }
}