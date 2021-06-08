<?php

namespace RZP\Gateway\Netbanking\Hdfc;

class EMandateDebitFileHeadings
{
    // Request file headings
    const SR                  = 'Sr';
    const TRANSACTION_REF_NO  = 'Transaction_Ref_No';
    const SUB_MERCHANT_NAME   = 'Sub-merchant Name';
    const MANDATE_ID          = 'Mandate ID';
    const ACCOUNT_NO          = 'Account_NO';
    const AMOUNT              = 'Amount';
    const SIP_DATE            = 'SIP_Date';
    const FREQUENCY           = 'Frequency';
    const FROM_DATE           = 'FROM_DATE';
    const TO_DATE             = 'TO_DATE';


    // Additional headings in response file
    const STATUS              = 'Status';
    const REJECTION_REMARKS   = 'Remark';
    const NARRATION           = 'Narration';
}
