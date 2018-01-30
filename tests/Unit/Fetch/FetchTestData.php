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
];
