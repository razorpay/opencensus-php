<?php
/**
 * @see https://docs.razorpay.com/v1/page/team-support
 */
$all = ['owner', 'manager', 'operations', 'finance', 'admin', 'support', 'sellerapp'];

$writers = ['owner', 'manager', 'operations', 'admin'];
$readers = $writers + ['finance'];

// There are two exclusion roles: support, sellerapp

$allButSellerApp = array_diff($all, ['sellerapp']);

return [
    'dashboard'                 => $readers,
    'edit_webhooks'             => ['owner', 'manager', 'admin'],
    'batch_fetch_multiple'      => $allButSellerApp,
    'batch_fetch_single'        => $allButSellerApp,
    'batch_download'            => $allButSellerApp,
    'batch_upload'              => $writers,
    'batch_retry'               => $writers,
    'payment_get_single'        => $allButSellerApp,
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
    'payment_get_single'        => $allButSellerApp,
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
    'settlements_fetch_one'     => $readers,
    'referred_merchants_list'   => ['owner', 'manager', 'admin'],
    'keys_setup'                => ['owner', 'admin'],
    // Might wanna drop support from here later
    'balance_get'               => $allButSellerApp,
    'bank_account_fetch'        => $allButSellerApp,
    'submerchant_register'      => $writers,
    'invoices_fetch_all'        => $all,
    'invoices_fetch_single'     => $all,
    // Support role can't create invoices, but sellerapp role can
    'invoices_create'           => $writers + ['sellerapp'],
];
