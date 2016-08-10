<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    */

    'available' => array(
        'amex',
        'atom',
        'axis_genius',
        'axis_migs',
        'billdesk',
        'cybersource',
        'ebs',
        'hdfc',
        'kotak',
        'mobikwik',
        'paytm',
        'netbanking_hdfc',
        'netbanking_kotak',
        'sharp',
        'sbiepay',
        'wallet_payzapp',
        'wallet_payumoney',
    ),

    'mock_amex'             => env('AMEX_MOCK'),
    'mock_hdfc'             => env('HDFC_MOCK'),
    'mock_cybersource'      => env('CYBERSOURCE_MOCK'),
    'mock_atom'             => env('ATOM_MOCK'),
    'mock_axis_migs'        => env('AXIS_MIGS_MOCK'),
    'mock_axis_genius'      => env('AXIS_GENIUS_MOCK'),
    'mock_kotak'            => env('KOTAK_MOCK'),
    'mock_mobikwik'         => env('MOBIKWIK_MOCK'),
    'mock_paytm'            => env('PAYTM_MOCK'),
    'mock_netbanking_hdfc'  => env('NETBANKING_HDFC_MOCK'),
    'mock_netbanking_kotak' => env('NETBANKING_KOTAK_MOCK'),
    'mock_billdesk'         => env('BILLDESK_MOCK'),
    'mock_ebs'              => env('EBS_MOCK'),
    'mock_sbiepay'          => false,
    'mock_wallet_payzapp'   => env('PAYZAPP_MOCK'),
    'mock_wallet_payumoney' => env('PAYUMONEY_MOCK'),

    'hdfc' => array(
        'test_terminal_id'  => env('HDFC_GATEWAY_TEST_TERMINAL_ID'),
        'test_terminal_pwd' => env('HDFC_GATEWAY_TEST_TERMINAL_PASSWORD'),
        'mock_server'       => false,
    ),

    'cybersource' => array(
        'test_username'         => env('CYBERSOURCE_GATEWAY_TEST_MERCHANT_ID'),
        'test_password'         => env('CYBERSOURCE_GATEWAY_TEST_ACCESS_CODE'),
        'test_merchant_id'      => env('CYBERSOURCE_GATEWAY_TEST_USERNAME', 'cybersource_id'),
        'test_merchant_secret'  => env('CYBERSOURCE_GATEWAY_TEST_SECRET', 'cybersource_secret'),
    ),

    'amex' => array(
        'test_hash_secret'  => env('AMEX_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => env('AMEX_GATEWAY_TEST_MERCHANT_ID'),
        'test_access_code'  => env('AMEX_GATEWAY_TEST_ACCESS_CODE'),
        'test_ama_user'     => env('AMEX_GATEWAY_TEST_AMA_USER'),
        'test_ama_password' => env('AMEX_GATEWAY_TEST_AMA_PASSWORD'),
    ),

    'axis_migs' => array(
        'test_hash_secret'  => env('AXIS_MIGS_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => env('AXIS_MIGS_GATEWAY_TEST_MERCHANT_ID'),
        'test_access_code'  => env('AXIS_MIGS_GATEWAY_TEST_ACCESS_CODE'),
        'test_ama_user'     => env('AXIS_MIGS_GATEWAY_TEST_AMA_USER'),
        'test_ama_password' => env('AXIS_MIGS_GATEWAY_TEST_AMA_PASSWORD'),
    ),

    'axis_genius' => array(
        'test_hash_secret'  => env('AXIS_GENIUS_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => env('AXIS_GENIUS_GATEWAY_TEST_MERCHANT_ID'),
        'test_access_code'  => env('AXIS_GENIUS_GATEWAY_TEST_ACCESS_CODE'),
    ),

    'billdesk' => array(
        'test_merchant_id'      => env('BILLDESK_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'      => env('BILLDESK_GATEWAY_TEST_HASH_SECRET'),
        'test_access_code'      => env('BILLDESK_GATEWAY_TEST_ACCESS_CODE'),
        'live_hash_secret'      => env('BILLDESK_GATEWAY_TEST_HASH_SECRET'),
        'live_access_code'      => env('BILLDESK_GATEWAY_TEST_ACCESS_CODE'),
        //SECRET FOR SECURITIES MERCHANTS
        'live_hash_secret_sec'  => env('BILLDESK_GATEWAY_SECURITIES_LIVE_HASH_SECRET'),
        'live_access_code_sec'  => env('BILLDESK_GATEWAY_SECURITIES_LIVE_ACCESS_CODE'),
    ),

    'ebs' => array(
        'test_merchant_id' => env('EBS_GATEWAY_TEST_MERCHANT_ID', 'random'),
        'test_hash_secret' => env('EBS_GATEWAY_TEST_HASH_SECRET', 'secret'),
        'merchant_id'      => env('EBS_GATEWAY_MERCHANT_ID', 'random'),
        'hash_secret'      => env('EBS_GATEWAY_HASH_SECRET', 'secret'),
    ),

    'kotak' => array(
        'test_hash_secret'  => env('KOTAK_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => env('KOTAK_GATEWAY_TEST_MERCHANT_ID'),
        'test_access_code'  => env('KOTAK_GATEWAY_TEST_ACCESS_CODE'),
        'test_terminal_id'  => env('KOTAK_GATEWAY_TEST_TERMINAL_ID'),
    ),

    'mobikwik' => array(
        'test_hash_secret'  => env('MOBIKWIK_GATEWAY_TEST_HASH_SECRET'),
        'test_merchant_id'  => 'MBK9002',
        // 'test_merchant_id'  => 'MBK7518',
    ),

    'paytm' => array(
        'test_merchant_id'  => env('PAYTM_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'  => env('PAYTM_GATEWAY_TEST_HASH_SECRET'),
    ),

    'wallet_payzapp' => array(
        'pg_merchant_login_id'      => env('PAYZAPP_WALLET_PG_MERCHANT_LOGIN_ID'),
        'test_merchant_id'          => env('PAYZAPP_WALLET_TEST_MERCHANT_ID'),
        'test_merchant_app_id'      => env('PAYZAPP_WALLET_TEST_MERCHANT_APP_ID'),
        'test_hash_secret'          => env('PAYZAPP_WALLET_TEST_HASH_SECRET'),
        'test_pg_instance_id'       => env('PAYZAPP_WALLET_TEST_PG_INSTANCE_ID'),
        'test_pg_merchant_id'       => env('PAYZAPP_WALLET_TEST_PG_MERCHANT_ID'),
        'test_pg_hash_key'          => env('PAYZAPP_WALLET_TEST_PG_HASH_KEY'),
        'live_pg_instance_id'       => env('PAYZAPP_WALLET_LIVE_PG_INSTANCE_ID'),
    ),

    'wallet_payumoney' => array(
        'test_hash_secret'      => env('PAYUMONEY_WALLET_TEST_HASH_SECRET'),
        'test_merchant_id'      => env('PAYUMONEY_WALLET_TEST_MERCHANT_ID'),
        'test_access_code'      => env('PAYUMONEY_WALLET_TEST_CLIENT_ID'),
        'test_auth_header'      => env('PAYUMONEY_WALLET_TEST_AUTH_HEADER'),
    ),

    'netbanking_hdfc' => array(
        'live_hash_secret'  => env('NETBANKING_HDFC_GATEWAY_LIVE_HASH_SECRET'),
    ),

    'netbanking_kotak' => array(
        'live_hash_secret'  => env('NETBANKING_KOTAK_GATEWAY_LIVE_HASH_SECRET'),
        'test_hash_secret'  => env('NETBANKING_KOTAK_GATEWAY_TEST_HASH_SECRET'),
    ),

    'sharp' => array(
    ),

    'sbiepay' => array(
        'test_merchant_id'  => env('SBIEPAY_GATEWAY_TEST_MERCHANT_ID'),
        'test_hash_secret'  => env('SBIEPAY_GATEWAY_TEST_HASH_SECRET'),
    ),


    'proxy_address' => 'https://splunk.razorpay.com:8888',
);
