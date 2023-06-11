<?php

namespace RZP\Models\Transfer;

use RZP\Error\ErrorCode;

class Utility
{
    const INSUFFICIENT_BALANCE_RETRY_INTERVAL = 3600;

    protected $errorMessageToRetryDelayInSecsMap = [
        'Something very wrong is happening! Balance is going negative',
    ];

    protected $errorCodeToRetryDelayInSecsMap = [
        ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
        ErrorCode::BAD_REQUEST_INVALID_ID,
    ];

    public function isRetryableError($ex)
    {
        return false;
        // TODO: Disabling retries, re-enable after verifying
//        return (in_array($ex->getMessage(), $this->errorMessageToRetryDelayInSecsMap, true) or
//                in_array($ex->getCode(), $this->errorCodeToRetryDelayInSecsMap, true));
    }

    public function getDelay($ex)
    {
        if ($ex->getCode() === ErrorCode::BAD_REQUEST_INVALID_ID)
        {
            $retryTime = 900;
        }
        else
        {
            $retryTime = self::INSUFFICIENT_BALANCE_RETRY_INTERVAL;
        }

        return $retryTime;
    }
}
