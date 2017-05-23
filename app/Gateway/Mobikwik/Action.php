<?php

namespace RZP\Gateway\Mobikwik;

use RZP\Gateway\Base;

class Action extends Base\Action
{
    const CHECK_USER    = 'check_user';
    const OTP_GENERATE  = 'otp_generate';
    const OTP_SUBMIT    = 'otp_submit';
    const CREATE_USER   = 'create_user';
}
