<?php

namespace Trace;

use Monolog\Logger;
use Trace\Trace;

class GatewayTrace extends Trace
{
    // string code for events
    const ENROLL_REQUEST = 'ENROLL_REQUEST';
    const ENROLL_RESPONSE = 'ENROLL_RESPONSE';
    const ENROLL_ERROR = 'ENROLL_RESPONSE';
    const NOT_ENROLLED_REQUEST = 'NOT_ENROLLED_REQUEST';
    const NOT_ENROLLED_RESPONSE = 'NOT_ENROLLED_RESPONSE';
    const NOT_ENROLLED_ERROR = 'NOT_ENROLLED_ERROR';
    const ENROLLED_AUTH_REQUEST = 'ENROLLED_AUTH_REQUEST';
    const ENROLLED_AUTH_RESPONSE = 'ENROLLED_AUTH_RESPONSE';
    const ENROLLED_AUTH_ERROR = 'ENROLLED_AUTH_ERROR';
    const CARD_ENROLLED = 'CARD_ENROLLED';
    const GATEWAY_ACS_CALLBACK_SUCCESSFUL = 'GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL';
    const GATEWAY_ACS_REQUEST_TIMEOUT = 'GATEWAY_HDFC_ACS_REQUEST_TIMEOUT';
    const TRACKID_MISMATCH = 'TRACKID_MISMATCH';

    protected $component = 'gateway';

    protected static $compulsoryFields = array(
        'message',
        'transaction_id');

    protected static $fields = array(
        self::ENROLL_REQUEST => array(
            'url',
            'type',
            'data'
        ),
        self::ENROLL_RESPONSE => array(
            'type',
            'data'
        ),
        self::ENROLL_ERROR => array(
            'type',
            'data',
            'error'
        ),
        self::NOT_ENROLLED_REQUEST => array(
            'url',
            'type',
            'data'
        ),
        self::NOT_ENROLLED_RESPONSE => array(
            'type',
            'data'
        ),
        self::NOT_ENROLLED_ERROR => array(
            'type',
            'data',
            'error'
        ),
        self::ENROLLED_AUTH_REQUEST => array(
            'url',
            'type',
            'data'
        ),
        self::ENROLLED_AUTH_RESPONSE => array(
            'type',
            'data'
        ),
        self::ENROLLED_AUTH_ERROR => array(
            'type',
            'data',
            'error'
        ),
        self::CARD_ENROLLED => array(),
        self::GATEWAY_ACS_CALLBACK_SUCCESSFUL => array(),
        self::GATEWAY_ACS_REQUEST_TIMEOUT => array(),
        self::TRACKID_MISMATCH => array(
            'response_track_id'),
        );
}