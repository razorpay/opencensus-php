<?php

namespace RZP\Models\Customer\Truecaller\AuthRequest;

class Metric
{
    // Truecaller - labels
    public const LABEL_ERROR_MESSAGE                    = 'error_message';
    public const LABEL_SUCCESS_MESSAGE                  = 'success_message';
    public const LABEL_STATUS_CODE                      = 'status_code';

    // Truecaller - request id metrics
    public const CREATE_TRUECALLER_ENTITY_REQUEST       = 'create_truecaller_entity_request';

    // Truecaller - callback metrics
    public const TRUECALLER_CALLBACK_SUCCESS            = 'truecaller_callback_success';
    public const TRUECALLER_CALLBACK_ERROR              = 'truecaller_callback_error';

    // Truecaller - user profile API metrics
    public const TRUECALLER_SERVICE_ERROR               = 'truecaller_service_error';

    // Truecaller - Verify request metrics
    public const TRUECALLER_VERIFY_REQUEST_ERROR        = 'truecaller_verify_request_error';
    public const TRUECALLER_VERIFY_REQUEST_SUCCESS      = 'truecaller_verify_request_success';

}
