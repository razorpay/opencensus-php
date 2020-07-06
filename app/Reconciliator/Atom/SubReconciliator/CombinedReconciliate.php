<?php

namespace RZP\Reconciliator\Atom\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_TXN_STATE = 'txn_state';

    const PAYMENT_TXN  = 'sale';
    const REFUND_TXN   = 'refund';
    const REFUND_TXN_2 = 'full refund';
    const REFUND_TXN_3 = 'partial refund';

    const COLUMN_MERCHANT_NAME  = 'merchant_name';
    const COLUMN_MERCHANT_ID    = 'merchant_id';
    const COLUMN_TXN_DATE       = 'txn_date';

    // Allowing refund recon for entries for which only 1
    // refund entry is present against given payment_id and amount.
    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PAYMENT_TXN   => BaseReconciliate::PAYMENT,
        self::REFUND_TXN    => BaseReconciliate::REFUND,
        self::REFUND_TXN_2  => BaseReconciliate::REFUND,
    ];

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow(&$row)
    {
        if (isset($row[self::COLUMN_TXN_STATE]) === false)
        {
            return null;
        }

        $txnState = strtolower($row[self::COLUMN_TXN_STATE]);

        if (isset(self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnState]) === true)
        {
            return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnState];
        }
        else
        {

            //
            // Sometimes we get extra comma in merchant_name and that causes the columns to
            // shift, thus we get atom_txn_id in txn_state column. Earlier we used to return NA
            // when txnState not in TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP list and that
            // caused that row to get bypassed and such row did not even get logged under
            // recon_file_row.
            //
            return $this->getReconTypeForSpecialCase($row, $txnState);
        }
    }

    protected function getReconTypeForSpecialCase(&$row, $txnState)
    {
        $reconType = self::NA;

        //
        // Check if it is a case of extra comma in merchant_name,
        // which causes txn_state column to shift to txn_date column.
        // If yes : then left shift the column values to fix the row
        // and then get the recon type
        //
        $txnDate = strtolower($row[self::COLUMN_TXN_DATE] ?? null);

        if (isset(self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnDate]) === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code' => Base\InfoCode::RECON_ROW_INVALID_FORMAT_FOUND,
                    'gateway'   => $this->gateway,
                    'txn_type'  => $txnState,
                    'row'       => $row,
                ]);

            $this->leftShiftRowValues($row);

            // $row has been modified now, get the $txnState again
            $txnState = strtolower($row[self::COLUMN_TXN_STATE]);

            $reconType = self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnState] ?? self::NA;
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

    protected function modifyRowIfNeeded(&$row)
    {
        if ($this->shouldModifyRow($row) === false)
        {
            return;
        }

        $originalRow = $row;
        $row = [];

        foreach (ReconciliationFields::OLD_TO_NEW_FILE_MAPPING as $column)
        {
            if (is_array($column) === false)
            {
                $row[$column] = $originalRow[$column] ?? '';
            }
            else
            {
                // See which column in the array is set, take that value
                $columnSet = Base\SubReconciliator\Helper::getArrayFirstValue($originalRow, $column);

                // Here 0th index corresponds to old file column.
                $row[$column[0]] = $columnSet ?? '';
            }
        }

        // We have this idempotent id set when recon request coming from batch service
        if (empty($originalRow[Base\Constants::IDEMPOTENT_ID]) === false)
        {
            $row[Base\Constants::IDEMPOTENT_ID] = $originalRow[Base\Constants::IDEMPOTENT_ID];
        }
    }

    /**
     * To check if the file is in new format, we check
     * presence of 2 crucial columns for recon, and
     * then proceed to change it to older format, so
     * that reconciliation output file remain same.
     *
     * @param $row
     * @return bool
     */
    protected function shouldModifyRow($row)
    {
        return (isset($row[ReconciliationFields::TXN_STATE[1]]) and
                isset($row[ReconciliationFields::ATOM_TXN_ID[1]]));
    }
}
