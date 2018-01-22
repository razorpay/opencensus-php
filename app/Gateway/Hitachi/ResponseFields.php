<?php

namespace RZP\Gateway\Hitachi;

class ResponseFields
{
    const TRANSACTION_TYPE    = 'pTranType';
    const MERCHANT_ID         = 'pMercID';
    const MERCHANT_REF_NUMBER = 'pMerchantRefNr';
    const ENROLLED            = 'pEnrolled';
    const BANK_URL            = 'pURL';
    const ACCOUNT_ID          = 'pAccountId';
    const PAREQ               = 'pPAREQ';
    const CARD_NUMBER         = 'pPAN';
    const PMD                 = 'pMD';
    const PARES               = 'PaRes';
    const MD                  = 'MD';
    const AUTH_STATUS         = 'pAuthStatus';
    const ECI                 = 'pECI';
    const XID                 = 'pXID';
    const CAVV2               = 'pCAVV2';
    const UCAF                = 'pUCAF';
    const ALGORITHM           = 'pALGO';
    const TRANSACTION_AMOUNT  = 'pTranAmount';
    const AUTH_ID             = 'pAuthID';
    const RETRIEVAL_REF_NUM   = 'pRRN';
    const RESPONSE_CODE       = 'pRespCode';
    const REQUEST_ID          = 'pRequestId';
    const STATUS              = 'pStatus';

    //Bharat Qr Fields
    const F002        = 'F002';
    const F003        = 'F003';
    const F004        = 'F004';
    const F011        = 'F011';
    const F012        = 'F012';
    const F013        = 'F013';
    const F037        = 'F037';
    const F038        = 'F038';
    const F039        = 'F039';
    const F041        = 'F041';
    const F042        = 'F042';
    const F043        = 'F043';
    const F102        = 'F102';
    const PURCHASE_ID = 'PurchaseID';
    const SENDER_NAME = 'SenderName';
}
