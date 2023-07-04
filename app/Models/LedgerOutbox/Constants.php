<?php

namespace RZP\Models\LedgerOutbox;

use RZP\Models\Ledger\Constants as LedgerConstants;

class Constants
{
    const PAYMENT  = "payment";
    const REVERSAL = "reversal";
    const REFUND   = "refund";

    const REQUEST           = "request";
    const RESPONSE          = "response";
    const ERROR_RESPONSE    = "error_response";
    const TYPE              = "type";
    const CODE              = "code";
    const MSG               = "msg";

    const LEDGER_ACCOUNT_NOT_FOUND                     = "ledger_account_not_found";
    const ERROR_TYPE                                   = "error_type";
    const RECOVERABLE_ERROR                            = "recoverable_error";
    const NON_RECOVERABLE_ERROR                        = "non_recoverable_error";
    const ERROR_MESSAGE                                = "error_message";
    const SOURCE                                       = "source";
    const CRON                                         = "cron";
    const ACK_WORKER                                   = "ack_worker";
    const SYNC                                         = "sync";
    const PAYLOAD                                      = "payload";
    const PAYLOAD_NAME                                 = "payload_name";
    const OUBTOX_ENTRIES_COUNT                         = "outbox_entry_count";
    const TRANSACTION_CREATE                           = "transaction_create";
    const JOURNAL_CREATE                               = "journal_create";

    const CREDIT_LOADING                                = "credit_loading";

    // Async journal create error codes
    const BAD_REQUEST_RECORD_ALREADY_EXIST                     = "validation_failure: record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXIST";
    const PAYLOAD_VALIDATION_FAILURE                           = "validation_failure: validation_failure: BAD_REQUEST_VALIDATION_FAILURE";
    const INSUFFICIENT_BALANCE_FAILURE                         = "insufficient_balance_failure: BAD_REQUEST_INSUFFICIENT_BALANCE";
    const ACCOUNT_DISCOVERY_ACCOUNT_NOT_FOUND_FAILURE          = "account_discovery_failure: ACCOUNT_DISCOVERY_ACCOUNT_NOT_FOUND";
    const ACCOUNT_DISCOVER_MULTIPLE_ACCOUNTS_NOT_FOUND_FAILURE = "account_discovery_failure: ACCOUNT_DISCOVERY_MULTIPLE_ACCOUNTS_FOUND";
    const LEDGER_BUILDER_FAILURE                               = "ledger_builder_failure: No parameter";
    const IDEMPOTENCY_REQUEST_MISMATCH                         = "idempotency_failure: idempotency_failure: IDEMPOTENCY_REQUEST_MISMATCH";

    // As the errors will remain the same after multiple tries, journal creation should not be retried for following errors.
    const NON_RETRYABLE_ERROR_CODES = [
        self::PAYLOAD_VALIDATION_FAILURE,
        self::LEDGER_BUILDER_FAILURE,
        self::BAD_REQUEST_RECORD_ALREADY_EXIST,
        self::IDEMPOTENCY_REQUEST_MISMATCH,
    ];

    // Cron
    const MAX_RETRY_COUNT           = 10;
    const OUTBOX_RETRY_DEFAULT_END_TIME = 600;
    const OUTBOX_RETRY_DEFAULT_START_TIME = 864000;
    const DEFAULT_LIMIT             = 100;

    // Transactor events for which transaction is not created
    const NON_TRANSACTION_EVENTS = [
        LedgerConstants::GATEWAY_CAPTURED,
        LedgerConstants::MERCHANT_FEE_CREDIT_LOADING,
        LedgerConstants::MERCHANT_REFUND_CREDIT_LOADING
    ];

    const BULK_JOURNAL_EVENTS = [
        LedgerConstants::MERCHANT_FEE_CREDIT_LOADING,
        LedgerConstants::MERCHANT_REFUND_CREDIT_LOADING,
        LedgerConstants::MERCHANT_RESERVE_BALANCE_LOADING,
        LedgerConstants::TRANSFER,
        LedgerConstants::TRANSFER_REVERSAL_PROCESSED
    ];
}
