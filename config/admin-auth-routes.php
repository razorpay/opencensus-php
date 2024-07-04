<?php

return [

    /**
     * Mapping of route names to required permissions
     */

    'admin_merchant_login'  => ['view_merchant_login'],
    'admin_fetch_entity'    => ['view_all_entity'],
    'admin_payment_capture' => ['edit_payment_capture'],

    // Email
    'email_logs_get'        => ['view_email_logs'],
    'email_bounce_get'      => ['view_email_bounces'],
    'email_bounce_delete'   => ['delete_email_bounces'],
    'ucs_generic_handler'   => ['ucs_admin_all', 'ucs_admin_view', 'ucs_admin_write', 'ucs_admin_delete'],
];
