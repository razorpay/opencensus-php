<?php

namespace RZP\Gateway\Wallet\Freecharge;

use RZP\Gateway\Wallet\Freecharge;
use RZP\Gateway\Base;

class Action extends Base\Action
{
    const DEBIT_WALLET      = 'debit_wallet';
    const OTP_GENERATE      = 'otp_generate';
    const OTP_SUBMIT        = 'otp_submit';
    const GET_BALANCE       = 'get_balance';
    const TOPUP_WALLET      = 'topup_wallet';
}
