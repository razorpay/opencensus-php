<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCompanySearchSuccess' => [
        'request'     => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/company_search',
            'content' => [
                'search_string' => 'abc',
            ],
        ],
        'response'    => [
            'content' => [
                'results' => [
                    [
                        'company_name'    => 'ABC MATRIMONIALS.COM LIMITED',
                        'identity_number' => 'U74899DL2000PLC105536',
                        'identity_type'   => 'cin'
                    ],
                    [
                        'company_name'    => 'ABC IMAGINATION PRIVATE LIMITED',
                        'identity_number' => 'U74899DL1995PTC074932',
                        'identity_type'   => 'cin'
                    ],
                    [
                        'company_name'    => 'ABC TEA WORKERS WELFARE SERVICES',
                        'identity_number' => 'U15311WB1968NPL027334',
                        'identity_type'   => 'cin'
                    ],
                    [
                        'company_name'    => 'ABC INDUSTRIAL INFRA-MANAGEMENT PRIVATE LIMITED',
                        'identity_number' => 'U45400GJ2000PTC037720',
                        'identity_type'   => 'cin'
                    ],
                    [
                        'company_name'    => 'ABC TRAVEL AND FOREX INDIA PRIVATE LIMITED',
                        'identity_number' => 'U63040MH1999PTC119454',
                        'identity_type'   => 'cin'
                    ],
                    [
                        'company_name'    => 'ABC COMMODITIES LTD.',
                        'identity_number' => 'U67120WB1994PLC064845',
                        'identity_type'   => 'cin'
                    ],
                    [
                        'company_name'    => 'ABC EDUMED LLP',
                        'identity_number' => 'AAM-1477',
                        'identity_type'   => 'llpin'
                    ],
                    [
                        'company_name'    => 'ABC EMPORIO LLP',
                        'identity_number' => 'AAG-2428',
                        'identity_type'   => 'llpin'
                    ]
                ]
            ],
        ],
        'status_code' => 200,
    ],

    'testCompanySearchFailure' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/company_search',
            'content' => [
                'search_string' => 'abc',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => 'internal',
                    'description' => 'hystrix: timeout'
                ]
            ],
        ],
    ],

    'testCompanySearchRateLimitExhausted' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/company_search',
            'content' => [
                'search_string' => 'abc',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_COMPANY_SEARCH_RETRIES_EXHAUSTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_COMPANY_SEARCH_RETRIES_EXHAUSTED,
        ],
    ],

    'testCompanySearchInvalidBusinessType' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/company_search',
            'content' => [
                'search_string' => 'abc',
            ],
        ],
        'response' => [
            'content' => [
                'results' => [],
            ]
        ]
    ]
];
