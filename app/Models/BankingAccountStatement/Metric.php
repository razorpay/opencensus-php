<?php

namespace RZP\Models\BankingAccountStatement;

final class Metric
{
    // Labels
    const LABEL_CODE          = 'code';
    const LABEL_CHANNEL       = 'channel';
    const LABEL_ERROR_MESSAGE = 'error_message';

    const BAS_PROCESSOR_JOB_FAILURES_TOTAL        = 'bas_processor_job_failures_total';
    const BAS_PROCESSOR_QUEUE_PUSH_FAILURES_TOTAL = 'bas_processor_queue_push_failures_total';
    const MISSING_STATEMENT_REDIS_INSERT_FAILURES = 'missing_statement_redis_insert_failures';
    const MISSING_STATEMENTS_FOUND                = 'missing_statements_found';
}

