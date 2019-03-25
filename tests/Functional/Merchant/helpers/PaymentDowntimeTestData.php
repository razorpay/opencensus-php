<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;

return [
    'testGetCardDowntimeForRupayGateways' => [
        'request' => [
            'url' => '/methods/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    'entity'     => 'payment.downtime',
                    'method'     => 'card',
                    'end'        => null,
                    'instrument' => [
                        'network' => 'RUPAY',
                    ]
                ],
            ],
        ],
    ],

    'testGetNoCardDowntimeForSingleRupayGateway' => [
        'request' => [
            'url' => '/methods/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [
                ],
            ],
        ],
    ],

    'testGetUpiDowntimeForAllGateways' => [
        'request' => [
            'url' => '/methods/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'upi',
                        'end'        => null,
                        'status'     => 'scheduled',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetUpiDowntimeForIndividualGateways' => [
        'request' => [
            'url' => '/methods/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'upi',
                        'end'        => null,
                        'status'     => 'scheduled',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNoUpiDowntimeForSingleGateway' => [
        'request' => [
            'url' => '/methods/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [],
            ],
        ],
    ],
];
