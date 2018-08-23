<?php

namespace RZP\Gateway\Netbanking\Axis;

class EMandateDebitReconFileHeadings
{
    const HEADING_PAYMENT_ID        = 'Txn Reference';
    const HEADING_DEBIT_DATE        = 'Execution Date';
    const HEADING_MERCHANT_ID       = 'Originator ID';
    const HEADING_BANK_REF_NUMBER   = 'Mandate Ref/UMR';
    const HEADING_CUSTOMER_NAME     = 'Customer Name';
    const HEADING_DEBIT_ACCOUNT     = 'Customer Bank Account';
    const HEADING_DEBIT_AMOUNT      = 'Paid In Amount';
    const HEADING_MIS_INFO3         = 'MIS_INFO3';
    const HEADING_MIS_INFO4         = 'MIS_INFO4';
    const HEADING_FILE_REF          = 'File_Ref';
    const HEADING_STATUS            = 'Status';
    const HEADING_REMARK            = 'Return reason';
    const HEADING_RECORD_IDENTIFIER = 'Record Identifier';
}
