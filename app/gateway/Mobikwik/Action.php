<?php

namespace Gateway\Mobikwik;

use Gateway\Base;

class Action extends Base\Action
{
    // const CHECK_USER    = 'check_user';
    const OTP_GENERATE  = 'otp_generate';
    const OTP_SUBMIT    = 'otp_submit';
    const CREATE_WALLET_USER = 'create_wallet_user';
}