<?php
namespace RZP\Models\Gateway\Terminal;

class Constants
{
    const DEFAULT_CONTACT_NAME                   =   "Razorpay";

    // Actions
    const MERCHANT_ONBOARD      = 'merchantOnboard';
    const CREATE_TERMINAL       = 'create_terminal';
    const DISABLE_TERMINAL      = 'disable_terminal';
    const ENABLE_TERMINAL       = 'enable_terminal';


    // Request
    const MPAN       = 'mpan';
    const VISA       = 'visa';
    const MASTERCARD = 'mastercard';
    const RUPAY      = 'rupay';
    const TERMINAL   = 'terminal';


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

    const TERMINAL_DEACTIVATION_SUCCESSFUL        =   'terminal_deactivation_successful';
    const TERMINAL_REACTIVATION_SUCCESSFUL        =   'terminal_reactivation_successful';

    const MERCHANT_IS_ALREADY_IN_DEACTIVE_STATE   =   'Merchant is already in deactive state';
    const MERCHANT_IS_ALREADY_IN_ACTIVE_STATE     =   'Merchant is already in active state';

    // new batch service related constants
    const IDEMPOTENCY_KEY             = 'idempotency_key';
    const TERMINAL_ID                 = 'terminal_id';
    const VPA_WHITELISTED             = 'vpa_whitelisted';
    const INVALID_PLAN                = 'invalid_plan';
    const BATCH_ERROR                 = 'error';
    const BATCH_ERROR_CODE            = 'code';
    const BATCH_ERROR_DESCRIPTION     = 'description';
    const BATCH_SUCCESS               = 'success';
    const BATCH_HTTP_STATUS_CODE      = 'http_status_code';
    const CURRENCY                    = "currency";
    const CATEGORY                    = "category";

    const CURRENCY_CODE               = "currency_code";

    const SYNC_INSTRUMENTS            = "sync_instruments";

    const SYNC_INSTRUMENTS_WORKFLOWS_TAG = 'syncinstruments';

    const TERMINAL_EDIT_GOD_MODE = 'terminal_edit_god_mode';

}
