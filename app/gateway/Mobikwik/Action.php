<?php

namespace Gateway\Mobikwik;

use Gateway\Base;

class Action extends Base\Action
{
    const CHECK_USER = 'existingusercheck';
    const OTP_GENERATE = 'otp_generate';
    const OTP_SUBMIT = 'otp_submit';
}