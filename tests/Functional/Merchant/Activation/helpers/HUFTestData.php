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
                "registered"     => [
                    "proprietorship"     => [
                        "id"=> 1,
                        "status"=> "active"
                    ],
                    "partnership"     => [
                        "id"=> 3,
                        "status"=> "active"
                    ],
                    "private_limited"     => [
                        "id"=> 4,
                        "status"=>"active"
                    ],
                    "public_limited"     => [
                        "id"=> 5,
                        "status"=> "active"
                    ],
                    "llp"     => [
                        "id"=> 6,
                        "status"=>"active"
                    ],
                    "educational_institutes"     => [
                        "id"=> 8,
                        "status"=> "active"
                    ],
                    "trust"     => [
                        "id"=> 9,
                        "status"=> "active"
                    ],
                    "society"     => [
                        "id"=> 10,
                        "status"=> "active"
                    ],
                    "other"     => [
                        "id"=> 12,
                        "status"=> "active"
                    ],
                    "ngo"     => [
                        "id"=> 7,
                        "status"=> "active"
                    ],
                    "huf"     => [
                        "id"=> 13,
                        "status"=>"active"
                    ]
                ],
                "unregistered"     => [
                    "individual"     => [
                        "id"=> 2,
                        "status"=> "active"
                    ],
                    "not_yet_registered"     => [
                        "id"=> 11,
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
                "registered"     => [
                    "proprietorship"     => [
                        "id"=> 1,
                        "status"=> "active"
                    ],
                    "partnership"     => [
                        "id"=> 3,
                        "status"=> "active"
                    ],
                    "private_limited"     => [
                        "id"=> 4,
                        "status"=>"active"
                    ],
                    "public_limited"     => [
                        "id"=> 5,
                        "status"=> "active"
                    ],
                    "llp"     => [
                        "id"=> 6,
                        "status"=>"active"
                    ],
                    "educational_institutes"     => [
                        "id"=> 8,
                        "status"=> "active"
                    ],
                    "trust"     => [
                        "id"=> 9,
                        "status"=> "active"
                    ],
                    "society"     => [
                        "id"=> 10,
                        "status"=> "active"
                    ],
                    "other"     => [
                        "id"=> 12,
                        "status"=> "active"
                    ],
                    "ngo"     => [
                        "id"=> 7,
                        "status"=> "active"
                    ],
                    "huf"     => [
                        "id"=> 13,
                        "status"=>"inactive"
                    ]
                ],
                "unregistered"     => [
                    "individual"     => [
                        "id"=> 2,
                        "status"=> "active"
                    ],
                    "not_yet_registered"     => [
                        "id"=> 11,
                        "status"=> "active"
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
];
