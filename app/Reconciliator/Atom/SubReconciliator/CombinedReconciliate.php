<?php

namespace RZP\Reconciliator\Atom\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_TXN_STATE = 'txn_state';
    const COLUMN_REFUND_STATUS = 'refund_status';

    const PAYMENT_TXN = 'Sale';
    const REFUND_TXN   = 'Full Refund';
    const REFUND_TXN_2 = 'Partial Refund';

    const COLUMN_MERCHANT_NAME  = 'merchant_name';
    const COLUMN_MERCHANT_ID    = 'merchant_id';
    const COLUMN_TXN_DATE       = 'txn_date';

    // Refund cannot be reconned as we dont the refund id
    // in the recon file
    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PAYMENT_TXN   => BaseReconciliate::PAYMENT,
    ];

    protected function getReconciliationTypeForRow(&$row)
    {
        if (isset($row[self::COLUMN_TXN_STATE]) === false)
        {
            return null;
        }

        $txnType = $row[self::COLUMN_TXN_STATE];

        if (isset(self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType]) === true)
        {
            return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType];
        }
        else
        {

            //
            // Sometimes we get extra comma in merchant_name and that causes the columns to
            // shift, thus we get txn_id in txt_state column. Earlier we used to return NA
            // when txtType not in TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP list and that
            // caused that row to get bypassed and such row did not even get logged under
            // recon_file_row.
            //
            return $this->getReconTypeForSpecialCase($row);
        }
    }

    protected function getReconTypeForSpecialCase(&$row)
    {
        $txtState = $row[self::COLUMN_TXN_STATE];

        $reconType = self::NA;

        //
        // Check if it is a case of extra comma in merchant_name,
        // which causes txn_state column to shift to txn_date column.
        // If yes : then left shift the column values to fix the row
        // and then get the recon type
        //
        $txnDate = $row[self::COLUMN_TXN_DATE];

        if (isset(self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnDate]) === true)
        {
            $this->trace->info(
                TraceCode::RECON_ALERT,
                [
                    'info_code' => Base\InfoCode::RECON_ROW_INVALID_FORMAT_FOUND,
                    'gateway'   => $this->gateway,
                    'txn_type'  => $txtState,
                    'row'       => $row,
                ]);

            $this->leftShiftRowValues($row);

            $txtState = $row[self::COLUMN_TXN_STATE];

            $reconType = self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txtState] ?? self::NA;
        }

        return $reconType;
    }

    protected function leftShiftRowValues(&$row)
    {
        $row[self::COLUMN_MERCHANT_ID] = $row[self::COLUMN_MERCHANT_NAME] . ',' . $row[self::COLUMN_MERCHANT_ID];

        $columns = array_keys($row);
        $values = array_values($row);

        array_shift($values);

        $row = array_combine_pad($columns, $values);
    }
}
