<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\Partner\Constants;

return [
    'testCreateStakeholderForCompletelyFilledRequest' => [
        'request' => [
            'url'     => '/v2/accounts/{account_id}/stakeholders',
            'method'  => 'POST',
            'content' => [
                'percentage_ownership'=> 40.3,
                'name'=> 'Rahul Sharma',
                'email'=> 'rahul@acme.org',
                'relationship'=> [
                    'director'=> true,
                    'executive'=> true,
                ],
                'phone'=> [
                    'primary'=> '7474747474',
                    'secondary'=> '7474747474'
                ],
                'addresses'=> [
                    'residential'=> [
                        'street'=> '506, Koramangala 1st block',
                        'city'=> 'Bengaluru',
                        'state'=> 'Karnataka',
                        'postal_code'=> '560034',
                        'country'=> 'IN'
                    ]
                ],
                'kyc'=> [
                    'pan'=> 'AVOPB1111K'
                ],
                'notes'=> [
                    'random_key_by_partner'=> 'random_value'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'stakeholder',
                'percentage_ownership'=> 40.3,
                'name'=> 'Rahul Sharma',
                'email'=> 'rahul@acme.org',
                'relationship'=> [
                    'director'=> true,
                    'executive'=> true
                ],
                'phone'=> [
                    'primary'=> '7474747474',
                    'secondary'=> '7474747474'
                ],
                'addresses'=> [
                    'residential'=> [
                        'street'=> '506, Koramangala 1st block',
                        'city'=> 'Bengaluru',
                        'state'=> 'Karnataka',
                        'postal_code'=> '560034',
                        'country'=> 'in'
                    ]
                ],
                'kyc'=> [
                    'pan'=> 'AVOPB1111K'
                ],
                'notes'=> [
                    'random_key_by_partner'=> 'random_value'
                ]
            ],
        ],
    ],

    'testUpdateStakeholderCompleteRequest' => [
        'request' => [
            'url'     => '/v2/accounts/{account_id}/stakeholders',
            'method'  => 'PATCH',
            'content' => [
                'percentage_ownership'=> 20,
                'name'=> 'Rahul SharmaJi',
                'email'=> 'rahul@acme.com',
                'relationship'=> [
                    'director'=> false,
                    'executive'=> false,
                ],
                'phone'=> [
                    'primary'=> '7474757474',
                    'secondary'=> '7474757474'
                ],
                'addresses'=> [
                    'residential'=> [
                        'street'=> '507, Koramangala 1st block',
                        'city'=> 'Bangalore',
                        'state'=> 'Andhra Pradesh',
                        'postal_code'=> '518501',
                        'country'=> 'BD'
                    ]
                ],
                'kyc'=> [
                    'pan'=> 'AVOPB1111J'
                ],
                'notes'=> [
                    'random_key_by_partner'=> 'random_value_2'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'stakeholder',
                'percentage_ownership'=> 20,
                'name'=> 'Rahul SharmaJi',
                'email'=> 'rahul@acme.com',
                'relationship'=> [
                ],
                'phone'=> [
                    'primary'=> '7474757474',
                    'secondary'=> '7474757474'
                ],
                'addresses'=> [
                    'residential'=> [
                        'street'=> '507, Koramangala 1st block',
                        'city'=> 'Bangalore',
                        'state'=> 'Andhra Pradesh',
                        'postal_code'=> '518501',
                        'country'=> 'bd'
                    ]
                ],
                'kyc'=> [
                    'pan'=> 'AVOPB1111J'
                ],
                'notes'=> [
                    'random_key_by_partner'=> 'random_value_2'
                ]
            ],
        ],
    ],

    'testFetchAllAccountStakeholders' => [
        'request' => [
            'url'     => '/v2/accounts/{account_id}/stakeholders',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity' => 'stakeholder',
                        'percentage_ownership'=> 20,
                        'name'=> 'Rahul SharmaJi',
                        'email'=> 'rahul@acme.com',
                        'relationship'=> [
                        ],
                        'phone'=> [
                            'primary'=> '7474757474',
                            'secondary'=> '7474757474'
                        ],
                        'addresses'=> [
                            'residential'=> [
                                'street'=> '507, Koramangala 1st block',
                                'city'=> 'Bangalore',
                                'state'=> 'Andhra Pradesh',
                                'postal_code'=> '518501',
                                'country'=> 'bd'
                            ]
                        ],
                        'kyc'=> [
                            'pan'=> 'AVOPB1111J'
                        ],
                        'notes'=> [
                            'random_key_by_partner'=> 'random_value_2'
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testFetchStakeholder' => [
        'request' => [
            'url'     => '/v2/accounts/{account_id}/stakeholders/{stakeholderId}',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'stakeholder',
                'percentage_ownership'=> 40.3,
                'name'=> 'Rahul Sharma',
                'email'=> 'rahul@acme.org',
                'relationship'=> [
                    'director'=> true,
                    'executive'=> true
                ],
                'phone'=> [
                    'primary'=> '7474747474',
                    'secondary'=> '7474747474'
                ],
                'addresses'=> [
                    'residential'=> [
                        'street'=> '506, Koramangala 1st block',
                        'city'=> 'Bengaluru',
                        'state'=> 'Karnataka',
                        'postal_code'=> '560034',
                        'country'=> 'in'
                    ]
                ],
                'kyc'=> [
                    'pan'=> 'AVOPB1111K'
                ],
                'notes'=> [
                    'random_key_by_partner'=> 'random_value'
                ]
            ],
        ],
    ],

    'testCreateStakeholderForThinRequest' => [
        'request' => [
            'url'     => '/v2/accounts/{account_id}/stakeholders',
            'method'  => 'POST',
            'content' => [
                'name'=> 'Rahul Sharma',
                'email'=> 'rahul@acme.org',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'stakeholder',
                'name'=> 'Rahul Sharma',
                'email'=> 'rahul@acme.org',
                'relationship' => [],
                'notes' => [],
                'phone' => [],
                'kyc' => [],
            ],
        ],
    ],

    'testCreateStakeholderInvalidPercentageOwnership' => [
        'request' => [
            'url'     => '/v2/accounts/{account_id}/stakeholders',
            'method'  => 'POST',
            'content' => [
                'name'=> 'Rahul Sharma',
                'email'=> 'rahul@acme.org',
                'percentage_ownership' => 87.456,
            ],
        ],
        'response' => [
            'content' => [
                'name'=> 'Rahul Sharma',
                'email'=> 'rahul@acme.org',
                'percentage_ownership' => 87.46,
            ],
        ],
    ],

    'testUpdateStakeholderThinToCompleteRequest' => [
        'request' => [
            'url'     => '/v2/accounts/{account_id}/stakeholders',
            'method'  => 'PATCH',
            'content' => [
                'percentage_ownership'=> 20.85,
                'name'=> 'Rahul SharmaJi',
                'email'=> 'rahul@acme.com',
                'relationship'=> [
                    'director'=> false,
                    'executive'=> false,
                ],
                'phone'=> [
                    'primary'=> '7474757474',
                    'secondary'=> '7474757474'
                ],
                'addresses'=> [
                    'residential'=> [
                        'street'=> '507, Koramangala 1st block',
                        'city'=> 'Bangalore',
                        'state'=> 'Andhra Pradesh',
                        'postal_code'=> '518501',
                        'country'=> 'BD'
                    ]
                ],
                'kyc'=> [
                    'pan'=> 'AVOPB1111J'
                ],
                'notes'=> [
                    'random_key_by_partner'=> 'random_value_2'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'stakeholder',
                'percentage_ownership'=> 20.85,
                'name'=> 'Rahul SharmaJi',
                'email'=> 'rahul@acme.com',
                'relationship'=> [
                ],
                'phone'=> [
                    'primary'=> '7474757474',
                    'secondary'=> '7474757474'
                ],
                'addresses'=> [
                    'residential'=> [
                        'street'=> '507, Koramangala 1st block',
                        'city'=> 'Bangalore',
                        'state'=> 'Andhra Pradesh',
                        'postal_code'=> '518501',
                        'country'=> 'bd'
                    ]
                ],
                'kyc'=> [
                    'pan'=> 'AVOPB1111J'
                ],
                'notes'=> [
                    'random_key_by_partner'=> 'random_value_2'
                ]
            ],
        ],
    ]

];
