<?php

return [
    'production'    =>  'https://api.razorpay.com',
    'beta'          =>  'https://beta.razorpay.com',
    'checkout'      =>  'https://checkout.razorpay.com',

    'invoice'       =>  [
        'production'    => 'https://invoices.razorpay.com',
        'beta'          => 'https://betainvoices.razorpay.com',
        'testing'       => 'https://dummyinvoices.razorpay.com',
        'dev'           => 'https://dummyinvoices.razorpay.com',
    ],

    'cdn' => [
        'beta'       => 'https://betacdn.razorpay.com',
        'production' => 'https://cdn.razorpay.com',
        'testing'    => 'https://dummycdn.razorpay.com',
        'dev'        => 'https://dummycdn.razorpay.com',
    ],
];