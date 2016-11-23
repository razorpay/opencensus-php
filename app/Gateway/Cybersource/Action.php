<?php

namespace RZP\Gateway\Cybersource;

use RZP\Gateway\Base;

class Action extends Base\Action
{
    const SALE          = 'sale';
    const AUTH_REVERSAL = 'auth_reversal';
}