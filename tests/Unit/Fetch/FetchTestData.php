<?php

use RZP\Http\BasicAuth\Type;
use \RZP\Constants\Entity as E;

return [
    E::ADDRESS => [
        Type::PRIVATE_AUTH   => [

        ],
        Type::PROXY_AUTH     => [

        ],
        Type::PRIVILEGE_AUTH => [
            [
                'entity_id' => str_random(14)
            ],
        ],
    ],

    E::ADMIN => [
        Type::PRIVILEGE_AUTH => [
            [
                'email' => 'void@razorpay.com'
            ],
        ],
    ],

    E::ADMIN_LEAD => [
        Type::PRIVILEGE_AUTH => [
            [
                'email' => 'void@razorpay.com'
            ],
        ],
    ],

    E::GROUP => [
        Type::PRIVILEGE_AUTH => [
            [
                'name' => 'razarpay'
            ],
        ],
    ],

    E::ORG_FIELD_MAP => [
        Type::PRIVILEGE_AUTH => [
            [
                'entity_name' => 'fake'
            ],
        ],
    ],

    E::ORG_HOSTNAME => [
        Type::PRIVILEGE_AUTH => [
            [
                'hostname' => 'fake'
            ],
        ],
    ],

    E::ORG => [
        Type::PRIVILEGE_AUTH => [
            [
                'auth_type' => 'fake'
            ],
        ],
    ],

    E::PERMISSION => [
        Type::PRIVILEGE_AUTH => [
            [
                'category' => 'fake'
            ],
        ],
    ],

    E::ROLE => [
        Type::PRIVILEGE_AUTH => [
            [
                'name' => 'fake'
            ],
        ],
        Type::ADMIN_AUTH => [
            [
                'org_id' => str_random(14)
            ],
        ],
    ],

    E::ADJUSTMENT => [
        Type::PRIVILEGE_AUTH => [
            [
                'transaction_id' => str_random(14)
            ],
        ],
    ],

    E::BANK_ACCOUNT => [
        Type::PRIVILEGE_AUTH => [
            [
                'entity_id' => str_random(14)
            ],
        ],
    ],

    E::BANK_TRANSFER => [
        Type::PRIVILEGE_AUTH => [
            [
                'mode'       => 'fake',
                'payment_id' => 'FAKEPAYMENTID1'
            ],
        ],
    ],

    E::BATCH => [
        Type::PROXY_AUTH => [
            [
                'type'        => 'refund',
            ]
        ],
        Type::PRIVILEGE_AUTH => [
            [
                'type'        => 'refund',
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::IIN => [
        Type::PRIVILEGE_AUTH => [
            [
                'type' => 'debit',
                'iin'  => '110000'
            ],
        ],
    ],

    E::CARD => [
        Type::PRIVILEGE_AUTH => [
            [
                'iin' => '110000'
            ],
        ],
    ],

    E::COUPON => [
        Type::PRIVILEGE_AUTH => [
            [
                'entity_id'   => str_random(14),
                'merchant_id' => '10000000000000',
                'entity_type' => 'promotion'
            ],
        ],
    ],

    E::COMMENT => [
        Type::ADMIN_AUTH    => [
            [
                'entity_type' => 'dispute'
            ],
        ],
    ],

    E::DISPUTE => [
        Type::PRIVILEGE_AUTH    => [
            [
                'amount' => 1
            ],
        ],
    ],

    E::EMI_PLAN => [
        Type::PRIVILEGE_AUTH    => [
            [
                'bank' => "hdfc"
            ],
        ],
    ],

    E::FEATURE=> [
        Type::PRIVILEGE_AUTH    => [
            [
                'name' => "name"
            ],
        ],
    ],

    E::FILE_STORE=> [
        Type::PRIVILEGE_AUTH    => [
            [
                'type' => "type"
            ],
        ],
    ],

    E::ITEM=> [
        Type::PRIVILEGE_AUTH    => [
            [
                'merchant_id' => "merchant123456"
            ],
        ],
        Type::PROXY_AUTH    => [
            [
                'type' => "plan"
            ],
        ],
    ],

    E::FILE_STORE=> [
        Type::PRIVILEGE_AUTH    => [
            [
                'type' => "type"
            ],
        ],
    ],

    E::KEY => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::MERCHANT => [
        Type::PRIVILEGE_AUTH => [
            [
                'international' => true
            ],
        ],
        Type::ADMIN_AUTH => [
            [
                'groups' => []
            ],
        ],
    ],

    E::OFFER => [
        Type::PRIVILEGE_AUTH => [
            [
                'payment_method' => 'netbanking'
            ],
        ],
    ],

    E::PAYOUT => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::PLAN => [
        Type::PROXY_AUTH => [
            [
//                'period'    => 'weekly',
                'interval'  => 1
            ],
        ],
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id'   => 'merchant123456',
                'item_id'       => 'merchant123456',
            ],
        ],
    ],

    E::PRICING => [
        Type::PRIVILEGE_AUTH => [
            [
                'deleted' => '1'
            ],
        ],
    ],

    E::REPORT => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
        Type::PROXY_AUTH => [
            [
                'type' => '1'
            ],
        ],
    ],

    E::REVERSAL => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::RISK => [
        Type::PRIVILEGE_AUTH => [
            [
                'reason' => 'reason'
            ],
        ],
    ],

    E::RISK => [
        Type::PRIVILEGE_AUTH => [
            [
                'reason' => 'reason'
            ],
        ],
    ],

    E::SETTLEMENT => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::STATE => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::TRANSACTION => [
        Type::PRIVILEGE_AUTH => [
            [
                'on_hold' => '1'
            ],
        ],
    ],

    E::TRANSFER => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::USER => [
        Type::PRIVILEGE_AUTH => [
            [
                'email' => 'email@gmail.com'
            ],
        ],
    ],

    E::VIRTUAL_ACCOUNT => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::WORKFLOW => [
        Type::ADMIN_AUTH => [
            [
                'org_id' => 'organization12'
            ],
        ],
    ],

    E::GEO_IP => [
        Type::PRIVILEGE_AUTH => [
            [
                'country' => 'IN'
            ],
        ],
    ],

    E::GATEWAY_DOWNTIME => [
        Type::PRIVATE_AUTH => [
            [
                'partial' => 1
            ],
        ],
    ],

    E::INVOICE => [],

    E::WEBHOOK => [
        Type::PRIVATE_AUTH => [
            [
                'application_id' => str_random(14)
            ],
        ],
    ],

    E::NODAL_STATEMENT => [],
];
