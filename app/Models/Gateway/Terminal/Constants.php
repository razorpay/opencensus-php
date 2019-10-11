<?php
namespace RZP\Models\Gateway\Terminal;

class Constants
{
    const ATOS_ACTIVATION_RETRY_LIMIT       =   10;
    const ATOS_ACTIVATION_NEXT_RETRY_MINS   =   20; // mins

    // Request
    const MPAN       = 'mpan';
    const VISA       = 'visa';
    const MASTERCARD = 'mastercard';
    const RUPAY      = 'rupay';

    // Response
    const DATA                          =   'data';
    const RES_CODE                      =   'res_code';
    const RETRY                         =   'retry';
    const STATUS                        =   'status';
    const SUCCESS                       =   'success';
    const DESCRIPTION                   =   'description';
    const ERROR                         =   'error';
    const INTERNAL_ERROR_CODE           =   'internal_error_code';
    const GATEWAY_ERROR_CODE            =   'gateway_error_code';
    const GATEWAY_ERROR_DESCRIPTION     =   'gateway_error_description';
    const GATEWAY_FAILURE_ERROR_CODE    =   '05';

    const CALLBACK_SUCCESSFUL           =   'callback_successful';
    const CALLBACK_FAILED               =   'callback_failed';

    // cron Response
    const ACTIVATED_TERMINALS           =   'activated_terminals';
    const PENDING_TERMINALS             =   'pending_terminals';
    const ACTIVATION_FAILED_TERMINALS   =   'activation_failed_terminals';
    const NOT_APPLICABLE_TERMINALS      =   'not_applicable_terminals'; // terminals which are acquired or already been processed by other mutex
    const VERIFICATION_ERROR_TERMINALS  =   'verification_error_terminals';
}