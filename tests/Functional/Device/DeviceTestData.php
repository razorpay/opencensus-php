<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateDevice' => [
        'request' => [
            'url' => '/upi/devices',
            'method' => 'POST',
            'content' => [
                'imei'         => '869649022152494',
                'os_version'   => '1.1.1.1',
                'package_name' => 'com.razorpay.sampleapp',
                'challenge'    => 'FtJ3dqDNZWMLXm+mHBW2Ma+4rCMKk15K/QIVbuKjPNZ1p/RnU4rWqbxohrH1E/6xDblxSzrNJ37z7zOqNiOT4OtEJfmWlAN+Qz8CeO86bXyi3dlCn4Kj1rXRM1Odr4GoHVS+9/9zX84c393M8vKBfm86Qz83eN9kNtY9oZNvRpgowgEJwFOkWs0FpLAfB+wASNcF2+qUV0hh2xSnrDlJorJPV7nB8dQ/oRrE8MJN2d3fsXgt8AAOAK7mmOgnbm6ZQl8t11Si3gGNKnUh1GgyL+MYuiMO402Np137Q9ePRgTD9Rpe/hex1o8YMo2Id+6fYQvqC4hfV6qmfbTsnE8GMA==',
            ]
        ],
        'response' => [
            'content' => [
                'os_version'         => '1.1.1.1',
                'customer_details'   => null,
                'status'             => 'created',
            ],
        ],
    ],

    'testFetchCreatedDevice' => [
        'request' => [
            'url' => '/upi/devices/dev_RazorpayDevice',
            'method' => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'id'                 => 'dev_RazorpayDevice',
                'type'               => 'android',
                'os'                 => 'android',
                'os_version'         => '5.2.3',
                'tag'                => null,
                'customer_details'   => null,
                'upi_token'          => 'upi_auth_token',
                'status'             => 'created',
                'customer_id'        => null,
                'token_id'           => null,
                'auth_token'         => 'authentication_token',
                'verification_token' => 'sample_verification_token',
            ],
        ],
    ],

    'testFetchVerifiedDevice' => [
        'request' => [
            'method' => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'type'               => 'android',
                'os'                 => 'android',
                'os_version'         => '5.2.3',
                'tag'                => null,
                'customer_details'   => null,
                'upi_token'          => 'random_upi_token',
                'status'             => 'verified',
                'customer_id'        => null,
                'token_id'           => null,
            ],
        ],
    ],

    'testDeviceVerification' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'keyword' => 'VERIFY',
                'number'  => '919999999999',
            ]
        ],
        'response' => [
            'content' => [],
        ],
    ],
];
