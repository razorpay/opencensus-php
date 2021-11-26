<?php

namespace RZP\Constants;

use RZP\Http\Route;

class Tracing
{
    // constants related to distributed tracing setup
    const SERVICE_NAME_IN_JAEGER      =   'api';

    // all routes which are to be excluded from distributed tracing
    public static function getRoutesToExclude(): array
    {
        $allCronRoutes =  Route::$internalApps['cron'];
        $otherRoutesToExclude = ['merchant_edit_config_logo', 'merchant_checkout_preferences'];

        $routesToExclude = array_merge($allCronRoutes, $otherRoutesToExclude);
        $routesToExclude = array_diff($routesToExclude, self::getRoutesToInclude());
        return $routesToExclude;
    }

    // all routes which are to be included from distributed tracing
    public static function getRoutesToInclude(): array
    {
        $routesToInclude = array_merge(Route::$public,
            Route::$direct,
            Route::$internalApps['settlements_service'],
            Route::$internalApps['fts'],
            Route::$internalApps['ledger'],
            Route::$internalApps['payouts_service'],
            Route::$internalApps['capital_collections_client'],
            Route::$internalApps['pg_router'],
            Route::$internalApps['care'],
            Route::$internalApps['account_service']
        );

        return array_merge($routesToInclude, [
            // used by capital-cards service
            'user_fetch',
            'capital_cards_service',
            'capital_cards_admin',

            // care Routes
            'care_service_dashboard_proxy',
            'care_service_cron_proxy',
            'care_service_admin_proxy',
            'care_service_chat_proxy',

            // used by fts service
            'update_fts_fund_transfer',
            'fts_channel_notification',

            // used by capital-collections service
            'merchant_balance_create',
            'internal_balance_fetch',
            'credit_repayment_transaction_create',
            'capital_transaction_create',
            'internal_balance_fetch_by_id',
            'capital_collections_service',
            'capital_collections_admin',

            // used by payout-links service
            'payout_create_internal',
            'payout_fetch_multiple_internal',
            'payout_purpose_validate_internal',
            'contact_get_internal',
            'contact_create_internal',
            'fund_account_get_internal',
            'fund_account_list_internal',
            'fund_account_create_internal',
            'merchant_fetch_internal',
            'banking_accounts_list_internal',
            'payout_links_send_email',
            'app_merchant_mapping_create',

            // s2s routes for payments
            'payment_create_private',
            'payment_create_private_json',
            'payment_create_checkout_json',
            'payment_create_private_old',

            // routes used by Vendor Payments
            'contact_update_internal',
            'tax_payments_send_email',
            'vendor_payment_verify_otp',
            'order_create',
            'setl_fetch_multiple',
            'payment_refund',
            'vendor_payment_send_email_generic',
            'settings_fetch_internal',
            'settings_upsert_internal',
            'tax_payments_enabled_merchant_settings',

            // Vendor Payments API routes
            'vendor_payment_execute_bulk',
            'vendor_payment_composite_expands_helper',
            'vendor_payment_send_failure_email',
            'vendor_payment_summary',
            'vendor_payment_contact_list',
            'vendor_payment_invoice_upload',
            'vendor_payment_invoice_get_signed_url',
            'vendor_payment_contact_get',
            'vendor_payment_contact_create',
            'vendor_payment_contact_update',
            'vendor_payment_create',
            'vendor_payment_list',
            'vendor_payment_get_by_id',
            'vendor_payment_verify_otp',
            'vendor_payment_execute',
            'vendor_payment_get_tds_categories',
            'vendor_payment_edit',
            'vendor_payment_cancel',
            'vendor_payment_bulk_cancel',
            'vendor_payment_get_ocr_data',
            'vendor_payment_get_ocr_data',
            'vendor_payment_mark_as_paid',
            'vendor_payment_reporting_info',
            'vendor_payment_bulk_invoice_download',
            'vendor_payment_update_invoice_file_id',
            'vendor_payment_get_invoice_zip_file',
            'vendor_payment_email_integration_webhook',
            'vendor_payment_get_email_mapping',
            'vendor_payment_send_vendor_invite_ei',
            'merchant_fetch_internal_users',
            'vendor_payment_get_auto_processed_invoice',

            // accounting-payouts API routes
            'accounting_payouts_integration_status',
            'accounting_payouts_integration_app_get_url',
            'accounting_payouts_integration_app_initiate',
            'accounting_payouts_integration_status_app',
            'accounting_payouts_callback',
            'accounting_payouts_app_credentials',
            'accounting_payouts_delete_integration',
            'accounting_payouts_sync_status',
            'accounting_payouts_sync',
            'accounting_payouts_sync_internal',
            'accounting_payouts_waitlist',

            // tax payments API routes
            'tax_payments_monthly_summary',
            'tax_payments_admin_auth_api',
            'tax_payments_pay',
            'tax_payments_bulk_pay',
            'tax_payments_get_all_settings',
            'tax_payments_add_or_update_settings',
            'tax_payments_list',
            'tax_payments_create',
            'tax_payments_get_by_id',
            'tax_payments_mark_as_paid',
            'tax_payments_challan_upload',
            'tax_payments_update_challan_file_id',
            'tax_payments_edit',
            'tax_payments_cancel',
            'tax_payments_fetch_pending_gst',

            // direct tax payments API routes
            'direct_tax_payments_tds_category_public',
            'direct_tax_payments_tds_category_opt',
            'direct_tax_payments_create',
            'direct_tax_payments_create_options',
            'direct_tax_payments_pg_webhook',

            // payout API routes
            'payout_create',
            'payout_create_with_otp',

            'payment_fetch_multiple',
            'reconciliate_via_batch_service',
            'pricing_get_merchant_plans',
            'customer_fetch_multiple',

            //Login route analysis
            'user_login',
            'merchant_features_fetch',
            'merchant_get_tags',
            'merchant_product_switch',
            'merchant_partner_configs_fetch',
            'merchant_activation_details',
            'user_fetch_admin',
            'fetch_partner_intent',
            'credits_fetch_multiple',

            //payment page create
            'payment_page_create',
            'payment_page_set_receipt_details',
            'pages_view',
            'pages_view_by_slug',
            'payment_page_view_get',
            'payment_callback_with_key_post',
            'payment_callback_with_key_get',
            'payment_page_update',
            'payment_page_notify',
            'payment_page_expire_cron',
            'payment_page_deactivate',
            'payment_page_activate',
            'payment_page_slug_exists',
            'payment_page_item_update',
            'payment_page_create_order',
            'payment_page_create_order_option',
            'payment_page_set_merchant_details',
            'payment_page_fetch_merchant_details',
            'payment_page_get_invoice_details',
            'payment_page_send_receipt',
            'payment_page_save_receipt_for_payment',
            'payment_page_images',
            'payment_page_get',
            'payment_page_get_details',
            'payment_page_get_payments',
            'payment_page_list',

            // Route
            'transfer_fetch',
            'transfer_fetch_multiple',
            'payment_transfer',
            'payment_fetch_transfers',

            // tokenization
            'token_create',
            'token_fetch',
            'token_fetch_cryptogram',
            'token_delete',
            'token_status',


            'buy_pricing_terminal_cost',

        ]);
    }

