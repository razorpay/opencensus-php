<?php

namespace RZP\Gateway\Wallet\Freecharge;

class Status
{
    const TRANSACTION_INITIATED = 'INITIATED';
    const TRANSACTION_SUCCESS   = 'SUCCESS';
    const TRANSACTION_PENDING   = 'PENDING';
    const TRANSACTION_FAILED    = 'FAILED';

    const OTP_SENT              = 'VERIFY';
    // User does not exist, freecharge asks to redirect to create account.
    const OTP_REDIRECT          = 'REDIRECT';

    const DEBIT_SUCCESS         = 'COMPLETED';
    const DEBIT_FAILED          = 'FAILED';

    const TOPUP_SUCCESS         = 'COMPLETED';
    const TOPUP_FAILED          = 'FAILED';
}
