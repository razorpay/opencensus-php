<?php

namespace RZP\Gateway\Wallet\Base;

class Otp
{
    const INSUFFICIENT_BALANCE  = '100000';

    const INCORRECT             = '200000';

    const EXPIRED               = '300000';

    const THRESHOLD_REACHED     = '400000';

    const USER_DOES_NOT_EXIST   = '500000';

    const WALLET_LIMIT_EXCEEDED = '600000';
}
