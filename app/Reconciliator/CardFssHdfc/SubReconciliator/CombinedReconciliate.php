<?php

namespace RZP\Reconciliator\CardFssHdfc\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\CardFssHdfc\Reconciliate as CardFssReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const BLACKLISTED_COLUMNS = [
        PaymentReconciliate::COLUMN_CARD_HOLDER_NAME,
    ];

    //
    // This is needed to identify if the row is payment or refund.
    //
    protected $validTransactionType = ['Purchase', 'Credit'];

    protected function getReconciliationTypeForRow($row)
    {
        //
        // Identifies if row type is payment or refund.
        //
        $reconType = null;

        //
        // Check which of the key columns is set in the row,
        // accordingly we return the recon type
        // e.g., if 'transaction_type' is set and the value is in ['Purchase', 'Credit'], then it means its a payment row
        //       if 'action_code' is set and the value is in ['Purchase', 'Credit'], then it means its a refund row
        //
        // Assumptions: There won't be a case when a payment row's 'transaction_type' column is set to 'Credit' .
        //              There won't be a case when a refund row's 'action_code' column is set to 'Purchase' .
        //

        $entityColumn = array_first(array_keys(CardFssReconciliate::KEY_COLUMN_NAMES),
                                    function ($keyColumn) use ($row)
                                    {
                                        return ((isset($row[$keyColumn]) === true) and
                                                (in_array($row[$keyColumn], $this->validTransactionType, true) === true));
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
