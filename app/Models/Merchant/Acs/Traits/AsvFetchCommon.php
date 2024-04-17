<?php

namespace RZP\Models\Merchant\Acs\Traits;

use Database\Connection;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

trait AsvFetchCommon
{
    public function getEntityDetails(
        string   $callingIdentifier,
        bool     $shouldRouteToAsv,
        callable $fetchFromAccountServiceCallback,
        callable $fetchFromDatabaseCallback,
        callable $fetchFromAsvDatabaseCallback)
    {
        if ($shouldRouteToAsv) {
            try {
                if ($this->isTransactionActive()) {
                    return $fetchFromAsvDatabaseCallback();
                } else {
                    $this->trace->info(TraceCode::ACCOUNT_SERVICE_GET_ENTITY_REQUEST, [
                        "identifier" => $callingIdentifier
                    ]);
                    return $fetchFromAccountServiceCallback();
                }
            }
            catch
                (\Exception $e) {
                    $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_GET_ENTITY_DETAILS_EXCEPTION, [
                        "identifier" => $callingIdentifier
                    ]);
                }
        }

        return $fetchFromDatabaseCallback();
    }
}
