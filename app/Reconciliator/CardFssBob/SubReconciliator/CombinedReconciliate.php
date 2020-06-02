<?php

namespace RZP\Reconciliator\CardFssBob\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

/**
 * Class CombinedReconciliate
 * @see https://docs.google.com/spreadsheets/d/1T8SHup7_Jgzk2jYYS_D3x--zcU8nrGwyq_0M7UGi3ro/edit?usp=sharing
 */
class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const PURCHASE_TXN = 'purchase';
    const REFUND_TXN   = 'refund';

    const BLACKLISTED_COLUMNS = [];

    //
    // Threshold to identify that this is new file
    // format which comes with 129 columns.
    //
    const NEW_FILE_COLUMN_COUNT_THRESHOLD = 100;

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PURCHASE_TXN => Base\Reconciliate::PAYMENT,
        self::REFUND_TXN   => Base\Reconciliate::REFUND
    ];

    /**
     * Column 'Transaction Type' in excel indicates whether
     * txn is payment or refund
     *
     * For payment, value is 'Purchase'
     * For refund, value is 'Refund'
     *
     * @param array $row
     * @return string|null if invalid transaction type is passed
     */
    protected function getReconciliationTypeForRow($row)
    {
        $columnTransactionType = array_first(ReconciliationFields::TRANSACTION_TYPE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $transactionType = strtolower($row[$columnTransactionType]);

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null ;
    }

    /**
     * Currently we are getting 2 types of MIS file. One has 129
     * columns and other has 68 columns. Earlier we used to get
     * 68 column file only.
     * This is creating is issue for Looker dashboard as the output
     * file for this new MIS is not mapping to existing looker schema,
     * which was build for 68 column file.
     *
     * So here we convert this new file rows to old file format rows,
     * by removing unnecessary columns and changing column order.
     * @param $row
     */
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
                // See which column in the array is Set, take that value
                $columnSet = null;

                $columnSet = array_first($column, function ($col) use ($originalRow)
                {
                    return (isset($originalRow[$col]) === true);
                });

                // Here 0th index corresponds to old file column
                // Though index 1 can also be used.
                $row[$column[0]] = $originalRow[$columnSet] ?? '';
            }
        }

        // We have this idempotent id set when recon request coming from batch service
        if (empty($originalRow[Base\Constants::IDEMPOTENT_ID]) === false)
        {
            $row[Base\Constants::IDEMPOTENT_ID] = $originalRow[Base\Constants::IDEMPOTENT_ID];
        }
    }

    //
    // Return true if this is new format file with exact
    // column count is 129. Though not putting this number
    // in condition to allow some flexibility, in case few
    // columns get removed in future from this new format file
    //
    protected function shouldModifyRow(array $row)
    {
        return (count($row) > self::NEW_FILE_COLUMN_COUNT_THRESHOLD);
    }
}
