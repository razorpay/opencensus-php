<?php

namespace RZP\Gateway\CardlessEmi;

class ResponseFields
{
    const TOKEN                 = 'token';
    const EXPIRY                = 'expiry';
    const CURRENCY              = 'currency';
    const AMOUNT                = 'amount';
    const PAYMENT_ID            = 'rzp_payment_id';
    const PAYMENT               = 'payment';
    const PROVIDER_PAYMENT_ID   = 'provider_payment_id';
    const STATUS                = 'status';
    const STATUS_CODE           = 'status_code';
    const ERROR_CODE            = 'error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const ACCOUNT_EXISTS        = 'account_exists';
    const EMI_PLANS             = 'emi_plans';
    const LOAN_URL              = 'loan_agreement';
    const PROVIDER_REFUND_ID    = 'provider_refund_id';
    const REFUND                = 'refund';
    const CHECKSUM              = 'checksum';
    const ENTITY                = 'entity';
    const REDIRECT_URL          = 'redirection_url';
    const EXTRA                 = 'extra';

    const EPAYLATER_ERROR_CODE          = 'errorCode';
    const EPAYLATER_ERROR_DESCRIPTION   = 'errorDescription';
    const REDIRECT_URL_EARLYSALARY      = 'redirect_url';
}
