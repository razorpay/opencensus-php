<?php

namespace RZP\Gateway\NpciPaySecure;

class Fields
{
    // Request Fields
    const TOKEN                         = 'Token';
    const VERSION                       = 'Version';
    const CALLER_ID                     = 'CallerID';
    const USER_CREDENTIALS              = 'UserCredentials';
    const USER_ID                       = 'UserID';
    const USER_PASSWORD                 = 'Password';
    const PARTNER_ID                    = 'partner_id';
    const MERCHANT_PASSWORD             = 'merchant_password';
    const CARD_BIN                      = 'card_bin';

    // Response Fields
    const STATUS                = 'status';
    const ERROR_CODE            = 'errorcode';
    const ERROR_MESSAGE         = 'errormsg';
    const QUALIFIED_INTERNETPIN = 'qualified_internetpin';
    const IMPLEMENTS_REDIRECT   = 'implements_redirect';
}
