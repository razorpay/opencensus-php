<?php

namespace RZP\Reconciliator\Base\Foundation;

use RZP\Exception\LogicException;
use RZP\Models\Payment;
use App;

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
    protected $total;

    /**
     * All the payments/refunds which were successfully reconciled.
     * These include payments/refunds for which we were able to successfully record the gateway
     * service tax and gateway fees in db.
     *
     * @var $successes
     */
    protected $successes;

    /**
     * All the payments/refunds which could not be reconciled.
     * These include the payments/refunds for which we could not record the gateway service tax
     * and gateway fees in db.
     *
     * @var $failures
     */
    protected $failures;

    protected function persistReconciledAt($entity)
    {
        $transaction = $entity->transaction;
        $time = time();
        $transaction->setReconciledAt($time);
        $transaction->saveOrFail();

        // Increment the success count for the summary.
        $this->setSummaryCount(self::SUCCESSES_SUMMARY, $entity->getKey());
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
}