<?php

return [
    'axis' => [
        'secret'    => env('AXIS_NODAL_AES_SECRET'),
        'iv'        => env('AXIS_NODAL_AES_IV'),
    ],

    'rbl' => [
        'url'                    => env('RBL_NODAL_URL'),
        'username'               => env('RBL_NODAL_USERNAME'),
        'password'               => env('RBL_NODAL_PASSWORD'),
        'client_id'              => env('RBL_NODAL_CLIENT_ID'),
        'client_password'        => env('RBL_NODAL_CLIENT_PASSWORD'),
        'client_certificate'     => env('RBL_NODAL_CLIENT_CERTIFICATE'),
        'client_certificate_key' => env('RBL_NODAL_CLIENT_CERTIFICATE_KEY'),
        'certificate_name'       => env('RBL_NODAL_CERTIFICATE_NAME'),
        'certificate_path'       => env('RBL_NODAL_CERTIFICATE_PATH'),
        'certificate_key_name'   => env('RBL_NODAL_CERTIFICATE_KEY_NAME'),
    ],
];
