<?php

namespace RZP\Gateway\CardlessEmi\ErrorCodes;

class ErrorCodes
{
    const USER_DNE                = 'User Does Not Exist With Provider';
    const INV_TOKEN               = 'Token provided is invalid';
    const INV_MERCHANT_NAME       = 'Merchant name is invalid';
    const INV_EMI_PLAN_ID         = 'Emi plan selected is invalid';
    const MIN_AMT_REQ             = 'Minimum amount required for transaction';
    const MAX_AMT_LMT             = 'Maximum amount limit exhausted';
    const PAYMENT_TIMED_OUT       = 'Payment timed out';
    const PAYMENT_CANCELLED       = 'Payment cancelled by user';
    const PAYMENT_FAILED_PARTNER  = 'Payment failed by partner due to some internal error';
    const CREDIT_LIMIT_EXHAUSTED  = 'Credit Limit of customer has exhausted';
    const INV_CAPTURE_AMT         = 'Capture Amount greater than Authorized Amount';
}
