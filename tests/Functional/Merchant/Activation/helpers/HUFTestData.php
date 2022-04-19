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
                        "name"=> "proprietorship",
                        "id"=> 1,
                        "display_name"=> "Proprietorship",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "partnership",
                        "id"=> 3,
                        "display_name"=> "Partnership",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "private_limited",
                        "id"=> 4,
                        "display_name"=> "Private Limited",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "public_limited",
                        "id"=> 5,
                        "display_name"=> "Public Limited",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "llp",
                        "id"=> 6,
                        "display_name"=> "LLP",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "educational_institutes",
                        "id"=> 8,
                        "display_name"=> "Educational Institutes",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "trust",
                        "id"=> 9,
                        "display_name"=> "Trust",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "society",
                        "id"=> 10,
                        "display_name"=> "Society",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "other",
                        "id"=> 12,
                        "display_name"=> "Other",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "ngo",
                        "id"=> 7,
                        "display_name"=> "NGO",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "huf",
                        "id"=> 13,
                        "display_name"=> "HUF",
                        "status"=> "active"
                    ]
                ],
                "unregistered"=> [
                    [
                        "name"=> "individual",
                        "id"=> 2,
                        "display_name"=> "Individual",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "not_yet_registered",
                        "id"=> 11,
                        "display_name"=> "Not Yet Registered",
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
                        "name"=> "proprietorship",
                        "id"=> 1,
                        "display_name"=> "Proprietorship",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "partnership",
                        "id"=> 3,
                        "display_name"=> "Partnership",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "private_limited",
                        "id"=> 4,
                        "display_name"=> "Private Limited",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "public_limited",
                        "id"=> 5,
                        "display_name"=> "Public Limited",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "llp",
                        "id"=> 6,
                        "display_name"=> "LLP",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "educational_institutes",
                        "id"=> 8,
                        "display_name"=> "Educational Institutes",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "trust",
                        "id"=> 9,
                        "display_name"=> "Trust",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "society",
                        "id"=> 10,
                        "display_name"=> "Society",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "other",
                        "id"=> 12,
                        "display_name"=> "Other",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "ngo",
                        "id"=> 7,
                        "display_name"=> "NGO",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "huf",
                        "id"=> 13,
                        "display_name"=> "HUF",
                        "status"=> "inactive"
                    ]
                ],
                "unregistered"=> [
                    [
                        "name"=> "individual",
                        "id"=> 2,
                        "display_name"=> "Individual",
                        "status"=> "active"
                    ],
                    [
                        "name"=> "not_yet_registered",
                        "id"=> 11,
                        "display_name"=> "Not Yet Registered",
                        "status"=> "active"
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
];
