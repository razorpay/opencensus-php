<?php

return array(
    'DB_MYSQL_PORT'             => $_ENV['WERCKER_MYSQL_HOST'],
    'DB_MYSQL_HOST'             => $_ENV['WERCKER_MYSQL_PORT'],
    'DB_MYSQL_DATABASE'         => $_ENV['WERCKER_MYSQL_HOST'],
    'DB_MYSQL_USERNAME'         => $_ENV['WERCKER_MYSQL_USERNAME'],
    'DB_MYSQL_PASSWORD'         => $_ENV['WERCKER_MYSQL_PASSWORD'],

    'APP_DASHBOARD_SECRET'      => 'secret',

    'HDFC_ID'                   => 'hdfc_id',
    'HDFC_PASSWORD'             => 'hdfc_password',
);