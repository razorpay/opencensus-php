<?php

return [
    'api' => [
        'production' =>  env('APP_URL'),
        'alpha'      =>  env('ALPHA_APP_URL'),
        'beta'       =>  env('BETA_APP_URL'),
    ],
    'checkout'      =>  'https://checkout.razorpay.com',
    'cdn' => [
        'beta'       => 'https://betacdn.razorpay.com',
        'production' => 'https://cdn.razorpay.com',
        'testing'    => 'https://dummycdn.razorpay.com',
        'dev'        => 'https://dummycdn.razorpay.com',
    ],
];
