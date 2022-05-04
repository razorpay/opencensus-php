<?php

namespace RZP\Tests\Functional\Merchant\helpers;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Constants;

return [
    'testGetBusinessTypeExperimentOn' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchant/onboarding/business_types',
        ],
        'response' => [
            'content'     => [
                "registered"=> [
                    [
                        "id"=> "1",
                        "label"=> "Proprietorship",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "3",
                        "label"=> "Partnership",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "4",
                        "label"=> "Private Limited",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "5",
                        "label"=> "Public Limited",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "6",
                        "label"=> "LLP",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "8",
                        "label"=> "Educational Institutes",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "9",
                        "label"=> "Trust",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "10",
                        "label"=> "Society",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "12",
                        "label"=> "Other",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "7",
                        "label"=> "NGO",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "13",
                        "label"=> "HUF",
                        "status"=> "active"
                    ]
                ],
                "unregistered"=> [
                    [
                        "id"=> "2",
                        "label"=> "Individual",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "11",
                        "label"=> "Not Yet Registered",
                        "status"=> "active"
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testGetBusinessTypeExperimentOff' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchant/onboarding/business_types',
        ],
        'response' => [
            'content'     => [
                "registered"=> [
                    [
                        "id"=> "1",
                        "label"=> "Proprietorship",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "3",
                        "label"=> "Partnership",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "4",
                        "label"=> "Private Limited",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "5",
                        "label"=> "Public Limited",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "6",
                        "label"=> "LLP",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "8",
                        "label"=> "Educational Institutes",
                        "status"=> "inactive"
                    ],
                    [
                        "id"=> "9",
                        "label"=> "Trust",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "10",
                        "label"=> "Society",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "12",
                        "label"=> "Other",
                        "status"=> "inactive"
                    ],
                    [
                        "id"=> "7",
                        "label"=> "NGO",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "13",
                        "label"=> "HUF",
                        "status"=> "inactive"
                    ]
                ],
                "unregistered"=> [
                    [
                        "id"=> "2",
                        "label"=> "Individual",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "11",
                        "label"=> "Not Yet Registered",
                        "status"=> "active"
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testGetBusinessTypeSubMerchant' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchant/onboarding/business_types',
        ],
        'response' => [
            'content'     => [
                "registered"=> [
                    [
                        "id"=> "1",
                        "label"=> "Proprietorship",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "3",
                        "label"=> "Partnership",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "4",
                        "label"=> "Private Limited",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "5",
                        "label"=> "Public Limited",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "6",
                        "label"=> "LLP",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "8",
                        "label"=> "Educational Institutes",
                        "status"=> "inactive"
                    ],
                    [
                        "id"=> "9",
                        "label"=> "Trust",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "10",
                        "label"=> "Society",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "12",
                        "label"=> "Other",
                        "status"=> "inactive"
                    ],
                    [
                        "id"=> "7",
                        "label"=> "NGO",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "13",
                        "label"=> "HUF",
                        "status"=> "inactive"
                    ]
                ],
                "unregistered"=> [
                    [
                        "id"=> "2",
                        "label"=> "Individual",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "11",
                        "label"=> "Not Yet Registered",
                        "status"=> "active"
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testGetBusinessTypeAdmin' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchant/onboarding/business_types/admin',
        ],
        'response' => [
            'content'     => [
                "registered"=> [
                    [
                        "id"=> "1",
                        "label"=> "Proprietorship",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "3",
                        "label"=> "Partnership",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "4",
                        "label"=> "Private Limited",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "5",
                        "label"=> "Public Limited",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "6",
                        "label"=> "LLP",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "8",
                        "label"=> "Educational Institutes",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "9",
                        "label"=> "Trust",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "10",
                        "label"=> "Society",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "12",
                        "label"=> "Other",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "7",
                        "label"=> "NGO",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "13",
                        "label"=> "HUF",
                        "status"=> "active"
                    ]
                ],
                "unregistered"=> [
                    [
                        "id"=> "2",
                        "label"=> "Individual",
                        "status"=> "active"
                    ],
                    [
                        "id"=> "11",
                        "label"=> "Not Yet Registered",
                        "status"=> "active"
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
];
