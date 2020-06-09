<?php
namespace RZP\Models\Gateway\Terminal;

class Constants
{
    const DEFAULT_CONTACT_NAME                   =   "Razorpay";
    const WORLDLINE_ACTIVATION_RETRY_LIMIT       =   5;
    const WORLDLINE_ACTIVATION_NEXT_RETRY_MINS   =   10; // mins
    const WORLDLINE_ACTIVATION_DEFAULT_TIME      =   45; // time taken by a worldline terminal to be activated, after creating on gateway

    // Actions
    const MERCHANT_ONBOARD      = 'merchantOnboard';
    const CREATE_TERMINAL       = 'create_terminal';
    const VERIFY_TERMINAL       = 'verify_terminal';
    const DISABLE_TERMINAL      = 'disable_terminal';
    const ENABLE_TERMINAL       = 'enable_terminal';


    // Request
    const MPAN       = 'mpan';
    const VISA       = 'visa';
    const MASTERCARD = 'mastercard';
    const RUPAY      = 'rupay';

    // Default merchant details for merchant onboarding
    const DEFAULT_BUSINESS_OPERATION_ADDRESS      = 'SJR Cyber Laskar, Hosur Rd, Opp Adugodi Police Station, Bengaluru';
    const DEFAULT_BUSINESS_OPERATION_STATE        = 'Karnataka';
    const DEFAULT_BUSINESS_OPERATION_STATE_CODE   = 'KA';
    const DEFAULT_BUSINESS_OPERATION_PIN          = '560030';
    const DEFAULT_BUSINESS_DBA                    = 'Razorpay';
    const DEFAULT_BUSINESS_NAME                   = 'Razorpay';
    const DEFAULT_BUSINESS_OPERATION_CITY         = 'Bengaluru';

    // Response
    const DATA                                    =   'data';
    const RES_CODE                                =   'res_code';
    const RETRY                                   =   'retry';
    const STATUS                                  =   'status';
    const SUCCESS                                 =   'success';
    const DESCRIPTION                             =   'description';
    const ERROR                                   =   'error';
    const INTERNAL_ERROR_CODE                     =   'internal_error_code';
    const GATEWAY_ERROR_CODE                      =   'gateway_error_code';
    const GATEWAY_ERROR_DESCRIPTION               =   'gateway_error_description';
    const GATEWAY_FAILURE_ERROR_CODE              =   '05';
    const DUPLICATE_MERCHANT_CODE                 =   'Duplicate Merchant code';

    const TERMINAL_ACTIVATION_SUCCESSFULL         =   'terminal_activation_successful';
    const TERMINAL_ACTIVATION_FAILED              =   'terminal_activation_failed';
    const TERMINAL_DEACTIVATION_SUCCESSFUL        =   'terminal_deactivation_successful';
    const TERMINAL_REACTIVATION_SUCCESSFUL        =   'terminal_reactivation_successful';

    // cron Response
    const ACTIVATED_TERMINALS                     =   'activated_terminals';
    const PENDING_TERMINALS                       =   'pending_terminals';
    const ACTIVATION_FAILED_TERMINALS             =   'activation_failed_terminals';
    const NOT_APPLICABLE_TERMINALS                =   'not_applicable_terminals'; // terminals which are acquired or already been processed by other mutex
    const VERIFICATION_ERROR_TERMINALS            =   'verification_error_terminals';

    // new batch service related constants
    const IDEMPOTENCY_KEY             = 'idempotency_key';
    const TERMINAL_ID                 = 'terminal_id';
    const BATCH_ERROR                 = 'error';
    const BATCH_ERROR_CODE            = 'code';
    const BATCH_ERROR_DESCRIPTION     = 'description';
    const BATCH_SUCCESS               = 'success';
    const BATCH_HTTP_STATUS_CODE      = 'http_status_code';
    const CURRENCY                    = "currency";
    const CATEGORY                    = "category";

    const CURRENCY_CODE               = "currency_code";

}
