<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants;

class XLSXHeaders
{
    const SHEET_TITLE              = 'ACCOUNT DETAILS';
    const SHEET_SUB_TITLE          = 'General Details';
    const ACCOUNT_NAME             = 'Account Name:';
    const ACCOUNT_NUMBER           = 'Account Number:';
    const STATEMENT_PERIOD         = 'Period:';

    const NO                      = 'no';
    const TRANSACTION_ID          = 'Transaction ID';
    const VALUE_DATE              = 'Value Date';
    const TRANSACTION_POSTED_DATE = 'Txn Posted Date';
    const CHEQUE_NO               = 'ChequeNo';
    const DESCRIPTION             = 'Description';
    const CR_DR                   = 'Cr/Dr';
    const TRANSACTION_AMOUNT      = 'Transaction Amount(INR)';
    const AVAILABLE_BALANCE       = 'Available Balance(INR)';
    const ITEM_DATE_FORMAT        = 'd/m/Y';
    const POSTED_DATE_FORMAT      = 'd/m/y g:i:s A';

    const STATEMENT_SUMMARY               = 'Statement Summary';
    const OPENING_BALANCE                 = 'opening_balance';
    const CLOSING_BALANCE                 = 'closing_balance';
    const EFFECTIVE_BALANCE               = 'effective_balance';
    const DEBIT_COUNT                     = 'debit_count';
    const CREDIT_COUNT                    = 'credit_count';
    const STATEMENT_GENERATED_DATE        = 'statement_generated_date';
    const STATEMENT_GENERATED_DATE_FORMAT = 'd/m/Y g:i A';
}
