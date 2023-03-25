<?php

namespace RZP\Models\Ledger\ReverseShadow;

class Constants
{
    const PAYMENT = "payment";

    // Sync journal create error codes
    const BAD_REQUEST_RECORD_ALREADY_EXIST                     = "record_already_exist";
    const PAYLOAD_VALIDATION_FAILURE                           = "validation_failure";
    const INSUFFICIENT_BALANCE_FAILURE                         = "insufficient_balance_failure";
    const ACCOUNT_DISCOVERY_ACCOUNT_NOT_FOUND_FAILURE          = "account_discovery_failure";
    const ACCOUNT_DISCOVERY_MULTIPLE_ACCOUNTS_FOUND_FAILURE    = "account_discovery_failure";
    const LEDGER_BUILDER_FAILURE                               = "ledger_builder_failure";
    const IDEMPOTENCY_REQUEST_MISMATCH                         = "idempotency_failure";

    // As the errors will remain the same after multiple tries, journal creation should not be retried for following errors.
    const NON_RETRYABLE_ERROR_CODES = [
        self::BAD_REQUEST_RECORD_ALREADY_EXIST,
        self::PAYLOAD_VALIDATION_FAILURE,
        self::LEDGER_BUILDER_FAILURE,
        self::IDEMPOTENCY_REQUEST_MISMATCH,
    ];


    const MAX_RETRY_COUNT           = 3;
    const MAX_RETRY_COUNT_CRON      = 10;
    const RETRY_COUNT               = 'retry_count';
}
