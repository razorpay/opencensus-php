<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class CardNumberTraceException extends ServerErrorException
{
    public function __construct()
    {
        $code = ErrorCode::SERVER_ERROR_CARD_NUMBER_LOGGED;

        $message = 'Card number is getting logged';

        parent::__construct($message, $code);
    }
}
