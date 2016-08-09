<?php

namespace RZP\Gateway\Wallet\Freecharge;

class Status
{
    const REFUND_FAILED             = 'Failed';
    const REFUND_INITIATED          = 'Initiated';
    const REFUND_SUCCESS            = 'Success';

    const TRANSACTION_INITIATED     = 'Initiated';
    const TRANSACTION_SUCCESS       = 'Completed';
    const TRANSACTION_PENDING       = 'Pending';
    const TRANSACTION_FAILED        = 'Failed';

    const OTP_SENT                  = 'VERIFY';
    // User does not exist, freecharge asks to redirect to create account.
    const OTP_REDIRECT              = 'REDIRECT';
}
