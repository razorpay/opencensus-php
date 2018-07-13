<?php

namespace RZP\Gateway\Netbanking\Canara;

class ResponseFields
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
    const BANK_REFERENCE_NUMBER         = 'fldBankRefNo';
    const MESSAGE                       = 'Message';

    const VER_CLIENT_ACCOUNT            = 'ClientAccount';
    const PUR_DATE                      = 'fldOrgDatTimeTxn';    // date of original payment entity
    const VER_PAYMENT_ID                = 'MerchRefNbr';
    const VER_AMOUNT                    = 'TxnAmt';
    const VER_BANK_REFERENCE_NUMBER     = 'BankRefNo';
    const RETURN_CODE                   = 'ReturnCode';
    const VERIFY_STATUS                 = 'VerifyStatus';

}