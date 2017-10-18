<?php

namespace RZP\Reconciliator\Base\Foundation;

use App;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Exception\LogicException;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class SubReconciliate
{
    const TOTAL_SUMMARY     = 'total_summary';
    const FAILURES_SUMMARY  = 'failures_summary';
    const SUCCESSES_SUMMARY = 'successes_summary';

    /**
     * The total number of payments/refunds attempted to reconcile.
     *
     * @var $total
     */
    protected $total = [];

    /**
     * All the payments/refunds which were successfully reconciled.
     * These include payments/refunds for which we were able to successfully record the gateway
     * service tax and gateway fees in db.
     *
     * @var $successes
     */
    protected $successes = [];

    /**
     * All the payments/refunds which could not be reconciled.
     * These include the payments/refunds for which we could not record the gateway service tax
     * and gateway fees in db.
     *
     * @var $failures
     */
    protected $failures = [];

    public function getTotal()
    {
        return $this->total;
    }

    public function getSuccesses()
    {
        return $this->successes;
    }

    public function getFailures()
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

    protected function setSummaryCount($type, $entityId)
    {
        switch($type)
        {
            case self::TOTAL_SUMMARY:
                $this->total[] = $entityId;
                break;
            case self::FAILURES_SUMMARY:
                $this->failures[] = $entityId;
                break;
            case self::SUCCESSES_SUMMARY:
                $this->successes[] = $entityId;
                break;
            default:
                throw new LogicException(
                    'Should not have reached here. Unknown type given for summary.',
                    null,
                    ['entity_id' => $entityId]
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
        $forceUpdateFields = $this->extraDetails[Orchestrator::INPUT_DETAILS][Orchestrator::FORCE_UPDATE] ?? [];

        return in_array($field, $forceUpdateFields, true);
    }

    public function setExtraDetails(array $extraDetails)
    {
        $this->extraDetails = $extraDetails;
    }

    protected function updateBatchWithSummary(Batch\Entity $batch)
    {
        $batch->setSuccessCount(count($this->successes));

        $batch->setFailureCount(count($this->failures));
    }
}
