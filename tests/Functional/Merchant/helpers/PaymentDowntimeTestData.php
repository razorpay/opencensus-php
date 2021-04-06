<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;

return [
    'testGetCardDowntimeForRupayGateways' => [
        'request' => [
            'url' => '/payments/downtimes',
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
            'url' => '/payments/downtimes',
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
            'url' => '/payments/downtimes',
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
                        'status'     => 'started',
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
            'url' => '/payments/downtimes',
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
                        'status'     => 'started',
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
            'url' => '/payments/downtimes',
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

    'testGetNetbankingDowntimeForSingleBankBilldeskGateway' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'netbanking',
                        'end'        => null,
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'bank' => 'BACB'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testPaymentDowntimeForAllGatewayAndSingleGateway' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'netbanking',
                        'end'        => null,
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'bank' => 'SVCB'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testPaymentDowntimeForSingleGatewayAndAllGateway' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'netbanking',
                        'end'        => null,
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'bank' => 'SVCB'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testPaymentDowntimeForFewSupportingGateways' => [
        'request' => [
            'url' => '/payments/downtimes',
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

    'testPaymentDowntimeForAllSupportingGateways' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'netbanking',
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'bank' => 'SBIN'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'createNetbankingAllGatewayDowntime' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'netbanking',
                        'end'        => null,
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'bank' => 'HDFC'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeForIndividualGateways' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'netbanking',
                        'end'        => null,
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'bank' => 'PSIB',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNoNetbankingDowntimeForSingleGateway' => [
        'request' => [
            'url' => '/payments/downtimes',
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

    'testGetCardDowntimeForSingleNetworkHdfcGateway' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'card',
                        'end'        => null,
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'network' => 'DICL'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCardDowntimeForIndividualGateways' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'card',
                        'end'        => null,
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'network' => 'RUPAY',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNoCardDowntimeForSingleGateway' => [
        'request' => [
            'url' => '/payments/downtimes',
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

    'testPaymentDowntimeStartedWebhook' => [
        'entity'   => 'event',
        'event'    => 'payment.downtime.started',
        'account_id' => 'acc_10000000000000',
        'contains' => [
            'payment.downtime',
        ],
        'payload'  => [
            'payment.downtime' => [
                'entity' => [
                    'entity'     => 'payment.downtime',
                    'method'     => 'netbanking',
                    'status'     => 'started',
                    'scheduled'  => false,
                    'severity'   => 'medium',
                    'instrument' => [
                        'bank' => 'SBIN',
                    ],
                ],
            ],
        ],
    ],

    'testPaymentDowntimeResolvedWebhook' => [
        'entity'   => 'event',
        'event'    => 'payment.downtime.resolved',
        'account_id' => 'acc_10000000000000',
        'contains' => [
            'payment.downtime',
        ],
        'payload'  => [
            'payment.downtime' => [
                'entity' => [
                    'entity'     => 'payment.downtime',
                    'method'     => 'netbanking',
                    'status'     => 'resolved',
                    'scheduled'  => false,
                    'severity'   => 'medium',
                    'instrument' => [
                        'bank' => 'SBIN',
                    ],
                ],
            ],
        ],
    ],

    'testGetWalletDowntimeForSingleGateway' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'     => 'payment.downtime',
                        'method'     => 'wallet',
                        'end'        => null,
                        'status'     => 'started',
                        'scheduled'  => false,
                        'severity'   => 'low',
                        'instrument' => [
                            'wallet' => 'olamoney'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithPaymentDowntime' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'payment_downtime' => [
                    'count' => 1,
                    'items' => [
                        [
                            'method' => 'netbanking',
                        ],
                    ],
                ],
            ],
        ]
    ],
];
