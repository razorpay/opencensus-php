<?php

namespace RZP\Models\Transfer;

use App;
use RZP\Error\ErrorCode;

class Utility
{
    const INSUFFICIENT_BALANCE_RETRY_INTERVAL = 600;

    protected $errorMessageToRetryDelayInSecsMap = [
        'Something very wrong is happening! Balance is going negative',
    ];

    protected $errorCodeToRetryDelayInSecsMap = [
        ErrorCode::BAD_REQUEST_ORDER_TRANSFER_PROCESS_IN_PROGRESS,
        ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_PROCESS_IN_PROGRESS,
        ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
        ErrorCode::BAD_REQUEST_INVALID_ID,
    ];

    public function isRetryableError($ex)
    {
        $app = App::getFacadeRoot();

        if ($app->runningInQueue() === false)
        {
            // If this flow is invoked via cron APIs, we will not be retrying it. This retry functionality
            // is supported only for transfers processed done via the workers (see TransferProcess.php and
            // other subclasses)
            return false;
        }

        return (in_array($ex->getMessage(), $this->errorMessageToRetryDelayInSecsMap, true) or
                in_array($ex->getCode(), $this->errorCodeToRetryDelayInSecsMap, true));
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
