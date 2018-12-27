<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Error;

class Status
{
    const SUCCESS               = 'success';

    // Transaction failed
    const FAILED                = 'failed';

    // Bad request
    const ERROR                 = 'error';

    // Transaction was initiated but the user didn't complete the payment
    const INITIATED             = 'initiated';

    // Transaction has been successfully completed
    const COMPLETED             = 'completed';
}
