<?php

return [
    'testFetchedOffersAreSortedByPopularity' => [
        'request' => [
            'content' => [
                'key' => 'rzp_test_TheTestAuthKey',
                'components' => [
                    'offers',
                ],
            ],
            'method' => 'GET',
            'url' => '/v1/affordability',
        ],
        'response' => [
            'content' => [
                'enabled' => true,
                'entities' => [
                    'offers' => [
                        'items' => [], // Filled by the Test
                    ],
                ],
            ],
        ],
    ],
    'testFetchOffersReturnsAllActiveOffersIrrespectiveOfTheirDefaultCheckoutVisibility' => [
        'request' => [
            'content' => [
                'key' => 'rzp_test_TheTestAuthKey',
                'components' => [
                    'offers',
                ],
            ],
            'method' => 'GET',
            'url' => '/v1/affordability',
        ],
        'response' => [
            'content' => [
                'enabled' => true,
                'entities' => [
                    'offers' => [
                        'items' => [], // Filled by the Test
                    ],
                ],
            ],
        ],
    ],
    'testFetchOffersReturnsEmptyResponseWhenThereIsNoActiveOffer' => [
        'request' => [
            'content' => [
                'key' => 'rzp_test_TheTestAuthKey',
                'components' => [
                    'offers',
                ],
            ],
            'method' => 'GET',
            'url' => '/v1/affordability',
        ],
        'response' => [
            'content' => [
                'enabled' => true,
                'entities' => [
                    'offers' => [
                        'items' => [],
                    ],
                ],
            ],
        ],
    ],
];
