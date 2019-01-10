<?php

namespace RZP\Gateway\Netbanking\Canara;

class Constants
{
    const CLIENT_CODE                       = 'CLIENTCODE';
    const DEFAULT_MESSAGE                   = 'This field will contain message description';
    const SUCCESS                           = '0';
    const SUCCESS_VERIFY_STATUS             = '0';
    const SUCCESS_AND_FAILURE_STATIC_FLAG   = 'N';
    const CURRENCY                          = 'INR';

    const SAMPLE_FAILURE_CODE               = 10;  // this is only a sample value. Failure return code is non-zero
    const SAMPLE_FAILURE_VERIFY_STATUS      = 10;  // this is only a sample value. Failure return code is non-zero

    const MODE_CBC                          = 2;
}
