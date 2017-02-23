<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Gateway\Base;

class Action extends Base\Action
{
    const DEBIT_WALLET      = 'debit_wallet';
    const OTP_GENERATE      = 'otp_generate';
    const OTP_RESEND        = 'otp_resend';
    const CHECK_BALANCE     = 'check_balance';
    const OTP_SUBMIT        = 'otp_submit';
    const GET_BALANCE       = 'get_balance';
    const TOPUP_WALLET      = 'topup_wallet';
    const TOPUP_REDIRECT    = 'topup_redirect';
}
