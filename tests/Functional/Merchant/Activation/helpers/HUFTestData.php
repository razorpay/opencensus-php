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
                "registered" => [
                    "proprietorship" => "active",
                    "partnership" => "active",
                    "private_limited" => "active",
                    "public_limited" => "active",
                    "llp" => "active",
                    "educational_institutes" => "active",
                    "trust" => "active",
                    "society" => "active",
                    "other" => "active",
                    "ngo" => "active",
                    "huf" => "active"],
                "unregistered" => [
                    "individual" => "active",
                    "not_yet_registered" => "active"]
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
                "registered" => [
                    "proprietorship" => "active",
                    "partnership" => "active",
                    "private_limited" => "active",
                    "public_limited" => "active",
                    "llp" => "active",
                    "educational_institutes" => "active",
                    "trust" => "active",
                    "society" => "active",
                    "other" => "active",
                    "ngo" => "active",
                    "huf" => "inactive"],
                "unregistered" => [
                    "individual" => "active",
                    "not_yet_registered" => "active"]
            ],
            'status_code' => 200,
        ],
    ],
];
