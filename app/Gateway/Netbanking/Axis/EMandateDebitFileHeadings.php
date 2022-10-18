<?php

namespace RZP\Gateway\Netbanking\Axis;

class EMandateDebitFileHeadings
{
    // Request file headings
    const PAYMENT_ID                  = 'INVOICE_NO';
    const DEBIT_DATE                  = 'BILL_DEBIT_DATE';
    const GATEWAY_MERCHANT_ID         = 'COMPANY_CODE';
    const CUSTOMER_UID                = 'CUSTOMER_UID';
    const CUSTOMER_NAME               = 'CUSTOMER_NAME';
    const DEBIT_ACCOUNT               = 'DEBIT_ACCOUNT';
    const AMOUNT                      = 'DEBIT_BILL_AMOUNT';
    const ADDITIONAL_INFO_1           = 'Additional Info 1';
    const ADDITIONAL_INFO_2           = 'Additional Info 2';
    const UNDERLYING_REFERENCE_NUMBER = 'underlying reference no';
    const CUSTOMER_UID_1              = 'CUSTOMER_UID_1';
    const RAZORPAY_CODE               = 'RAZORPAY_CODE';
    const RAZOR_DATE                  = 'RAZOR_DATE';
}
