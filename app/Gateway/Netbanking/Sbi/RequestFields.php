<?php

namespace RZP\Gateway\Netbanking\Sbi;

class RequestFields
{
    // Authorize request fields
    const REF_NO        = 'ref_no';      //razorpay payment id

    const AMOUNT        = 'amount';     // amount

    const PAYMENT_ID    = 'payment_id'; // payment_id

    const REDIRECT_URL  = 'Redt_url';

    const CANCEL_URL    = 'Cncl_url';

    const CHECKSUM      = 'checkSum';

    // Emandate request fields
    const MANDATE_HOLDER_NAME     = 'Mandate_Holder_Name';     // Name of Mandate holder
    const MANDATE_PAYMENT_ID      = 'Payment_ID';
    const TOKEN_ID                = 'Token_ID';
    const MANDATE_RETURN_URL      = 'Return_URL';
    const MANDATE_ERROR_URL       = 'Error_URL';
    const MANDATE_AMOUNT          = 'Mandate_Amount';
    const MANDATE_TXN_AMOUNT      = 'Txn_Amount';
    const DEBIT_ACCOUNT_NUMBER    = 'Debit_Account_No';        // Account no of Mandate holder
    const MANDATE_START_DATE      = 'Mandate_Start_date';      // First Collection date(DD/MM/YYYY)
    const MANDATE_END_DATE        = 'Mandate_End_date';	       // Last Collection date(DD/MM/YYYY)
    const FREQUENCY               = 'Mandate_Frequency';	   // Frequency of Mandate execution.
                                                               // (Daily,Weekly,Monthly,Bi-Monthly,Quartely,Yearly,NONE)
                                                               // NONE is for “As and when required”
    const MANDATE_MODE            = 'Mode';
    const MANDATE_AMOUNT_TYPE     = 'Amount_Type';	           // Amount Type - Possible Values will be
                                                               // “F-Fixed and M-Maximum".

    const MANDATE_VERIFY_TXN_AMOUNT = 'Txn_Amt';

    // Authorize query params
    const ENCDATA      = 'encdata';       // encrypted request string

    const MERCHANT_CODE = 'merchant_code'; // gateway merchant id

    const ACCOUNT_NUMBER = 'debit_accountno';

    // Verify request fields
    const BANK_REF_NO = 'bank_ref_no';
}
