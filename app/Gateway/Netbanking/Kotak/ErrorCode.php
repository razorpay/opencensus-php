<?php

namespace RZP\Gateway\Netbanking\Kotak;

use RZP\Error;

class ErrorCode
{
    const TRANSFER_TERMINATED_BY_USER = 'TRANSFER_TERMINATED_BY_USER';

    protected static $messages = array(
        self::TRANSFER_TERMINATED_BY_USER => 'Funds transfer terminated by user',
    );

    protected static $errorMap = array(
        self::TRANSFER_TERMINATED_BY_USER => Error\ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER);
}