    public static function getServiceName($app): string
    {
        $app_mode = $app['config']->get('applications.jaeger.app_mode');

        if($app_mode){
            return self::SERVICE_NAME_IN_JAEGER . '-' . $app_mode;
        }
        else{
            return self::SERVICE_NAME_IN_JAEGER;
        }
    }

    public static function getBasicSpanAttributes($app): array
    {
        $attrs = ['service.version' => $app['config']->get('applications.jaeger.tag_service_version')];

        if(isset($app['rzp.mode'])){
            $attrs['rzp_mode'] = $app['rzp.mode'];
        }

        if (isset($app['request']))
        {
            $attrs['task_id'] = $app['request']->getTaskId();
        }

        $app_env = $app['config']->get('applications.jaeger.tag_app_env');
        if($app_env){
            $attrs['app_env'] = $app_env;
        }

        $app_mode = $app['config']->get('applications.jaeger.app_mode');
        if($app_mode){
            $attrs['app_mode'] = $app_mode;
        }

        return $attrs;
    }

    public static function shouldTraceRoute($route): bool
    {
        if(!(in_array($route->getName(), self::getRoutesToInclude())) or
            in_array($route->getName(), self::getRoutesToExclude()))
        {
            return false;
        }

        return true;
    }

    public static function isEnabled($app): bool
    {
        if ((php_sapi_name() == 'cli') or
            ($app['config']->get('applications.jaeger.enabled') === false))
        {
            return false;
        }

        return true;
    }
}
