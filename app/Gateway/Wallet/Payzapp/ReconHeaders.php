<?php

namespace RZP\Gateway\Wallet\Payzapp;

class ReconHeaders
{
    const TERMINAL_ID               = 'TERMINAL ID';
    const MERCHANT_NAME             = 'MERCHANT NAME';
    const TRANSACTION_TYPE          = 'TRANSACTION TYPE';
    const CARD_NUMBER               = 'CARD NUMBER';
    const GROSS_AMT                 = 'GROSS AMT';
    const COMMISSION_AMT            = 'COMMISSION AMT';
    const CGST                      = 'CGST';
    const SGST                      = 'SGST';
    const IGST                      = 'IGST';
    const UTGST                     = 'UTGST';
    const NET_AMT                   = 'NET AMT';
    const TRAN_DATE                 = 'TRAN DATE';
    const AUTH_CODE                 = 'AUTH CODE';
    const TRACK_ID                  = 'TRACK ID';
    const PG_TXN_ID                 = 'PG TXN ID';
    const PG_SALE_ID                = 'PG SALE ID';
    const CREDIT_DEBIT_CARD_FLAG    = 'CREDIT/DEBIT CARD FLAG';
    const GSTN                      = 'GSTN';
    const INVOICE_NUMBER            = 'Invoice_number';
    const CGST_PERCENTAGE           = 'CGST%';
    const SGST_PERCENTAGE           = 'SGST%';
    const IGST_PERCENTAGE           = 'IGST%';
    const UTGST_PERCENTAGE          = 'UTGST%';
    const CGSTCESS1                 = 'CGSTCESS1';
    const CGSTCESS2                 = 'CGSTCESS2';
    const CGSTCESS3                 = 'CGSTCESS3';
    const SGSTCESS1                 = 'SGSTCESS1';
    const SGSTCESS2                 = 'SGSTCESS2';
    const SGSTCESS3                 = 'SGSTCESS3';
    const IGSTCESS1                 = 'IGSTCESS1';
    const IGSTCESS2                 = 'IGSTCESS2';
    const IGSTCESS3                 = 'IGSTCESS3';
    const UTGSTCESS1                = 'UTGSTCESS1';
    const UTGSTCESS2                = 'UTGSTCESS2';
    const UTGSTCESS3                = 'UTGSTCESS3';

    //Rename CGST%, SGST% so that they don't override cgst after normalization
    const CGST_PERCENTAGE_RENAME    = 'CGST_PERCENTAGE';
    const SGST_PERCENTAGE_RENAME    = 'SGST_PERCENTAGE';
    const IGST_PERCENTAGE_RENAME    = 'IGST_PERCENTAGE';
    const UTGST_PERCENTAGE_RENAME   = 'UTGST_PERCENTAGE';
}
