<?php


use App\User\Constants;

return [
    'new_signup_experiments_config' => [
        'rx_onboarding_v2' => [
            Constants::REQUEST_ORIGIN        => 'banking',
            Constants::TIMESTAMP_THRESHOLD   => 1594625400, // "13 Jul 2020, 01:00:00 PM".
            Constants::DEFAULT_RESULT        => ['result' => 'off']
        ],
        // new onboarding CA Self Serve flow
        'rx_ca_self_serve_flow' => [
            Constants::REQUEST_ORIGIN        => 'banking',
            Constants::TIMESTAMP_THRESHOLD   => 1601562600, // "1 Oct 2020, 20:00:00 IST"
            Constants::DEFAULT_RESULT        => ['result' => 'off']
        ],
        // App framework - which redesigns Home screen and adds App store on RX dashboard
        'rx_home_v2' => [
            Constants::REQUEST_ORIGIN        => 'banking',
            Constants::TIMESTAMP_THRESHOLD   => 1604500200, // 04 Nov 2020, 08:00:00 PM IST
            Constants::DEFAULT_RESULT        => ['result' => 'off'],
        ],
    ]
];

