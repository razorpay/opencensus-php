<?php

namespace RZP\Models\BankingAccountStatement;

final class Metric
{
    // Labels
    const LABEL_CODE          = 'code';
    const LABEL_CHANNEL       = 'channel';
    const LABEL_ERROR_MESSAGE = 'error_message';

    const BAS_PROCESSOR_JOB_FAILURES_TOTAL                = 'bas_processor_job_failures_total';
    const BAS_PROCESSOR_QUEUE_PUSH_FAILURES_TOTAL         = 'bas_processor_queue_push_failures_total';
    const MISSING_STATEMENT_REDIS_INSERT_FAILURES         = 'missing_statement_redis_insert_failures';
    const MISSING_STATEMENT_FETCH_ERROR_GATEWAY_EXCEPTION = 'missing_statement_fetch_error_gateway_exception';
    const MISSING_STATEMENT_FETCH_ERROR_RETRIES_EXHAUSTED = 'missing_statement_fetch_error_retries_exhausted';
    const MISSING_STATEMENT_INSERT_FAILURE                = 'missing_statement_insert_failure';
    const MISSING_STATEMENT_UPDATE_FAILURE                = 'missing_statement_update_failure';
    const MISSING_STATEMENTS_FOUND                        = 'missing_statements_found';
}

