<?php

namespace RZP\Gateway\Esigner\Legaldesk;

class ResponseFields
{
    // Create mandate response
    const REFERENCE_ID        = 'reference_id';
    const API_RESPONSE_ID     = 'api_response_id';
    const EMANDATE_ID         = 'emandate_id';
    const STATUS              = 'status';
    const CALLBACK_STATUS     = 'mandate_status';
    const RESPONSE_TIME_STAMP = 'response_time_stamp';
    const QUICK_INVITE_URL    = 'quick_invite_url';
    const ERROR               = 'error';
    const ERROR_CODE          = 'error_code';

    const MESSAGE             = 'message';

    const CONTENT             = 'content';
    const CONTENT_TYPE        = 'content_type';
}
