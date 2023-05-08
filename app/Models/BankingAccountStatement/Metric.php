<?php

namespace RZP\Models\BankingAccountStatement;

final class Metric
{
    // Labels
    const LABEL_CODE          = 'code';
    const LABEL_CHANNEL       = 'channel';
    const LABEL_ERROR_MESSAGE = 'error_message';

    const INSERT_AND_UPDATE_BAS_FAILURE                      = 'insert_and_update_bas_failure';
    const BAS_UPDATE_QUEUE_DISPATCH_FAILURE                  = 'bas_update_queue_dispatch_failure';
    const REMOVAL_OF_INSERTED_STATEMENTS_FROM_REDIS_FAILURE  = 'removal_of_inserted_statements_from_redis_failure';
    const MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED = 'missing_banking_account_statement_fetch_job_failed';
    const BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED         = 'banking_account_statement_fetch_job_failed';
    const BANKING_ACCOUNT_STATEMENT_ICICI_TEMP_RECORD_COUNT  = 'banking_account_statement_icici_temp_record_count';

    const BAS_PROCESSOR_JOB_FAILURES_TOTAL                    = 'bas_processor_job_failures_total';
    const BAS_PROCESSOR_QUEUE_PUSH_FAILURES_TOTAL             = 'bas_processor_queue_push_failures_total';
    const MISSING_STATEMENT_REDIS_INSERT_FAILURES             = 'missing_statement_redis_insert_failures';
    const MISSING_STATEMENT_FETCH_ERROR_GATEWAY_EXCEPTION     = 'missing_statement_fetch_error_gateway_exception';
    const MISSING_STATEMENT_FETCH_ERROR_RETRIES_EXHAUSTED     = 'missing_statement_fetch_error_retries_exhausted';
    const MISSING_STATEMENT_INSERT_FAILURE                    = 'missing_statement_insert_failure';
    const MISSING_STATEMENT_UPDATE_FAILURE                    = 'missing_statement_update_failure';
    const MISSING_STATEMENTS_FOUND                            = 'missing_statements_found';
    const STATEMENT_BALANCES_DO_NOT_MATCH                     = 'statement_balances_do_not_match';
    const MISSING_STATEMENT_RECON_PAGINATION_KEY_ALREADY_NULL = 'missing_statement_recon_pagination_key_already_null';

}

