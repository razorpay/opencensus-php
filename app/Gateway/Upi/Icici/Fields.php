<?php

namespace RZP\Gateway\Upi\Icici;

class Fields
{
    // Amount and note are lowercase
    // despite being uppercase in docs
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
    //The field 'Amount' (capital A) was introduced during Migration.
    const AMOUNT_NEW                = 'Amount';
    const COLLECT_BY_DATE           = 'collectByDate';
    const BILL_NUMBER               = 'billNumber';
    const PAYEE_VA                  = 'payeeVA';
    const VERIFY_AMOUNT             = 'amount';
    const PAYER_AMOUNT              = 'PayerAmount';
    const PAYER_MOBILE              = 'PayerMobile';
    const PAYER_ACCOUNT             = 'payerAccount';
    const PAYER_IFSC                = 'payerIFSC';
    const PAYER_NAME                = 'PayerName';
    const PAYER_VA                  = 'PayerVA';
    const VALIDATE_PAYER_ACCOUNT    = 'ValidatePayerAccFlag';
    const VALIDATE_PAYER_ACCOUNT2   = 'validatePayerAccFlag';
    const VERIFY_PAYER_VA           = 'payerVA';
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
    const RESPONSE_CODE             = 'ResponseCode';
    const UMN                       = 'UMN';
    const REMARK                    = 'Remark';
    const PAYER_ACCOUNT_TYPE        = 'PayerAccountType';
    const VALIDITY_END_DATE_TIME    = 'validityEndDateTime';
    const UPDATE                    = 'update';
}
