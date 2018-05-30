<?php

namespace RZP\Gateway\Netbanking\Rbl;

class ReconciliationFields
{
    const SERIAL_NO          = 'srno';
    const TRANSACTION_DATE   = 'transaction_datetime';
    const USER_ID            = 'user_id';
    const DEBIT_ACCOUNT      = 'debit_account';
    const CREDIT_ACCOUNT     = 'credit_account';
    const TRANSACTION_AMOUNT = 'debit_amount';
    const PGI_REFERENCE      = 'transaction_no';
    const BANK_REFERENCE     = 'merchant_ref_no';
    const MERCHANT_NAME      = 'merchant_name';
    const PGI_STATUS         = 'status';
    const ERROR_DESCRIPTION  = 'error_description';
    const TRANSACTION_STATUS = 'pg_status';
}
