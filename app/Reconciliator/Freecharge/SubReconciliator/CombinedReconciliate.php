<?php

namespace RZP\Reconciliator\Freecharge\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_TRANSACTION_TYPE = 'transaction_type';

    const TXN_TYPE_PAYMENT = 'CREDIT';

    const TXN_TYPE_PAYMENT_REVERSAL = 'DEBIT';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::TXN_TYPE_PAYMENT          => BaseReconciliate::PAYMENT,
        self::TXN_TYPE_PAYMENT_REVERSAL => BaseReconciliate::REFUND
    ];

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = $row[self::COLUMN_TRANSACTION_TYPE];

        // check if transactionType is one of the valid types that we expect
        if (in_array(
                    $transactionType,
                    array_keys(self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP),
                    true) === true)
        {
            return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType];
        }

        //
        // If transactionType is empty (not defined in TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP),
        // We return back NA, since we these are expected and we don't care about them.
        // But if it's not empty AND not present in the map, we don't want to fail the row, but instead
        // raise an alert and return back NA and continue with the normal processing.
        // If we send `null`, the row is going to get marked as failed and an exception is going to be thrown.
        //

        if (empty($transactionType) === false)
        {
            $message = 'Unexpected reconciliation type for the row in combined reconciliation.';

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_PARSE_ERROR,
                    'message'           => $message,
                    'transaction_type'  => $transactionType,
                    'gateway'           => $this->gateway
                ]);
        }

        return self::NA;
    }
}
