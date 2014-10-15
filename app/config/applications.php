<?php

return array(

    'dashboard' => array(
        'url'       =>  $_ENV['APP_DASHBOARD_URL'],
        'secret'    =>  $_ENV['APP_DASHBOARD_SECRET'],
        'cloud'     =>  true,
        'pretend'   =>  false   
    ),

    'settlement_cron' => array(
        'secret'    =>  'settlement_cron'
    ),

    'mailgun'       =>  array(
        'secret'    =>  'mailgun'
    ),
);