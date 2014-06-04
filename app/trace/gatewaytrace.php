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
    const SUPPORT_REQUEST = 'SUPPORT_REQUEST';
    const SUPPORT_RESPONSE = 'SUPPORT_RESPONSE';
    const SUPPORT_ERROR = 'SUPPORT_ERROR';
    
    protected static $defaults = array('message');

    protected static $defaultMessage = array(
        self::ENROLL_REQUEST                => 'Request for enrollment sent',
        self::ENROLL_RESPONSE               => 'Enrollment response received',
        self::ENROLL_ERROR                  => 'Error in enrollment',
        self::NOT_ENROLLED_REQUEST          => 'Request for not-enrolled card',
        self::NOT_ENROLLED_RESPONSE         => 'Response for not-enrolled card received',
        self::NOT_ENROLLED_ERROR            => 'Error occured for not-enrolled card',
        self::ENROLLED_AUTH_REQUEST         => 'Authentication request sent for enrolled card',
        self::ENROLLED_AUTH_RESPONSE        => 'Authentication response received for enrolled card',
        self::ENROLLED_AUTH_ERROR           => 'Error occured for enrolled card',
        self::SUPPORT_REQUEST               => 'Support request',
        self::SUPPORT_RESPONSE              => 'Support response',
        self::SUPPORT_ERROR                 => 'Error occured for support'
        );

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
        self::SUPPORT_REQUEST => array(
            'url',
            'type',
            'data'
        ),
        self::SUPPORT_RESPONSE => array(
            'type',
            'data'
        ),
        self::SUPPORT_ERROR => array(
            'type',
            'data',
            'error'
        ),
        
    );
}