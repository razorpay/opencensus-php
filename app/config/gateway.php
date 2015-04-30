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
        'axis',
        'genius'),

    'mock_hdfc'     => $_ENV['HDFC_MOCK'],
    'mock_atom'     => $_ENV['ATOM_MOCK'],
    'mock_axis'     => $_ENV['AXIS_MOCK'],
    'mock_genius'   => 'false',

    'mockhdfc_server' => false,

    'axis' => array(
        'test_hash_secret' => $_ENV['AXIS_GATEWAY_TEST_HASH_SECRET'],
        'test_merchant_id' => $_ENV['AXIS_GATEWAY_TEST_MERCHANT_ID'],
        'test_access_code' => $_ENV['AXIS_GATEWAY_TEST_ACCESS_CODE'],
    ),
);
