<?php

namespace RZP\Models\Transfer;

use App;
use Illuminate\Support\Str;
use RZP\Error\ErrorCode;


class Utility
{
    const INSUFFICIENT_BALANCE_RETRY_INTERVAL = 600;

    protected $errorMessageToRetryDelayInSecsMap = [
        'Something very wrong is happening! Balance is going negative',
        'Unexpected response code received from Ledger service.',
        'cURL error 28: Operation timed out',
        'invalid username/password for authentication',
        'SQLSTATE[HY000]',
        'cURL error',
        'Error completing the request'
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

        if (in_array($ex->getCode(), $this->errorCodeToRetryDelayInSecsMap, true))
        {
            return true;
        }


        if (Str::contains($ex->getMessage(), $this->errorMessageToRetryDelayInSecsMap, true))
        {
            return true;
        }

        return false;
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
