<?php

return [
    'axis' => [
        'secret'    => env('AXIS_NODAL_AES_SECRET'),
        'iv'        => env('AXIS_NODAL_AES_IV'),
    ],

    'rbl' => [
        'url'             => env('RBL_NODAL_URL'),
        'username'        => env('RBL_NODAL_USERNAME'),
        'password'        => env('RBL_NODAL_PASSWORD'),
        'client_id'       => env('RBL_NODAL_CLIENT_ID'),
        'client_password' => env('RBL_NODAL_CLIENT_PASSWORD'),
    ],
];
