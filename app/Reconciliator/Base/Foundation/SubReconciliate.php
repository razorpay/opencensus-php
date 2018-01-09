<?php

namespace RZP\Reconciliator\Base\Foundation;

use App;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\RequestProcessor;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class SubReconciliate extends Base\Core
{
    const TOTAL_SUMMARY     = 'total_summary';
    const FAILURES_SUMMARY  = 'failures_summary';
    const SUCCESSES_SUMMARY = 'successes_summary';

    /**
     * The list of payments/refunds attempted to reconcile.
     *
     * @var array
     */
    protected $total = [];

    /**
     * All the payments/refunds which were successfully reconciled.
     * These include payments/refunds for which we were able to successfully record the gateway
     * service tax and gateway fees in db.
     *
     * @var array
     */
    protected $successes = [];

    /**
     * All the payments/refunds which could not be reconciled.
     * These include the payments/refunds for which we could not record the gateway service tax
     * and gateway fees in db.
     *
     * @var array
     */
    protected $failures = [];

    /**
     * Decides whether to mark the row as success / failure if it is unprocessable.
     * By default, we want to mark such a row as failed, hence setting it to true.
     *
     * @var boolean
     */
    protected $failUnprocessedRow = true;

    public function getTotal(): array
    {
        return $this->total;
    }

    public function getSuccesses(): array
    {
        return $this->successes;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Contains details for files, email or manual details
     * Manual details is being used to check for force_update
     *
     * @var array
     */
    protected $extraDetails = [];
    /**
     * This method resets any instance attributes which could have been set during
     * processing reconciliation of a particular row. In certain cases like combined
     * reconciliate the  subreconciliator instances are reused so we don't want
     * instance attributes to persist between specific runs. Implementation to be
     * provided by child classes
     */
    public function resetProcessingAttributes()
    {
        $this->extraDetails = [];

        $this->setFailUnprocessedRow(true);
    }

    /**
     * This is the start of the actual reconciliation.
     * Reconciliation is done for each row in the file content.
     *
     * @param array $fileContents
     * @return array
     */
    public function startReconciliation(array $fileContents)
    {
        $this->setExtraDetails($fileContents[Orchestrator::EXTRA_DETAILS]);
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($row)
            {
                $this->runReconciliate($row);
            });
        }

        return $this->getSummary();
    }

    /**
     * Runs the same reconciliation process, though here we always update the batch with recon
     * summary, regardless of any exception thrown during the process.
     *
     * @param array          $fileContents      file contents to be processed
     * @param Batch\Entity   $batch             Batch entity for the current run
     */
    public function startReconciliationV2(array $fileContents, Batch\Entity $batch)
    {
        $this->setExtraDetails($fileContents[Orchestrator::EXTRA_DETAILS]);
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        try
        {
            foreach ($fileContents as $row)
            {
                $this->repo->transactionOnLiveAndTest(function() use ($row)
                {
                    $this->runReconciliate($row);
                });
            }
        }
        finally
        {
            $this->updateBatchWithSummary($batch);
        }
    }

    protected function persistReconciledAt($entity)
    {
        $transaction = $entity->transaction;
        $time = time();
        $transaction->setReconciledAt($time);
        $transaction->saveOrFail();

        // Increment the success count for the summary.
        $this->setSummaryCount(self::SUCCESSES_SUMMARY, $entity->getKey());
    }

    protected function persistGatewaySettledAt(Base\Entity $entity, array $rowDetails)
    {
        $gatewaySettledAt = $rowDetails[BaseReconciliate::GATEWAY_SETTLED_AT];

        if (empty($gatewaySettledAt) === true)
        {
            return;
        }

        $transaction = $entity->transaction;

        // Since we might be running this before the actual recon process,
        // it's possible that the transaction for this particular entity is
        // not even present.
        // We will be running this in the last step of recon process too.
        // So, it will get recorded in that step, if not in the first step.
        if ($transaction === null)
        {
            return;
        }

        // If this is already recorded, no need to record it again.
        // This could have got recorded in pre-recon-process itself.
        if ($transaction->getGatewaySettledAt() !== null)
        {
            return;
        }

        $transaction->setGatewaySettledAt($gatewaySettledAt);

        $transaction->saveOrFail();
    }

    protected function checkIfAlreadyReconciled($entity)
    {
        $transaction = $entity->transaction;

        if ($transaction === null)
        {
            // If transaction is not present, it would mean that
            // the reconciliation did not happen for this.
            return false;
        }

        return $entity->transaction->isReconciled();
    }

    protected function setSummaryCount(string $type, string $identifier)
    {
        switch($type)
        {
            case self::TOTAL_SUMMARY:
                $this->total[] = $identifier;
                break;
            case self::FAILURES_SUMMARY:
                $this->failures[] = $identifier;
                break;
            case self::SUCCESSES_SUMMARY:
                $this->successes[] = $identifier;
                break;
            default:
                throw new LogicException(
                    'Should not have reached here. Unknown type given for summary.',
                    null,
                    ['entity_id' => $identifier]
                );
        }
    }

    protected function getSummary()
    {
        $summary = [
            'total_count' => count($this->total),
            'failure_count' => count($this->failures),
            'success_count' => count($this->successes),
        ];

        if (empty($this->failures) === false)
        {
            $summary['failures'] = $this->failures;
        }

        return $summary;
    }

    /**
     * Not all gateways provide us with gateway_settled_at.
     * Hence, we send back null for these gateways.
     *
     * @param $row
     * @return null
     */
    protected function getGatewaySettledAt(array $row)
    {
        return null;
    }

    /**
     * Method check if FORCE_UPDATE for argument fields
     * is passed in MANUAL_DETAILS.
     *
     * @param string $field
     * @return bool
     */
    protected function shouldForceUpdate(string $field) : bool
    {
        $forceUpdateFields = $this->extraDetails
            [RequestProcessor\Base::INPUT_DETAILS]
            [RequestProcessor\Base::FORCE_UPDATE] ?? [];

        return in_array($field, $forceUpdateFields, true);
    }

    public function setExtraDetails(array $extraDetails)
    {
        $this->extraDetails = $extraDetails;
    }

    /**
     * @param  Batch\Entity $batch  Batch entity for the current reconciliation request
     */
    protected function updateBatchWithSummary(Batch\Entity $batch)
    {
        //
        // We are not updating the batch total count here, as that is already done
        // when we parse the file, before processing has begn. This is because recon
        // files usually have extra rows, and hence updating the total_count here
        // will not reflect the actual number of rows in the file.
        //
        $batch->setSuccessCount(count($this->successes));

        $batch->setFailureCount(count($this->failures));
    }

    /**
     * Rows for which the corresponding entities, have already been marked as reconciled,
     * we add it to the list of successfully processed rows.
     *
     * @param  string $entityId
     */
    protected function handleAlreadyReconciled(string $entityId)
    {
        $this->setSummaryCount(self::SUCCESSES_SUMMARY, $entityId);
    }

    protected function setFailUnprocessedRow(bool $failUnprocessedRow)
    {
        $this->failUnprocessedRow = $failUnprocessedRow;
    }

    /**
     * For certain rows, where we are not able to successfully identify the payment
     * or refund entity to reconcile, we mark the row processing as success or failure
     * depending on the specific gateway's reconciliator.
     *
     * @param  array  $row
     */
    protected function handleUnprocessedRow(array $row)
    {
        $this->trace->info(TraceCode::RECON_UNPROCESSED_ROW,
            [
                'gateway' => get_called_class(),
                'row'     => $row,
            ]);

        if ($this->failUnprocessedRow === true)
        {
            return $this->setSummaryCount(self::FAILURES_SUMMARY, head($row));
        }

        return $this->setSummaryCount(self::SUCCESSES_SUMMARY, head($row));
    }
}
