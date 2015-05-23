<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    */

    'available' => array(
        'hdfc',
        'atom',
        'axis_migs',
        'axis_genius',
        'kotak',
        'paytm',
        'netbanking_hdfc'),

    'mock_hdfc'             => $_ENV['HDFC_MOCK'],
    'mock_atom'             => $_ENV['ATOM_MOCK'],
    'mock_axis_migs'        => $_ENV['AXIS_MIGS_MOCK'],
    'mock_axis_genius'      => $_ENV['AXIS_GENIUS_MOCK'],
    'mock_kotak'            => $_ENV['KOTAK_MOCK'],
    'mock_paytm'            => $_ENV['PAYTM_MOCK'],

    'mockhdfc_server' => false,

    'hdfc' => array(
        'test_terminal_id'  => $_ENV['HDFC_ID'],
        'test_terminal_pwd' => $_ENV['HDFC_PASSWORD'],
        'mock_server'       => false,
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

    'kotak' => array(
        'test_hash_secret'  => $_ENV['KOTAK_GATEWAY_TEST_HASH_SECRET'],
        'test_merchant_id'  => $_ENV['KOTAK_GATEWAY_TEST_MERCHANT_ID'],
        'test_access_code'  => $_ENV['KOTAK_GATEWAY_TEST_ACCESS_CODE'],
        'test_terminal_id'  => $_ENV['KOTAK_GATEWAY_TEST_TERMINAL_ID'],
    ),

    'paytm' => array(
        'test_merchant_id'  => $_ENV['PAYTM_GATEWAY_TEST_MERCHANT_ID'],
        'test_hash_secret'  => $_ENV['PAYTM_GATEWAY_TEST_HASH_SECRET'],
    ),
);
