<?php

namespace RZP\Gateway\Netbanking\Canara;

class ResponseFields
{
    const ACTION                        = 'fldTxnId';      // purchase or verify
    const CLIENT_CODE                   = 'ClientCode';
    const MERCHANT_CODE                 = 'MerchantCode';
    const CURRENCY                      = 'TxnCurrency';
    const AMOUNT                        = 'TxnAmount';
    const SERVICE_CHARGE                = 'TxnScAmount';
    const PAYMENT_ID                    = 'MerchRefNo';
    const ACK_STATIC_FLAG               = 'AckStaticFlag';
    const RESPONSE_STATIC_FLAG          = 'ResponseStaticFlag';
    const DATE                          = 'Date';
    const BANK_REFERENCE_NUMBER         = 'fldBankRefNo';
    const MESSAGE                       = 'Message';
    const CHECKSUM                      = 'checksum';
    const ENCRYPTED_DATA                = 'encdata';

    const VER_CLIENT_ACCOUNT            = 'ClientAccount';
    const PUR_DATE                      = 'fldOrgDatTimeTxn';    // date of original payment entity
    const VER_PAYMENT_ID                = 'MerchRefNbr';
    const VER_AMOUNT                    = 'TxnAmt';
    const VER_BANK_REFERENCE_NUMBER     = 'BankRefNo';
    const RETURN_CODE                   = 'ReturnCode';
    const VERIFY_STATUS                 = 'VerifyStatus';
    const STATUS                        = 'Status';
}
