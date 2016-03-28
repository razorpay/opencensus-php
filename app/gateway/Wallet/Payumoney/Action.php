<?php

namespace Gateway\Wallet\Payumoney;

use Gateway\Base;

class Action extends Base\Action
{
    const REGISTER_USER = 'register_user';
    const USE_WALLET 	= 'use_wallet';
    const OTP_SUBMIT 	= 'otp_submit';
    const CREATED 		= 'created';
}