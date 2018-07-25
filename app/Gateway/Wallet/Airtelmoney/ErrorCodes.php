<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use RZP\Gateway\Netbanking\Airtel;

class ErrorCodes extends Airtel\ErrorCodes
{
    const REFUND_VERIFY_SUCCESS   = '0';
    const REFUND_VERIFY_ERROR     = 'errorCode';

}
