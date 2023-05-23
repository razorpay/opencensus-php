<?php

namespace RZP\Models\BankingAccountStatement;

class Constants
{
    const ACTION                        = 'action';
    const FETCH                         = 'fetch';
    const INSERT                        = 'insert';
    const UPDATE                        = 'update';
    const FAILURE                       = 'failure';
    const DRY_RUN                       = 'dry_run';
    const SUCCESS                       = 'success';
    const DEFAULT                       = 'default';
    const RECON_LIMIT                   = 'recon_limit';
    const NEW_CRON_SETUP                = 'new_cron_setup';
    const PAGINATION_KEY                = 'pagination_key';
    const ACCOUNT_NUMBERS               = 'account_numbers';
    const MONITORING_CRON               = 'monitoring_cron';
    const EXPECTED_ATTEMPTS             = 'expected_attempts';
    const IS_AUTOMATED_CLEANUP          = 'is_automated_cleanup';
    const LAST_RECONCILED_AT_LIMIT      = 'last_reconciled_at_limit';
    const ACCOUNT_NUMBERS_PRESENT       = 'account_numbers_present';
    const FETCH_MISSING_STATEMENT       = 'fetch_missing_statement';
    const UPDATE_MISSING_STATEMENT      = 'update_missing_statement';
    const INSERT_MISSING_STATEMENT      = 'insert_missing_statement';
    const SUSPECTED_MISMATCH_TIMESTAMP  = 'suspected_mismatch_timestamp';

    // Clean Up tooling Constants
    const CLEAN_UP_CONFIG       = 'clean_up_config';
    const COMPLETED             = 'completed';
    const FETCH_INPUT           = 'fetch_input';
    const FETCH_IN_PROGRESS     = 'fetch_in_progress';
    const MISMATCH_DATA         = 'mismatch_data';
    const MISMATCH_AMOUNT_FOUND = 'mismatch_amount_found';
    const TOTAL_MISMATCH_AMOUNT = 'total_mismatch_amount';

    // Recon Job Constants
    const BANKING_ACCOUNT_STATEMENT_CLEAN_UP          = 'BankingAccountStatementCleanUp';
    const BANKING_ACCOUNT_MISSING_STATEMENT_INSERT    = 'BankingAccountMissingStatementInsert';
    const BANKING_ACCOUNT_STATEMENT_RECON_PROCESS_NEO = 'worker:banking_account_statement_recon_process_neo';
}
