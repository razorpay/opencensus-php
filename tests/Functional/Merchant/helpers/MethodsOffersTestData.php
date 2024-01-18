<?php

return [
    'testGetPaymentMethodsAndOffersForCheckoutWithoutOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm' => true,
                        'grabpay' => true,
                        'touchngo' => true,
                        'boost' => true,
                        'mcash' => true
                    ],
                    'emi' => false,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => false,
                        'debit' => false
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutWithOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm' => true,
                    ],
                    'emi' => false,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => false,
                        'debit' => false
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "deferred",
                        'terms' => "Terms and Condition",
                        'cashback_amount' => 10000,
                    ],
                ],
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutForB2BExportForPaymentLinkWithOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'intl_bank_transfer' => [
                        'usd' => 1,
                        'swift' => 1
                    ],
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 200000,
                        'amount' => 180000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 200000,
                        'amount' => 180000,
                        'terms' => "Terms and Condition",
                    ]
                ]
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutForB2BExportWithNonPaymentLinkOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'intl_bank_transfer' => [],
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ]
                ]
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutForB2BExportWithOrderAmountLessThanMinAmount' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'intl_bank_transfer' => [],
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ]
                ]
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutForB2BExportWithOrderAmountGreaterThanMaxAmount' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'intl_bank_transfer' => [],
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 9000000,
                        'amount' => 8100000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 9000000,
                        'amount' => 8100000,
                        'terms' => "Terms and Condition",
                    ]
                ]
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutWithInvoiceId' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm' => true,
                    ],
                    'emi' => false,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => false,
                        'debit' => false
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ]
                ]
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutWithSubscriptionId' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'subscription_id' => '', // Filled by the TestCase
                'subscription_card_change' => false,
            ],
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm' => true,
                    ],
                    'emi' => false,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => false,
                        'debit' => false
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ]
                ]
            ],
        ],
    ],

    'testGetCacheableMethodsDataForCheckout' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'debit_card' => true,
                'credit_card' => true,
                'prepaid_card' => true,
                'card_networks' => [
                    'AMEX' => 0,
                    'MC' => 1,
                    'VISA' => 1,
                ],
                'card_subtype' => [
                    'consumer' => 1,
                    'business' => 0,
                    'premium' => 0
                ],
                'amex' => false,
                'netbanking' => [
                    'AUBL' => 'AU Small Finance Bank',
                    'UTIB' => 'Axis Bank',
                ],
                'wallet' => [
                    'paytm' => true,
                    'grabpay' => true,
                    'touchngo' => true,
                    'boost' => true,
                    'mcash' => true
                ],
                'emi' => true,
                'upi' => false,
                'cardless_emi' => [],
                'paylater' => [],
                'google_pay_cards' => false,
                'app' => [
                    'cred' => 0,
                    'twid' => 0,
                    'trustly' => 0,
                    'poli' => 0,
                    'sofort' => 0,
                    'giropay' => 0
                ],
                'gpay' => false,
                'emi_types' => [
                    'credit' => true,
                    'debit' => true
                ],
                'debit_emi_providers' => [
                    'HDFC' => 0,
                    'KKBK' => 0,
                    'INDB' => 0
                ],
                'intl_bank_transfer' => [],
                'fpx' => [],
                'nach' => false,
                'cod' => false,
                'offline' => false,
                'upi_intent' => true,
                'upi_type' => [
                    'collect' => 0,
                    'intent' => 0,
                ],
                'emi_plans' => [
                    'CITI' => [
                        'min_amount' =>300000,
                        'plans' => [
                            '3' => 12,
                        ],
                    ],
                    'SBIN' => [
                        'min_amount' =>100000,
                        'plans' => [
                            '3' => 16.5,
                            '6' => 15,
                        ],
                    ],
                ],
                'emi_options' => [
                    'CITI' => [
                        [
                            'duration'   => 3,
                            'interest'   => 12,
                            'min_amount' => 300000,
                            'subvention' => 'customer',
                            'merchant_payback' => '5.18',
                            'processing_fee_plan' => [
                                'type' => 'combination',
                                'percentage' => 1,
                                'amount' => 10000,
                            ]
                        ]
                    ],
                    'SBIN' => [
                        [
                            'duration'   => 3,
                            'interest'   => 16.5,
                            'min_amount' => 100000,
                            'subvention' => 'customer',
                            'merchant_payback' => '5.18',
                        ],
                        [
                            'duration'   => 6,
                            'interest'   => 15,
                            'min_amount' => 100000,
                            'subvention' => 'customer',
                            'merchant_payback' => '5.18',
                            'processing_fee_plan' => [
                                'type' => 'fixed',
                                'amount' => 9900,
                                'min_amount' => 1250000
                            ]
                        ],
                    ]
                ],
                'force_offer_emi_plans' => [
                    'CITI' => [
                        'min_amount' =>300000,
                        'plans' => [
                            '3' => 12,
                        ],
                    ],
                    'SBIN' => [
                        'min_amount' =>100000,
                        'plans' => [
                            '3' => 16.5,
                            '6' => 15,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetEmiDataForCheckoutWithForcedEmiSubventionOfferWithMerchantSpecificEmi' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "emi",
                        'payment_method_type' => "credit",
                        'issuer' => "HDFC",
                        'emi_subvention' => true,
                        'type' => "instant",
                        'terms' => "Terms and Condition",
                    ]
                ],
                'emi_plans' => [
                    'HDFC' => [
                        'min_amount' => 25000,
                        'plans' => [
                            6 => 12.5,
                        ],
                    ],
                ],
                'emi_options' => [
                    'HDFC' => [
                        [
                            'duration' => 6,
                            'interest' => 0,
                            'subvention' => "merchant",
                            'min_amount' => 100000,
                            'merchant_payback' => "5.18",
                            'processing_fee_plan' => [
                                'type' => "fixed",
                                'amount' => 19900,
                            ]
                        ]
                    ],
                ],
                'force_offer' => true,
            ],
        ],
    ],

    'testGetEmiDataForCheckoutWithEmiSubventionOfferWithMerchantSpecificEmi' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "emi",
                        'payment_method_type' => "credit",
                        'issuer' => "HDFC",
                        'emi_subvention' => true,
                        'type' => "instant",
                        'terms' => "Terms and Condition",
                    ]
                ],
                'emi_plans' => [
                    'HDFC' => [
                        'min_amount' => 25000,
                        'plans' => [
                            6 => 12.5,
                        ],
                    ],
                ],
                'emi_options' => [
                    'HDFC' => [
                        [
                            'duration' => 6,
                            'interest' => 0,
                            'subvention' => "merchant",
                            'min_amount' => 100000,
                            'merchant_payback' => "5.18",
                            'processing_fee_plan' => [
                                'type' => "fixed",
                                'amount' => 19900,
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testGetEmiDataForCheckoutWithMultipleSubEmiOffers' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "emi",
                        'payment_method_type' => "credit",
                        'payment_network' => "AMEX",
                        'emi_subvention' => true,
                        'type' => "instant",
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "emi",
                        'payment_method_type' => "credit",
                        'payment_network' => "AMEX",
                        'emi_subvention' => true,
                        'type' => "instant",
                        'terms' => "Terms and Condition",
                    ]
                ],
                'emi_plans' => [
                    'HDFC' => [
                        'min_amount' => 300000,
                        'plans' => [
                            9 => 12,
                            6 => 12,
                        ],
                    ],
                    'AMEX' => [
                        'min_amount' => 300000,
                        'plans' => [
                            9 => 12,
                            6 => 12,
                        ],
                    ],
                ],
                'emi_options' => [
                    'HDFC' => [
                        [
                            'duration' => 9,
                            'interest' => 12,
                            'subvention' => "customer",
                            'min_amount' => 300000,
                            'merchant_payback' => "5.18",
                            'processing_fee_plan' => [
                                'type' => "fixed",
                                'amount' => 19900,
                            ]
                        ],
                        [
                            'duration' => 6,
                            'interest' => 12,
                            'subvention' => "customer",
                            'min_amount' => 300000,
                            'merchant_payback' => "5.18",
                            'processing_fee_plan' => [
                                'type' => "fixed",
                                'amount' => 19900,
                            ]
                        ]
                    ],
                    'AMEX' => [
                        [
                            'duration' => 9,
                            'interest' => 0,
                            'subvention' => "merchant",
                            'min_amount' => 319149,
                            'merchant_payback' => "5.18",
                            'processing_fee_plan' => [
                                'type' => "fixed",
                                'amount' => 19900,
                            ]
                        ],
                        [
                            'duration' => 6,
                            'interest' => 0,
                            'subvention' => "merchant",
                            'min_amount' => 319149,
                            'merchant_payback' => "6.00",
                            'processing_fee_plan' => [
                                'type' => "fixed",
                                'amount' => 19900,
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testGetEmiDataForCheckoutForDebitEmiWithExistingCreditEmi' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'offers' => [],
                'emi_plans' => [
                    'HDFC_DC' => [
                        'min_amount' => 300000,
                        'plans' => [
                            3 => 12,
                        ],
                    ],
                    'HDFC' => [
                        'min_amount' => 300000,
                        'plans' => [
                            3 => 12,
                        ],
                    ],
                ],
                'emi_options' => [
                    'HDFC_DC' => [
                        [
                            'duration' => 3,
                            'interest' => 12,
                            'subvention' => "customer",
                            'min_amount' => 300000,
                            'merchant_payback' => "5.18",
                            'processing_fee_plan' => [
                                'type' => "fixed",
                                'amount' => 19900,
                            ]
                        ]
                    ],
                    'HDFC' => [
                        [
                            'duration' => 3,
                            'interest' => 12,
                            'subvention' => "customer",
                            'min_amount' => 300000,
                            'merchant_payback' => "5.18",
                            'processing_fee_plan' => [
                                'type' => "fixed",
                                'amount' => 19900,
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testGetOffersDataForCheckoutWithOrder' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                        'terms' => "Terms and Condition",
                    ],
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_method_type' => "credit",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "deferred",
                        'terms' => "Terms and Condition",
                        'cashback_amount' => 10000,
                    ]
                ],
            ],
        ],
    ],

    'testGetPaymentMethodsAndOffersForCheckoutWithOrderProcessingFee' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'amount' => 1000000,
            ],
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => true,
                    'prepaid_card' => true,
                    'card_networks' => [
                        'AMEX' => 0,
                        'MC' => 1,
                        'VISA' => 1,
                    ],
                    'card_subtype' => [
                        'consumer' => 1,
                        'business' => 0,
                        'premium' => 0
                    ],
                    'amex' => false,
                    'netbanking' => [
                        'AUBL' => 'AU Small Finance Bank',
                        'UTIB' => 'Axis Bank',
                    ],
                    'wallet' => [
                        'paytm' => true,
                        'grabpay' => true,
                        'touchngo' => true,
                        'boost' => true,
                        'mcash' => true
                    ],
                    'emi' => true,
                    'upi' => false,
                    'cardless_emi' => [],
                    'paylater' => [],
                    'google_pay_cards' => false,
                    'app' => [
                        'cred' => 0,
                        'twid' => 0,
                        'trustly' => 0,
                        'poli' => 0,
                        'sofort' => 0,
                        'giropay' => 0
                    ],
                    'gpay' => false,
                    'emi_types' => [
                        'credit' => true,
                        'debit' => true
                    ],
                    'debit_emi_providers' => [
                        'HDFC' => 0,
                        'KKBK' => 0,
                        'INDB' => 0
                    ],
                    'emi_plans' => [
                        'CITI' => [
                            'min_amount' => 300000,
                            'plans' => [
                                '3' => 12,
                            ],
                        ],
                        'SBIN' => [
                            'min_amount' => 100000,
                            'plans' => [
                                '3' => 16.5,
                                '6' => 15,
                                '9' => 15,
                                '12' => 15,
                            ],
                        ],
                    ],
                    'emi_options' => [
                        'CITI' => [
                            [
                                'duration' => 3,
                                'interest' => 12,
                                'min_amount' => 300000,
                                'processing_fee_plan' => [
                                    'type' => 'combination',
                                    'percentage' => 1,
                                    'amount' => 10000,
                                ]
                            ]
                        ],
                        'SBIN' => [
                            [
                                'duration' => 3,
                                'interest' => 16.5,
                                'min_amount' => 100000,
                            ],
                            [
                                'duration' => 6,
                                'interest' => 15,
                                'min_amount' => 100000,
                            ],
                            [
                                'duration' => 9,
                                'interest' => 15,
                                'min_amount' => 100000,
                                'processing_fee_plan' => [
                                    'type' => 'fixed',
                                    'amount' => 9900,
                                    'min_amount' => 900000
                                ]
                            ],
                            [
                                'duration' => 12,
                                'interest' => 15,
                                'min_amount' => 100000,
                                'processing_fee_plan' => [
                                    'type' => 'fixed',
                                    'amount' => 9900,
                                    'min_amount' => 700000
                                ]
                            ]
                        ]
                    ],
                    'intl_bank_transfer' => [],
                    'fpx' => [],
                    'nach' => false,
                    'cod' => false,
                    'offline' => false,
                    'upi_intent' => true,
                    'upi_type' => [
                        'collect' => 0,
                        'intent' => 0,
                    ],
                    'app_meta' => []
                ],
            ],
        ],
    ],

    'testGetEmiDataForCheckoutWithEmiSubventionOfferWithMerchantSpecificEmiProcessingFee' => [
        'request' => [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type' => 1,
                'amount' => 1000000,
            ],
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "emi",
                        'payment_method_type' => "credit",
                        'issuer' => "SBIN",
                        'emi_subvention' => true,
                        'type' => "instant",
                        'terms' => "Terms and Condition",
                    ]
                ],
                'emi_plans' => [
                    'SBIN' => [
                        'min_amount' => 100000,
                        'plans' => [
                            6 => 15,
                        ],
                    ],
                ],
                'emi_options' => [
                    'SBIN' => [
                        [
                            'duration' => 6,
                            'interest' => 15,
                            'min_amount' => 100000,
                        ]
                    ],
                ],
            ],
        ],
    ],
];
