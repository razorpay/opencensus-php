<?php

return array(
    'DB_MYSQL_PORT'             => getenv('WERCKER_MYSQL_HOST'),
    'DB_MYSQL_HOST'             => getenv('WERCKER_MYSQL_PORT'),
    'DB_MYSQL_DATABASE'         => getenv('WERCKER_MYSQL_HOST'),
    'DB_MYSQL_USERNAME'         => getenv('WERCKER_MYSQL_USERNAME'),
    'DB_MYSQL_PASSWORD'         => getenv('WERCKER_MYSQL_PASSWORD'),

    'APP_DASHBOARD_URL'     	=> 'http://dashboard.razorpay.com/',
    'APP_DASHBOARD_SECRET'      => 'secret',

    'HDFC_ID'                   => $_ENV['HDFC_ID'],
    'HDFC_PASSWORD'             => $_ENV['HDFC_PASSWORD'],
);