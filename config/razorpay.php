<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Add the curresponding email addresses for the respective categories
    |--------------------------------------------------------------------------
    |
    | Here you can add different company email addresses
    |
    */

    'emails' => array(

        'contact' => 'contact@razorpay.com',

        'sales' => 'salesteam@razorpay.com',

        'activations' => 'activationsteam@razorpay.com'
    ),

    'zapier'    =>  [
        'signups'       =>  'https://zapier.com/hooks/catch/1088429/2e1xtg/',
        'submissions'   =>  'https://hooks.zapier.com/hooks/catch/1088429/46x8fa/',
        'activations'   =>  'https://hooks.zapier.com/hooks/catch/1088429/4twqyo/',
        // Define this and set to true in order to enable mocking
        'mock'          =>  env('ZAPIER_MOCK', false),
    ],

    'slack' =>  [
        // Channel to which to log operation team actions
        'operations'    =>  'C0KUX9WSE',
        'activations'   =>  'C17UC7DHS',
        'creevey'       =>  'C0KUX9WSE',
        'risk'          =>  'C0SG9Q7TM',

        // This is used by the /v query command on slack
        'command_token' =>  env('SLACK_COMMAND_TOKEN', 'By9i0lp0y0T4mvgUn0Ljj1Rt'),
    ],

    'signup'    => [
        'nocaptcha_secret'  => env('NOCAPTCHA_SECRET')
    ],

    'graphql'   => [
        'server_url'    => env('GRAPHQL_SERVER_URL'),
        'server_canary_url' => env('GRAPHQL_SERVER_URL'),
        'server_path'   => env('GRAPHQL_SERVER_PATH')
    ],
);
