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
}
