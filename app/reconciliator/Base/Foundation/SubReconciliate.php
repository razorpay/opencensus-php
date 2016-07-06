<?php

namespace Reconciliator\Base\Foundation;

use RZP\Models\Payment;
use App;

class SubReconciliate
{
    protected function persistReconciledAt($entity)
    {
        $transaction = $entity->transaction;
        $time = time();
        $transaction->setReconciledAt($time);
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
}