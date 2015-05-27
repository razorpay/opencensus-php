<?php

namespace Gateway\Netbanking\Hdfc;

use EE\Error;
use EE\Error\ErrorCode;

class ErrorCode
{
    const TRANSFER_TERMINATED_BY_USER;

    protected static $messages = array(
         => 'Funds transfer terminated by user',
    );

    protected static $errorMap = array(
        self::TRANSFER_TERMINATED_BY_USER)
}