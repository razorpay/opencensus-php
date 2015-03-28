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
        'axis'),

    'mock_hdfc' => $_ENV['HDFC_MOCK'],

    'mock_atom' => $_ENV['ATOM_MOCK'],

    'mock_axis' => 'false',

    'mockhdfc_server' => false,
);
