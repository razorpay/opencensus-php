<?php

return [
    'testFeatureDisabledOnAffordabilityWidget' => [
        'request' => [
            'content' => [
                "key" => "rzp_test_TheTestAuthKey",
                "components" => [
                    "offers",
                ]
            ],
            'url'    => '/affordability',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "enabled" => false,
            ]
        ]
    ],
    'testFeatureEnabledOnRzpAffordabilityWidget' => [
        'request' => [
            'content' => [
                "key" => "rzp_test_TheTestAuthKey",
                "components" => [
                    "offers",
                ]
            ],
            'url'    => '/affordability',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "enabled" => true,
            ]
        ]
    ],
    'testFeatureEnabledOnAffordabilityWidget' => [
        'request' => [
            'content' => [
                "key" => "rzp_test_TheTestAuthKey",
                "components" => [
                    "offers",
                ]
            ],
            'url'    => '/affordability',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "enabled" => true,
            ]
        ]
    ],

    'testPaylaterOnAffordabilityWidget' => [
        'request' => [
            'content' => [
                "key" => "rzp_test_TheTestAuthKey",
                "components" => [
                    "paylater",
                ]
            ],
            'url'    => '/affordability',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "enabled" => true,
                "entities" => [
                    "paylater" => [
                        'providers' => [
                            'epaylater' => [
                                'enabled' => true,
                                'min_amount' => null,
                            ],
                            'getsimpl' => [
                                'enabled' => true,
                                'min_amount' => 100,
                            ],
                            'icic' => [
                                'enabled' => true,
                                'min_amount' => 100,
                            ],
                            'hdfc' => [
                                'enabled' => true,
                                'min_amount' => 100000,
                            ],
                            'kkbk' => [
                                'enabled' => true,
                                'min_amount' => 200000,
                            ],
                            'lazypay' => [
                                'enabled' => true,
                                'min_amount' => 100,
                            ],
                        ],
                    ],
                ]
            ]
        ]
    ],

    'testEmiOnAffordabilityWidget' => [
        'request' => [
            'content' => [
                "key" => "rzp_test_TheTestAuthKey",
                "components" => [
                    "emi",
                ]
            ],
            'url'    => '/affordability',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "enabled" => true,
                "entities" => [
                    "emi" => [
                        "items" => [

                        ]
                    ]
                ]
            ]
        ]
    ],

    'testCardlessEmiOnAffordabilityWidget' => [
        'request' => [
            'content' => [
                "key" => "rzp_test_TheTestAuthKey",
                "components" => [
                    "cardless_emi",
                ]
            ],
            'url'    => '/affordability',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "enabled" => true,
                "entities" => [
                    "cardless_emi" => [
                        "providers" => [

                        ]
                    ]
                ]
            ]
        ]
    ],

    'testAffordabilityWidgetSuite' => [
        'request' => [
            'content' => [
                "key" => "rzp_test_TheTestAuthKey",
                "components" => [
                    "paylater",
                    "emi",
                    "cardless_emi",
                ]
            ],
            'url'    => '/affordability',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "enabled" => true,
                "entities" => [
                    "paylater" => [
                        'providers' => [
                            'epaylater' => [
                                'enabled' => true,
                                'min_amount' => null,
                            ],
                            'getsimpl' => [
                                'enabled' => true,
                                'min_amount' => 100,
                            ],
                            'icic' => [
                                'enabled' => true,
                                'min_amount' => 100,
                            ],
                            'hdfc' => [
                                'enabled' => true,
                                'min_amount' => 100000,
                            ],
                            'kkbk' => [
                                'enabled' => true,
                                'min_amount' => 200000,
                            ],
                            'lazypay' => [
                                'enabled' => true,
                                'min_amount' => 100,
                            ],
                        ],
                    ],
                    "emi" => [

                    ],
                    "cardless_emi" => [
                        "providers" => [

                        ]
                    ],
                ]
            ]
        ]
    ],
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
