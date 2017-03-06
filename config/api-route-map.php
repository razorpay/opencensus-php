<?php

return [
    'org_create' 						=> 'orgs',
    'org_get_multiple' 					=> 'orgs',
    'org_get' 							=> 'orgs/{id}',
    'org_edit'                          => 'orgs/{id}',
    'org_delete'                        => 'orgs/{id}',

    // Roles
    'role_get_multiple'                 => 'orgs/{id}/roles',
    'role_get'                          => 'orgs/{id}/roles/{roleId}',
    'role_create'                       => 'orgs/{id}/roles',
    'role_delete'                       => 'orgs/{id}/roles/{roleId}',
    'role_edit'                         => 'orgs/{id}/roles/{roleId}',

    // Groups
    'group_get_multiple'                => 'orgs/{id}/groups',
    'group_create'                      => 'orgs/{id}/groups',
    'group_get'                         => 'orgs/{id}/groups/{groupId}',
    'group_admins_create'               => 'orgs/{id}/groups/{groupId}/admins',
    'group_delete'                      => 'orgs/{id}/groups/{groupId}',
    'edit_group'                        => 'orgs/{id}/groups/{groupId}',
    'group_get_allowed_groups'          => 'orgs/{id}/groups/{groupId}/allowed_groups',

    // Admins
    'admin_get'                         => 'orgs/{id}/admins/{adminId}',
    'admin_get_multiple'                => 'orgs/{id}/admins',
    'admin_edit'                        => 'orgs/{id}/admins/{adminId}',
    'admin_delete'                      => 'orgs/{id}/admins/{adminId}',
    'admin_create'                      => 'orgs/{id}/admins',
    'admin_get_app_auth'                => 'orgs/{id}/current_admin',

    // AuditLog
    'auditlog_search'                    => 'orgs/{id}/auditlog/search',

    // Permissions
    'permission_get_multiple'           => 'permissions',
    'permission_create'                 => 'permissions',

    'merchant_attach_admin'             => 'merchants/{id}/admins',

    // Payments
    'payment_capture'                   => 'payments/{id}/capture',
    'payment_verify'                    => 'payments/{id}/verify',
    'payment_force_authorize'           => 'payments/{id}/force_authorize',
    'payment_cancel'                    => 'payments/{id}/cancel',
    'payment_authorize_failed'          => 'payments/{id}/authorize_failed',
    'payment_fix_authorize_at'          => 'payments/fix_authorized_at',
    'payment_authorize_refund'          => 'payments/{id}/authorize_refund',
    'payments_multiple_authorize_refund'=> 'payments/authorize_refund/bulk',
    'payment_add_metadata'              => 'payments/{id}/metadata',

    'payment_fetch_multiple'            => 'payments',
    'payment_fetch_by_id'               => 'payments/{id}',

    'payment_fetch_card_details'        => 'payments/{id}/card',
    'payment_fetch_refunds'             => 'payments/{id}/refunds',
    'payment_fetch_refund_by_id'        => 'payments/{paymentId}/refunds/{rfndId}',
    'payment_fetch_transaction'         => 'payments/{id}/transaction',
    'payment_auth_notify'               => 'payments/auth/notify',
    'payment_timeout'                   => 'payments/timeout',
    'payment_auto_capture'              => 'payments/autocapture',
    'payment_auto_capture_email'        => 'payments/autocapture/email',
    'payment_verify_multiple'           => 'payments/verify/{filter}',
    'payment_capture_reminder'          => 'payments/all/reminder',
    'payment_refund_authorized'         => 'payments/refund/authorized',
    'payment_capture_verify'            => 'payments/{id}/verify/capture',
    'payment_capture_gateway_manual'    => 'payments/{id}/gateway/capture',
    'payment_authorize_time_out'        => 'payments/authorize/timeout/{ids}',

    // Order
    'order_create'                      => 'orders',
    'order_fetch'                       => 'orders',
    'order_fetch_by_id'                 => 'orders/{id}',
    'order_payments'                    => 'orders/{id}/payments',
    'order_refund_multiple_authorized'  => 'orders/payments/refund',

    // Refunds
    'refund_create'                     => 'refunds',
    'refund_fetch_by_id'                => 'refunds/{id}',
    'refund_fetch_multiple'             => 'refunds',
    'refund_netbanking_generate_excel'  => 'refunds/netbanking/excel',
    'refund_generate_excel'             => 'refunds/excel',
    'refund_verify'                     => 'refunds/{ids}/verify',
    'refund_create_missing_txn'         => 'refunds/transaction',
    'refund_gateway_refunded_txns'      => 'refunds/gateway_refunded/transaction',
    'refund_gateway_manual'             => 'refunds/{ids}/gateway',
];
