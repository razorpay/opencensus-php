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

    'sorting_hat'   =>  [
        // This is the token we pass to sorting-hat
        // to verify authenticity of signups
        'token' =>  env('SORTING_HAT_TOKEN', '4cace071107c854f0e320c309da81ff62c950da324e2c21d312362f174ca5b5e'),
        // sorting-hat url
        'url'   =>  env('SORTING_HAT_URL', 'https://sorting-hat-slack.herokuapp.com/')
    ],

    'zapier'    =>  [
        'signups'       =>  'https://zapier.com/hooks/catch/1088429/2e1xtg/',
        'submissions'   =>  'https://hooks.zapier.com/hooks/catch/1088429/46x8fa/',
        'activations'   =>  'https://hooks.zapier.com/hooks/catch/1088429/4twqyo/',
        // Define this and set to true in order to enable mocking
        'mock'          =>  getenv('ZAPIER_MOCK', false),
    ],

    'slack' =>  [
        // Channel to which to log operation team actions
        'operations'    =>  '#operations_log',
        'activations'   =>  '#activations_log',
        'creevey'       =>  '#operations_log',
        'risk'          =>  '#risk',

        // This is used by the /v query command on slack
        'command_token' =>  env('SLACK_COMMAND_TOKEN', 'By9i0lp0y0T4mvgUn0Ljj1Rt'),
    ],

    'mailchimp' => [
        'list_id'   =>  env('MAILCHIMP_LIST_ID', 'random_id'),
        'api_key'   =>  env('MAILCHIMP_API_TOKEN', 'mailchimp_token'),
        'mock'      =>  env('MAILCHIMP_MOCK', false),
    ]
);
