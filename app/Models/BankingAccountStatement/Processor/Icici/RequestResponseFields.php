<?php

namespace RZP\Models\BankingAccountStatement\Processor\Icici;

class RequestResponseFields
{
    // Request Fields
    const ENTITIES                 = 'entities';
    const ATTEMPT                  = 'attempt';
    const FROM_DATE                = 'from_date';
    const TO_DATE                  = 'to_date';
    const CONFLG                   = 'conflg';
    const SOURCE_ACCOUNT           = 'source_account';
    const ACCOUNT_NUMBER           = 'account_number';
    const CREDENTIALS              = 'credentials';
    const CORP_ID                  = 'corp_id';
    const USER_ID                  = 'user_id';
    const AGGR_ID                  = 'aggr_id';
    const URN                      = 'urn';
    const ACCOUNT_STATEMENT_APIKEY = 'accountStatementApiKey';
    const LAST_TRANSACTION         = 'last_transaction';
    const LASTTRID                 = 'lasttrid';
    const MERCHANT_ID              = 'merchant_id';

    // response fields
    const DATA              = 'data';
    const ACCOUNT_NO        = 'ACCOUNTNO';
    const AGGR_ID_RESPONSE  = 'AGGR_ID';
    const CORP_ID_RESPONSE  = 'CORP_ID';
    const LASTTRID_RESPONSE = 'LASTTRID';
    const RESPONSE          = 'RESPONSE';
    const RECORD            = 'Record';
    const AMOUNT            = 'AMOUNT';
    const BALANCE           = 'BALANCE';
    const CHEQUENO          = 'CHEQUENO';
    const REMARKS           = 'REMARKS';
    const TRANSACTION_ID    = 'TRANSACTIONID';
    const TRANSACTION_DATE  = 'TXNDATE';
    const TYPE              = 'TYPE';
    const VALUEDATE         = 'VALUEDATE';
    const URN_RESPONSE      = 'URN';
    const USER_ID_RESPONSE  = 'USER_ID';


    // fields fetched from config
    const AGGR_ID_CONFIG                   = 'aggr_id';
    const ACCOUNT_STATEMENT_API_KEY_CONFIG = 'beneficiary_api_key';
}
