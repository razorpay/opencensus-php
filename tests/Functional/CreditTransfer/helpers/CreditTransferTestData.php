<?php

return [
    'testCreditTransferCreateAsync' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/credit_transfer/create_async',
            'content' => [
                'amount'             => 1000,
                'currency'           => 'INR',
                'channel'            => 'rzpx',
                'description'        => 'testing',
                'mode'               => 'IFT',
                'source_entity_id'   => 'abcd1234abcd12',
                'source_entity_type' => 'payout',
                'payer_account'      => '1234561234561234',
                'payer_name'         => 'test merchant',
                'payer_ifsc'         => 'YESB0CMSNOC',
                'payee_details'      => [
                    'account_number' => '',
                    'ifsc_code'      => '',
                ],
                'payee_account_type' => 'bank_account'
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'accepted'
            ],
        ],
    ],
];
