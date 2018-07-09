<?php

class RequestFields
{
    const ACTION                        = 'fldTxnId';      // purchase or verify
    const CLIENT_CODE                   = 'fldClientCode';
    const CLIENT_ACCOUNT                = 'fldClientAccount';
    const MERCHANT_CODE                 = 'fldMerchCode';
    const CURRENCY                      = 'fldTxnCurr';
    const AMOUNT                        = 'fldTxnAmt';
    const SERVICE_CHARGE                = 'fldTxnScAmt';
    const PAYMENT_ID                    = 'fldMerchRefNbr';
    const SUCCESS_STATIC_FLAG           = 'fldSucStatFlg';
    const FAILURE_STATIC_FLAG           = 'fldFailStatFlg';
    const DATE                          = 'fldDatTimeTxn';
}