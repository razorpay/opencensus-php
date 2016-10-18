<?php

namespace RZP\Reconciliator;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function reconciliateCancelledTransactions($gateway)
    {
        if ($gateway !== Payment\Gateway::BILLDESK)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY,
                'gateway',
                $gateway);
        }

        $transactions = $this->repo->transaction->getCancelledBilldeskTransactions();

        $transactionCore = new Transaction\Core;

        $successCount = $failureCount = 0;

        $failures = [];

        foreach ($transactions as $transaction)
        {
            $success = $transactionCore->updateReconciliationData($transaction);

            if ($success === true)
            {
                $successCount += 1;
            }
            else
            {
                $failures[] = $transaction->getId();
                $failureCount += 1;
            }
        }

        $data = [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failures'      => $failures,
        ];

        $this->trace->info(
            TraceCode::RECONCILE_CANCELLED_TRANSACTIONS,
            $data
        );

        return $data;
    }
}