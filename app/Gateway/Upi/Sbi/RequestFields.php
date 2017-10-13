<?php

namespace RZP\Gateway\Upi\Sbi;

class RequestFields
{
    const PG_MERCHANT_ID    = 'pgMerchantId';
    const PSP_REFERENCE_NO  = 'pspRefNo';
    const TRANSACTION_NOTE  = 'transactionNote';
    const REQUEST_INFO      = 'requestInfo';
    const PAYER_TYPE        = 'payerType';
    const VIRTUAL_ADDRESS   = 'virtualAddress';
    const EXPIRY_TIME       = 'expiryTime';
    const AMOUNT            = 'amount';
    const ADDITIONAL_INFO   = 'addInfo';
    const ADDITIONAL_INFO9  = 'addInfo9';
    const ADDITIONAL_INFO10 = 'addInfo10';
    const REQUEST_MESSAGE   = 'requestMsg';
}