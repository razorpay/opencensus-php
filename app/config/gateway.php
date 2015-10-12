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
        'hdfc',
        'kotak',
        'mobikwik',
        'paytm',
        'netbanking_hdfc',
        'sharp',
        'sbiepay'
        'wallet_payzapp',
    ),

    'mock_amex'             => $_ENV['AMEX_MOCK'],
    'mock_hdfc'             => $_ENV['HDFC_MOCK'],
    'mock_atom'             => $_ENV['ATOM_MOCK'],
    'mock_axis_migs'        => $_ENV['AXIS_MIGS_MOCK'],
    'mock_axis_genius'      => $_ENV['AXIS_GENIUS_MOCK'],
    'mock_kotak'            => $_ENV['KOTAK_MOCK'],
    'mock_mobikwik'         => $_ENV['MOBIKWIK_MOCK'],
    'mock_paytm'            => $_ENV['PAYTM_MOCK'],
    'mock_netbanking_hdfc'  => $_ENV['NETBANKING_HDFC_MOCK'],
    'mock_billdesk'         => $_ENV['BILLDESK_MOCK'],
    'mock_sbiepay'          => $_ENV['SBIEPAY_MOCK'],
    'mock_payzapp'          => $_ENV['PAYZAPP_MOCK'],

    'hdfc' => array(
        'test_terminal_id'  => $_ENV['HDFC_GATEWAY_TEST_TERMINAL_ID'],
        'test_terminal_pwd' => $_ENV['HDFC_GATEWAY_TEST_TERMINAL_PASSWORD'],
        'mock_server'       => false,
    ),

    'amex' => array(
        'test_hash_secret'  => $_ENV['AMEX_GATEWAY_TEST_HASH_SECRET'],
        'test_merchant_id'  => $_ENV['AMEX_GATEWAY_TEST_MERCHANT_ID'],
        'test_access_code'  => $_ENV['AMEX_GATEWAY_TEST_ACCESS_CODE'],
        'test_ama_user'     => $_ENV['AMEX_GATEWAY_TEST_AMA_USER'],
        'test_ama_password' => $_ENV['AMEX_GATEWAY_TEST_AMA_PASSWORD'],
    ),

    'axis_migs' => array(
        'test_hash_secret'  => $_ENV['AXIS_MIGS_GATEWAY_TEST_HASH_SECRET'],
        'test_merchant_id'  => $_ENV['AXIS_MIGS_GATEWAY_TEST_MERCHANT_ID'],
        'test_access_code'  => $_ENV['AXIS_MIGS_GATEWAY_TEST_ACCESS_CODE'],
        'test_ama_user'     => $_ENV['AXIS_MIGS_GATEWAY_TEST_AMA_USER'],
        'test_ama_password' => $_ENV['AXIS_MIGS_GATEWAY_TEST_AMA_PASSWORD'],
    ),

    'axis_genius' => array(
        'test_hash_secret'  => $_ENV['AXIS_GENIUS_GATEWAY_TEST_HASH_SECRET'],
        'test_merchant_id'  => $_ENV['AXIS_GENIUS_GATEWAY_TEST_MERCHANT_ID'],
        'test_access_code'  => $_ENV['AXIS_GENIUS_GATEWAY_TEST_ACCESS_CODE'],
    ),

    'billdesk' => array(
        'test_merchant_id'  => $_ENV['BILLDESK_GATEWAY_TEST_MERCHANT_ID'],
        'test_hash_secret'  => $_ENV['BILLDESK_GATEWAY_TEST_HASH_SECRET'],
        'test_access_code'  => $_ENV['BILLDESK_GATEWAY_TEST_ACCESS_CODE'],
        'live_hash_secret'  => $_ENV['BILLDESK_GATEWAY_TEST_HASH_SECRET'],
        'live_access_code'  => $_ENV['BILLDESK_GATEWAY_TEST_ACCESS_CODE'],
    ),

    'kotak' => array(
        'test_hash_secret'  => $_ENV['KOTAK_GATEWAY_TEST_HASH_SECRET'],
        'test_merchant_id'  => $_ENV['KOTAK_GATEWAY_TEST_MERCHANT_ID'],
        'test_access_code'  => $_ENV['KOTAK_GATEWAY_TEST_ACCESS_CODE'],
        'test_terminal_id'  => $_ENV['KOTAK_GATEWAY_TEST_TERMINAL_ID'],
    ),

    'mobikwik' => array(
        'test_hash_secret'  => $_ENV['MOBIKWIK_GATEWAY_TEST_HASH_SECRET'],
    ),

    'paytm' => array(
        'test_merchant_id'  => $_ENV['PAYTM_GATEWAY_TEST_MERCHANT_ID'],
        'test_hash_secret'  => $_ENV['PAYTM_GATEWAY_TEST_HASH_SECRET'],
    ),

    'wallet_payzapp' => array(
        'test_merchant_id'      => $_ENV['PAYZAPP_WALLET_TEST_MERCHANT_ID'],
        'test_merchant_app_id'  => $_ENV['PAYZAPP_WALLET_TEST_MERCHANT_APP_ID'],
        'test_hash_secret'      => $_ENV['PAYZAPP_WALLET_TEST_HASH_SECRET'],
    ),

    'netbanking_hdfc' => array(
        'live_hash_secret'  => $_ENV['NETBANKING_HDFC_GATEWAY_LIVE_HASH_SECRET'],
    ),

    'sharp' => array(
    ),

    'sbiepay' => array(
        'test_merchant_id'  => $_ENV['SBIEPAY_GATEWAY_TEST_MERCHANT_ID'],
        'test_hash_secret'  => $_ENV['SBIEPAY_GATEWAY_TEST_HASH_SECRET'],
    ),


    'proxy_address' => 'https://splunk.razorpay.com:8888',
);
