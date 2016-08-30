<?php

return [

    'post_refund'               => ['owner', 'manager', 'operations'],
    'post_capture'              => ['owner', 'manager', 'operations'],
    'get_keys'                  => ['owner'],
    'post_keys'                 => ['owner'],
    'get_activation_details'    => ['owner', 'manager'],
    'post_activation'           => ['owner', 'manager'],
    'post_activation_save_step' => ['owner', 'manager'],
    'post_activation_save_file' => ['owner', 'manager'],
    'get_webhooks'              => ['owner', 'manager'],
    'post_webhooks'             => ['owner', 'manager'],
    'edit_webhooks'             => ['owner', 'manager'],
    'get_config'                => ['owner', 'manager'],
    'post_config_logo'          => ['owner', 'manager'],
];
