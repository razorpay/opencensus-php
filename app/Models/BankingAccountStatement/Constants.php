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
}
