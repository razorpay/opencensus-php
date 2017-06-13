<?php

namespace RZP\Gateway\Upi\Icici;

class Fields
{
    const BANK_RRN                  = 'BankRRN';
    const MERCHANT_ID               = 'merchantId';
    const MERCHANT_TRAN_ID          = 'merchantTranId';
    const MERCHANT_NAME             = 'merchantName';
    const MESSAGE                   = 'message';
    const NOTE                      = 'note';
    const ONLINE_REFUND             = 'onlineRefund';
    const ORIGINAL_BANK_RRN         = 'OriginalBankRRN';
    // notice the small case o
    const ORIGINAL_BANK_RRN_REQ     = 'originalBankRRN';
    const ORIGINAL_MERCHANT_TRAN_ID = 'originalmerchantTranId';
    const AMOUNT                    = 'amount';
    const COLLECT_BY_DATE           = 'collectByDate';
    const BILL_NUMBER               = 'billNumber';
    const PAYEE_VA                  = 'payeeVA';
    const PAYER_AMOUNT              = 'PayerAmount';
    const PAYER_MOBILE              = 'PayerMobile';
    const PAYER_NAME                = 'PayerName';
    const PAYER_VA                  = 'PayerVA';
    const PAYER_VA_REQ              = 'payerVa';
    const REFUND_ID                 = 'refund_id';
    const REFUND_AMOUNT             = 'refundAmount';
    const RESPONSE                  = 'response';
    const SUBMERCHANT_ID            = 'subMerchantId';
    const SUBMERCHANT_NAME          = 'subMerchantName';
    const SUCCESS                   = 'success';
    const STATUS                    = 'status';
    const TERMINAL_ID               = 'terminalId';
    const TXN_COMPLETION_DATE       = 'TxnCompletionDate';
    const TXN_INIT_DATE             = 'TxnInitDate';
    const TXN_STATUS                = 'TxnStatus';
}
