<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
final class MerchantSaveFlows {

    public const MAP = array(
        /* routes */
        'merchant_mtu_update_dashboard' => true,
        'merchant_billing_label_update' => true,
        'merchant_activation_update' => true,
        'user_add_email_verify' => true,
        'stakeholder_update_v2' => true,
        'product_config_update_v2' => true,
        'merchant_bank_account_update' => true,
        'merchant_edit_email_create_user' => true,
        'merchant_activation_update_website' => true,
        'user_verify_and_update_mobile_otp' => true,
        'update_partner_type' => true,
        'merchant_razorx_bulk_evaluate' => true,
        'merchant_activation_status' => true,
        'merchant_activation_clarifications_save' => true,
        'merchant_activation_save' => true,
        'action_checker_create' => true,
        'user_register' => true,
        'merchant_product_switch' => true,
        'merchant_document_upload' => true,
        'user_oauth_register' => true,
        'merchant_edit_pre_signup_details' => true,
        'linked_account_create_batch' => true,
        'beta_account_create' => true,
        'add_additional_website' => true,
        'verify_user_otp_register' => true,
        'merchant_website_section_save' => true,
        'merchant_activation_clarifications_save_admin' => true,
        'merchant_activation_otp_send' => true,
        'link_account_documents_v2' => true,
        'merchant_details_patch' => true,
        'stakeholder_create_v2' => true,
        'merchant_update_key_access' => true,
        'merchant_sub_create' => true,
        'merchant_edit' => true,
        'account_create_v2' => true,
        'merchant_gstin_self_serve_update' => true,
        'merchant_tags_bulk' => true,
        'product_config_create_v2' => true,
        'collect_info_merchant_details_internal' => true,
        'merchant_onboarding_crons' => true,
        'user_oauth_login' => true,
        'link_stakeholder_documents_v2' => true,
        'merchant_website_section_action' => true,
        'merchant_sub_create_batch' => true,
        'merchant_data_fix_activation' => true,
        'merchant_international_enablement_submit' => true,
        'settlement_ondemand_feature_enable' => true,
        'action_request_execute' => true,
        'merchant_product_international_request' => true,
        'user_merchant_upgrade' => true,
        'feature_bulk_assign' => true,
        'linked_account_update_bank_account' => true,
        'payout_service_idempotency_key_feature_remove' => true,
        'create_exec_risk_action' => true,
        'merchant_features_fetch' => true,
        'user_fetch' => true,
        'account_edit_v2' => true,
        'merchant_edit_config' => true,
        'merchant_activation_details' => true,
        'merchant_edit_config_logo' => true,
        'merchant_requests_create' => true,
        'growth_get_asset_details' => true,
        'banking_accounts_list' => true,
        'merchant_get_tags' => true,
        'credits_fetch_multiple' => true,
        'merchant_get_preferences' => true,
        'account_delete_v2' => true,
        'user_opt_in_whatsapp' => true,
        'merchant_nc_revamp_eligibility' => true,
        'enable_es_scheduled' => true,
        'merchant_2fa_change_setting' => true,
        'feature_delete' => true,

        /* jobs/workers */
        'worker:update_merchant_context' => true,
        'worker:bvs_validation_job' => true,
        'worker:merchant_hold_funds_sync' => true,
        'worker:auto_update_merchant_products' => true,
        'worker:add_ondemand_pricing_if_absent_for_merchant' => true,
        'worker:add_ondemand_restricted_feature_for_merchant' => true,
        'worker:what_c_m_s_processor' => true
    );

    public static function isSaveFlow(string $flow) {
        if (array_key_exists($flow, self::MAP)) {
            return true;
        }

        return false;
    }

}
