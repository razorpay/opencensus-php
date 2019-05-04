<?php

namespace RZP\Gateway\Hitachi;

class ResponseFields
{
    const TRANSACTION_TYPE     = 'pTranType';
    const MERCHANT_ID          = 'pMercID';
    const MERCHANT_REF_NUMBER  = 'pMerchantRefNr';
    const MERCHANT_REFERENCE   = 'pTranID';
    const ENROLLED             = 'pEnrolled';
    const BANK_URL             = 'pURL';
    const ACCOUNT_ID           = 'pAccountId';
    const PAREQ                = 'pPAREQ';
    const CARD_NUMBER          = 'pPAN';
    const PMD                  = 'pMD';
    const PARES                = 'PaRes';
    const MD                   = 'MD';
    const AUTH_STATUS          = 'pAuthStatus';
    const ECI                  = 'pECI';
    const XID                  = 'pXID';
    const CAVV2                = 'pCAVV2';
    const UCAF                 = 'pUCAF';
    const ALGORITHM            = 'pALGO';
    const TRANSACTION_AMOUNT   = 'pTranAmount';
    const AUTH_ID              = 'pAuthID';
    const RETRIEVAL_REF_NUM    = 'pRRN';
    const RESPONSE_CODE        = 'pRespCode';
    const REQUEST_ID           = 'pRequestId';
    const STATUS               = 'pStatus';
    const CURRENCY             = 'pCurrencyCode';
    const FAILED_RESPONSE_CODE = 'response_code'; // Will be returned in some cases like Format Error (error_code : 30)

    //Bharat Qr Fields
    const MASKED_CARD_NUMBER = 'F002';
    const CARD_NETWORK       = 'F003';
    const AMOUNT             = 'F004';
    const AUDIT_TRACE_NUMBER = 'F011';
    const TRANSACTION_TIME   = 'F012';
    const TRANSACTION_DATE   = 'F013';
    const RRN                = 'F037';
    const AUTHORIZATION_ID   = 'F038';
    const STATUS_CODE        = 'F039';
    const TERMINAL_ID        = 'F041';
    const MID                = 'F042';
    const MERCHANT_NAME      = 'F043';
    const TERMINAL_ID_DESC   = 'F102';
    const PURCHASE_ID        = 'PurchaseID';
    const SENDER_NAME        = 'SenderName';
    const CHECKSUM           = 'CheckSum';
}
