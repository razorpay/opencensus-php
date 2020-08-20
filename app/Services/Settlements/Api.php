<?php

namespace RZP\Services\Settlements;

use RZP\Exception;

class Api extends Base
{
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * This is used to call the transaction release method via the API auth
     * @param array $txnIds
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function transactionRelease(array $txnIds) : array
    {
        return $this->makeRequest(self::TRANSACTION_RELEASE, [
            "ids" => $txnIds,
        ], self::SERVICE_API);
    }

    /**
     * This is used to call the transaction Hold method via the API auth
     * @param array $txnIds
     * @param string $reason
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function transactionHold(array $txnIds, string $reason) : array
    {
        return $this->makeRequest(self::TRANSACTION_HOLD, [
            "ids"    => $txnIds,
            "reason" => $reason,
        ], self::SERVICE_API);
    }
}
