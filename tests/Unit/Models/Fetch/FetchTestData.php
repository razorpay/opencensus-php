<?php

use \RZP\Constants\Entity;
use RZP\Http\BasicAuth\Type;

return [
    Entity::ADDRESS => [
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

    Entity::ADMIN => [
        Type::PRIVILEGE_AUTH => [
            [
                'email' => 'void@razorpay.com'
            ],
        ],
    ],

    Entity::ADMIN_LEAD => [
        Type::PRIVILEGE_AUTH => [
            [
                'email' => 'void@razorpay.com'
            ],
        ],
    ],

    Entity::GROUP => [
        Type::PRIVILEGE_AUTH => [
            [
                'name' => 'razarpay'
            ],
        ],
    ],

    Entity::ORG_FIELD_MAP => [
        Type::PRIVILEGE_AUTH => [
            [
                'entity_name' => 'fake'
            ],
        ],
    ],

    Entity::ORG_HOSTNAME => [
        Type::PRIVILEGE_AUTH => [
            [
                'hostname' => 'fake'
            ],
        ],
    ],

    Entity::ORG => [
        Type::PRIVILEGE_AUTH => [
            [
                'auth_type' => 'fake'
            ],
        ],
    ],

    Entity::PERMISSION => [
        Type::PRIVILEGE_AUTH => [
            [
                'category' => 'fake'
            ],
        ],
    ],

    Entity::ROLE => [
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

    Entity::ADJUSTMENT => [
        Type::PRIVILEGE_AUTH => [
            [
                'transaction_id' => str_random(14)
            ],
        ],
    ],

    Entity::BANK_ACCOUNT => [
        Type::PRIVILEGE_AUTH => [
            [
                'entity_id' => str_random(14)
            ],
        ],
    ],

    Entity::BANK_TRANSFER => [
        Type::PRIVILEGE_AUTH => [
            [
                'mode'       => 'fake',
                'payment_id' => 'FAKEPAYMENTID1'
            ],
        ],
    ],

    Entity::BATCH => [
        Type::PRIVILEGE_AUTH => [
            [
                'type'        => 'refund',
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    Entity::IIN => [
        Type::PRIVILEGE_AUTH => [
            [
                'type' => 'debit',
                'iin'  => '110000'
            ],
        ],
    ],

    Entity::CARD => [
        Type::PRIVILEGE_AUTH => [
            [
                'iin' => '110000'
            ],
        ],
    ],

    Entity::COUPON => [
        Type::PRIVILEGE_AUTH => [
            [
                'entity_id'   => str_random(14),
                'merchant_id' => '10000000000000',
                'entity_type' => 'promotion'
            ],
        ],
    ],
];
