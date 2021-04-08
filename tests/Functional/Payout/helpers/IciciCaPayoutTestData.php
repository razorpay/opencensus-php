<?php

return [
    'testIciciAccountStatementFetchV2' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'icici',
            ],
        ],
        'response' => [
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'icici'
            ],
        ],
    ],
];
