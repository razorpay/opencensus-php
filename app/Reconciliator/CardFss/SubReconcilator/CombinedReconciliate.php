<?php

namespace RZP\Reconciliator\CardFss;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\CardFss\Reconciliate as CardFssReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    protected function getReconciliationTypeForRow($row)
    {
        //
        // Identifies if row type is payment or refund.
        //
        $reconType = null;

        //
        // Check which of the key columns is set in the row,
        // accordingly we return the recon type
        // e.g., if 'transaction_type' is set, it means its a payment row
        //       if 'action_code' is set, it means its a refund row
        //
        $entityColumn = array_first(array_keys(CardFssReconciliate::KEY_COLUMN_NAMES),
                                                    function ($keyColumn) use ($row)
                                                    {
                                                        return (isset($row[$keyColumn]) === true);
                                                    });

        if ($entityColumn === null)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => Base\InfoCode::UNKNOWN_RECON_TYPE,
                    'message'       => 'Unable to identify the row recon type using key columns for the gateway',
                    'row_details'   => $row,
                    'gateway'       => $this->gateway
                ]);

            $reconType = self::NA;
        }
        else
        {
            $reconType = CardFssReconciliate::KEY_COLUMN_NAMES[$entityColumn];
        }

        return $reconType;
    }
}
