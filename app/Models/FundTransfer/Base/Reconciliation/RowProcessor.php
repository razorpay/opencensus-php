<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Carbon\Carbon;
use Monolog\Logger;

use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\FundTransfer\Attempt\Type;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Constants\HyperTrace;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Attempt\Metric;
use RZP\Models\FundTransfer\Attempt\Alerts;

abstract class RowProcessor extends Base\Core
{
    use DispatchesEvents;

    protected $row;

    protected $reconcileFile;

    protected $version;

    protected $parsedData;

    /**
     * @var Attempt\Entity
     */
    protected $reconEntity   = null;

    protected $reconEntityId = null;

    abstract protected function processRow();

    abstract protected function updateReconEntity();

    /**
     * Returns the value to be updated as UTR
     */
    abstract protected function getUtrToUpdate();

    public function __construct($row, $reconcileFile = null)
    {
        parent::__construct();

        $this->row = $row;

        $this->reconcileFile = $reconcileFile;
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
        (new Attempt\Lock)->acquireLockAndProcessAttempt(
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

    protected function raiseUtrUpdateEvent($utr)
    {
        $batchFta = $this->reconEntity->batchFundTransfer;

        $batchFtaId = null;

        //BatchFTA can be null in test mode
        if (empty($batchFta) === false)
        {
            $batchFtaId = $batchFta->getId();
        }

        $customProperties = [
            'channel'                           => $this->reconEntity->getChannel(),
            'fund_transfer_attempt_purpose'     => $this->reconEntity->getPurpose(),
            'fund_transfer_attempt_id'          => $this->reconEntity->getId(),
            'utr'                               => $utr,
            'batch_fund_transfer_attempt_id'    => $batchFtaId,
            'source_type'                       => $this->reconEntity->getSourceType(),
            'fund_transfer_attempt_mode'        => $this->reconEntity->getMode(),
            'fund_transfer_attempt_status'      => $this->reconEntity->getStatus(),
            'source_id'                         => $this->reconEntity->getSourceId(),
            'reconcile_file_name'               => $this->reconcileFile,
        ];

        $this->app['diag']->trackSettlementEvent(
            EventCode::FTA_UTR_UPDATED,
            null,
            null,
            $customProperties);
    }

    protected function updateEntities()
    {
        $this->updateReconEntity();
        $sourceBatchId = $this->reconEntity->source->getBatchFundTransferId();

        $reconEntityBatchId = $this->reconEntity->getBatchFundTransferId();

        if (($this->reconEntity->getSourceType() !== Type::REFUND) and ($sourceBatchId !== $reconEntityBatchId))
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

            $this->raiseUtrUpdateEvent($utr);
        }

        $this->reconEntity->setUtr($utr);
    }

    protected function updateUtrMetric()
    {
        // Batch created_at is the tentative time at which an attempt was initiated
        $batchCreatedAt = optional($this->reconEntity->batchFundTransfer)->getCreatedAt();

        if (empty($batchCreatedAt) === false)
        {
            $timeTaken = intval((Carbon::now(Timezone::IST)->getTimestamp() - $batchCreatedAt) / 60);

            $this->trace->histogram(
                Metric::ATTEMPTS_TIME_FOR_UTR_MINUTES,
                $timeTaken,
                [
                    Metric::CHANNEL => $this->reconEntity->getChannel(),
                    Metric::SOURCE_TYPE => $this->reconEntity->getSourceType()
                ]);
        }
        else
        {
            $this->trace->error(TraceCode::FTA_BATCH_FUND_TRANSFER_ABSENT,
                [
                    'recon_entity_id' => $this->reconEntity->getId(),
                ]);
        }
    }

    protected function updateSourceEntity()
    {
        $source = $this->reconEntity->source;

        $statusNamespace = $this->getStatusClass($this->reconEntity);

        $statusClass = new $statusNamespace;

        $requestFailure = $this->parsedData[Constants::REQUEST_FAILURE] ?? false;

        $isInternalError = $requestFailure or $statusClass::isCriticalError($this->reconEntity);

        $bankStatusCode = $this->reconEntity->getBankStatusCode();

        $publicErrorMessage = $statusClass::getPublicFailureReason($bankStatusCode);

        $ftaData = [
            'bank_account_id'   => $this->reconEntity->getBankAccountId(),
            'vpa_id'            => $this->reconEntity->getVpaId(),
            'merchant_id'       => $this->reconEntity->getMerchantId(),
            'fta_id'            => $this->reconEntity->getId(),
            'mode'              => $this->reconEntity->getMode(),
            'source_id'         => $source->getId(),
            'beneficiary_name'  => $this->parsedData[Constants::NAME_WITH_BENE_BANK],
            'remarks'           => $this->reconEntity->getRemarks(),
            'utr'               => $this->reconEntity->getUtr(),
            'fta_status'        => $this->reconEntity->getStatus(),
            'bank_status_code'  => $bankStatusCode,
            'internal_error'    => $isInternalError,
            'failure_reason'    => $publicErrorMessage,
        ];

        $batchFta = $this->reconEntity->batchFundTransfer;

        $batchFtaId = null;

        //BatchFTA can be null in test mode
        if (empty($batchFta) === false)
        {
            $batchFtaId = $batchFta->getId();
        }

        $customProperties = [
            'channel'                           => $this->reconEntity->getChannel(),
            'purpose'                           => $this->reconEntity->getPurpose(),
            'fund_transfer_attempt_id'          => $this->reconEntity->getId(),
            'batch_fund_transfer_attempt_id'    => $batchFtaId,
            'utr'                               => $this->reconEntity->getUtr(),
            'source_type'                       => $this->reconEntity->getSourceType(),
            'fund_transfer_attempt_mode'        => $this->reconEntity->getMode(),
            'fund_transfer_attempt_status'      => $this->reconEntity->getStatus(),
            'source_id'                         => $this->reconEntity->getSourceId(),
            'error_message'                     => $publicErrorMessage,
            'reconcileFile'                     => $this->reconcileFile
        ];

        $this->app['diag']->trackSettlementEvent(
            EventCode::FTA_DATA_UPDATED_FROM_REVERSE_FEED,
            null,
            null,
            $customProperties);

        $this->postFtaStatusProcess($source, $ftaData);
    }

    protected function postFtaStatusProcess($entity, array $ftaData)
    {
        try
        {
            $sourceType = $entity->getEntity();

            $sourceCoreClass = Entity::getEntityNamespace($sourceType) . '\\Core';

            $sourceCore = new $sourceCoreClass();

            if (method_exists($sourceCore, 'updateWithDetailsBeforeFtaRecon') === false)
            {
                return;
            }

            $sourceCore->updateWithDetailsBeforeFtaRecon($entity, $ftaData);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FTA_SOURCE_PROCESSING_FAILED,
                $ftaData
            );

            $this->trace->count(Metric::WEBHOOK_UPDATE_FAILURE_COUNT,
                                [
                                    'error' => $e->getMessage()
                                ]);
        }
    }

    protected function getStatusClass(Attempt\Entity $entity)
    {
        $channel = $entity->getChannel();

        if ($entity->hasVpa() === true)
        {
            return '\\RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\GatewayStatus';
        }

        return 'RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\Status';
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

        if (($this->reconEntity->getSourceType() !== Type::REFUND) and ($sourceBatchId !== $this->reconEntity->getBatchFundTransferId()))
        {
            return;
        }

        $this->updateSourceEntity();
    }

    /**
     * Gives request type for the given attempt.
     * Based on these attempts nodal config will be picked while making any request to bank
     *
     * @param Attempt\Entity $attempt
     * @return string
     */
    protected function getRequestType(Attempt\Entity $attempt): string
    {
        switch (true)
        {
            case $attempt->isOfBanking():
                return Attempt\Type::BANKING;

            case $attempt->isPennyTesting():
                return Attempt\Type::SYNC;

            default:
                return Attempt\Type::PRIMARY;
        }
    }
}
