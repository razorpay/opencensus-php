<?php


use App\User\Constants;

return [
    'new_signup_experiments_config' => [
        // new onboarding CA Self Serve flow
        'rx_ca_self_serve_flow' => [
            Constants::REQUEST_ORIGIN        => 'banking',
            Constants::TIMESTAMP_THRESHOLD   => 1601562600, // "1 Oct 2020, 20:00:00 IST"
            Constants::DEFAULT_RESULT        => ['result' => 'off']
        ],
    ]
];

