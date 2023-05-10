<?php

namespace RZP\Models\Merchant\Acs\traits;

use RZP\Mail\System\Trace;
use RZP\Trace\TraceCode;

trait AsvFetch {
    public function getEntityDetails(
        string $callingIdentifier,
        bool $shouldRouteToAsv,
        callable $fetchFromAccountServiceCallback,
        callable $fetchFromDatabaseCallback)
    {
        if ($shouldRouteToAsv) {
            try {
                $this->trace->info(TraceCode::ACCOUNT_SERVICE_GET_ENTITY_REQUEST, [
                    "identifier" => $callingIdentifier
                ]);

                return $fetchFromAccountServiceCallback();
            } catch (\Exception $e) {

               $this->trace->error(TraceCode::ACCOUNT_SERVICE_GET_ENTITY_DETAILS_EXCEPTION,
               [
                   "error_code" => $e->getCode(),
                   "error_message" => $e->getMessage(),
                   "trace" => $e->getTrace(),
                   "identifier" => $callingIdentifier
               ]);
            }
        }
        return $fetchFromDatabaseCallback();
    }
}

