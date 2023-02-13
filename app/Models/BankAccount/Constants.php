<?php

namespace RZP\Models\BankAccount;

class Constants
{
    const OLD_BANK_ACCOUNT_ARRAY                            = 'old_bank_account_array';
    const NEW_BANK_ACCOUNT_ARRAY                            = 'new_bank_account_array';
    const BANK_ACCOUNT_UPDATE_POST_PENNY_TESTING_ROUTE_NAME = 'merchant_bank_account_update';
    const BANK_ACCOUNT_UPDATE_POST_PENNY_TESTING_CONTROLLER = 'RZP\Http\Controllers\MerchantController@putBankAccountUpdatePostPennyTestingWorkflow';
    const BANK_ACCOUNT_UPDATE_INPUT                         = 'input';

    const BANK_ACCOUNT_UPDATE_PENNY_TESTING_TTL       = 360 * 60; // in seconds
    const BANK_ACCOUNT_UPDATE_PENNY_TESTING_CACHE_KEY = 'bank_account_update_penny_testing_%s';
    const BANK_ACCOUNT_UPDATE_MUTEX_RESOURCE          = 'bank_account_update_mutex_resource_%s';

    const BANK_ACCOUNT_UPDATE_SYNC_ONLY_TTL           = 360 * 60; // in seconds
    const BANK_ACCOUNT_UPDATE_SYNC_ONLY_CACHE_KEY     = 'new_bank_account_update_data_%s';


    const ACCOUNT_STATUS                                    = 'account_status';
    const REGISTERED_NAME                                   = 'registered_name';
    const IS_NAME_MATCHED                                   = 'is_name_matched';
    const ACTIVE                                            = 'active';
    const BANK_ACCOUNT_UPDATE_CALLBACK_HANDLER_BVS          = 'bank_account_update_callback_handler';
    const ADMIN_EMAIL                                       = 'admin_email';
    const ADMIN_ORG                                         = 'admin_org';
    const WORKFLOW_COMMENT_ERROR_ADDING_VALIDATION_RESULT   = 'Error adding penny testing and fuzzy match details';
    const WORKFLOW_COMMENT_ERROR_ADDING_DEDUPE_RESULT       = 'Error adding dedupe result';
    const COMMENT                                           = 'comment';
    const ERROR_MESSAGE                                     = 'error_message';
    const DEDUPE_FALSE_COMMENT                              = 'dedupe_status: false';
    const RESULT                                            = 'result';
    const RESULT1                                           = 'result1';
    const RESULT2                                           = 'result2';
    const FAILURE_REASON                                    = 'failure_reason';
    const RULE_EXECUTION_RESULT                             = 'rule_execution_result';
    const OPERANDS                                          = 'operands';
    const OPERANDS1                                         = 'operand_1';
    const OPERANDS2                                         = 'operand_2';
    const REMARKS                                           = 'remarks';
    const MATCH_PERCENTAGE                                  = 'match_percentage';
    const NAME_MATCH_RESULT                                 = 'name_match_result';
    const PENNY_TEST_RESULT                                 = 'penny_test_result';
    const NEW_BANK_ACCOUNT                                  = 'new_bank_account';
    const SYNC_FLOW                                         = 'sync_flow';
    const WORKFLOW_CREATED                                  = 'workflow_created';
    const CREATE_WORKFLOW                                   = 'create_workflow';
    const TIMEOUT                                           = 'timeout';
    const SYNC_ONLY                                         = 'sync_only';
    const SUPER_ADMIN_WORKFLOW_CHECKER_EMAIL                = 'SUPER_ADMIN_WORKFLOW_CHECKER_EMAIL';
}
