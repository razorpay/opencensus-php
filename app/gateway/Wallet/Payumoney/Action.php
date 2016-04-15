<?php

namespace Gateway\Wallet\Payumoney;

use Gateway\Base;

class Action extends Base\Action
{
    const GENERATE_OTP  = 'generate_otp';
    const USE_WALLET    = 'use_wallet';
    const OTP_SUBMIT    = 'otp_submit';
    const GET_BALANCE   = 'get_balance';
    const CREATED       = 'created';
}