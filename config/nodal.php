<?php

return [
    'axis' => [
        'secret'         => env('AXIS_NODAL_AES_SECRET'),
        'iv'             => env('AXIS_NODAL_AES_IV'),
        'account_number' => env('AXIS_NODAL_ACCOUNT_NUMBER'),
        'ifsc_code'      => env('AXIS_NODAL_IFSC')
    ],

    'rbl' => [
        'mock'                        => env('RBL_NODAL_MOCK', false),
        'url'                         => env('RBL_NODAL_URL'),
        'username'                    => env('RBL_NODAL_USERNAME'),
        'password'                    => env('RBL_NODAL_PASSWORD'),
        'client_id'                   => env('RBL_NODAL_CLIENT_ID'),
        'account_number'              => env('RBL_NODAL_ACCOUNT_NUMBER'),
        'client_password'             => env('RBL_NODAL_CLIENT_PASSWORD'),
        'client_certificate'          => env('RBL_NODAL_CLIENT_CERTIFICATE'),
        'client_certificate_key'      => env('RBL_NODAL_CLIENT_CERTIFICATE_KEY'),
        'certificate_name'            => env('RBL_NODAL_CERTIFICATE_NAME'),
        'certificate_path'            => env('RBL_NODAL_CERTIFICATE_PATH'),
        'certificate_key_name'        => env('RBL_NODAL_CERTIFICATE_KEY_NAME'),
        'ben_add_url_suffix'          => env('RBL_BEN_ADD_URL_SUFFIX'),
        'fund_transfer_url_suffix'    => env('RBL_FUND_TRANSFER_URL_SUFFIX'),
        'payment_status_url_suffix'   => env('RBL_PAYMENT_STATUS_URL_SUFFIX')
    ],

    'kotak' => [
        'url'               => env('KOTAK_NODAL_BALANCE_URL'),
        'username'          => env('KOTAK_NODAL_BALANCE_USERNAME'),
        'password'          => env('KOTAK_NODAL_BALANCE_PASSWORD'),
        'src_app_cd'        => env('KOTAK_NODAL_BALANCE_APPLICATION_ID'),
        'crn'               => env('KOTAK_NODAL_CRN'),
        'account_number'    => env('KOTAK_NODAL_ACCOUNT_NUMBER')
    ]
];
