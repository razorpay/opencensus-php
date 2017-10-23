<?php

namespace RZP\Gateway\Upi\Sbi;

class RequestFields
{
    const PG_MERCHANT_ID             = 'pgMerchantId';

    /**
     * Razorpay Payment ID
     */
    const PSP_REFERENCE_NO           = 'pspRefNo';

    /**
     * RRN Number, which is unique in the UPI platform - mapped to Customer Reference ID
     */
    const CUSTOMER_REFERENCE_NO      = 'custRefNo';

    const TRANSACTION_NOTE           = 'transactionNote';
    const REQUEST_INFO               = 'requestInfo';
    const PAYER_TYPE                 = 'payerType';
    const VIRTUAL_ADDRESS            = 'virtualAddress';
    const EXPIRY_TIME                = 'expiryTime';
    const AMOUNT                     = 'amount';
    const ADDITIONAL_INFO            = 'addInfo';
    const ADDITIONAL_INFO1           = 'addInfo1';
    const ADDITIONAL_INFO9           = 'addInfo9';
    const ADDITIONAL_INFO10          = 'addInfo10';
    const REQUEST_MESSAGE            = 'requestMsg';
}