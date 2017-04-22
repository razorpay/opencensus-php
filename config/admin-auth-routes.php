<?php

return [

    /**
     * Mapping of route names to required permissions
     */

    'admin_merchant_login'  => ['view_merchant_login'],
    'admin_fetch_entity'    => ['view_all_entity'],
    'admin_payment_capture' => ['edit_payment_capture'],
    'admin_delete_features' => ['delete_merchant_features'],

    // Email
    'email_logs_get'        => ['view_email_logs'],
    'email_bounce_get'      => ['view_email_bounces'],
    'email_bounce_delete'   => ['delete_email_bounces'],
];
