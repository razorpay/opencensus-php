<?php

namespace RZP\Gateway\NpciPaySecure;

use RZP\Error\ErrorCode;

trait ErrorHandlerTrait
{
    protected $errorCodeMappings = [
        '401' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '403' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
    ];

    public function getErrorCodeMapped($errorCode)
    {
        return $this->errorCodeMappings[$errorCode];
    }
}
