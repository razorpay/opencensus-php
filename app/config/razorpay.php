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

        'sales' => 'salesteam@razorpay.com'
    ),

    'sorting_hat'   =>  [
        'token' =>  '4cace071107c854f0e320c309da81ff62c950da324e2c21d312362f174ca5b5e',
        'url'   =>  'https://sorting-hat-slack.herokuapp.com/'
    ],

    'slack' =>  [
        // Channel to which to log operation team actions
        'operations'    =>  '#operations_log',
        'creevey'       =>  '#operations_log',


        // This is used by the /v query command on slack
        'command_token' =>  'By9i0lp0y0T4mvgUn0Ljj1Rt'
    ],

    'mailchimp' => [
        'list_id'   =>  $_ENV['MAILCHIMP_LIST_ID'],
        'api_key'   =>  $_ENV['MAILCHIMP_API_TOKEN']
    ],
);
