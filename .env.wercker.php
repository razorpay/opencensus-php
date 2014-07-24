<?php

return array(
    'DB_MYSQL_HOST'             => getenv('WERCKER_MYSQL_HOST'),
    'DB_MYSQL_PORT'             => getenv('WERCKER_MYSQL_PORT'),
    'DB_MYSQL_DATABASE'         => getenv('WERCKER_MYSQL_DATABASE'),
    'DB_MYSQL_USERNAME'         => getenv('WERCKER_MYSQL_USERNAME'),
    'DB_MYSQL_PASSWORD'         => getenv('WERCKER_MYSQL_PASSWORD'),

    'APP_DASHBOARD_URL'     	=> 'https://dashboard.razorpay.com/',
    'APP_DASHBOARD_SECRET'      => 'secret',

//    'HDFC_ID'                   => getenv('HDFC_ID'),
//    'HDFC_PASSWORD'             => getenv('HDFC_PASSWORD'),
);