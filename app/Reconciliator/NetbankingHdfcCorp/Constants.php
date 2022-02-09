<?php

namespace RZP\Reconciliator\NetbankingHdfcCorp;

class Constants
{
    const COLUMN_MERCHANT_CODE  = 'merchant_code';

    const CLIENT_CODE           = 'client_code';

    const COLUMN_CURRENCY       = 'currency_code';

    const COLUMN_PAYMENT_AMOUNT = 'transaction_amount';

    const COLUMN_FEE            = 'service_change_amount';

    const COLUMN_PAYMENT_ID     = 'merchant_reference_no';

    const BANK_PAYMENT_ID       = 'bank_reference_no';

    const STATUS_CODE           = 'status';

    const COLUMN_PAYMENT_DATE   = 'transaction_date';

    const ERROR_DESCRIPTION     = 'error_message';

    const PAYMENT_COLUMN_HEADERS = [
        self::COLUMN_MERCHANT_CODE,
        self::CLIENT_CODE,
        self::COLUMN_CURRENCY,
        self::COLUMN_PAYMENT_AMOUNT,
        self::COLUMN_FEE,
        self::COLUMN_PAYMENT_ID,
        self::STATUS_CODE,
        self::BANK_PAYMENT_ID,
        self::COLUMN_PAYMENT_DATE,
        self::ERROR_DESCRIPTION,
    ];

    const REFUND_FILE_HEADERS = [
        'Sr.No.',
        'Refund Id',
        'Bank Id',
        'Merchant Name',
        'Txn Date',
        'Refund Date',
        'Bank Merchant Code',
        'Bank Ref No.',
        'PGI Reference No.',
        'Txn Amount (Rs Ps)',
        'Refund Amount (Rs Ps)',
        'Bank Account No.',
        'Bank Pay Type',
        'PGI Bank Id',
        'Txn Currency Code'
    ];

    const CLAIM_FILE_HEADERS = [
        'Sr No.',
        'Line No.',
        'Record Line',
        'PGIRefNo',
        'BankRefNo',
        'TxnAmount',
        'TxnDate',
        'BillerId',
        'MeBankId',
        'AuthStatus'
    ];
}
