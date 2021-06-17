<?php

namespace RZP\Gateway\Netbanking\Hdfc;

class EMandateRegisterFileHeadings
{
    // Request file headings
    const CLIENT_NAME                   = 'Client Name';
    const SUB_MERCHANT_NAME             = 'Sub-merchant Name';
    const CUSTOMER_NAME                 = 'Customer Name';
    const CUSTOMER_ACCOUNT_NUMBER       = 'Customer Account Number';
    const AMOUNT                        = 'Amount';
    const AMOUNT_TYPE                   = 'Amount Type';
    const START_DATE                    = 'Start_Date';
    const END_DATE                      = 'End_Date';
    const FREQUENCY                     = 'Frequency';
    const MANDATE_ID                    = 'Mandate ID';
    const MERCHANT_UNIQUE_REFERENCE_NO  = 'Merchant Unique Reference No';
    const MANDATE_SERIAL_NUMBER         = 'Mandate Serial Number';
    const MERCHANT_REQUEST_NO           = 'Merchant Request No';

    // Additional headings in Response file
    const STATUS                        = 'STATUS';
    const REMARK                        = 'REMARK';
}
