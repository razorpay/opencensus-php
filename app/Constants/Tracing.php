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
            Route::$internalApps['account_service'],
            Route::$internalApps['payouts_service']
        );

        return array_merge($routesToInclude, [
            // used by capital-cards service
            'user_fetch',
            'capital_cards_service',
            'capital_cards_onboarding',
            'capital_cards_admin',

            // care Routes
            'care_service_get_dashboard_proxy',
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
            'payout_create_2FA_internal',
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
            'payment_create_upi',

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
            'tax_payments_internal_icici_action',

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
            'vendor_payment_create_vendor_advance',
            'vendor_payment_get_vendor_advance',
            'vendor_payment_list_vendor_advance',
            'vendor_payments_check_existing_invoice',
            'vendor_payment_list',
            'vendor_payment_get_by_id',
            'vendor_payment_verify_otp',
            'vendor_payment_execute',
            'vendor_payment_execute_2fa',
            'vendor_payment_get_tds_categories',
            'vendor_payment_edit',
            'vendor_payment_cancel',
            'vendor_payment_bulk_cancel',
            'vendor_payment_accept',
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
            'vendor_payment_disable_vendor_portal',
            'vendor_payment_enable_vendor_portal',
            'vendor_payment_get_settings',
            'vendor_payment_add_or_update_settings',
            'vendor_payment_approve_reject',
            'merchant_fetch_internal_users',
            'vendor_payment_get_auto_processed_invoice',
            'vendor_settlement_execute_single',
            'vendor_settlement_fund_accounts',
            'vendor_settlement_execute_multiple',
            'vendor_settlement_mark_as_paid',
            'vendor_settlement_vendor_balance',
            'vendor_payment_list_vendors',
            'vendor_sign_up_create_business_info',
            'vendor_sign_up_get_business_info_status',
            'vendor_payment_create_file_upload',
            'vendor_payment_get_file_upload',
            'vendor_payment_delete_file_upload',

            'vendor_invoices_list',
            'vendor_invoice_get_by_id',
            'vendor_portal_list_tds_categories',
            'vendor_portal_invoice_get_signed_url',
            'vendor_portal_invites_list',
            'vendor_invoice_create',
            'vendor_portal_upload_invoice',
            'vendor_portal_get_ocr_data',
            'x_apps_get_all_settings',
            'x_apps_add_or_update_settings',
            'invite_to_vendor_portal',
            'vendor_portal_get_vendor_preferences',
            'vendor_portal_update_vendor_preferences',
            'resend_invite_to_vendor_portal',
            'vendor_payment_get_latest_approvers',
            'vendor_payment_get_timeline_view',

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
            'tax_payments_add_or_update_settings_auto',
            'tax_payments_list',
            'tax_payments_create',
            'tax_payments_get_by_id',
            'tax_payments_mark_as_paid',
            'tax_payments_challan_upload',
            'tax_payments_update_challan_file_id',
            'tax_payments_edit',
            'tax_payments_cancel',
            'tax_payments_fetch_pending_gst',
            'tax_payments_downtime_schedules_list',
            'tax_payments_downtime_schedule',

            // direct tax payments API routes
            'direct_tax_payments_tds_category_public',
            'direct_tax_payments_tds_category_opt',
            'direct_tax_payments_create',
            'direct_tax_payments_create_options',
            'direct_tax_payments_pg_webhook',
            'direct_tax_payments_downtime_schedule_public',
            'direct_tax_payments_downtime_schedule_opt',

            // payout API routes
            'payout_create',
            'payout_create_with_otp',
            'undo_payout_creation',
            'resume_payout_creation',

            'payment_fetch_multiple',
            'reconciliate_via_batch_service',
            'pricing_get_merchant_plans',
            'customer_fetch_multiple',

            //Login route analysis
            'user_login',
            'user_otp_login',
            'verify_user_otp_login',
            'user_oauth_login',
            'user_register',
            'user_otp_register',
            'verify_user_otp_register',
            'user_oauth_register',
            'merchant_edit_pre_signup_details',
            'merchant_pre_signup_details',
            'merchant_features_fetch',
            'merchant_get_tags',
            'merchant_product_switch',
            'merchant_partner_configs_fetch',
            'merchant_activation_details',
            'user_fetch_admin',
            'fetch_partner_intent',
            'credits_fetch_multiple',
            'user_salesforce_event',
            'send_salesforce_user_otp',
            'verify_salesforce_user_otp',

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
            'beta_account_create',
            'merchant_sub_create',
            'linked_account_create_batch',

            // tokenization
            'token_create',
            'token_fetch',
            'token_fetch_cryptogram',
            'token_delete',
            'token_status',

            //Push Token Provisioning
            'tokens_push',
            'tokens_list',

            'customer_fetch_tokens',


            'buy_pricing_terminal_cost',

            // batch service routes
            'nach_batch_process',
            'emandate_batch_process',

//            virtual account routes
            'virtual_account_create',
            'virtual_account_fetch',
            'virtual_account_fetch_multiple',
            'virtual_account_fetch_payments',
            'payment_bank_transfer_fetch',
            'payment_upi_transfer_fetch',
            'virtual_account_add_allowed_payer',
            'virtual_account_delete_allowed_payer',
            'virtual_account_add_receivers',
            'payment_refund',
            'upi_transfer_process',

            // Partner routes
            'merchant_bulk_onboarding_admin',
            'merchant_sub_create',
            'submerchants_fetch_multiple',
            'partner_referral_fetch',
            'partner_referral_create',
            'merchant_sub_create_batch',
            'update_partner_type',
            'account_create',
            'account_list',
            'account_fetch',
            'account_edit',
            'account_fetch_by_external_id',
            'commissions_bulk_capture_by_partner',
            'commissions_capture',
            'commissions_mark_for_settlement',
            'invoice_on_hold_clear_bulk',
            'commissions_invoice_status_change',
            'commissions_invoice_fetch',
            'fetch_partner_first_user_experience',

            'oauth_token_create',
            'oauth_token_fetch_multiple',
            'oauth_token_revoke',
            'oauth_application_fetch_multiple',

            //Onboarding APIs
            'account_create_v2',
            'account_fetch_v2',
            'account_edit_v2',
            'account_delete_v2',

            'stakeholder_create_v2',
            'stakeholder_update_v2',
            'stakeholder_fetch_v2',
            'stakeholder_fetch_all_v2',

            'link_account_documents_v2',
            'link_stakeholder_documents_v2',
            'get_account_documents_v2',
            'get_stakeholder_documents_v2',

            'product_config_fetch_v2',
            'product_config_update_v2',
            'product_config_create_v2',
            'business_unit_tnc_fetch_v2',

            // used by frontend-graphql
            'banking_accounts_list',
            'contact_list',
            'contact_create',
            'contact_get',
            'contact_update',
            'contact_types_get',
            'contact_types_post',
            'payout_fetch_by_id',
            'fund_account_list',
            'fund_account_get',
            'payout_fetch_multiple',
            'payouts_summary',
            'user_otp_create',
            'payout_reject',
            'payout_approve',
            'payout_2fa_approve',
            'payout_approve_bulk',
            'payout_reject_bulk',
            'transaction_statement_fetch',
            'transaction_statement_fetch_multiple',
            'transaction_statement_fetch_multiple_for_banking',
            'payout_purpose_get',
            'payout_purpose_post',
            'fund_account_create',
            'payout_links_fetch_multiple',
            'payment_refund',
            'merchant_fetch_users',
            'get_merchant_data_for_segment',
            'merchant_activation_save',
            'merchant_document_upload',
            'merchant_document_delete',
            'user_opt_in_whatsapp',
            'fetch_merchant_escalation',
            'bvs_service_dashboard',
            'merchant_analytics',
            'payment_fetch_by_id',
            'payment_fetch_refunds',
            'payment_capture',
            'refund_fetch_creation_data',
            'invoice_fetch',
            'invoice_fetch_multiple',
            'setl_fetch_by_id',
            'org_setl_fetch_by_id',
            'setl_get_details',
            'setl_amount',
            'merchant_activation_business_details',
            'merchant_fetch_config',
            'payment_fetch_card_details',
            'user_device_detail_save',
            'm2m_referral_link_get',
            'payment_handle_suggestion',
            'merchant_activation_otp_send',
            'payment_links_fetch_multiple',
            'payment_links_create',
            'payment_links_get',
            'payment_links_cancel',
            'payment_links_notify_by_medium',
            'merchant_store_fetch',
            'merchant_store_add',
            'payment_handle_availability',
            'payment_handle_update',
            'payment_handle_get',
            'merchant_activation_update_website',
            'merchant_balance_fetch_by_id',
            'virtual_account_banking_fetch_multiple',
            'wfs_config_get',

            // Gateway file
            'gateway_file_create',

            //QR codes
            'qr_code_create',
            'qr_code_close',
            'qr_code_fetch',
            'qr_code_fetch_multiple',
            'qr_payments_fetch_multiple',
            'qr_payment_fetch_for_qr_code',
            'qr_code_payment_fetch_by_id',

            // recurring
            'subscription_registration_charge_token',
            'emandate_batch_process',

            // p2p_routes
            'p2p_merchant_devices_fetch_all',
            'p2p_merchant_vpa_fetch_all',

            // validate vpa routes
            'payment_validate_vpa',
            'payment_validate_vpa_old',

            'fund_account_validate',
            'bank_transfer_process',
            'bank_transfer_process_internal',
            'bank_transfer_process_icici',
            'bank_transfer_process_icici_internal',
            'feature_delete'
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

    public static function maskQueryString($queryString): string
    {
        $queryParamsToRedact = ['email', 'contact', 'customer_email', 'customer_contact', 'contact_ps'];
        parse_str($queryString, $queryParams);

        foreach ($queryParamsToRedact as $param)
        {
            if(array_key_exists($param, $queryParams))
            {
                $queryParams[$param] = 'SCRUBBED' . '(' . strlen($queryParams[$param]) . ')';
            }
        }

        return urldecode(http_build_query($queryParams));
    }

    public static function maskUrl($url): string
    {
        $parsed = parse_url($url);

        if (isset($parsed['query']) && $parsed['query'] !== "") {
            $query = $parsed['query'];
            $parsed['query'] = self::maskQueryString($query);
        }

        return http_build_url($parsed);
    }
}
