<?php

namespace RZP\Reconciliator\EmandateAxis\SubReconciliator;

use RZP\Reconciliator\Base;

class EmandateDebitReconciliate extends Base\SubReconciliator\EmandateDebitReconciliate
{
    const COLUMN_PAYMENT_ID        = 'txn_reference';
    const COLUMN_DEBIT_DATE        = 'execution_date';
    const ORIGINATOR_ID            = 'originator_id';
    const COLUMN_GATEWAY_TOKEN     = 'mandate_refumr';
    const COLUMN_CUSTOMER_NAME     = 'customer_name';
    const COLUMN_DEBIT_ACCOUNT     = 'customer_bank_account';
    const COLUMN_AMOUNT            = 'paid_in_amount';
    const COLUMN_MIS_INFO3         = 'mis_info3';
    const COLUMN_MIS_INFO4         = 'mis_info4';
    const COLUMN_FILE_REF          = 'file_ref';
    const COLUMN_STATUS            = 'status';
    const COLUMN_REASON            = 'return_reason';
    const COLUMN_RECORD_IDENTIFIER = 'record_identifier';

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'rejected';

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            return $row[self::COLUMN_PAYMENT_ID];
        }

        return null;
    }
}
