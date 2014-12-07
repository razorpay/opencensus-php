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
        'atom'),

    'mock_hdfc' => $_ENV['HDFC_MOCK'],

    'mock_atom' => $_ENV['ATOM_MOCK'],

    'mockhdfc_server' => false,
);
