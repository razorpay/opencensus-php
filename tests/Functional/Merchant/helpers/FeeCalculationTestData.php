<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testPaymentCalculateFeesWithFeeConfigNetBankingPayeeCustomerFlatValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'bank'     => 'HDFC',
                'convenience_fee' => 200
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.36,
                    'fees'             => 2.36,
                    'amount'          => 102.36,
                    'razorpay_fee'    => 2,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigNetBankingPayeeCustomerPercentageValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'bank'     => 'HDFC'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.27,
                    'fees'             => 1.77,
                    'amount'          => 101.77,
                    'razorpay_fee'    => 1.5,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigNetBankingPayeeBusinessPercentageValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'bank'     => 'HDFC'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.27,
                    'fees'             => 1.77,
                    'amount'          => 101.77,
                    'razorpay_fee'    => 1.5,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigNetBankingPayeeBusinessFlatValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'bank'     => 'HDFC'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.36,
                    'fees'             => 2.36,
                    'amount'          => 102.36,
                    'razorpay_fee'    => 2,
                    'original_amount' => 100
                ],
            ],
        ],
    ],
    'testPaymentCalculateFeesWithFeeConfigNetBankingPayeeCustomerFlatValueWithCFB' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'bank'     => 'HDFC',
                'convenience_fee' => 200
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.36,
                    'fees'             => 2.36,
                    'amount'          => 102.36,
                    'razorpay_fee'    => 2,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigNetBankingPayeeCustomerPercentageValueWithCFB' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'bank'     => 'HDFC'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.27,
                    'fees'             => 1.77,
                    'amount'          => 101.77,
                    'razorpay_fee'    => 1.5,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigNetBankingPayeeBusinessPercentageValueWithCFB' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'bank'     => 'HDFC'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.27,
                    'fees'             => 1.77,
                    'amount'          => 101.77,
                    'razorpay_fee'    => 1.5,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigNetBankingPayeeBusinessFlatValueWithCFB' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'bank'     => 'HDFC'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.36,
                    'fees'             => 2.36,
                    'amount'          => 102.36,
                    'razorpay_fee'    => 2,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesForUPIWithFeeConfigNetBankingPayeeCustomerFlatValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'upi',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'vpa'      => 'test@okaxis'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.72,
                    'fees'            => 4.72,
                    'amount'          => 104.72,
                    'razorpay_fee'    => 4,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesForUPIWithFeeConfigNetBankingPayeeCustomerPercentageValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'upi',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'vpa'      => 'test@okaxis'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.72,
                    'fees'            => 4.72,
                    'amount'          => 104.72,
                    'razorpay_fee'    => 4,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesForUPIWithFeeConfigNetBankingPayeeBusinessPercentageValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'upi',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'vpa'      => 'test@okaxis'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.72,
                    'fees'            => 4.72,
                    'amount'          => 104.72,
                    'razorpay_fee'    => 4,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesForUPIWithFeeConfigNetBankingPayeeBusinessFlatValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'upi',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'vpa'      => 'test@okaxis'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.72,
                    'fees'            => 4.72,
                    'amount'          => 104.72,
                    'razorpay_fee'    => 4,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigCreditCardPayeeCustomerFlatValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'card',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'card'     => ['number' => '4111111111111111', 'cvv' => 123, 'name' => 'QARazorpay', 'expiry_month' => 11, 'expiry_year' => 23],
                'convenience_fee' => 200
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0,
                    'fees'             => 2,
                    'amount'          => 102,
                    'razorpay_fee'    => 2,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigCreditCardPayeeCustomerPercentageValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'card',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'card'     => ['number' => '4111111111111111', 'cvv' => 123, 'name' => 'QARazorpay', 'expiry_month' => 11, 'expiry_year' => 23]
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0,
                    'fees'             => 1.20,
                    'amount'          => 101.20,
                    'razorpay_fee'    => 1.20,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigCreditCardPayeeBusinessPercentageValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'card',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'card'     => ['number' => '4111111111111111', 'cvv' => 123, 'name' => 'QARazorpay', 'expiry_month' => 11, 'expiry_year' => 23]
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0,
                    'fees'             => 1.20,
                    'amount'          => 101.20,
                    'razorpay_fee'    => 1.20,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigCreditCardPayeeBusinessFlatValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'card',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'card'     => ['number' => '4111111111111111', 'cvv' => 123, 'name' => 'QARazorpay', 'expiry_month' => 11, 'expiry_year' => 23]
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0,
                    'fees'             => 2,
                    'amount'          => 102,
                    'razorpay_fee'    => 2,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigWalletPayeeBusinessFlatValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'wallet',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'wallet'   =>  'freecharge',
                'convenience_fee' => 200
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0,
                    'fees'             => 0,
                    'amount'          => 100,
                    'razorpay_fee'    => 0,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigWalletPayeeCustomerFlatValueGreaterthanFee' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'wallet',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'wallet'   =>  'freecharge',
                'convenience_fee' => 400
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.54,
                    'fees'             => 3.54,
                    'amount'          => 103.54,
                    'razorpay_fee'    => 3,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigWalletPayeeBusinessPercentageValue' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'wallet',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'wallet'   =>  'freecharge'
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0.36,
                    'fees'             => 2.37,
                    'amount'          => 102.37,
                    'razorpay_fee'    => 2.01,
                    'original_amount' => 100
                ],
            ],
        ],
    ],

    'testPaymentCalculateFeesWithFeeConfigWalletPayeeCustomerFlatValueZero' => [
        'request'  => [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'method'   => 'wallet',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'wallet'   =>  'freecharge',
                'convenience_fee' => 0

            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => 0,
                    'fees'             => 0,
                    'amount'          => 100,
                    'razorpay_fee'    => 0,
                    'original_amount' => 100
                ],
            ],
        ],
    ],
];
