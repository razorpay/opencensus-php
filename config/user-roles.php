<?php

return [

    'post_refund'               => ['owner', 'manager', 'operations', 'admin'],
    'post_capture'              => ['owner', 'manager', 'operations', 'admin'],
    'post_keys'                 => ['owner', 'admin'],
    'get_activation_details'    => ['owner', 'manager', 'admin'],
    'post_activation'           => ['owner', 'manager', 'admin'],
    'post_activation_save_step' => ['owner', 'manager', 'admin'],
    'post_activation_save_file' => ['owner', 'manager', 'admin'],
    'get_webhooks'              => ['owner', 'manager', 'admin'],
    'post_webhooks'             => ['owner', 'manager', 'admin'],
    'edit_webhooks'             => ['owner', 'manager', 'admin'],
    'get_config'                => ['owner', 'manager', 'admin'],
    'post_config_logo'          => ['owner', 'manager', 'admin'],
    'dashboard'                 => ['owner', 'manager', 'operations', 'finance', 'admin'],
    'reports_entity'            => ['owner', 'manager', 'operations', 'finance', 'admin'],
    'reports_invoice'           => ['owner', 'manager', 'operations', 'finance', 'admin'],
    'settlements'               => ['owner', 'manager', 'operations', 'finance', 'admin'],
    'settlement'                => ['owner', 'manager', 'operations', 'finance', 'admin'],
    'settlement_detail'         => ['owner', 'manager', 'operations', 'finance', 'admin'],
];
