<?php

namespace RZP\Models\Settlement;

use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;

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
