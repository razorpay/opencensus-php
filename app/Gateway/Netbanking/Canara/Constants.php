<?php

namespace RZP\Gateway\Netbanking\Canara;

class Constants
{
    const MODE_OF_TRANSACTION_PURCHASE      = 'PUR';
    const MODE_OF_TRANSACTION_VERIFY        = 'VRF';
    const INDIAN_CURRENCY                   = 'INR';
    const CLIENT_CODE                       = 'CLIENTCODE';
    const MESSAGE                           = 'This field will contain message description';
    const SUCCESS                           = '0';
    const SUCCESS_VERIFY_STATUS             = '0';

    const SAMPLE_FAILURE_CODE               = 10;  // this is only a sample value. Failure return code is non-zero
    const SAMPLE_FAILURE_VERIFY_STATUS      = 10;  // this is only a sample value. Failure return code is non-zero
}