<?php

namespace RZP\Gateway\Hitachi;

class RequestFields
{
    const TRANSACTION_TYPE          = 'pTranType';
    const MERCHANT_ID               = 'pMercID';
    const TERMINAL_ID               = 'pTermID';
    const CARD_NUMBER               = 'pPan';
    const MERCHANT_REF_NUMBER       = 'pMerchantRefNr';
    const TERM_URL                  = 'TermUrl';
    const PAREQ                     = 'PaReq';
    const PMD                       = 'pMD';
    const MD                        = 'MD';
    const PARES                     = 'pPaRes';
    const TRANSACTION_AMOUNT        = 'pTranAmount';
    const DISPLAY_AMOUNT            = 'pDisplayAmount';
    const TRANSACTION_TIME          = 'pTranTime';
    const TRANSACTION_DATE          = 'pTranDate';
    const EXPIRY_DATE               = 'pExpiryDate';
    const AUTH_STATUS               = 'pAuthStatus';
    const ECI                       = 'pECI';
    const XID                       = 'pXID';
    const CAVV2                     = 'pCAVV2';
    const UCAF                      = 'pUCAF';
    const ALGORITHM                 = 'pALGO';
    const CVV2                      = 'pCVV2';
    const REQUEST_ID                = 'pRequestId';
    const RETRIEVAL_REF_NUM         = 'pRRN';
    const CURRENCY_CODE             = 'pCurrencyCode';
    const DYNAMIC_MERCHANT_NAME     = 'pDBAName';
    const AUTH_ID                   = 'pAuthID';
    const MCC                       = 'pMCC';
    const MC_DS_TRANSACTION_ID      = 'pMCDSTransactionID';
    const MC_PROTOCOL_VERSION       = 'pMCProtocolVersion';
}
