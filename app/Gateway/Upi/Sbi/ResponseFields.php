<?php

namespace RZP\Gateway\Upi\Sbi;

class ResponseFields
{
    const RESPONSE               = 'resp';
    const API_RESPONSE           = 'apiResp';
    const PSP_REFERENCE_NO       = 'pspRefNo';
    const UPI_TRANS_REFERENCE_NO = 'upiTransRefNo';
    const NPCI_TRANSACTION_ID    = 'npciTransId';
    const CUSTOMER_REFERENCE_NO  = 'custRefNo';
    const AMOUNT                 = 'amount';
    const TRANSACTION_AUTH_DATE  = 'txnAuthDate';
    const STATUS                 = 'status';
    const STATUS_DESCRIPTION     = 'statusDesc';
    const ADDITIONAL_INFO        = 'addInfo';
    const PAYER_VPA              = 'payerVPA';
    const PAYEE_VPA              = 'payeeVPA';
    const PG_MERCHANT_ID         = 'pgMerchantId';
}