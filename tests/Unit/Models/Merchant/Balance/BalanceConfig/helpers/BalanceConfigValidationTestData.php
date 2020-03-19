<?php

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Balance\BalanceConfig;

return [
    'testCreateBalanceConfigEntityInvalidBalanceType' => [
        'input'    => [
            'negative_limit_auto'    => 5000000,
            'type'                    => 'random'
        ],
        'expected' => 'Invalid type name: random',
    ],

    'testCreateBalanceConfigEntityBalanceIdMissing' => [
        'input'    => [
            'negative_limit_auto'    => 5000000,
            'type'                    => 'primary',
        ],
        'expected' => 'The balance id field is required.',
    ],

    'testCreateBalanceConfigEntitySuccess' => [
        'input'    => [
            'negative_limit_auto'               => 5000000,
            'negative_limit_manual'             => 5000000,
            'type'                               => 'primary',
            'balance_id'                         => '279839248042',
            'negative_transaction_flows'        => ['payment', 'refund']
        ],
        'expected' => []
    ],

    'testCreateBalanceConfigEntityInvalidTransactionFlowForPrimary' => [
        'input'    => [
            'negative_limit_auto'              => 5000000,
            'type'                             => 'primary',
            'balance_id'                       => '279839248042',
            'negative_transaction_flows'      => ['payment', 'payout']
        ],
        'expected' => 'Negative Flow [payment,payout] is not in the allowed flows list.'.
            ' Allowed flows for balance type primary are [payment,transfer,refund,adjustment]' ,
    ],

    'testCreateBalanceConfigEntityInvalidTransactionFlowForBanking' => [
        'input'    => [
            'negative_limit_auto'              => 5000000,
            'type'                             => 'banking',
            'balance_id'                       => '279839248042',
            'negative_transaction_flows'      => ['payment', 'payout']
        ],
        'expected' => 'Negative Flow [payment,payout] is not in the allowed flows list.'.
            ' Allowed flows for balance type banking are [payout,adjustment]'
    ],

    'testCreateBalanceConfigEntityBankingSuccess' => [
        'input'    => [
            'negative_limit_auto'               => 5000000,
            'type'                               => 'banking',
            'balance_id'                         => '279839248042',
            'negative_transaction_flows'        => ['payout']
        ],
        'expected' => []
    ],

    'testCreateBalanceConfigEntityInvalidTransactionFlow' => [
        'input'    => [
            'negative_limit_auto'               => 5000000,
            'type'                               => 'banking',
            'balance_id'                         => '279839248042',
            'negative_transaction_flows'        => ['random']
        ],
        'expected' => 'Negative Flow [random] is not in the allowed flows list.'.
            ' Allowed flows for balance type banking are [payout,adjustment]'
    ],

    'testCreateBalanceConfigEntityTransactionFlowNotArray' => [
        'input'    => [
            'negative_limit_auto'               => 5000000,
            'type'                               => 'primary',
            'balance_id'                         => '279839248042',
            'negative_transaction_flows'        => 'payment'
        ],
        'expected' => 'The negative transaction flows must be an array.'
    ],
];
