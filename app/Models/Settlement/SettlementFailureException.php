<?php

namespace RZP\Models\Settlement;

use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;

class SettlementFailureException extends ServerErrorException
{
    protected $channel;

    public function __construct(
        $channel,
        $message = null,
        $data = null,
        \Throwable $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_SETTLEMENTS_FAILED;
        $message = $message ?: 'Critical error: Settlements failed';

        $data['channel'] = $channel;

        $this->channel = $channel;

        parent::__construct($message, $code, $data, $previous);
    }

}
