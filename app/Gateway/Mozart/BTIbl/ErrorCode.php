<?php

namespace RZP\Gateway\Mozart\BTIbl;

class ErrorCode
{
    /*
     * Error Type : Technical
     * Error Message : Internal Technical Failure
     * Error Correction : ESB Service didn’t respond because of a technical roadblock.
     */
    const ER002  = 'ER002';

    const RETRYABLE_ERROR_CODES = [
        self::ER002 => 'ER002',
    ];
}
