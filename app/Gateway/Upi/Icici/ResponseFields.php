<?php

namespace RZP\Gateway\Upi\Icici;

class ResponseFields
{
    const RESPONSE                  = "response";
    const MERCHANT_ID               = "merchantId";
    const SUBMERCHANT_ID            = "subMerchantId";
    const TERMINAL_ID               = "terminalId";
    const SUCCESS                   = "success";
    const MESSAGE                   = "message";
    const MERCHANT_TRAN_ID          = "merchantTranId";
    const BANK_RRN                  = "BankRRN";
    const ORIGINAL_BANK_RRN         = "OriginalBankRRN";
    const PAYER_AMOUNT              = "PayerAmount";
    const PAYER_MOBILE              = "PayerMobile";
    const PAYER_NAME                = "PayerName";
    const PAYER_VA                  = "PayerVA";
    const TXN_COMPLETION_DATE       = "TxnCompletionDate";
    const TXN_INIT_DATE             = "TxnInitDate";
    const TXN_STATUS                = "TxnStatus";
}
