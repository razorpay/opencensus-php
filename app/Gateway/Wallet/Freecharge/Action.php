<?php

namespace RZP\Gateway\Wallet\Freecharge;

use RZP\Gateway\Wallet\Base;

class Action extends Base\Action
{
    const EXCHANGE_TOKEN       = 'exchange_token';
    const OTP_REDIRECT         = 'otp_redirect';
    const CREATE_REFUND_RECORD = 'create_refund_record';
}
