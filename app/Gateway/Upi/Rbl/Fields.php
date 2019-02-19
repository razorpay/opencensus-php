<?php

namespace RZP\Gateway\Upi\Rbl;

class Fields
{
    // for sessionToken Api request and response
    const CHANNEL_PARTNER_LOGIN_REQUEST     = 'channelpartnerloginreq';
    const USER_NAME                         = 'username';
    const PASSWORD                          = 'password';
    const BC_AGENT                          = 'bcagent';
    const SESSION_TOKEN                     = 'sessiontoken';
    const TIMEOUT                           = 'timeout';
    const STATUS                            = 'status';
    const CHANNEL_PARTNER_LOGIN_RESPONSE    = 'channelpartnerloginres';

    // for generateAuthToken Api request and response
    const GENERATE_AUTH_TOKEN_REQUEST       = 'generateauthtokenreq';
    const HEADER                            = 'header';
    const MERCHANT_ORGANIZATION_ID          = 'mrchOrgId';
    const AGGREGATOR_ID                     = 'aggrOrgId';
    const NOTE                              = 'note';
    const REF_ID                            = 'refId';
    const REF_URL                           = 'refUrl';
    const MOBILE                            = 'mobile';
    const GEO_CODE                          = 'geocode';
    const LOCATION                          = 'location';
    const IP                                = 'ip';
    const TYPE                              = 'type';
    const ID                                = 'id';
    const OS                                = 'os';
    const APP                               = 'app';
    const CAPABILITY                        = 'capability';
    const HMAC                              = 'hmac';
    const GENERATE_AUTH_TOKEN_RESPONSE      = 'generateauthtokenres';
    const TOKEN                             = 'token';

    // for get Transaction Id Api
    const GET_TRANSACTION_ID_REQUEST    = 'gettxnid';
    const GET_TRANSACTION_ID_RESPONSE   = 'RespGetTxnId';
    const DESCRIPTION                   = 'description';

    // for collect money API
    const COLLECT_REQUEST               = 'collectrequest';
    const PAYEE_ADDRESS                 = 'payeeaddress';
    const PAYER_ADDRESS                 = 'payeraddress';
    const PAYEE_NAME                    = 'payeename';
    const PAYER_NAME                    = 'payername';
    const AMOUNT                        = 'amount';
    const ORG_TRANSACTION_ID            = 'orgTxnId';
    const VALID_UPTO                    = 'validupto';
    const GATEWAY_TRANSACTION_ID        = 'txnId';
    const COLLECT_RESPONSE              = 'collectresponse';
    const RESULT                        = 'result';

    // for callback response
    const UPI_TRANSACTION_ID          = 'upi_txn_id';
    const CUSTOMER_REF                = 'custref';
    const TRANSACTION_STATUS          = 'transaction_status';
    const PAYER_VPA                   = 'payer_vpa';
    const PAYER_VERIFIED_NAME         = 'payer_verified_name';
    const PAYER_MOBILE                = 'payer_mobile';
    const PAYEE_VPA                   = 'payee_vpa';
    const PAYEE_VERIFIED_NAME         = 'payee_verified_name';
    const PAYEE_MOBILE                = 'payee_mobile';
    const MERCHANT_ID                 = 'mrchid';
    const AGGR_ID                     = 'aggrid';
    const TRANSACTION_DATE_TIME       = 'transaction_datetime';
    const REFID                       = 'RefID';
    const REFURL                      = 'RefURL';
    const UPI_PUSH_REQUEST            = 'UPI_PUSH_Request';
    const UPI_PUSH_RESPONSE           = 'UPI_PUSH_Response';
    const STATUS_CODE                 = 'statuscode';
    const NPCI_ERROR_CODE             = 'npci_error_code';

    // for verify request and response
    const ORG_TXN_ID_OR_REF_ID        = 'orgTxnIdorrefId';
    const FLAG                        = 'flag';
    const SEARCH_REQUEST              = 'searchrequest';
    const PAYER_ACCOUNT_NUMBER        = 'PayeraccountNumber';
    const PAYER_IFSC                  = 'payerifsc';
}
