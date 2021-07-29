<?php

namespace RZP\Models\BankingAccountStatement\Processor\Rbl;

class RequestResponseFields
{
    const ID                                = 'id';
    const ATTEMPT                           = 'attempt';
    const FROM_DATE                         = 'from_date';
    const TO_DATE                           = 'to_date';
    const TRANSACTION_TYPE                  = 'transaction_type';
    const SOURCE_ACCOUNT                    = 'source_account';
    const ACCOUNT_NUMBER                    = 'account_number';
    const CREDENTIALS                       = 'credentials';
    const CLIENT_ID                         = 'client_id';
    const CLIENT_SECRET                     = 'client_secret';
    const CORP_ID                           = 'corp_id';
    const AUTH_USERNAME                     = 'auth_username';
    const AUTH_PASSWORD                     = 'auth_password';

    const LAST_TRANSACTION                  = 'last_transaction';
    const AMOUNT                            = 'amount';
    const CURRENCY                          = 'currency';
    const POSTED_DATE                       = 'posted_date';
    const TRANSACTION_DATE                  = 'transaction_date';
    const TRANSACTION_ID                    = 'transaction_id';
    const SERIAL_NUMBER                     = 'serial_number';
    const BALANCE                           = 'balance';
    const NEXT_KEY                          = 'next_key';

    const DATA                              = 'data';
    const HEADER                            = 'Header';
    const BODY                              = 'Body';
    const STATUS                            = 'Status';
    const PAYMENT_GENERIC_RESPONSE          = 'PayGenRes';
    const TRANSACTION_DETAILS               = 'transactionDetails';
    const HAS_MORE_DATA                     = 'hasMoreData';
    const TRANSACTION_ID_RESPONSE           = 'txnId';
    const TRANSACTION_SUMMARY               = 'transactionSummary';
    const TRANSACTION_AMOUNT                = 'txnAmt';
    const AMOUNT_VALUE                      = 'amountValue';
    const CURRENCY_CODE                     = 'currencyCode';
    const TRANSACTION_TYPE_RESPONSE         = 'txnType';
    const TRANSACTION_DESCRIPTION           = 'txnDesc';
    const TRANSACTION_DATE_RESPONSE         = 'txnDate';
    const TRANSACTION_POSTED_DATE           = 'pstdDate';
    const TRANSACTION_BALANCE               = 'txnBalance';
    const TRANSACTION_SERIAL_NUMBER         = 'txnSrlNo';
    const INSTRUMENT_ID                     = 'instrumentId';
    const TRANSACTION_CATEGORY              = 'txnCat';

    const FETCH_ACCOUNT_STATEMENT_RESPONSE  = 'FetchAccStmtRes';
    const FILE_DATA                         = 'File_Data';
    const ACCOUNT_STATEMENT_DATA            = 'AccStmtData';
    const TOTAL_BUCKET_NUMBER               = 'total_bucket';
    const STATUS_DESCRIPTION                = 'Status_Desc';

}
