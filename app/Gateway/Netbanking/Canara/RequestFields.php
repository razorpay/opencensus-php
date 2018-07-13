<?php

namespace RZP\Gateway\Netbanking\Canara;

class RequestFields
{
    const MODE_OF_TRANSACTION           = 'fldTxnId';             // purchase or verify
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


    const VER_DATE                      = 'fldDatTimeTxn';       // verify start date
    const PUR_DATE                      = 'fldOrgDatTimeTxn';    // date of original payment entity

}