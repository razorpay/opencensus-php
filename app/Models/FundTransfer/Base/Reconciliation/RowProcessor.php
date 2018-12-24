<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt\Lock;
use RZP\Models\FundTransfer\Attempt\Metric;

abstract class RowProcessor extends Base\Core
{
    use DispatchesEvents;

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

    /**
     * This is called for both file based and api based..
     *
     * @return null
     */
    public function process()
    {
        //
        // We can take a lock only after processRow runs
        // since only then we get the fta ID.
        // This applies for both file based and API based.
        //

        $this->processRow();

        if (empty($this->reconEntityId) === false)
        {
            $this->fetchEntities();
        }

        if (empty($this->reconEntity) === true)
        {
            $this->trace->error(TraceCode::SETTLEMENT_RECONCILIATION_SKIPPED,
                [
                    'row'           => $this->row,
                    'parsed_data'   => $this->parsedData,
                ]);

            return null;
        }

        // We are accepting a dummy collection because
        // that's how the function was written.
        (new Lock)->acquireLockAndProcessAttempt(
            $this->reconEntity,
            function(Base\PublicCollection $collection)
            {
                $this->updateEntities();
            });

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

        $reconEntityBatchId = $this->reconEntity->getBatchFundTransferId();

        if ($sourceBatchId !== $reconEntityBatchId)
        {
            $this->trace->info(
                TraceCode::FTA_RECON_SOURCE_UPDATE_SKIPPED,
                [
                    'source_batch_id'       => $sourceBatchId,
                    'recon_entity_batch_id' => $reconEntityBatchId
                ]);

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
        $source = $this->reconEntity->source;

        if ($source->getEntity() === Constants\Entity::PAYOUT)
        {
            (new Payout\Core)->updateWithDetailsBeforeFtaRecon($source, $this->reconEntity);

            return;
        }

        $utr = $this->reconEntity->getUtr();

        $remarks = $this->reconEntity->getRemarks();

        $source->setUtr($utr);

        $source->setRemarks($remarks);

        $this->trace->info(
            TraceCode::FTA_RECON_SOURCE_UPDATED,
            [
                'source_id'         => $source->getId(),
                'fta_id'            => $this->reconEntityId,
                'source_original'   => $source->getOriginalAttributesAgainstDirty(),
                'source_dirty'      => $source->getDirty(),
            ]);

        $this->repo->saveOrFail($source);
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

    public function verifyRow()
    {
        $this->processRow();

        $this->trace->info(TraceCode::FTA_RECON_PARSED_DATA, ['parsed_data' => $this->parsedData]);

        if (empty($this->reconEntityId) === false)
        {
            $this->fetchEntities();
        }

        if (empty($this->reconEntity) === true)
        {
            $this->trace->error(TraceCode::FTA_VERIFICATION_SKIPPED,
                [
                    'row'           => $this->row,
                    'parsed_data'   => $this->parsedData,
                ]);

            return null;
        }

        $this->updateVerificationResult();

        return $this->reconEntity;
    }

    protected function updateVerificationResult()
    {
        $this->updateVerifyReconEntity();

        $sourceBatchId = $this->reconEntity->source->getBatchFundTransferId();

        if ($sourceBatchId !== $this->reconEntity->getBatchFundTransferId())
        {
            return;
        }

        $this->updateSourceEntity();
    }
}
