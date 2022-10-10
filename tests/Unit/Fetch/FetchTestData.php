<?php

use Illuminate\Support\Str;
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

    E::ADMIN_REPORT => [],

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

    E::BANKING_ACCOUNT => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::BANKING_ACCOUNT_STATEMENT => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::BANKING_ACCOUNT_STATEMENT_POOL_RBL => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::BANKING_ACCOUNT_STATEMENT_POOL_ICICI => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::BANKING_ACCOUNT_STATEMENT_DETAILS => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::BANKING_ACCOUNT_COMMENT => [
        Type::PRIVILEGE_AUTH => [
            [
                'source_team_type' => 'internal'
            ],
        ],
    ],

    E::BANKING_ACCOUNT_CALL_LOG => [
        Type::PRIVILEGE_AUTH => [
            [
                'banking_account_id' => str_random(14)
            ],
        ],
    ],

    E::BANKING_ACCOUNT_BANK_LMS => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::EXTERNAL => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
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

    E::D2C_BUREAU_DETAIL => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ]
        ]
    ],

    E::D2C_BUREAU_REPORT => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ]
        ]
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

    E::BHARAT_QR => [
        Type::PRIVILEGE_AUTH => [
            [
                'method'     => 'card',
                'payment_id' => 'FAKEPAYMENTID1'
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

    E::UPI_MANDATE => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
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

    E::PAYMENT => [
        Type::PRIVATE_AUTH    => [
            [
                'email' => 'test@razorpay.com'
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

    E::PAYOUT_LINK => [
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

    E::QR_CODE => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::QR_PAYMENT => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::QR_PAYMENT_REQUEST => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => 'merchant123456'
            ],
        ],
    ],

    E::FUND_ACCOUNT_VALIDATION => [
        Type::ADMIN_AUTH => [
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

    E::WORKFLOW_PAYOUT_AMOUNT_RULES => [
        Type::ADMIN_AUTH => [
            [
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

    E::SETTLEMENT_ONDEMAND => [
        Type::ADMIN_AUTH => [
            [
                'merchant_id' => '10000000000000',
                'status'      => 'processed',
            ],
        ],
    ],

    E::SETTLEMENT_ONDEMAND_FEATURE_CONFIG => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::EARLY_SETTLEMENT_FEATURE_PERIOD => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ],
        ],
    ],

    E::SETTLEMENT_ONDEMAND_ATTEMPT => [
        Type::PRIVILEGE_AUTH => [
            [
                'settlement_ondemand_transfer_id' => 'stid000001'
            ],
        ],
    ],

    E::SETTLEMENT_ONDEMAND_BULK => [
        Type::PRIVILEGE_AUTH => [
            [
                'settlement_ondemand_id' => 'sid000001'
            ],
        ],
    ],

    E::SETTLEMENT_ONDEMAND_TRANSFER => [
        Type::PRIVILEGE_AUTH => [
            [
                'payout_id' => 'pid000001'
            ],
        ],
    ],

    E::SETTLEMENT_ONDEMAND_PAYOUT => [
        Type::ADMIN_AUTH => [
            [
                'merchant_id'            => '10000000000000',
                'status'                 => 'processed',
                'settlement_ondemand_id' => 'sod_' . str_random(14),
            ],
        ],
    ],

    E::SETTLEMENT_ONDEMAND_FUND_ACCOUNT => [
        Type::ADMIN_AUTH => [
            [
                'merchant_id'            => '10000000000000',
            ],
        ],
    ],

    // Full test case for Invoices Fetch
    E::INVOICE => [

        Type::PRIVATE_AUTH => [
            [
                'type'            => 'invoice',
                'payment_id'      => 'pay_F3abwoFneOLGrd',
                'receipt'         => 'jfksfksfjsgkkk',
                // as "The id provided does not exist" Exception is thrown, should be mocked TODO
                // 'customer_id'     => 'F3abwoFneOLGrd',
                'entity_type'     => 'invoices',
                'international'   => false,
                'subscription_id' => 'sub_F3aSpOE1PAXHDT',
            ],
        ],

        Type::PROXY_AUTH => [
            [
                'type'             => 'invoice',
                'payment_id'       => 'pay_F3abwoFneOLGrd',
                'receipt'          => 'jfksfksfjsgkkk',
                // as "The id provided does not exist" Exception is thrown, should be mocked TODO
                // 'customer_id'     => 'F3abwoFneOLGrd',
                // 'batch_id'        => '12345678901234',
                // 'user_id' => 'F3abwoFneOLGrd',
                'entity_type'      => 'invoices',
                'international'    => false,
                'subscription_id'  => 'sub_F3aSpOE1PAXHDT',
                'status'           => 'issued',
                'types'            => ['invoice'],
                'statuses'         => ['issued'],
                'customer_name'    => 'jayD',
                'customer_contact' => '+919999999999',
                'customer_email'   => 'j@j.com',
                //'notes' => [],
                'q'                => 'something',
                'search_hits'      => true,
                'subscriptions'    => true,
                'expand.*'         => 'payments',
            ],
        ],
    ],

    E::MERCHANT_INVOICE => [],

    E::REWARD => [],

    E::MERCHANT_REWARD => [],

    E::REWARD_COUPON => [],

    E::NODAL_STATEMENT => [],

    E::PAYMENT_LINK => [],

    E::PAPER_MANDATE_UPLOAD => [],

    E::CONTACT => [],

    E::FUND_ACCOUNT => [],

    E::STATEMENT => [],

    E::MERCHANT_USER => [],

    E::ACCESS_CONTROL_PRIVILEGES => [],

    E::ROLES => [],

    E::ACCESS_POLICY_AUTHZ_ROLES_MAP => [],

    E::ACCESS_CONTROL_HISTORY_LOGS => [],

    E::ROLE_ACCESS_POLICY_MAP => [],

    E::LOW_BALANCE_CONFIG => [],

    E::PAYOUTS_INTERMEDIATE_TRANSACTIONS => [],

    E::SUB_BALANCE_MAP => [],

    E::ORDER => [],

    E::P2P_DEVICE => [
        Type::PRIVATE_AUTH  => [
            [
                'contact'   => '919876543210',
            ],
        ],
        Type::PRIVILEGE_AUTH  => [],
    ],

    E::P2P_DEVICE_TOKEN => [],

    E::P2P_REGISTER_TOKEN => [],

    E::P2P_BANK => [],

    E::P2P_BANK_ACCOUNT => [],

    E::P2P_VPA => [],

    E::P2P_HANDLE => [],

    E::P2P_BENEFICIARY => [],

    E::P2P_TRANSACTION => [],

    E::P2P_UPI_TRANSACTION => [],

    E::P2P_CONCERN => [],

    E::P2P_MANDATE => [],

    E::P2P_UPI_MANDATE => [],

    E::P2P_MANDATE_PATCH => [],

    E::P2P_BLACKLIST => [],

    E::CUSTOMER => [],

    E::CREDITNOTE => [],

    E::MPAN => [],

    E::UPI_TRANSFER => [],

    E::UPI_METADATA => [],

    E::IDEMPOTENCY_KEY => [],

    E::BANK_TRANSFER_REQUEST => [],

    E::UPI_TRANSFER_REQUEST => [],

    E::VIRTUAL_ACCOUNT_TPV => [],

    E::BANK_TRANSFER_HISTORY => [],

    E::VIRTUAL_VPA_PREFIX => [],

    E::VIRTUAL_VPA_PREFIX_HISTORY => [],

    E::BALANCE_CONFIG => [
        Type::PROXY_AUTH => [
            [
                'balance_id' => str_random(14)
            ],
        ],
    ],

    E::BALANCE => [
        Type::PROXY_AUTH => [
            [
                'type' => 'banking'
            ],
        ],
    ],

    E::MERCHANT_FRESHDESK_TICKETS => [
        Type::PROXY_AUTH => [
            [
                'type'        => str_random(),
            ],
        ],
    ],

    E::MERCHANT_REMINDERS => [
        Type::PROXY_AUTH => [
            [
                'merchant_id' => str_random(14),
                'reminder_id' => str_random(14)
            ],
        ],
    ],

    E::FEE_RECOVERY => [],

    E::BANKING_ACCOUNT_TPV => [],

    E::PAYOUT_DOWNTIMES => [],

    E::FUND_LOADING_DOWNTIMES => [],

    E::REQUEST_LOG => [],

    E::MERCHANT_E_INVOICE => [],

    E::SUB_VIRTUAL_ACCOUNT => [],

    E::DISPUTE_EVIDENCE => [],

    E::DISPUTE_EVIDENCE_DOCUMENT => [],

    E::MERCHANT_RISK_NOTE => [],

    E::PAYMENT_FRAUD => [],

    E::PAYOUTS_BATCH => [],

    E::CREDIT_TRANSFER => [],

    E::CHECKOUT_ORDER => [
        'checkout_id' => Str::random(14),
        'invoice_id' => Str::random(14),
        'merchant_id' => Str::random(14),
        'order_id' => Str::random(14),
    ],

    E::TRUSTED_BADGE => [],

    E::TRUSTED_BADGE_HISTORY => [],

    E::PAYMENT_PAGE_ITEM => [],

    E::LEDGER_STATEMENT => [],

    E::DIRECT_ACCOUNT_STATEMENT => [],

    E::MERCHANT_NOTIFICATION_CONFIG => [],

    E::REFERRALS => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' => '10000000000000'
            ]
        ]
    ],

    E::MERCHANT_ATTRIBUTE => [
        Type::PRIVILEGE_AUTH => [
            [
                'merchant_id' =>  '10000000000000'
            ]
        ]
    ],

    E::PARTNER_BANK_HEALTH => [],
];
