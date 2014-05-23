<?php

namespace Trace;

use Monolog\Logger;
use Trace\Trace;

class GatewayTrace extends Trace
{
    // string code for events
    const ENROLL_REQUEST_FAILED = 'ENROLL_REQUEST_FAILED';
    const CARD_ENROLLED = 'CARD_ENROLLED';
    const GATEWAY_ACS_CALLBACK_SUCCESSFUL = 'GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL';
    const GATEWAY_ACS_REQUEST_TIMEOUT = 'GATEWAY_HDFC_ACS_REQUEST_TIMEOUT';
    const TRACKID_MISMATCH = 'TRACKID_MISMATCH';

    protected $component = 'gateway';

    protected static $compulsoryFields = array(
        'message',
        'transaction_id');

    protected static $fields = array(
        self::ENROLL_REQUEST_FAILED => array(
            'gateway_error'),
        self::CARD_ENROLLED => array(),
        self::GATEWAY_ACS_CALLBACK_SUCCESSFUL => array(),
        self::GATEWAY_ACS_REQUEST_TIMEOUT => array(),
        self::TRACKID_MISMATCH => array(
            'response_track_id'),
        );
}