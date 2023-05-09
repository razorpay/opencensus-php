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
    const PAGINATION_KEY                = 'pagination_key';
    const ACCOUNT_NUMBERS               = 'account_numbers';
    const EXPECTED_ATTEMPTS             = 'expected_attempts';
    const ACCOUNT_NUMBERS_PRESENT       = 'account_numbers_present';
    const FETCH_MISSING_STATEMENT       = 'fetch_missing_statement';
    const UPDATE_MISSING_STATEMENT      = 'update_missing_statement';
    const DEFAULT                       = 'default';
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
    const BANKING_ACCOUNT_STATEMENT_RECON_PROCESS_NEO = 'BankingAccountStatementReconProcessNeo';
}
