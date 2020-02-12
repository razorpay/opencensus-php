<?php

return [
    'testRemovalFromUnclaimedGroup' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'Merchant_ID__c'                => '10000000000002',
                        'Owner'                         => [
                            'Email' => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'Solutions',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com,xyz@rzp.com'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testNonSmeMerchantsPoc' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'Merchant_ID__c'                => '10000000000002',
                        'Owner'                         => [
                            'Email' => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'Solutions',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com,xyz@rzp.com'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedNonSmeMerchantsPoc' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'Merchant_ID__c'                => '10000000000028',
                        'Owner'                         => [
                            'Email' => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'Solutions',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com,xyz@rzp.com'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSmeMerchantsPoc' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'attributes'                    => [
                            'type' => 'Account',
                            'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                        ],
                        'Merchant_ID__c'                => '10000000000003',
                        'Owner'                         => [
                            'attributes' => [
                                'type' => 'User',
                                'url'  => '/services/data/v34.0/sobjects/User/0056F00000AkfoEQAR'
                            ],
                            'Email'      => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com,xyz@rzp.com'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedSmeMerchantsPoc' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'attributes'                    => [
                            'type' => 'Account',
                            'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                        ],
                        'Merchant_ID__c'                => '10000000000028',
                        'Owner'                         => [
                            'attributes' => [
                                'type' => 'User',
                                'url'  => '/services/data/v34.0/sobjects/User/0056F00000AkfoEQAR'
                            ],
                            'Email'      => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'rst@rzp.com,xyz@rzp.com'
                    ],
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedSmeAndNonSMEMerchantsPoc' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 4,
                'done'      => true,
                'records'   => [
                    [
                        'attributes'                    => [
                            'type' => 'Account',
                            'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                        ],
                        'Merchant_ID__c'                => '10000000000028',
                        'Owner'                         => [
                            'attributes' => [
                                'type' => 'User',
                                'url'  => '/services/data/v34.0/sobjects/User/0056F00000AkfoEQAR'
                            ],
                            'Email'      => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'rst@rzp.com,xyz@rzp.com'
                    ],
                    [
                        'Merchant_ID__c'                => '10000000000448',
                        'Owner'                         => [
                            'Email' => 'xyz@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'Solutions',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com'
                    ],
                    [
                        'Merchant_ID__c'                => '10000000000003',
                        'Owner'                         => [
                            'Email' => 'xyz@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com'
                    ],
                    [
                        'Merchant_ID__c'                => '10000000000004',
                        'Owner'                         => [
                            'Email' => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'KAM',
                        'Managers_In_Role_Hierarchy__c' => 'rst@rzp.com'
                    ]

                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedSmeBelongsToOtherGroup' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'attributes'                    => [
                            'type' => 'Account',
                            'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                        ],
                        'Merchant_ID__c'                => '10000000000028',
                        'Owner'                         => [
                            'attributes' => [
                                'type' => 'User',
                                'url'  => '/services/data/v34.0/sobjects/User/0056F00000AkfoEQAR'
                            ],
                            'Email'      => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'rst@rzp.com,xyz@rzp.com'
                    ],
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedSmeBelongsToOtherAdmin' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'attributes'                    => [
                            'type' => 'Account',
                            'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                        ],
                        'Merchant_ID__c'                => '10000000000028',
                        'Owner'                         => [
                            'attributes' => [
                                'type' => 'User',
                                'url'  => '/services/data/v34.0/sobjects/User/0056F00000AkfoEQAR'
                            ],
                            'Email'      => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'rst@rzp.com,xyz@rzp.com'
                    ],
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedNonSmeBelongsToOtherGroup' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'Merchant_ID__c'                => '10000000000028',
                        'Owner'                         => [
                            'Email' => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'Solutions',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com,xyz@rzp.com'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedNonSmeBelongsToOtherAdmin' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 1,
                'done'      => true,
                'records'   => [
                    [
                        'Merchant_ID__c'                => '10000000000028',
                        'Owner'                         => [
                            'Email' => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'Solutions',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com,xyz@rzp.com'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedNonSmeClaimedToUnclaimed' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 3,
                'done'      => true,
                'records'   => [
                    [
                        'Merchant_ID__c'                => '10000000000448',
                        'Owner'                         => [
                            'Email' => 'xyz@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'Solutions',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com'
                    ],
                    [
                        'Merchant_ID__c'                => '10000000000003',
                        'Owner'                         => [
                            'Email' => 'xyz@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com'
                    ],
                    [
                        'Merchant_ID__c'                => '10000000000004',
                        'Owner'                         => [
                            'Email' => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'KAM',
                        'Managers_In_Role_Hierarchy__c' => 'rst@rzp.com'
                    ]

                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedSmeClaimedToUnclaimed' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 4,
                'done'      => true,
                'records'   => [
                    [
                        'attributes'                    => [
                            'type' => 'Account',
                            'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                        ],
                        'Merchant_ID__c'                => '10000000000448',
                        'Owner'                         => [
                            'attributes' => [
                                'type' => 'User',
                                'url'  => '/services/data/v34.0/sobjects/User/0056F00000AkfoEQAR'
                            ],
                            'Email'      => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'rst@rzp.com,xyz@rzp.com'
                    ],
                    [
                        'Merchant_ID__c'                => '10000000000448',
                        'Owner'                         => [
                            'Email' => 'xyz@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com'
                    ],
                    [
                        'Merchant_ID__c'                => '10000000000003',
                        'Owner'                         => [
                            'Email' => 'xyz@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'Solutions',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com'
                    ],
                    [
                        'Merchant_ID__c'                => '10000000000004',
                        'Owner'                         => [
                            'Email' => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'rst@rzp.com'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLinkedSmeAdminChanged' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/poc_update',
            'content' => [
                'totalSize' => 2,
                'done'      => true,
                'records'   => [
                    [
                        'attributes'                    => [
                            'type' => 'Account',
                            'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                        ],
                        'Merchant_ID__c'                => '10000000000002',
                        'Owner'                         => [
                            'Email' => 'xyz@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'xyz@rzp.com'
                    ],
                    [
                        'attributes'                    => [
                            'type' => 'Account',
                            'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                        ],
                        'Merchant_ID__c'                => '10000000000028',
                        'Owner'                         => [
                            'attributes' => [
                                'type' => 'User',
                                'url'  => '/services/data/v34.0/sobjects/User/0056F00000AkfoEQAR'
                            ],
                            'Email'      => 'abc@rzp.com'
                        ],
                        'Owner_Role__c'                 => 'SME Sales',
                        'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com'
                    ],

                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

];
