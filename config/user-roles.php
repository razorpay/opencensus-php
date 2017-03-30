<?php
/**
 * @see https://docs.razorpay.com/v1/page/team-support
 */
$all = ['owner', 'manager', 'operations', 'finance', 'admin', 'support', 'sellerapp'];

$writers = ['owner', 'manager', 'operations', 'admin'];
$readers = array_merge($writers, ['finance']);

// There are two exclusion roles: support, sellerapp

$allButSellerApp = array_diff($all, ['sellerapp']);

return [
    'dashboard'                 => $readers,
    'edit_webhooks'             => ['owner', 'manager', 'admin'],
    'batch_fetch_multiple'      => $readers,
    'batch_fetch_single'        => $readers,
    'batch_download'            => $readers,
    'batch_upload'              => $writers,
    'batch_retry'               => $writers,
    'payment_get_single'        => $allButSellerApp,
    'get_activation_details'    => ['owner', 'manager', 'admin'],
    'get_config'                => ['owner', 'manager', 'admin'],
    'get_webhooks'              => ['owner', 'manager', 'admin'],
    'get_payments'              => $allButSellerApp,
    'get_orders'                => $allButSellerApp,
    'get_order'                 => $allButSellerApp,
    'get_order_payments'        => $allButSellerApp,
    'invitation_resend'         => ['owner'],
    'invitations_send'          => ['owner'],
    'invitations_edit'          => ['owner'],
    'invitations_delete'        => ['owner'],
    'team_users_list'           => ['owner'],
    'team_users_delete'         => ['owner'],
    'team_users_update'         => ['owner'],
    'refunds_fetch_multiple'    => $allButSellerApp,
    'refunds_fetch_single'      => $allButSellerApp,
    'post_activation'           => ['owner', 'manager', 'admin'],
    'post_activation_save_file' => ['owner', 'manager', 'admin'],
    'post_activation_save_step' => ['owner', 'manager', 'admin'],
    'post_capture'              => $writers,
    'post_config_logo'          => ['owner', 'manager', 'admin'],
    'post_keys'                 => ['owner', 'admin'],
    'post_refund'               => $writers,
    'post_webhooks'             => ['owner', 'manager', 'admin'],
    'reports_entity'            => $readers,
    'reports_invoice'           => $readers,
    'settlements_fetch_all'     => $readers,
    'settlements_fetch_one'     => $readers,
    'referred_merchants_list'   => ['owner', 'manager', 'admin'],
    'keys_setup'                => ['owner', 'admin'],
    // Might wanna drop support from here later
    'balance_get'               => $allButSellerApp,
    'bank_account_fetch'        => $allButSellerApp,
    'submerchant_register'      => $writers,
    'subuser_register'          => $writers,
    'invoice_fetch_all'         => $all,
    'invoice_fetch_single'      => $all,
    // Support role can't create invoices, but sellerapp role can
    'invoice_create'            => array_merge($writers, ['sellerapp']),
    'invoice_edit'              => array_merge($writers, ['sellerapp']),
    'invoice_delete'            => array_merge($writers, ['sellerapp']),

    'customer_fetch_all'        => $allButSellerApp,
    'customer_autocomplete'     => $allButSellerApp,
    'customer_create'           => $writers,
    'customer_edit'             => $writers,
    'customer_delete'           => $writers,

    'item_fetch_all'            => $allButSellerApp,
    'item_fetch_autocomplete'   => $allButSellerApp,
    'item_create'               => $writers,
    'item_edit'                 => $writers,
    'item_delete'               => $writers,
];
