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
    'testColorAndImageAreSentInOptionsField' => [
        'request' => [
            'content' => [
                'key' => 'rzp_test_TheTestAuthKey',
                'components' => ['options'],
            ],
            'method' => 'GET',
            'url' => '/v1/affordability',
        ],
        'response' => [
            'content' => [
                'enabled' => true,
                'options' => [
                    'theme' => ['color' => '#1234FF'],
                    'image' => 'https://dummycdn.razorpay.com/logos/merchant_logo_medium.png',
                ],
            ],
        ],
    ],
    'testFetchOffersDoesNotReturnSubscriptionBasedOffers' => [
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
];
