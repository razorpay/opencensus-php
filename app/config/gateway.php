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
        'axis_genius'),

    'mock_hdfc'             => $_ENV['HDFC_MOCK'],
    'mock_atom'             => $_ENV['ATOM_MOCK'],
    'mock_axis_migs'        => $_ENV['AXIS_MIGS_MOCK'],
    'mock_axis_genius'      => $_ENV['AXIS_GENIUS_MOCK'],

    'mockhdfc_server' => false,

    'axis_migs' => array(
        'test_hash_secret' => $_ENV['AXIS_MIGS_GATEWAY_TEST_HASH_SECRET'],
        'test_merchant_id' => $_ENV['AXIS_MIGS_GATEWAY_TEST_MERCHANT_ID'],
        'test_access_code' => $_ENV['AXIS_MIGS_GATEWAY_TEST_ACCESS_CODE'],
    ),

    'axis_genius' => array(
        'test_hash_secret' => $_ENV['AXIS_GENIUS_GATEWAY_TEST_HASH_SECRET'],
        'test_merchant_id' => $_ENV['AXIS_GENIUS_GATEWAY_TEST_MERCHANT_ID'],
        'test_access_code' => $_ENV['AXIS_GENIUS_GATEWAY_TEST_ACCESS_CODE'],
    ),
);
