<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\V2;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

class RowProcessor extends Base\RowProcessor
{
    public function __construct($row)
    {
        parent::__construct($row);

        $this->version = Attempt\Version::V2;
    }

    public static function isV2($row): bool
    {
        if (($row[Headings::ENRICHMENT_2] !== null) and
            ($row[Headings::ENRICHMENT_2] === Attempt\Version::V2))
        {
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
        $this->reconEntity->setBankStatusCode($this->parsedData['bank_status_code']);
        $this->reconEntity->setDateTime($this->parsedData['date_time']);
        $this->reconEntity->setCmsRefNo($this->parsedData['cms_ref_no']);

        $dirtyAttributes = $this->reconEntity->getDirty();

        if (($status === Attempt\Status::FAILED) and
            ($utr !== null) and
            (in_array(Attempt\Entity::STATUS, array_keys($dirtyAttributes)) === true))
        {
            $this->firstFailure = true;
        }

        $this->reconEntity->saveOrFail();

        $source = $this->reconEntity->source;
        $source->setUtr($this->parsedData['utr']);
        $source->setFailureReason($this->parsedData['failure_reason']);
        $source->setStatus($this->parsedData['status']);
        $source->setRemarks($this->parsedData['remarks']);

        $source->saveOrFail();

        $source->transaction->setReconciledAt($this->reconciledAt);
        $source->transaction->saveOrFail();

        return $source;
    }
}