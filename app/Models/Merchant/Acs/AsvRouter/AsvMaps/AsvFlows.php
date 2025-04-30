<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
final class AsvFlows
{

    public const MAP = array(
        'worker:update_merchant_context' => true,
        'worker:pgos_cdc_events_job' => true,
        'merchant_activation_status' => true,
        'merchant_submit_internal' => true,
        'merchant_activation_save' => true,
        'merchant_activation_update' => true,
        'internal_merchant_activation_status' => true,
        'merchant_details_patch' => true,
        'action_checker_create' => true,
    );

    public const CacheDisabledFlows = array(
        'account_create_india_v2' => true,
        'account_create_v2' => true,
        'account_edit_v2' => true,
        'action_checker_create' => true,
        'action_request_execute' => true,
        'add_additional_website' => true,
        'admin_patch_purpose_code' => true,
        'admin_website_section_save' => true,
        'auto_approve_merchant_activation_checker' => true,
        'banking_account_create_dashboard' => true,
        'banking_account_service_lms_routes_all' => true,
        'banking_account_service_rbl_migration' => true,
        'banking_account_service_routes' => true,
        'bas_archive_banking_account_dependencies' => true,
        'bas_banking_accounts_create' => true,
        'bas_internal_admin_routes' => true,
        'bas_unarchive_banking_account_dependencies' => true,
        'beta_account_create' => true,
        'collect_info_merchant_details_internal' => true,
        'create_exec_risk_action' => true,
        'delete_additional_websites' => true,
        'feature_add' => true,
        'feature_bulk_assign' => true,
        'feature_delete' => true,
        'feature_delete_internal' => true,
        'fund_addition_tpv' => true,
        'pre_fund_withdraw' => true,
        'internal_post_website_update' => true,
        'link_account_documents_v2' => true,
        'link_stakeholder_documents_v2' => true,
        'linked_account_create_batch' => true,
        'internal_merchant_fetch' => true,
        'merchant_actions' => true,
        'merchant_activation_archive' => true,
        'merchant_activation_clarifications_save' => true,
        'merchant_activation_clarifications_save_admin' => true,
        'merchant_activation_otp_send' => true,
        'merchant_activation_save' => true,
        'merchant_activation_status' => true,
        'merchant_activation_update' => true,
        'merchant_activation_update_website' => true,
        'merchant_bank_account_update' => true,
        'merchant_billing_label_update' => true,
        'merchant_bulk_onboarding_admin' => true,
        'merchant_data_fix' => true,
        'merchant_details_patch' => true,
        'merchant_document_upload' => true,
        'merchant_edit' => true,
        'merchant_edit_config' => true,
        'merchant_edit_email_create_user' => true,
        'merchant_edit_pre_signup_details' => true,
        'merchant_gstin_self_serve_update' => true,
        'merchant_international_enablement_submit' => true,
        'merchant_mtu_update_dashboard' => true,
        'merchant_onboarding_crons' => true,
        'merchant_patch_purpose_code' => true,
        'merchant_pos_activation_status' => true,
        'internal_merchant_pos_activation_status' => true,
        'merchant_requests_create' => true,
        'merchant_sub_create' => true,
        'merchant_sub_create_batch' => true,
        'merchant_submit_internal' => true,
        'merchant_tags_bulk' => true,
        'merchant_update_miq' => true,
        'merchant_upload_miq_admin' => true,
        'offline_challan_validate' => true,
        'partner_activation_save' => true,
        'partner_config_create_admin' => true,
        'payout_service_idempotency_key_feature_remove' => true,
        'product_config_update_v2' => true,
        'register_merchant_verify_otp' => true,
        'register_merchant_sales' => true,
        'salesforce_converge_get_merchant_details' => true,
        'stakeholder_create_v2' => true,
        'stakeholder_update_v2' => true,
        'transfer_debug' => true,
        'transfer_debug_internal' => true,
        'transfer_la_debug' => true,
        'user_add_email_verify' => true,
        'user_merchant_upgrade' => true,
        'user_oauth_login' => true,
        'user_oauth_register' => true,
        'user_register' => true,
        'user_verify_and_update_mobile_otp' => true,
        'verify_user_otp_register' => true,
        'merchant_international_enablement_draft_internal' => true,
        'worker:auto_linked_account_creation' => true,
        'worker:auto_update_merchant_products' => true,
        'worker:batch' => true,
        'worker:bvs_validation_job' => true,
        'worker:cross_border_common_use_cases' => true,
        'worker:linked_account_bank_verification_status_backfill' => true,
        'worker:mcc_categorisation_consumer' => true,
        'worker:negative_keywords_consumer' => true,
        'worker:pgos_cdc_events_job' => true,
        'worker:update_merchant_context' => true,
        'worker:website_policy_consumer' => true,
        'workflow_config_delete' => true,
        'workflow_config_delete_admin' => true,
        'internal_merchant_activation_status' => true,
        'worker:es_sync' => true,
    );

    public static function isExclusionFLow(string $flow): bool
    {
        if (array_key_exists($flow, self::MAP)) {
            return true;
        }

        return false;
    }

    public static function isCacheDisabledFlow(string $flow): bool
    {
        if (array_key_exists($flow, self::CacheDisabledFlows)) {
            return true;
        }

        return false;
    }

}
