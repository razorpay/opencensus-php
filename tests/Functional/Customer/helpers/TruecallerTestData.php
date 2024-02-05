<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testTruecallerCallbackWithValidData' => [
        'request' => [
            'successContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
            	'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
            'userRejectedContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'status' => 'user_rejected',
            ],
            'usedAnotherNumberContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'status' => 'use_another_number',
            ],
            'userProfile' => [
                'contact' => 916300335800,
                'email' => 'komanduri.srikar7@gmail.com',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testTruecallerCallbackWithInvalidData' => [
        'request' => [
            'contentWithoutRequestId' => [],
            'contentWithoutAccessToken' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
            ],
            'contentWithoutEndpoint' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
            ],
        ],
    ],

    'testInternalTruecallerCallbackWithIdMigratedValidData' => [
        'request' => [
            'successContent' => [
                'is_request_id_migrated' => true,
                'true_caller_entity' => [
                    'request_id' => 'KlUikwkY8BSH6v-01'
                ],
                'requestId' => 'KlUikwkY8BSH6v-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
                'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
            'userRejectedContent' => [
                'is_request_id_migrated' => true,
                'true_caller_entity' => [
                    'request_id' => 'KlUikwkY8BSH6w-01'
                ],
                'requestId' => 'KlUikwkY8BSH6w-01',
                'status' => 'user_rejected',
            ],
            'usedAnotherNumberContent' => [
                'is_request_id_migrated' => true,
                'true_caller_entity' => [
                    'request_id' => 'KlUikwkY8BSH6x-01'
                ],
                'requestId' => 'KlUikwkY8BSH6x-01',
                'status' => 'use_another_number',
            ],
            'userProfile' => [
                'contact' => 916300335800,
                'email' => 'komanduri.srikar7@gmail.com',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testInternalTruecallerCallbackWithIdNotMigratedValidData' => [
        'request' => [
            'successContent' => [
                'is_request_id_migrated' => false,
                'requestId' => 'KlUikwkY8BSH6v-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
                'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
            'userRejectedContent' => [
                'is_request_id_migrated' => false,
                'requestId' => 'KlUikwkY8BSH6w-01',
                'status' => 'user_rejected',
            ],
            'usedAnotherNumberContent' => [
                'is_request_id_migrated' => false,
                'requestId' => 'KlUikwkY8BSH6x-01',
                'status' => 'use_another_number',
            ],
            'userProfile' => [
                'contact' => 916300335800,
                'email' => 'komanduri.srikar7@gmail.com',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testVerifyTruecallerRequestWithInvalidData' => [
        'request' => [
            'contentWithoutRequestId' => [],
            'contentWithInvalidRequestId' => [
                'request_id' => 'invalid_request_example'
            ]
        ]
    ],

    'testVerifyTruecallerRequestForSuccessResponse' => [
        'request' => [
            'userProfile' => [
                'contact' => 916300335800,
                'email' => 'komanduri.srikar7@gmail.com',
            ],
            'callbackContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
                'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
        ]
    ],

    'testVerifyTruecallerRequestForSuccessResponseOneClickCheckout' => [
        'request' => [
            'userProfile' => [
                'contact' => 919878543210,
                'email' => 'testexistingglobalcustomer@razorpay.com',
            ],
            'callbackContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
                'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'resolved',
                'email' => 'testexistingglobalcustomer@razorpay.com',
                'contact' => '+919878543210',
                'logged_in' => 1,
                'tokens' => [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        [
                            'id' => 'token_M9EQ5OztDvu5oh',
                            'entity' => 'token',
                            'token' => '1000lcardtoken',
                            'bank' => 'ICIC',
                            'wallet' => 'paytm',
                            'method' => 'card',
                            'card' => [
                                'entity' => 'card',
                                'name' => '',
                                'last4' => '1111',
                                'network' => 'Visa',
                                'type' => 'credit',
                                'issuer' => 'HDFC',
                                'international' => false,
                                'emi' => true,
                                'sub_type' => null,
                                'token_iin' => null,
                                'expiry_month' => '01',
                                'expiry_year' => '2099',
                                'flows' => [
                                    'recurring' => true,
                                ],
                                'cobranding_partner' => null,
                            ],
                            'recurring' => false,
                            'recurring_details' => [
                                'status' => null,
                                'failure_reason' => null,
                            ],
                            'auth_type' => null,
                            'mrn' => null,
                            'used_at' => 1688365852,
                            'created_at' => 1688365852,
                            'expired_at' => 1706725799,
                            'consent_taken' => true,
                            'status' => 'active',
                            'notes' => [],
                            'error_description' => null,
                            'source' => 'business',
                            'dcc_enabled' => false,
                            'max_amount' => null,
                            'error_code' => null,
                            'compliant_with_tokenisation_guidelines' => true,
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => 'M9ICOyJ139iODr',
                        'entity_id' => 'zMRVsEqPxuwiGl',
                        'entity_type' => 'customer',
                        'line1' => 'billing address line 1',
                        'line2' => 'some line two',
                        'city' => 'Bengaluru',
                        'zipcode' => '560030',
                        'state' => 'Karnataka',
                        'country' => 'in',
                        'type' => 'billing_address',
                        'primary' => true,
                        'contact' => null,
                        'name' => null,
                        'tag' => null,
                        'landmark' => null,
                        'deleted_at' => null,
                        'created_at' => 1688365852,
                        'source_id' => null,
                        'source_type' => 'shopify',
                    ],
                    [
                        'id' => 'M9ICOzBhiakey8',
                        'entity_id' => 'zMRVsEqPxuwiGl',
                        'entity_type' => 'customer',
                        'line1' => 'shipping address line 1',
                        'line2' => 'some line two',
                        'city' => 'Bengaluru',
                        'zipcode' => '560029',
                        'state' => 'Karnataka',
                        'country' => 'in',
                        'type' => 'shipping_address',
                        'primary' => true,
                        'contact' => null,
                        'name' => null,
                        'tag' => null,
                        'landmark' => null,
                        'deleted_at' => null,
                        'created_at' => 1688365852,
                        'source_id' => null,
                        'source_type' => 'payment_pages',
                    ],
                ],
                '1cc_consent_banner_views' => 0,
                '1cc_customer_consent' => 1,
            ],
        ],
    ],

    'testVerifyTruecallerRequestInternalForSuccessResponseOneClickCheckout' => [
        'request' => [
            'userProfile' => [
                'contact' => 919878543210,
                'email' => 'testexistingglobalcustomer@razorpay.com',
            ],
            'callbackContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
                'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'resolved',
                'email' => 'testexistingglobalcustomer@razorpay.com',
                'contact' => '+919878543210',
                'logged_in' => 1,
                'global_customer_id' => 'zMRVsEqPxuwiGl',
                'tokens' => [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        [
                            'id' => 'token_M9EQ5OztDvu5oh',
                            'entity' => 'token',
                            'token' => '1000lcardtoken',
                            'bank' => 'ICIC',
                            'wallet' => 'paytm',
                            'method' => 'card',
                            'card' => [
                                'entity' => 'card',
                                'name' => '',
                                'last4' => '1111',
                                'network' => 'Visa',
                                'type' => 'credit',
                                'issuer' => 'HDFC',
                                'international' => false,
                                'emi' => true,
                                'sub_type' => null,
                                'token_iin' => null,
                                'expiry_month' => '01',
                                'expiry_year' => '2099',
                                'flows' => [
                                    'recurring' => true,
                                ],
                                'cobranding_partner' => null,
                            ],
                            'recurring' => false,
                            'recurring_details' => [
                                'status' => null,
                                'failure_reason' => null,
                            ],
                            'auth_type' => null,
                            'mrn' => null,
                            'used_at' => 1688365852,
                            'created_at' => 1688365852,
                            'expired_at' => 1706725799,
                            'consent_taken' => true,
                            'status' => 'active',
                            'notes' => [],
                            'error_description' => null,
                            'source' => 'business',
                            'dcc_enabled' => false,
                            'max_amount' => null,
                            'error_code' => null,
                            'compliant_with_tokenisation_guidelines' => true,
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => 'M9ICOyJ139iODr',
                        'entity_id' => 'zMRVsEqPxuwiGl',
                        'entity_type' => 'customer',
                        'line1' => 'billing address line 1',
                        'line2' => 'some line two',
                        'city' => 'Bengaluru',
                        'zipcode' => '560030',
                        'state' => 'Karnataka',
                        'country' => 'in',
                        'type' => 'billing_address',
                        'primary' => true,
                        'contact' => null,
                        'name' => null,
                        'tag' => null,
                        'landmark' => null,
                        'deleted_at' => null,
                        'created_at' => 1688365852,
                        'source_id' => null,
                        'source_type' => 'shopify',
                    ],
                    [
                        'id' => 'M9ICOzBhiakey8',
                        'entity_id' => 'zMRVsEqPxuwiGl',
                        'entity_type' => 'customer',
                        'line1' => 'shipping address line 1',
                        'line2' => 'some line two',
                        'city' => 'Bengaluru',
                        'zipcode' => '560029',
                        'state' => 'Karnataka',
                        'country' => 'in',
                        'type' => 'shipping_address',
                        'primary' => true,
                        'contact' => null,
                        'name' => null,
                        'tag' => null,
                        'landmark' => null,
                        'deleted_at' => null,
                        'created_at' => 1688365852,
                        'source_id' => null,
                        'source_type' => 'payment_pages',
                    ],
                ],
                'one_cc_consent_banner_views' => 0,
                'one_cc_customer_consent' => 1,
            ],
        ],
    ],

    'testVerifyTruecallerRequestForRejectedResponse' => [
        'request' => [
            'userRejectedContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'status' => 'user_rejected'
            ],
            'usedAnotherNumberContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'status' => 'use_another_number'
            ],
        ]
    ],

    'testVerifyTruecallerRequestForErrorResponse' => [
        'request' => [
            'callbackContent' => [
                'requestId' => 'KlUikwkY8BSH6u-01',
                'accessToken' => 'a1asX--8_yw-OF--E6Gj_DPyKelJIGUUeYB9U9MJhyeu4hOCbrl',
                'endpoint' => 'https://profile4-noneu.truecaller.com/v1/default',
            ],
        ]
    ],

    'testCreateTruecallerAuthRequestInternal' => [
        'request' => [
            'url'       => '/internal/customers/truecaller/auth',
            'method'    => 'post',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'status' => 'active',
                'truecaller_status' => null,
                'context' => '10000000000000',
                'service' => 'checkout',
            ],
        ]
    ]
];
