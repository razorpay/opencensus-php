<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt\Metric;
use RZP\Models\FundTransfer\Attempt\Entity as AttemptEntity;

abstract class RowProcessor extends Base\Core
{
    protected $row;

    protected $version;

    protected $parsedData;

    protected $reconEntity   = null;

    protected $reconEntityId = null;

    abstract protected function processRow();

    abstract protected function updateReconEntity();

    /**
     * Returns the value to be updated as UTR
     */
    abstract protected function getUtrToUpdate();

    public function __construct($row)
    {
        parent::__construct();

        $this->row = $row;
    }

    public function process()
    {
        $this->processRow();

        $this->fetchEntities();

        if (empty($this->reconEntity) === true)
        {
            $this->trace->error(TraceCode::SETTLEMENT_RECONCILIATION_SKIPPED,
                [
                    'row'           => $this->row,
                    'parsed_data'   => $this->parsedData,
                ]);

            return null;
        }

        $this->updateEntities();

        return $this->reconEntity;
    }

    protected function fetchEntities()
    {
        $this->reconEntity = $this->repo
                                  ->fund_transfer_attempt
                                  ->findWithRelations($this->reconEntityId, ['source']);
    }

    protected function updateEntities()
    {
        $this->updateReconEntity();

        $sourceBatchId = $this->reconEntity->source->getBatchFundTransferId();

        if ($sourceBatchId !== $this->reconEntity->getBatchFundTransferId())
        {
            return;
        }

        $this->updateSourceEntity();
    }

    /**
     * All child classes must use this method to update UTR on the reconEntity
     * because this method determines if the UTR for the corresponding attempt
     * is being sent by the bank for the first time. This is required to track
     * the metric on time taken by the bank to send UTRs.
     */
    protected function updateUtrOnReconEntity()
    {
        $utr = $this->getUtrToUpdate();

        $currentUtr = $this->reconEntity->getUtr();

        if ((empty($currentUtr) === true) and (empty($utr) === false))
        {
            $this->updateUtrMetric();
        }

        $this->reconEntity->setUtr($utr);
    }

    protected function updateUtrMetric()
    {
        // Batch created_at is the tentative time at which an attempt was initiated
        $batchCreatedAt = $this->reconEntity->batchFundTransfer->getCreatedAt();

        $timeTaken = intval((Carbon::now(Timezone::IST)->getTimestamp() - $batchCreatedAt) / 60);

        $this->trace->histogram(
            Metric::ATTEMPTS_TIME_FOR_UTR_MINUTES,
            $timeTaken,
            [
                Metric::CHANNEL => $this->reconEntity->getChannel(),
                Metric::SOURCE_TYPE => $this->reconEntity->getSourceType()
            ]);
    }

    protected function updateSourceEntity()
    {
        $utr = $this->reconEntity->getUtr();

        $remarks = $this->reconEntity->getRemarks();

        $this->reconEntity->source->setUtr($utr);

        $this->reconEntity->source->setRemarks($remarks);

        $this->repo->saveOrFail($this->reconEntity->source);
    }

    /**
     * trims the value and checks for empty.
     * ensures empty strings are considered as null
     *
     * @param string $key
     *
     * @return null|string
     */
    protected function getNullOnEmpty(string $key)
    {
        $value = trim($this->row[$key] ?? null);

        return (empty($value) === true) ? null : $value;
    }
}
