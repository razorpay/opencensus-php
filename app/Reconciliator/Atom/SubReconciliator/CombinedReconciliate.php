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
    const REFUND_TXN_3 = 'Auto Reversal';

    const COLUMN_MERCHANT_NAME  = 'merchant_name';
    const COLUMN_MERCHANT_ID    = 'merchant_id';
    const COLUMN_TXN_DATE       = 'txn_date';

    // Refund cannot be reconned as we dont the refund id
    // in the recon file
    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PAYMENT_TXN   => BaseReconciliate::PAYMENT,
        self::REFUND_TXN    => self::NA,
        self::REFUND_TXN_2  => self::NA,
        self::REFUND_TXN_3  => self::NA,
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
        $txtType = $row[self::COLUMN_TXN_STATE];

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
                    'info_code' => Base\InfoCode::RECON_ROW_EXTRA_COMMA_FOUND,
                    'gateway'   => $this->gateway,
                    'txn_type'  => $txtType,
                    'row'       => $row,
                ]);

            $this->leftShiftRowValues($row);

            $txtType = $row[self::COLUMN_TXN_STATE];

            $reconType = self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txtType];
        }

        return $reconType;
    }

    protected function leftShiftRowValues(&$row)
    {
        $row[self::COLUMN_MERCHANT_ID] = $row[self::COLUMN_MERCHANT_NAME] . ',' . $row[self::COLUMN_MERCHANT_ID];

        $row['extra_temp_field'] = '';

        $columns = array_keys($row);

        // Now assign each column to the value of next column.
        for ($i = 0; $i < count($columns) - 1 ; $i++)
        {
            $currentColumnName = $columns[$i];

            $nextColumnName = $columns[$i + 1];

            $row[$currentColumnName] = $row[$nextColumnName];
        }

        unset($row['extra_temp_field']);
    }
}
