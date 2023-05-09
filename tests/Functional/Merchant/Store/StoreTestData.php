<?php


namespace Functional\Merchant\Store;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testInvalidCreateStore' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchants/config/store',
            'content' => [
                'some_invalid_key'  => 'value'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],
    'testInvalidPermissionCreateStore' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchants/config/store',
            'content' => [
                'namespace'                 => 'onboarding',
                'gst_details_from_pan'      => '[]'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\InvalidPermissionException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PERMISSION,
        ],
    ],
    'testValidCreateStore' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchants/config/store',
            'content' => [
                'namespace'                 => 'onboarding',
                'mtu_coupon_popup_count'    => '1'
            ]
        ],
        'response' => [
            'content' => [],
        ],
        'status_code' => 200,
    ],

    'testFetchOnboardingStore' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchants/config/store?namespace=onboarding',
        ],
        'response' => [
            'content' => [
                'mtu_coupon_popup_count'    => '1'
            ]
        ],
        'status_code' => 200,
    ],

    'testGetUPITerminalProcurementBannerStatus' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchants/config/store?namespace=onboarding',
        ],
        'response' => [
            'content' => [
                'upi_terminal_procurement_status_banner' => 'no_banner'
            ]
        ],
        'status_code' => 200,
    ],

    'testStoreUPITerminalProcurementBannerStatus' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchants/config/store',
            'content' => [
                'namespace'                                 => 'onboarding',
                'upi_terminal_procurement_status_banner'    => 'pending_ack'
            ]
        ],
        'response' => [
            'content' => [],
        ],
        'status_code' => 200,
    ],

    'testGetUPITerminalBannerStatusForNoKafkaResponseBeyondThreshold' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchants/config/store?namespace=onboarding',
        ],
        'response' => [
            'content' => [
                'upi_terminal_procurement_status_banner' => 'pending'
            ]
        ],
        'status_code' => 200,
    ],
];
