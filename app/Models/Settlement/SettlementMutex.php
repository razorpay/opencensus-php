<?php

namespace RZP\Models\Settlement;

use RZP\Error\ErrorCode;

trait SettlementMutex
{
    public function acquireMutexOnSettlement()
    {
        if ($this->mutex->acquire(self::MUTEX_RESOURCE) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }
    }

    public function releaseMutexOnSettlement()
    {
        $this->mutex->release(self::MUTEX_RESOURCE);
    }
}
