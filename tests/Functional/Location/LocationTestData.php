<?php

return [
    'testGetCountries' => [
        'request' => [
            'method' => 'GET',
            'url' => '/countries',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetStates' => [
        'request' => [
            'method' => 'GET',
            'url' => '/states/at',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetStatesForIncorrectCountryCode' => [
        'request' => [
            'method' => 'GET',
            'url' => '/states/ww',
        ],
        'response' => [
            'content' => [
                'Status' => 'Country code sent is invalid'
            ],
            'status_code' => 400,
        ],
    ],
];
