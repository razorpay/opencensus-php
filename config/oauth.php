<?php

return [
    'merchant_oauth_mock'                   => env('MERCHANT_OAUTH_MOCK'),
    'merchant_oauth_client_id'              => env('MERCHANT_OAUTH_CLIENT_ID'),
    'merchant_oauth_client_id_epos'         => env('MERCHANT_OAUTH_CLIENT_ID_EPOS'),
    'merchant_oauth_client_id_android'      => env('MERCHANT_OAUTH_CLIENT_ID_ANDROID'),
    'merchant_oauth_client_id_ios'          => env('MERCHANT_OAUTH_CLIENT_ID_IOS'),
    'merchant_oauth_client_id_x_android'    => env('MERCHANT_OAUTH_CLIENT_ID_X_ANDROID'),
    'merchant_oauth_client_id_x_ios'        => env('MERCHANT_OAUTH_CLIENT_ID_X_IOS'),

    'admin_google_oauth_client_mock' => env('ADMIN_GOOGLE_OAUTH_CLIENT_MOCK', false),
    'admin_google_oauth_client_id'   => env('ADMIN_GOOGLE_OAUTH_CLIENT_ID'),
];
