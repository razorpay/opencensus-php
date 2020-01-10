<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testRegisterDevice' => [
        'request' => [
            'content' => [
                'serial_number' => 'TestSerial',
                'type'          => 'android',
                'manufacturer'  => 'google',
                'model'         => 'pixel',
            ],
            'method'    => 'POST',
            'url'       => '/offlines/devices/register',
        ],
        'response' => [
            'content' => [
                'serial_number' => 'TestSerial',
                'type'          => 'android',
            ],
        ],
    ],

    'testLinkDevice' => [
        'request' => [
            'content' => [
                'activation_token' => '',
            ],
            'method'    => 'POST',
            'url'       => '/offlines/devices/link',
        ],
        'response' => [
            'content' => [
                'serial_number' => 'TestSerial2',
                'type'          => 'android',
            ],
        ],
    ],

    'testDeviceActivateInit' => [
        'request' => [
            'content' => [
                'fingerprint' => 'abc',
                'os' => 'android',
                'firmware_version' => 'android 7',
                'serial_number' => 'TestSerial2',
                'push_token' => 'push_token2',
            ],
            'method'    => 'POST',
            'url'       => '/t/offlines/devices/activate/initiate',
        ],
        'response' => [
            'content' => [
                'serial_number' => 'TestSerial2',
                'type'          => 'android',
                'status'        => 'created',
            ],
        ],
    ]
];
