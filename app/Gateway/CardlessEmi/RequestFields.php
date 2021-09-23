<?php

namespace RZP\Gateway\CardlessEmi;

class RequestFields
{
    const MOBILE_NUMBER          = 'mobile_number';
    const AMOUNT                 = 'amount';
    const MERCHANT_ID            = 'merchant_id';
    const TXN_ID                 = 'client_txn_id';
    const MERCHANT_CATEGORY_CODE = 'mcc';
    const MERCHANT_NAME          = 'merchant_name';
    const MERCHANT_WEBSITE       = 'merchant_website';
    const MERCHANT_MCC           = 'merchant_mcc';
    const TOKEN                  = 'token';
    const ACTION                 = 'action';
    const CURRENCY               = 'currency';
    const PAYMENT_ID             = 'rzp_payment_id';
    const EMI_DURATION           = 'duration';
    const BILLING_LABEL          = 'billing_label';
    const MERCHANT               = 'merchant';
    const USER_IP                = 'user_ip';
    const REFUND_ID              = 'rzp_refund_id';
    const CALLBACK_URL           = 'callback_url';
    const CONTACT                = 'contact';
    const CHECKSUM               = 'checksum';
    const TRANSACTION_TYPE       = 'txn_type';
    const BANK_CODE              = 'bank_code';
    const REDIRECT_URL           = 'redirect_url';
    const RECEIPT                = 'receipt';
}
