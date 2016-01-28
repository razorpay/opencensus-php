<?php

namespace Gateway\Mobikwik\Gateway;

use Gateway\Base;

class Action extends Base\Action
{
    const CHECK_USER = 'existingusercheck';
    const OTP_GENERATE = 'otp_generate';
}