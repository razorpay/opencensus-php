<?php

namespace RZP\Error\twirp;

use RZP\Error\ErrorCode;

class ErrorCodeMap
{
    public static $twirpErrorCodeMap = [
        'cancel'              => ErrorCode::BAD_REQUEST_ACTION_CANCELLED,
        'invalid_argument'    => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        'malformed'           => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        'deadline_exceeded'   => ErrorCode::BAD_REQUEST_DEADLINE_EXCEEDED,
        'not_found'           => ErrorCode::BAD_REQUEST_ITEM_NOT_FOUND,
        'bad_route'           => ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
        'already_exists'      => ErrorCode::BAD_REQUEST_ITEM_ALREADY_EXISTS,
        'permission_denied'   => ErrorCode::BAD_REQUEST_PERMISSION_ERROR,
        'unauthenticated'     => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED,
        'resource_exhausted'  => ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED,
        'failed_precondition' => ErrorCode::BAD_REQUEST_FAILED_PRECONDITION,
        'out_of_range'        => ErrorCode::BAD_REQUEST_OUT_OF_RANGE,
        'aborted'             => ErrorCode::BAD_REQUEST_ACTION_ABORTED,
        'unimplemented'       => ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
        'unknown'             => ErrorCode::SERVER_ERROR,
        'internal'            => ErrorCode::SERVER_ERROR,
        'unavailable'         => ErrorCode::SERVER_ERROR_SERVICE_UNAVAILABLE,
        'dataloss'            => ErrorCode::SERVER_ERROR,
    ];
}
