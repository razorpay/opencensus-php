<?php

namespace Models\Settlement;

use EE\Error\Error;
use EE\Error\ErrorCode;
use EE\Exception\ServerErrorException;

class SettlementFailureException extends ServerErrorException
{
    protected $channel;

    public function __construct(
        $channel,
        $data = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_SETTLEMENTS_FAILED;
        $message = 'Critical error: Settlements failed';

        $data['channel'] = $channel;

        $this->channel = $channel;

        parent::__construct($message, $code, $data, $previous);
    }

}
