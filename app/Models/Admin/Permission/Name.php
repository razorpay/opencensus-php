<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Merchant;

class Name
{
    const VIEW_HOMEPAGE                       = 'view_homepage';
    const VIEW_ALL_MERCHANTS                  = 'view_all_merchants';
    const VIEW_MERCHANT                       = 'view_merchant';
    const VIEW_MERCHANT_EXTERNAL              = 'view_merchant_external';
    const VIEW_MERCHANT_BALANCE               = 'view_merchant_balance';
    const VIEW_CITIES                         = 'view_cities';
    // @todo:
    // Rename view_merchant_features to view_features as features
    // have now been extended to applications as well.
    const VIEW_MERCHANT_FEATURES              = 'view_merchant_features';
    const SUBMIT_ONE_CA                       = 'submit_one_ca';
    const VIEW_MERCHANT_BANKS                 = 'view_merchant_banks';
    const VIEW_NETWORKS                       = 'view_networks';
    const VIEW_MERCHANT_BANK_ACCOUNTS         = 'view_merchant_bank_accounts';
    const VIEW_MERCHANT_LOGIN                 = 'view_merchant_login';
    const VIEW_ACTIVITY                       = 'view_activity';
    const VIEW_PRICING_LIST                   = 'view_pricing_list';
    const VIEW_MERCHANT_HDFC_EXCEL            = 'view_merchant_hdfc_excel';
    const VIEW_BENEFICIARY_FILE               = 'view_beneficiary_file';
    const VIEW_MERCHANT_SCREENSHOT            = 'view_merchant_screenshot';
    const VIEW_ALL_MERCHANT_AGGREGATIONS      = 'view_all_merchant_aggregations';
    const VIEW_MERCHANT_AGGREGATIONS          = 'view_merchant_aggregations';
    const VIEW_MERCHANT_TAGS                  = 'view_merchant_tags';
    const VIEW_MERCHANT_CAPITAL_TAGS          = 'view_merchant_capital_tags';
    const CREATE_PRICING_PLAN                 = 'create_pricing_plan';
    const PAYMENTS_CREATE_BUY_PRICING_PLAN    = 'payments_create_buy_pricing_plan';
    const ADMIN_FETCH_MERCHANTS               = 'admin_fetch_merchants';
    const ONBOARDING_AND_ACTIVATIONS_VIEW     = 'onboarding_and_activations_view';
    const ONBOARDING_AND_ACTIVATIONS_EDIT     = 'onboarding_and_activations_edit';
    const ADMIN_GET_APP_AUTH                  = 'admin_get_app_auth';
    const ADMIN_GET_FILE                      = 'admin_get_file';
    const ADMIN_LEAD_VERIFY                   = 'admin_lead_verify';
    const MANAGE_CONFIG_KEYS                  = 'manage_config_keys';
    const FEATURE_ONBOARDING_FETCH_ALL_RESPONSES = 'feature_onboarding_fetch_all_responses';
    const FETCH_PAYMENT_CONFIG_ADMIN          = 'fetch_payment_config_admin';
    const UPDATE_PRICING_PLAN                 = 'update_pricing_plan';
    const PAYMENTS_UPDATE_BUY_PRICING_PLAN    = 'payments_update_buy_pricing_plan';
    const SET_PRICING_RULES                   = 'set_pricing_rules';
    const DELETE_PRICING_PLAN_RULES           = 'delete_pricing_plan_rules';
    const DELETE_EMI_PLAN                     = 'delete_emi_plan';
    const CREATE_EMI_PLAN                     = 'create_emi_plan';
    const CREATE_MERCHANT_LOCK                = 'create_merchant_lock';
    const CREATE_MERCHANT_UNLOCK              = 'create_merchant_unlock';
    const UPLOAD_MERCHANT                     = 'upload_merchant';
    const EDIT_MERCHANT                       = 'edit_merchant';
    const EDIT_MERCHANT_TAGS                  = 'edit_merchant_tags';
    const EDIT_MERCHANT_FEATURES              = 'edit_merchant_features';
    const EDIT_MERCHANT_COMMENTS              = 'edit_merchant_comments';
    const EDIT_MERCHANT_BANK_DETAIL           = 'edit_merchant_bank_detail';
    const EDIT_MERCHANT_GSTIN_DETAIL          = 'edit_merchant_gstin_detail';
    const UPDATE_MERCHANT_GSTIN_DETAIL        = 'update_merchant_gstin_detail';
    const EDIT_IIN_RULE                       = 'edit_iin_rule';
    const EDIT_IIN_RULE_BULK                  = 'edit_iin_rule_bulk';
    const IIN_BATCH_UPLOAD                    = 'iin_batch_upload';
    const MPAN_BATCH_UPLOAD                   = 'mpan_batch_upload';
    const EDIT_ACTIVATE_MERCHANT              = 'edit_activate_merchant';
    const EDIT_MERCHANT_KEY_ACCESS            = 'edit_merchant_key_access';
    const EDIT_MERCHANT_ENABLE_LIVE           = 'edit_merchant_enable_live';
    const EDIT_MERCHANT_DISABLE_LIVE          = 'edit_merchant_disable_live';
    const EDIT_MERCHANT_ENABLE_INTERNATIONAL  = 'edit_merchant_enable_international';
    const EDIT_MERCHANT_DISABLE_INTERNATIONAL = 'edit_merchant_disable_international';
    const EDIT_MERCHANT_ARCHIVE               = 'edit_merchant_archive';
    const EDIT_MERCHANT_UNARCHIVE             = 'edit_merchant_unarchive';
    const EDIT_MERCHANT_SUSPEND               = 'edit_merchant_suspend';
    const EDIT_MERCHANT_SUSPEND_BULK          = 'edit_merchant_suspend_bulk';
    const EDIT_MERCHANT_UNSUSPEND             = 'edit_merchant_unsuspend';
    const EDIT_MERCHANT_TOGGLE_LIVE_BULK      = 'edit_merchant_toggle_live_bulk';
    const EDIT_MERCHANT_HOLD_FUNDS_BULK       = 'edit_merchant_hold_funds_bulk';
    const EXECUTE_MERCHANT_SUSPEND_BULK       = 'execute_merchant_suspend_bulk';
    const EXECUTE_MERCHANT_TOGGLE_LIVE_BULK   = 'execute_merchant_toggle_live_bulk';
    const EXECUTE_MERCHANT_HOLD_FUNDS_BULK    = 'execute_merchant_hold_funds_bulk';
    const EDIT_MERCHANT_METHODS               = 'edit_merchant_methods';
    const EDIT_MERCHANT_TERMINAL              = 'edit_merchant_terminal';
    const EDIT_MERCHANT_PRICING               = 'edit_merchant_pricing';
    const INCREASE_TRANSACTION_LIMIT          = 'increase_transaction_limit';
    const VIEW_MERCHANT_COMPANY_INFO          = 'view_merchant_company_info';
    const VIEW_MERCHANT_CREDITS_LOG           = 'view_merchant_credits_log';
    const ADD_MERCHANT_CREDITS                = 'add_merchant_credits';
    const EDIT_MERCHANT_CREDITS               = 'edit_merchant_credits';
    const DELETE_MERCHANT_CREDITS             = 'delete_merchant_credits';
    const EDIT_MERCHANT_SCREENSHOT            = 'edit_merchant_screenshot';
    const VIEW_PAYMENT_VERIFY                 = 'view_payment_verify';
    const EDIT_VERIFY_PAYMENTS                = 'edit_verify_payments';
    const EDIT_AUTHORIZED_FAILED_PAYMENT      = 'edit_authorized_failed_payment';
    const VIEW_REFUND_PAYMENTS                = 'view_refund_payments';
    const EDIT_AUTHORIZED_REFUND_PAYMENT      = 'edit_authorized_refund_payment';
    const PAYMENTS_UPDATE_REFUND_AT           = 'payments_update_refund_at';
    const RETRY_REFUND_FAILED                 = 'retry_refund_failed';
    const EDIT_PAYMENT_REFUND                 = 'edit_payment_refund';
    const EDIT_PAYMENT_CAPTURE                = 'edit_payment_capture';
    const EDIT_MERCHANT_CONFIRM               = 'edit_merchant_confirm';
    const CREATE_BENEFICIARY_FILE             = 'create_beneficiary_file';
    const CREATE_NETBANKING_REFUND            = 'create_netbanking_refund';
    const VIEW_BANKING_CONFIGS                = 'view_banking_configs';
    const UPSERT_BANKING_CONFIGS              = 'upsert_banking_configs';
    const CREATE_EMI_FILES                    = 'create_emi_files';
    const CREATE_SETTLEMENT_INITIATE          = 'create_settlement_initiate';
    const CHECK_TERMINAL_SECRET               = 'check_terminal_secret';
    const VIEW_TERMINAL                       = 'view_terminal';
    const VIEW_TERMINAL_EXTERNAL_ORG          = 'view_terminal_external_org';
    const DELETE_TERMINAL                     = 'delete_terminal';
    const EDIT_TERMINAL                       = 'edit_terminal';
    const EDIT_TERMINAL_GOD_MODE              = 'edit_terminal_god_mode';
    const ENABLE_TERMINALS_BULK               = 'enable_terminals_bulk';
    const PAYMENTS_BATCH_CREATE_TERMINALS_BULK = "payments_batch_create_terminals_bulk";
    const TERMINAL_MANAGE_MERCHANT            = 'terminal_manage_merchant';
    const TOGGLE_TERMINAL                     = 'toggle_terminal';
    const CREATE_SETTLEMENTS_RECONCILE        = 'create_settlements_reconcile';
    const RETRY_SETTLEMENT                    = 'retry_settlement';
    const SETTLEMENT_BULK_UPDATE              = 'settlement_bulk_update';
    const SETTLEMENT_ONDEMAND_FEATURE_ENABLE  = 'settlement_ondemand_feature_enable';
    const MERCHANT_CAPITAL_TAGS_UPLOAD        = 'merchant_capital_tags_upload';
    const SETTLEMENT_ONDEMAND_TRANSFER_RETRY  = 'settlement_ondemand_transfer_retry';
    const CREATE_NODAL_ACCOUNT_TRANSFER       = 'create_nodal_account_transfer';
    const MERCHANT_INVOICE_EDIT               = 'merchant_invoice_edit';
    const MERCHANT_INVOICE_CONTROL            = 'merchant_invoice_control';
    const MERCHANT_EMAIL_EDIT                 = 'merchant_edit_email';
    const UPDATE_MOBILE_NUMBER                = 'update_mobile_number';
    const MERCHANT_PRICING_PLANS              = 'merchant_pricing_plans';
    const PAYMENTS_TERMINAL_BUY_PRICING_PLANS  = 'payments_terminal_buy_pricing_plans';
    const CREATE_RECONCILIATE                 = 'create_reconciliate';
    const VIEW_ACTIVATION_FORM                = 'view_activation_form';
    const MANAGE_UNDO_PAYOUT                  = 'manage_undo_payout';
    const RESET_WEBHOOK_DATA                  = 'reset_webhook_data';
    const EDIT_MERCHANT_LOCK_ACTIVATION       = 'edit_merchant_lock_activation';
    const EDIT_MERCHANT_UNLOCK_ACTIVATION     = 'edit_merchant_unlock_activation';
    const EDIT_MERCHANT_HOLD_FUNDS            = 'edit_merchant_hold_funds';
    const EDIT_MERCHANT_RELEASE_FUNDS         = 'edit_merchant_release_funds';
    const EDIT_MERCHANT_ENABLE_RECEIPT        = 'edit_merchant_enable_receipt';
    const EDIT_MERCHANT_DISABLE_RECEIPT       = 'edit_merchant_disable_receipt';
    const EDIT_BULK_MERCHANT                  = 'edit_bulk_merchant';
    const EDIT_BULK_MERCHANT_CHANNEL          = 'edit_bulk_merchant_channel';
    const EDIT_MERCHANT_REQUESTS              = 'edit_merchant_requests';
    const ASSIGN_MERCHANT_TERMINAL            = 'assign_merchant_terminal';
    const ASSIGN_MERCHANT_BANKS               = 'assign_merchant_banks';
    const ADD_MERCHANT_ADJUSTMENT             = 'add_merchant_adjustment';
    const GET_AMAZON_DATA_PULL_STATUS         = 'get_amazon_data_pull_status';
    const EDIT_MERCHANT_EMAIL                 = 'edit_merchant_email';
    const EDIT_MERCHANT_ADDITIONAL_EMAIL      = 'edit_merchant_additional_email';
    const BULK_CREATE_ENTITY                  = 'bulk_create_entity';
    const MERCHANT_AUTOFILL_FORM              = 'merchant_autofill_form';
    const EDIT_MERCHANT_MARK_REFERRED         = 'edit_merchant_mark_referred';
    const VIEW_AS_ENTITY                      = 'view_as_entity';
    const VIEW_MERCHANT_REFERRER              = 'view_merchant_referrer';
    const VIEW_MERCHANT_BALANCE_TEST          = 'view_merchant_balance_test';
    const VIEW_MERCHANT_BALANCE_LIVE          = 'view_merchant_balance_live';
    const VIEW_MERCHANT_REQUESTS              = 'view_merchant_requests';
    const ADD_RECONCILIATION_FILE             = 'add_reconciliation_file';
    const UPLOAD_NACH_MIGRATION               = 'upload_nach_migration';
    const VERIFY_NACH_UPLOADS                 = 'verify_nach_uploads';
    const ADD_MANUAL_RECONCILIATION_FILE      = 'add_manual_reconciliation_file';
    const ADD_SETTLEMENT_RECONCILIATION       = 'add_settlement_reconciliation';
    const SEND_NEWSLETTER                     = 'send_newsletter';
    const TRIGGER_DUMMY_ERROR                 = 'trigger_dummy_error';
    const CREATE_GATEWAY_FILE                 = 'create_gateway_file';
    const UPDATE_CONFIG_KEY                   = 'update_config_key';
    const MAKE_API_CALL                       = 'make_api_call';
    const MAKE_ADMIN_API_CALL                 = 'make_admin_api_call';
    const SCHEDULE_CREATE                     = 'schedule_create';
    const SCHEDULE_FETCH                      = 'schedule_fetch';
    const SCHEDULE_FETCH_MULTIPLE             = 'schedule_fetch_multiple';
    const SCHEDULE_DELETE                     = 'schedule_delete';
    const SCHEDULE_UPDATE                     = 'schedule_update';
    const SCHEDULE_ASSIGN                     = 'schedule_assign';
    const SCHEDULE_ASSIGN_BULK                = 'schedule_assign_bulk';
    const PRICING_ASSIGN_BULK                 = 'pricing_assign_bulk';
    const METHODS_ASSIGN_BULK                 = 'methods_assign_bulk';
    const SCHEDULE_MIGRATION                  = 'schedule_migration';
    const VIEW_ACTIONS                        = 'view_actions';
    const VIEW_MERCHANT_STATS                 = 'view_merchant_stats';
    const VIEW_ALL_ENTITY                     = 'view_all_entity';
    const SYNC_ENTITY_BY_ID                  = 'sync_entity_by_id';
    const EXTERNAL_ADMIN_VIEW_ALL_ENTITY      = 'external_admin_view_all_entity';
    const AXIS_ADMIN_VIEW_PAYMENTS            = 'AXIS_ADMIN_VIEW_PAYMENTS';
    const VIEW_ALL_ORG                        = 'view_all_org';
    const VIEW_ORG                            = 'view_org';
    const CREATE_ORG                          = 'create_org';
    const EDIT_ORG                            = 'edit_org';
    const DELETE_ORG                          = 'delete_org';
    const VIEW_ALL_ROLE                       = 'view_all_role';
    const VIEW_ROLE                           = 'view_role';
    const CREATE_ROLE                         = 'create_role';
    const EDIT_ROLE                           = 'edit_role';
    const EDIT_ROLE_ADD_PERMISSIONS           = 'edit_role_add_permissions';
    const DELETE_ROLE                         = 'delete_role';
    const VIEW_ALL_GROUP                      = 'view_all_group';
    const VIEW_GROUP                          = 'view_group';
    const CREATE_GROUP                        = 'create_group';
    const EDIT_GROUP                          = 'edit_group';
    const DELETE_GROUP                        = 'delete_group';
    const GROUP_GET_ALLOWED_GROUPS            = 'group_get_allowed_groups';
    const VIEW_ALL_ADMIN                      = 'view_all_admin';
    const VIEW_ADMIN                          = 'view_admin';
    const CREATE_ADMIN                        = 'create_admin';
    const EDIT_ADMIN                          = 'edit_admin';
    const DELETE_ADMIN                        = 'delete_admin';
    const VIEW_AUDITLOG                       = 'view_auditlog';
    const VIEW_ALL_PERMISSION                 = 'view_all_permission';
    const GET_PERMISSION                      = 'get_permission';
    const DELETE_PERMISSION                   = 'delete_permission';
    const CREATE_PERMISSION                   = 'create_permission';
    const EDIT_PERMISSION                     = 'edit_permission';
    const REMINDER_OPERATION                  = 'reminder_operation';
    const GATEWAY_PVT                         = 'gateway_pvt';
    const FTS_SOURCE_ACCOUNT_UPDATE           = 'fts_source_account_update';
    const FTS_SOURCE_ACCOUNT_GRACEFUL_UPDATE  = 'fts_source_account_graceful_update';
    const GET_RECONCILIATION                  = 'get_reconciliation';
    // @todo
    // Rename delete_merchant_features to delete_features as features
    // have now been extended to applications as well.
    const DELETE_MERCHANT_FEATURES            = 'delete_merchant_features';
    const CREATE_MERCHANT_INVITE              = 'create_merchant_invite';
    const EDIT_MERCHANT_INVITE                = 'edit_merchant_invite';
    const VIEW_MERCHANT_INVITE                = 'view_merchant_invite';
    const EDIT_MERCHANT_FORCE_ACTIVATION      = 'edit_merchant_force_activation';
    const EDIT_MERCHANT_RECEIPT_EMAIL_EVENT   = 'edit_merchant_receipt_email_event';
    const VIEW_WORKFLOW                       = 'view_workflow';
    const CREATE_WORKFLOW                     = 'create_workflow';
    const VIEW_ALL_WORKFLOW                   = 'view_all_workflow';
    const EDIT_WORKFLOW                       = 'edit_workflow';
    const DELETE_WORKFLOW                     = 'delete_workflow';
    const VIEW_WORKFLOW_REQUESTS              = 'view_workflow_requests';
    const EDIT_ACTION                         = 'edit_action';
    const CREATE_GATEWAY_RULE                 = 'create_gateway_rule';
    const EDIT_GATEWAY_RULE                   = 'edit_gateway_rule';
    const DELETE_GATEWAY_RULE                 = 'delete_gateway_rule';
    const VIEW_GATEWAY_RULE                   = 'view_gateway_rule';
    const RULE_VISIBILITY                     = 'rule_visibility';
    const OPTIMIZER_SINGLE_RECON              = 'optimizer_single_recon';
    const CREATE_GOVERNOR_RULE                = 'create_governor_rule';
    const EDIT_GOVERNOR_RULE                  = 'edit_governor_rule';
    const EDIT_SCORECARD_GOVERNOR_CONF        = 'edit_scorecard_governor_conf';
    const DELETE_GOVERNOR_RULE                = 'delete_governor_rule';
    const VIEW_MERCHANT_REPORT                = 'view_merchant_report';
    const VIEW_SPECIAL_MERCHANT_REPORT        = 'view_special_merchant_report';
    const CREATE_MERCHANT_OFFER               = 'create_merchant_offer';
    const EDIT_MERCHANT_OFFER                 = 'edit_merchant_offer';
    const ASSIGN_MERCHANT_HANDLE              = 'assign_merchant_handle';
    const VIEW_MERCHANT_PRICING               = 'view_merchant_pricing';
    const CREATE_DISPUTE                      = 'create_dispute';
    const EDIT_DISPUTE                        = 'edit_dispute';
    const FETCH_DISPUTE_FILES                 = 'fetch_dispute_files';
    const VIEW_WALLET_CONFIG                  = 'view_wallet_config';
    const CREATE_WALLET_CONFIG                = 'create_wallet_config';
    const EDIT_WALLET_CONFIG                  = 'edit_wallet_config';
    const MERCHANT_BATCH_UPLOAD               = 'merchant_batch_upload';
    const MERCHANT_BENEFICIARY_UPLOAD         = 'merchant_beneficiary_upload';
    const CREATE_DISPUTE_REASON               = 'create_dispute_reason';
    const MANAGE_ONBOARDING_SUBMISSIONS       = 'manage_onboarding_submissions';
    const MANAGE_BULK_FEATURE_MAPPING         = 'manage_bulk_feature_mapping';
    const MANAGE_BULK_MERCHANT_TAGGING        = 'manage_bulk_merchant_tagging';
    const MANAGE_IINS                         = 'manage_iins';
    const MANAGE_EMI_PLANS                    = 'manage_emi_plans';
    const MANAGE_RAZORX_OPERATIONS            = 'manage_razorx_operations';
    const GENERATE_REFUND_EXCEL               = 'generate_refund_excel';
    const GENERATE_EMI_EXCEL                  = 'generate_emi_excel';
    const CONFIRM_USER                        = 'confirm_user';
    const AUTHORIZE_PAYMENT                   = 'authorize_payment';
    const VERIFY_PAYMENT                      = 'verify_payment';
    const VERIFY_REFUND                       = 'verify_refund';
    const BARRICADE_VERIFY_PAYMENT            = 'barricade_verify_payment';
    const EDIT_MERCHANT_RISK_THRESHOLD        = 'edit_merchant_risk_threshold';
    const RETRY_BATCH                         = 'retry_batch';
    const UPDATE_GEO_IP                       = 'update_geo_ip';
    const MERCHANT_BALANCE_BULK_BACKFILL      = 'merchants_balance_bulk_backfill';
    const PAYMENT_AUTHORIZE_TIMEOUT           = 'payment_authorize_timeout';
    const AUTOCAPTURE_PAYMENTS_MAIL           = 'autocapture_payments_mail';
    const PAYMENT_CAPTURE_GATEWAY_MANUAL      = 'payment_capture_gateway_manual';
    const PAYMENT_CAPTURE_VERIFY              = 'payment_capture_verify';
    const FIX_PAYMENT_ATTEMPTED_ORDERS        = 'fix_payment_attempted_orders';
    const FIX_PAYMENT_AUTHORIZED_AT           = 'fix_payment_authorized_at';
    const FORCE_AUTHORIZE_PAYMENT             = 'force_authorize_payment';
    const REFUND_MULTIPLE_AUTHORIZE_PAYMENTS  = 'refund_multiple_authorize_payments';
    const CREATE_PAYMENT_CONFIG               = 'create_payment_config';
    const UPDATE_PAYMENT_CONFIG               = 'update_payment_config';
    const MIGRATE_TOKENS_TO_GATEWAY_TOKENS    = 'migrate_tokens_to_gateway_tokens';
    const VIEW_SHIELD_RULES                   = 'view_shield_rules';
    const CREATE_SHIELD_RULES                 = 'create_shield_rules';
    const EDIT_SHIELD_RULES                   = 'edit_shield_rules';
    const DELETE_SHIELD_RULES                 = 'delete_shield_rules';
    const VIEW_SHIELD_LISTS                   = 'view_shield_lists';
    const CREATE_SHIELD_LISTS                 = 'create_shield_lists';
    const DELETE_SHIELD_LISTS                 = 'delete_shield_lists';
    const VIEW_SHIELD_RULE                    = 'view_shield_rule';
    const CREATE_SHIELD_RULE                  = 'create_shield_rule';
    const EDIT_SHIELD_RULE                    = 'edit_shield_rule';
    const DELETE_SHIELD_RULE                  = 'delete_shield_rule';
    const VIEW_SHIELD_LIST                    = 'view_shield_list';
    const CREATE_SHIELD_LIST                  = 'create_shield_list';
    const DELETE_SHIELD_LIST                  = 'delete_shield_list';
    const ADD_SHIELD_LIST_ITEMS               = 'add_shield_list_items';
    const PURGE_SHIELD_LIST_ITEMS             = 'purge_shield_list_items';
    const DELETE_SHIELD_LIST_ITEM             = 'delete_shield_list_item';
    const RETRIEVE_SHIELD_UI_SETTINGS         = 'retrieve_shield_ui_settings';
    const VIEW_RISK_THRESHOLD_CONFIG          = 'view_risk_threshold_config';
    const CREATE_RISK_THRESHOLD_CONFIG        = 'create_risk_threshold_config';
    const UPDATE_RISK_THRESHOLD_CONFIG        = 'update_risk_threshold_config';
    const DELETE_RISK_THRESHOLD_CONFIG        = 'delete_risk_threshold_config';
    const VIEW_MERCHANT_RISK_THRESHOLD        = 'view_merchant_risk_threshold';
    const CREATE_MERCHANT_RISK_THRESHOLD      = 'create_merchant_risk_threshold';
    const UPDATE_MERCHANT_RISK_THRESHOLD      = 'update_merchant_risk_threshold';
    const DELETE_MERCHANT_RISK_THRESHOLD      = 'delete_merchant_risk_threshold';
    const BULK_UPDATE_MERCHANT_RISK_THRESHOLD = 'bulk_update_merchant_risk_threshold';
    const VIEW_MERCHANT_ANALYTICS             = 'view_merchant_analytics';
    const ASSIGN_MERCHANT_ACTIVATION_REVIEWER = 'assign_merchant_activation_reviewer';
    const DB_META_QUERY                       = 'db_meta_query';
    const ES_WRITE_OPERATION                  = 'es_write_operation';
    const OAUTH_SYNC_MERCHANT_MAP             = 'oauth_sync_merchant_map';
    const ADMIN_BATCH_CREATE                  = 'admin_batch_create';
    const ADMIN_FILE_UPLOAD                   = 'admin_file_upload';
    const EDIT_PARTNERS                       = 'edit_partners';
    const VIEW_PARTNERS                       = 'view_partners';
    const ADMIN_MANAGE_PARTNERS               = 'admin_manage_partners';
    const EDIT_REFUND                         = 'edit_refund';
    const VIEW_COMMISSIONS                    = 'view_commissions';
    const CREATE_QR_CODE                      = 'create_qr_code';
    const PARTNER_AND_SUBMERCHANT_ACTIONS     = 'partner_submerchant_actions';
    const UPDATE_REFUND_REFERENCE1            = 'update_refund_reference1';
    const UPDATE_SCROOGE_REFUND_REFERENCE1    = 'update_scrooge_refund_reference1';
    const UPDATE_PROCESSED_REFUNDS_STATUS     = 'update_processed_refunds_status';
    const REVERSE_FAILED_REFUNDS              = 'reverse_failed_refunds';
    const REFRESH_SCROOGE_FTA_MODES_CACHE     = 'refresh_scrooge_fta_modes_cache';
    const BULK_RETRY_REFUNDS_VIA_FTA          = 'bulk_retry_refunds_via_fta';
    const EDIT_INSTANT_REFUNDS_MODE_CONFIG    = 'edit_instant_refunds_mode_config';
    const RETRY_REFUND                        = 'retry_refund';
    const RETRY_REFUNDS_WITHOUT_VERIFY        = 'retry_refunds_without_verify';
    const DOWNLOAD_NON_MERCHANT_REPORT        = 'download_non_merchant_report';
    const REPORTING_DEVELOPER                 = 'reporting_developer';
    const CREATE_VIRTUAL_ACCOUNTS             = 'create_virtual_accounts';
    const CREATE_QR_CODE_CONFIG               = 'create_qr_code_config';
    const CREATE_BANKING_VIRTUAL_ACCOUNTS     = 'create_banking_virtual_accounts';
    const BANK_TRANSFER_INSERT                = 'bank_transfer_insert';
    const BANK_TRANSFER_MODIFY_PAYER_ACCOUNT  = 'bank_transfer_modify_payer_account';
    const GET_SELF_SERVE_REPORT               = 'get_self_serve_report';
    const VIEW_BUSINESS_REPORTS               = 'view_business_reports';
    const CREATE_SELF_SERVE_REPORT            = 'create_self_serve_report';
    const REPORT_CONFIG_FULL_OPERATIONS       = 'report_config_full_operations';
    const CREATE_GATEWAY_DOWNTIME             = 'create_gateway_downtime';
    const VIEW_GATEWAY_DOWNTIME               = 'view_gateway_downtime';
    const UPDATE_GATEWAY_DOWNTIME             = 'update_gateway_downtime';
    const CREATE_PROMOTION_COUPON             = 'create_promotion_coupon';
    const DEACTIVATE_PROMOTION                = 'deactivate_promotion';
    const RAZORX_APPROVERS                    = 'razorx_approvers';
    const USER_PASSWORD_RESET                 = 'user_password_reset';
    const MODIFY_SUBSCRIPTION_DATA            = 'modify_subscription_data';
    const VIEW_OPERATIONS_REPORT              = 'view_operations_report';
    const VIEW_SCROOGE_REFUNDS                = 'view_scrooge_refunds';
    const SETTLEMENT_RELEASE_HOLD_PAYMENT     = 'settlement_release_hold_payment';
    const BATCH_API_CALL                      = 'batch_api_call';
    const EDIT_SCROOGE_REDIS_CONFIG           = 'edit_scrooge_redis_config';
    const EDIT_THROTTLE_SETTINGS              = 'edit_throttle_settings';
    const VIEW_THROTTLE_SETTINGS              = 'view_throttle_settings';
    const ACCESS_EXCEL_STORE                  = 'access_excel_store';
    const CANCEL_BATCH                        = 'cancel_batch';
    const CAPTURE_SETTING_BATCH_UPLOAD        = 'capture_setting_batch_upload';
    const PAYMENT_CAPTURE_BULK                = 'payment_capture_bulk';
    const COMMISSION_CAPTURE                  = 'commission_capture';
    const COMMISSION_PAYOUT                   = 'commission_payout';
    const MERCHANT_RESTRICT                   = 'merchant_restrict';
    const USER_ACCOUNT_LOCK_UNLOCK            = 'user_account_lock_unlock';
    const UPDATE_USER_CONTACT_MOBILE          = 'update_user_contact_mobile';
    const FTS_TRANSFER_ATTEMPT_BULK_UPDATE    = 'fts_transfer_attempt_bulk_update';
    const FTS_ROUTING_RULES_UPDATE            = 'fts_routing_rules_update';
    const FTS_FAIL_QUEUED_TRANSFER            = 'fts_fail_queued_transfer';
    const FTS_MERCHANT_CONFIGURATIONS_UPDATE  = 'fts_merchant_configurations_update';
    const FTS_FORCE_RETRY_TRANSFER            = 'fts_force_retry_transfer';
    const MANAGE_RENDERING_PREFERENCES  	  = 'manage_rendering_preferences';
    const ADJUSTMENT_BATCH_UPLOAD             = 'adjustment_batch_upload';
    const CREATE_BULK_ADJUSTMENT              = 'create_bulk_adjustment';
    const ADD_BULK_MERCHANT_ADJUSTMENT        = 'add_bulk_merchant_adjustment';
    const REVERSE_BULK_MERCHANT_ADJUSTMENT    = 'reverse_bulk_merchant_adjustment';
    const REPORTING_BATCH_UPLOAD              = 'reporting_batch_upload';
    const DOWNLOAD_CREDIT_BUREAU_REPORTS      = 'download_credit_bureau_reports';
    const CAPITAL_DEVELOPER                   = 'capital_developer';
    const RETRY_REFUNDS_WITH_APPENDED_ID      = 'retry_refunds_with_appended_id';
    const REFUNDS_BANK_FILE_UPLOAD            = 'refunds_bank_file_upload';
    const CAPITAL_LOS_CREATE_APPLICATION      = 'capital_los_create_application';
    const DEVELOPER_CONSOLE_ADMIN             = 'developer_console_admin';
    const MAGIC_RTO_CONFIGS_EDIT              = 'magic_rto_configs_edit';
    const MAGIC_RTO_CONFIGS_VIEW              = 'magic_rto_configs_view';
    const MAGIC_OPS                           = 'magic_ops';

    const CAPITAL_LOS_APPLICATION_READ                              = 'capital_los_application_read';
    const CAPITAL_LOS_APPLICATION_EDIT                              = 'capital_los_application_edit';
    const CAPITAL_LOS_APPLICATION_CLOSURE                           = 'capital_los_application_closure';
    const CAPITAL_LOS_APPLICATION_DOC_READ                          = 'capital_los_application_doc_read';
    const CAPITAL_LOS_APPLICATION_DOC_EDIT                          = 'capital_los_application_doc_edit';
    const CAPITAL_LOS_APPLICATION_DOC_REVIEW                        = 'capital_los_application_doc_review';
    const CAPITAL_LOS_APPLICATION_SCORE_EDIT                        = 'capital_los_application_score_edit';
    const CAPITAL_LOS_APPLICATION_OFFER_READ                        = 'capital_los_application_offer_read';
    const CAPITAL_LOS_APPLICATION_OFFER_EDIT                        = 'capital_los_application_offer_edit';
    const CAPITAL_LOS_APPLICATION_DISBURSAL_READ                    = 'capital_los_application_disbursal_read';
    const CAPITAL_LOS_APPLICATION_DISBURSAL_EDIT                    = 'capital_los_application_disbursal_edit';
    const CAPITAL_LOS_APPLICATION_ESIGN_READ                        = 'capital_los_application_esign_read';
    const CAPITAL_LOS_APPLICATION_ESIGN_EDIT                        = 'capital_los_application_esign_edit';
    const CAPITAL_LOS_D2C_READ                                      = 'capital_los_d2c_read';
    const CAPITAL_LOS_D2C_MASKED_READ                               = 'capital_los_d2c_masked_read';
    const CAPITAL_LOS_D2C_SUMMARY_READ                              = 'capital_los_d2c_summary_read';
    const CAPITAL_LOS_D2C_REPORT_READ                               = 'capital_los_d2c_report_read';
    const CAPITAL_LOS_PRODUCTS_READ                                 = 'capital_los_products_read';
    const CAPITAL_LOS_PRODUCTS_EDIT                                 = 'capital_los_products_edit';
    const CAPITAL_LOS_DOCUMENTS_READ                                = 'capital_los_documents_read';
    const CAPITAL_LOS_DOCUMENTS_EDIT                                = 'capital_los_documents_edit';
    const CAPITAL_LOS_LENDER_READ                                   = 'capital_los_lender_read';
    const CAPITAL_LOS_LENDER_EDIT                                   = 'capital_los_lender_edit';
    const CAPITAL_LOS_M2P_EDIT                                      = 'capital_los_m2p_edit';
    const CAPITAL_LOS_BUREAU_COMPLIANCE_EDIT                        = 'capital_los_bureau_compliance_edit';
    const CAPITAL_LOS_DEVELOPER                                     = 'capital_los_developer';
    const CAPITAL_LOS_SCORECARD_FILES_DELETE                        = 'capital_los_scorecard_files_delete';
    const CAPITAL_LOS_D2C_INVITE_READ                               = 'capital_los_d2c_invite_read';
    const CAPITAL_LOS_D2C_INVITE_EDIT                               = 'capital_los_d2c_invite_edit';
    const CAPITAL_LOS_APPLICATION_OFFER_EDIT_PRIVILEGED             = 'capital_los_application_offer_edit_privileged';
    const CAPITAL_LOS_APPLICATION_DOC_VERIFICATION_DETAILS_READ     = 'capital_los_application_doc_verification_details_read';
    const CAPITAL_LOS_APPLICATION_DOC_VERIFICATION_DETAILS_TRIGGER  = 'capital_los_application_doc_verification_details_trigger';

    // Emandate config
    const MANAGE_EMANDATE_CONFIG                      = 'manage_emandate_config';

    //permission to edit/execute bulk international disable/enable workflowAction
    const EDIT_MERCHANT_DISABLE_INTERNATIONAL_BULK    = 'edit_merchant_disable_international_bulk';
    const EDIT_MERCHANT_ENABLE_INTERNATIONAL_BULK     = 'edit_merchant_enable_international_bulk';
    const EXECUTE_MERCHANT_DISABLE_INTERNATIONAL_BULK = 'execute_merchant_disable_international_bulk';
    const EXECUTE_MERCHANT_ENABLE_INTERNATIONAL_BULK  = 'execute_merchant_enable_international_bulk';

    // Admin Dashboard Bulk Updation of Max Payment and International Payment Limit
    const MERCHANT_MAX_PAYMENT_LIMIT_UPDATE           = 'merchant_max_payment_limit_update';
    const EXECUTE_MERCHANT_MAX_PAYMENT_LIMIT_WORKFLOW = 'execute_merchant_max_payment_limit_workflow';
    const UPDATE_MERCHANT_MAX_PAYMENT_LIMIT_WORKFLOW  = 'update_merchant_max_payment_limit_workflow';

    // Admin Dashboard Send Request Activation Documents Notification
    const SEND_REQUEST_ACTIVATION_DOCUMENTS_NOTIFICATION = 'send_request_activation_documents_notification';

    // Admin Dashboard Reports
    const VIEW_ADMIN_REPORTS                  = 'view_admin_reports';

    // Sub VA constants
    const ADMIN_SUB_VIRTUAL_ACCOUNT             = 'admin_sub_virtual_account';
    const MERCHANT_FETCH_SUB_VIRTUAL_ACCOUNT    = 'merchant_fetch_sub_virtual_account';
    const MERCHANT_SUB_VIRTUAL_ACCOUNT_TRANSFER = 'merchant_sub_virtual_account_transfer';

    const ADMIN_PROCESS_PENDING_BANK_TRANSFER    = 'admin_process_pending_bank_transfer';

    // Permission to create auto-kyc soft limit breached workflow
    const AUTO_KYC_SOFT_LIMIT_BREACH          = 'auto_kyc_soft_limit_breach';
    const AUTO_KYC_SOFT_LIMIT_BREACH_UNREGISTERED = 'auto_kyc_soft_limit_breach_unregistered';

    // International transaction limit of merchant
    const INCREASE_INTERNATIONAL_TRANSACTION_LIMIT = 'increase_international_transaction_limit';

     //non-3ds card processing of a merchant
     const ENABLE_NON_3DS_PROCESSING = 'enable_non_3ds_processing';

    // Permission to create NC responded WF
    const NEEDS_CLARIFICATION_RESPONDED = 'needs_clarification_responded';

    // Permission to create workflow for an impersonating merchant found during Dedupe
    const IMPERSONATING_MERCHANT_DEDUPE       = 'impersonating_merchant_dedupe';

    // UFH permission
    const DOWNLOAD_UFH_FILE_BY_MID            = 'download_ufh_file_by_mid';

    // RazorpayX/Business banking permissions
    const BANKING_UPDATE_ACCOUNT              = 'banking_update_account';
    const ASSIGN_BANKING_ACCOUNT_REVIEWER     = 'assign_banking_account_reviewer';
    const RBL_BANK_MID_OFFICE_VIEW_LEAD       = 'rbl_bank_mid_office_view_lead';
    const RBL_BANK_MID_OFFICE_MANAGE_LEAD     = 'rbl_bank_mid_office_manage_lead';
    const RBL_BANK_MID_OFFICE_EDIT_LEAD       = 'rbl_bank_mid_office_edit_lead';

    //Permissions for enabling maker/checker for payouts
    const CREATE_PAYOUT                       = 'create_payout';

    //Permission to allow fee recovery attempt
    const PROCESS_FEE_RECOVERY                = 'process_fee_recovery';

    //Permission to assign a fee recovery schedule to a merchant
    const ASSIGN_FEE_RECOVERY_SCHEDULE        = 'assign_fee_recovery_schedule';

    // Permission to manage payout mode config
    const MANAGE_PAYOUT_MODE_CONFIG           = 'manage_payout_mode_config';

    // Perform write operations around stork integration e.g. webhook migrations etc
    const STORK_WRITE_OPERATION               = 'stork_write_operation';

    // Perform various support operation e.g. processing bulk webhook events via csv etc.
    const STORK_SUPPORT_OPERATION             = 'stork_support_operation';

    // Allows performing various write operations around dual writes and migrations for credcase, and edge.
    const EDGE_WRITE_OPERATION                = 'edge_write_operation';

    // Permissions for UPI/P2P Service
    const UPI_MANAGE_PSPS                     = 'upi_manage_psps';
    const UPI_MANAGE_DATA                     = 'upi_manage_data';
    const P2P_MANAGE_MERCHANT                 = 'p2p_manage_merchant';

    // Permission to update business website.
    const UPDATE_MERCHANT_WEBSITE             = 'update_merchant_website';

    // Permission to see decrypted merchant website.
    const DECRYPT_MERCHANT_WEBSITE_COMMENT    = 'decrypt_merchant_website_comment';

    // Permission to add business website.
    const EDIT_MERCHANT_WEBSITE_DETAIL        = 'edit_merchant_website_detail';
    const ADD_ADDITIONAL_WEBSITE              = 'add_additional_website';

    // Bulk IIR create Permission
    const INTERNAL_INSTRUMENT_CREATE_BULK     = "internal_instrument_create_bulk";

    // Permission to access capital-los service
    const LOANS_EDIT                          = 'loans_edit';
    const LOS_BASIC                           = 'los_basic';

    const LOS_CARDS                           = 'los_cards';

    // Permission to access capital-cards service
    const CAPITAL_CARDS                       = 'capital_cards';

    // Permission to access capital-cards service rewards related read APIs
    const CAPITAL_CARDS_REWARDS_READ          = 'capital_cards_rewards_read';

    // Permission to access capital-cards service rewards related write APIs
    const CAPITAL_CARDS_REWARDS_EDIT          = 'capital_cards_rewards_edit';

    // This will give all required accesses to capital risk developers
    const CAPITAL_RISK_DEVELOPER              = 'capital_risk_developer';

    // Permission to access capital-scorecard service
    const CAPITAL_SCORECARD                   = 'capital_scorecard';

    // Permission to access Capital LOS application One pager data
    const ONE_PAGER                           = 'one_pager';

    // Permission to scorecard edit (bureau L1 edit) tab on Admin dashboard UI
    const SCORECARD_EDIT                      = 'scorecard_edit';

    // Permission to view scorecard entities on Admin dashboard UI
    const SCORECARD_VIEW                      = 'scorecard_view';

    // Permission to generate dedupe in Capital Risk/ Dedupe Tab
    const DEDUPE_MATCH                        = 'dedupe_match';

    // Permission to view dedupe in Capital Risk/ Dedupe Tab
    const DEDUPE_VIEW                         = 'dedupe_view';

    // Permission to trigger STP generation
    const GENERATE_STP                        = 'generate_stp';

    // Permissions to access download of CLI/CLD files
    const SCORECARD_CLI_CLD_VIEW              = 'scorecard_cli_cld_view';

    // Permissions to access upload and download of CLI/CLD files
    const SCORECARD_CLI_CLD_REVIEW            = 'scorecard_cli_cld_review';

    // Permission to access wallet admin actions
    const WALLETS                             = 'wallet';

    const MOB_ADMIN                           = 'mob_admin';
    const LOC                                 = 'loc';
    const LOC_CONFIG_EDIT                     = 'loc_config_edit';
    const LOC_CONFIG_VIEW                     = 'loc_config_view';
    const LOC_WITHDRAWAL_EDIT                 = 'loc_withdrawal_edit';
    const LOC_WITHDRAWAL_VIEW                 = 'loc_withdrawal_view';

    const CAPITAL_CREATE_PAYMENT_LINK         = 'capital_send_payment_link';

    // Workflow Service
    const WFS_CONFIG_CREATE                   = 'wfs_config_create';
    const WFS_CONFIG_UPDATE                   = 'wfs_config_update';
    const WFS_VIEW_SPR_WORKFLOWS              = 'wfs_view_spr_workflows';
    const WFS_VIEW_CB_WORKFLOWS               = 'wfs_view_cb_workflows';

    const CREATE_PAYOUT_BULK                  = 'create_payout_bulk';
    const APPROVE_PAYOUT_BULK                 = 'approve_payout_bulk';
    const REJECT_PAYOUT_BULK                  = 'reject_payout_bulk';
    const APPROVE_PAYOUT                      = 'approve_payout';
    const REJECT_PAYOUT                       = 'reject_payout';
    const RETRY_PAYOUT_WORKFLOW_BULK          = 'retry_payout_workflow_bulk';
    const VIEW_PAYOUT                         = 'view_payout';
    const CANCEL_PAYOUT                       = 'cancel_payout';
    const UPDATE_PAYOUT                       = 'update_payout';
    const DOWNLOAD_PAYOUT_ATTACHMENTS         = 'download_payout_attachments';
    const UPDATE_PAYOUT_ATTACHMENT            = 'update_payout_attachment';
    const VIEW_PAYOUT_PURPOSE                 = 'view_payout_purpose';
    const CREATE_PAYOUT_PURPOSE               = 'create_payout_purpose';
    const VIEW_PAYOUT_REVERSAL                = 'view_payout_reversal';
    const PROCESS_PAYOUT_QUEUED               = 'process_payout_queued';
    const PROCESS_PAYOUT_SCHEDULED            = 'process_payout_scheduled';
    const VIEW_PAYOUT_SUMMARY                 = 'view_payout_summary';
    const VIEW_PAYOUT_WORKFLOW_SUMMARY        = 'view_payout_workflow_summary';
    const VIEW_PAYOUT_LINKS                   = 'view_payout_links';
    const CREATE_PAYOUT_LINKS                 = 'create_payout_links';
    const APPROVE_PAYOUT_LINKS                = 'approve_payout_links';
    const REJECT_PAYOUT_LINKS                 = 'reject_payout_links';
    const BULK_APPROVE_PAYOUT_LINKS           = 'bulk_approve_payout_links';
    const BULK_REJECT_PAYOUT_LINKS            = 'bulk_reject_payout_links';
    const CANCEL_PAYOUT_LINKS                 = 'cancel_payout_links';
    const SUMMARY_PAYOUT_LINKS                = 'summary_payout_links';
    const ONBOARDING_PAYOUT_LINKS             = 'onboarding_payout_links';
    const SETTINGS_PAYOUT_LINKS               = 'settings_payout_links';
    const DASHBOARD_PAYOUT_LINKS              = 'dashboard_payout_links';
    const RESEND_PAYOUT_LINKS                 = 'resend_payout_links';
    const CREATE_VENDOR_PAYMENTS              = 'create_vendor_payments';
    const CREATE_VENDOR_PAYMENTS_EMAIL        = 'create_vendor_payments_email';
    const ENABLE_EMAIL_IMPORT                 = 'enable_email_import';
    const INVITE_VENDOR                       = 'invite_vendor';
    const EDIT_VENDOR_PAYMENTS                = 'edit_vendor_payments';
    const CANCEL_VENDOR_PAYMENTS              = 'cancel_vendor_payments';
    const VIEW_VENDOR_PAYMENTS                = 'view_vendor_payments';
    const VIEW_TDS_CATEGORIES                 = 'view_tds_categories';
    const VENDOR_PORTAL_PERMISSION            = 'vendor_portal_permission';
    const GET_SIGNED_URL                      = 'get_signed_url';
    const GENERATE_VP_INVOICE_ZIP             = 'generate_vp_invoice_zip';
    const MERCHANT_CONFIG_LOGO                = 'merchant_config_logo';
    const VIEW_CONTACT                        = 'view_contact';
    const CREATE_CONTACT                      = 'create_contact';
    const EDIT_CORPORATE_CARD                 = 'edit_corporate_card';
    const VIEW_CORPORATE_CARD                 = 'view_corporate_card';
    const CREATE_CONTACT_BULK                 = 'create_contact_bulk';
    const UPDATE_CONTACT                      = 'update_contact';
    const DELETE_CONTACT                      = 'delete_contact';
    const VIEW_CONTACT_TYPE                   = 'view_contact_type';
    const CREATE_CONTACT_TYPE                 = 'create_contact_type';
    const FUND_ACCOUNT_VALIDATION             = 'fund_account_validation';
    const FUND_ACCOUNT_VALIDATION_ADMIN       = 'fund_account_validation_admin';
    const VIEW_FUND_ACCOUNT_VALIDATION        = 'view_fund_account_validation';
    const VALIDATE_FUND_ACCOUNT               = 'validate_fund_account';
    const BULK_PATCH_FUND_ACCOUNT_VALIDATION  = 'bulk_patch_fund_account_validation';
    const VIEW_FUND_ACCOUNT                   = 'view_fund_account';
    const CREATE_FUND_ACCOUNT                 = 'create_fund_account';
    const UPDATE_FUND_ACCOUNT                 = 'update_fund_account';
    const CREATE_FUND_ACCOUNT_BULK            = 'create_fund_account_bulk';
    const CREATE_MERCHANT_KEY                 = 'create_merchant_key';
    const VIEW_MERCHANT_KEY                   = 'view_merchant_key';
    const VIEW_MERCHANT_INVOICE               = 'view_merchant_invoice';
    const UPDATE_USER_PROFILE                 = 'update_user_profile';
    const VIEW_USER                           = 'view_user';
    const VIEW_MERCHANT_USER                  = 'view_merchant_user';
    const CREATE_WEBHOOK                      = 'create_webhook';
    const UPDATE_WEBHOOK                      = 'update_webhook';
    const VIEW_WEBHOOK                        = 'view_webhook';
    const VIEW_WEBHOOK_EVENT                  = 'view_webhook_event';
    const STORK_WEBHOOK_REPLAY                = 'stork_webhook_replay';
    const STORK_CREATE_SMS_RATE_LIMIT         = 'stork_create_sms_rate_limit';
    const STORK_DELETE_SMS_RATE_LIMIT         = 'stork_delete_sms_rate_limit';
    const VIEW_REPORTING                      = 'view_reporting';
    const CREATE_REPORTING                    = 'create_reporting';
    const UPDATE_REPORTING                    = 'update_reporting';
    const VIEW_TRANSACTION_STATEMENT          = 'view_transaction_statement';
    const CREATE_INVITATION                   = 'create_invitation';
    const VIEW_INVITATION                     = 'view_invitation';
    const RESEND_INVITATION                   = 'resend_invitation';
    const UPDATE_INVITATION                   = 'update_invitation';
    const DELETE_INVITATION                   = 'delete_invitation';
    const MERCHANT_PRODUCT_SWITCH             = 'merchant_product_switch';
    const CREATE_BATCH                        = 'create_batch';
    const CREATE_PAYOUT_LINKS_BATCH           = 'create_payout_links_batch';
    const CREATE_USER_OTP                     = 'create_user_otp';
    const GENERATE_BANKING_ACCOUNT_STATEMENT  = 'generate_banking_account_statement';
    const MERCHANT_INSTANT_ACTIVATION         = 'merchant_instant_activation';
    const UPDATE_MERCHANT_FEATURE             = 'update_merchant_feature';
    const UPDATE_TEST_MERCHANT_BALANCE        = 'update_test_merchant_balance';
    const EDIT_MERCHANT_INTERNATIONAL         = 'edit_merchant_international';
    const EDIT_MERCHANT_PG_INTERNATIONAL      = 'edit_merchant_pg_international';
    const EDIT_MERCHANT_PROD_V2_INTERNATIONAL = 'edit_merchant_prod_v2_international';
    const TOGGLE_INTERNATIONAL_REVAMPED       = 'toggle_international_revamped';
    const VIEW_CONFIG_KEYS                    = 'view_config_keys';
    const MERCHANT_ACTIONS                    = 'merchant_actions';
    const MERCHANT_ACTIVATION_REVIEWERS       = 'merchant_activation_reviewers';
    const CREATE_MERCHANT                     = 'create_merchant';
    const VIEW_MERCHANT_DOCUMENT              = 'view_merchant_document';
    const AUTH_LOCAL_ADMIN                    = 'auth_local_admin';
    const MANAGE_REDIS_KEYS                   = 'manage_redis_keys';
    const USER_FETCH_ADMIN                    = 'user_fetch_admin';
    const USER_CREATE                         = 'user_create';
    const VIEW_VIRTUAL_ACCOUNT                = 'view_virtual_account';
    const EDIT_MERCHANT_INTERNATIONAL_NEW     = 'edit_merchant_international_new';
    const UPLOAD_MERCHANT_DOCUMENT            = 'upload_merchant_document';
    const DELETE_MERCHANT_DOCUMENT            = 'delete_merchant_document';
    const ADMIN_UPLOAD_MERCHANT_DOCUMENT      = 'admin_upload_merchant_document';
    const UPDATE_MERCHANT_BANK_ACCOUNT_STATUS = 'merchant_bank_account_change_status';
    const TOGGLE_TRANSACTION_HOLD_STATUS      = 'toggle_transaction_hold_status';
    const CREATE_PROMOTION_EVENT              = 'create_promotion_event';
    const CREDITS_BATCH_UPLOAD                = 'credits_batch_upload';
    const UPDATE_MERCHANT_2FA_SETTING         = 'update_merchant_2fa_setting';
    const MERCHANT_SEND_ACTIVATION_MAIL       = 'merchant_send_activation_mail';
    const PAYOUT_LINKS_ADMIN_BULK_CREATE      = 'payout_links_admin_bulk_create';
    const TALLY_PAYOUT_BULK_CREATE            = 'tally_payout_bulk_create';
    const UPDATE_MERCHANT_DETAILS             = 'update_merchant_details';

    // self serve workflow
    const SELF_SERVE_WORKFLOW_CONFIG     = 'self_serve_workflow_config';

    // merchant preferences
    const UPDATE_MERCHANT_PREFERENCE          = 'update_merchant_preference';
    const VIEW_MERCHANT_PREFERENCE            = 'view_merchant_preference';

    // tax payment settings
    const PAY_TAX_PAYMENTS                    = 'pay_tax_payment';
    const CREATE_TAX_PAYMENTS                 = 'create_tax_payment';
    const GENERATE_TDS_CHALLAN_ZIP            = 'generate_tds_challan_zip';
    const UPDATE_TAX_PAYMENT_SETTINGS         = 'update_tax_payment_settings';
    const UPDATE_TAX_PAYMENT_SETTINGS_AUTO    = 'update_tax_payment_settings_auto';
    const VIEW_TAX_PAYMENTS                   = 'view_tax_payments';
    const VIEW_TAX_PAYMENT_SETTINGS           = 'view_tax_payment_settings';

    // permission to raise needs clarification on workflow
    const MERCHANT_CLARIFICATION_ON_WORKFLOW  = 'merchant_clarification_on_workflow';

    const VIEW_INTERNAL_INSTRUMENT_REQUEST   = 'view_internal_instrument_request';
    const UPDATE_KAM_INTERNAL_INSTRUMENT_REQUEST = 'update_kam_internal_instrument_request';
    const VIEW_IIR_TEMPLATE                  = 'view_iir_template';
    const EDIT_IIR_TEMPLATE                  = 'edit_iir_template';
    const UPDATE_INTERNAL_INSTRUMENT_REQUEST = 'update_internal_instrument_request';
    const CANCEL_INTERNAL_INSTRUMENT_REQUEST = 'cancel_internal_instrument_request';
    const DELETE_INTERNAL_INSTRUMENT_REQUEST = 'delete_internal_instrument_request';

    const UPDATE_MERCHANT_INSTRUMENT_REQUEST = 'update_merchant_instrument_request';
    const SKIP_ACTIVATION_CHECK_WHILE_RAISING_MIR_FROM_KAM = 'skip_activation_check_while_raising_mir_from_kam';
    const VIEW_MERCHANT_INSTRUMENT_REQUEST   = 'view_merchant_instrument_request';

    const UPDATE_MERCHANT_INSTRUMENT = 'update_merchant_instrument';

    const MANAGE_TERMINAL_TESTING             = 'manage_terminal_testing';

    const VIEW_GATEWAY_CREDENTIAL             = 'view_gateway_credential';
    const CREATE_GATEWAY_CREDENTIAL           = 'create_gateway_credential';
    const DELETE_GATEWAY_CREDENTIAL           = 'delete_gateway_credential';

    const CREATE_EXTERNAL_ORG_TERMINALS       = 'external_org_create_terminals';
    const EDIT_EXTERNAL_ORG_TERMINALS         = 'external_org_edit_terminals';
    const EXECUTE_TERMINAL_TEST               = 'execute_terminal_test';

    const TERMINALS_UNIVERSAL_PROXY           = 'terminals_universal_proxy';
    const VIEW_IIR_DISCREPANCY                = 'view_iir_discrepancy';
    const CREATE_IIR_DISCREPANCY              = 'create_iir_discrepancy';
    const CREATE_DISCREPANCY                  = 'create_discrepancy';
    const EDIT_DISCREPANCY                    = 'edit_discrepancy';


    const MANAGE_PAYOUT_DOWNTIME              = 'manage_payout_downtime';
    const VIEW_PAYOUT_DOWNTIME                = 'view_payout_downtime';
    const MANAGE_FUND_LOADING_DOWNTIME        = 'manage_fund_loading_downtime';
    const VIEW_FUND_LOADING_DOWNTIME          = 'view_fund_loading_downtime';
    const VAULT_TOKEN_CREATE                  = 'vault_token_create';

    const CURRENCY_FETCH_RATES                = 'currency_fetch_rates';

    // Permissions for Financial Data Service - Razorpay Capital
    const FINANCIAL_DATA_SERVICE              = 'financial_data_service';

    // Redis Config Permissions
    const SET_RX_ACCOUNT_PREFIX               = 'set_rx_account_prefix';
    const SET_SHARED_ACCOUNT_ALLOWED_CHANNELS = 'set_shared_account_allowed_channels';
    const SET_PAYER_ACCOUNT_INVALID_REGEX     = 'set_payer_account_invalid_regex';
    const SET_PAYER_NAME_INVALID_REGEX        = 'set_payer_name_invalid_regex';
    const SET_TENANT_ROLES_CONFIG             = 'set_tenant_roles_config';
    const SET_DCS_READ_WRITE_CONFIG           = 'set_dcs_read_write_config';

    // Low Balance Config
    const CREATE_LOW_BALANCE_CONFIG           = 'create_low_balance_config';
    const UPDATE_LOW_BALANCE_CONFIG           = 'update_low_balance_config';
    const ENABLE_LOW_BALANCE_CONFIG           = 'enable_low_balance_config';
    const DISABLE_LOW_BALANCE_CONFIG          = 'disable_low_balance_config';
    const DELETE_LOW_BALANCE_CONFIG           = 'delete_low_balance_config';
    const CREATE_LOW_BALANCE_CONFIG_ADMIN     = 'create_low_balance_config_admin';
    const UPDATE_LOW_BALANCE_CONFIG_ADMIN     = 'update_low_balance_config_admin';

    // Merchant Notification Config
    const CREATE_MERCHANT_NOTIFICATION_CONFIG        = 'create_merchant_notification_config';
    const UPDATE_MERCHANT_NOTIFICATION_CONFIG        = 'update_merchant_notification_config';
    const ENABLE_MERCHANT_NOTIFICATION_CONFIG        = 'enable_merchant_notification_config';
    const DISABLE_MERCHANT_NOTIFICATION_CONFIG       = 'disable_merchant_notification_config';
    const DELETE_MERCHANT_NOTIFICATION_CONFIG        = 'delete_merchant_notification_config';

    // Admin merchant notification config
    const MERCHANT_NOTIFICATION_CONFIG_ADMIN         = 'create_merchant_notification_config_admin';

    const CORRECT_MERCHANT_OWNER_MISMATCH     = 'correct_merchant_owner_mismatch';

    const TAX_PAYMENT_ADMIN_AUTH_EXECUTE      = 'tax_payment_admin_auth_execute';

    const PAYOUT_LINK_ADMIN_AUTH_EXECUTE      = 'payout_link_admin_auth_execute';

    const PAYOUT_STATUS_UPDATE_MANUALLY       = 'payout_status_update_manually';

    const BANKING_ACCOUNT_STATEMENT_RUN_MANUALLY = 'banking_account_statement_run_manually';

    const INSERT_AND_UPDATE_BAS               = 'insert_and_update_bas';

    // Update Free Payout Permission
    const UPDATE_FREE_PAYOUTS_ATTRIBUTES      = 'update_free_payouts_attributes';

    // Free Payout Migration to Payouts Service
    const FREE_PAYOUT_MIGRATION_TO_PS         = 'free_payout_migration_to_ps';

    // View Free Payout Permission
    const VIEW_FREE_PAYOUTS_ATTRIBUTES        = 'view_free_payouts_attributes';

    // Transfer/Route Debug Permission

    const DEBUG_TRANSFERS_ROUTES              = 'debug_transfers_routes';

    const DEBUG_VIRTUAL_ACCOUNT               = 'debug_virtual_account';

    const PAYMENT_LINKS_V2_ADMIN              = 'payment_links_v2_admin';

    const PAYMENT_LINKS_OPS_BATCH_CANCEL      = 'payment_links_ops_batch_cancel';

    // linked account data reference Permission

    const LINKED_ACCOUNT_REFERENCE_DATA_CREATE       = 'linked_account_reference_data_create';
    const LINKED_ACCOUNT_REFERENCE_DATA_UPDATE       = 'linked_account_reference_data_update';
    const AMC_LINKED_ACCOUNT_CREATION                = 'amc_linked_account_creation';

    // Dedupe Permission
    const VIEW_MERCHANT_DEDUPE                = 'view_merchant_dedupe';
    const SUB_MERCHANT_DEDUPE                 = 'submerchant_dedupe';
    const EDIT_MERCHANT_DEDUPE                = 'edit_merchant_dedupe';

    // Permission for merchants to view free payouts attributes
    const MERCHANT_VIEW_FREE_PAYOUTS_ATTRIBUTES = 'merchant_view_free_payouts_attributes';

    // To be used to hit any route that needs tech admin route access.
    const RX_ADMIN_ACTION_PERMISSION          = 'rx_admin_action_permission';

    const UPDATE_BULK_PAYOUT_AMOUNT_TYPE      = 'update_bulk_payout_amount_type';

    const CREATE_ACCOUNTING_INTEGRATION   = 'create_accounting_integration';
    const DELETE_ACCOUNTING_INTEGRATION   = 'delete_accounting_integration';
    const VIEW_ACCOUNTING_INTEGRATION     = 'view_accounting_integration';
    const UPDATE_ACCOUNTING_INTEGRATION   = 'update_accounting_integration';
    const SYNC_ACCOUNTING_INTEGRATION     = 'sync_accounting_integration';
    const WAITLIST_ACCOUNTING_INTEGRATION = 'waitlist_accounting_integration';

    // Generic Accounting Integration Permissions
    // These permissions are intentionally not mapped to any route, this is purely for FE to consume,
    // actual route authorization is done at the microservice layer.
    const CREATE_GENERIC_ACCOUNTING_INTEGRATION = 'create_generic_accounting_integration';
    const VIEW_GENERIC_ACCOUNTING_INTEGRATION = 'view_generic_accounting_integration';

    const UPDATE_USER_ROLE = 'update_user_role';

    // Allow coupon validation for X
    const COUPON_VALIDATE                   = 'coupon_validate';

    const ADMIN_FETCH_FUND_ACCOUNT_VALIDATION           = 'admin_fetch_fund_account_validation';

    const MERCHANT_IP_WHITELIST                    = 'merchant_ip_whitelist';
    const ADMIN_MERCHANT_IP_WHITELIST              = 'admin_merchant_ip_whitelist';

    const APP_REGISTRATION                              = 'app_registration';
    const APP_MAPPING                                   = 'app_mapping';

    const MANUALLY_LINK_RBL_ACCOUNT_STATEMENT = 'manually_link_rbl_account_statement';

    const MERCHANT_RISK_ALERT_FOH           = 'merchant_risk_alert_foh';
    const MERCHANT_RISK_CONSTRUCTIVE_ACTION = 'merchant_risk_constructive_action';
    const MERCHANT_RISK_ALERT_UPSERT_RULE   = 'merchant_risk_alert_upsert_rule';
    const MERCHANT_RISK_ALERT_DELETE_RULE   = 'merchant_risk_alert_delete_rule';

    const MERCHANT_RISK_ALERT_CONFIG = 'merchant_risk_alert_config';

    const CREATE_CYBER_HELPDESK_WORKFLOW    = 'create_cyber_helpdesk_workflow';

    // NPS survey create
    const NPS_SURVEY                          = 'nps_survey';

    // Salesforce
    const VIEW_SALESFORCE_OPPORTUNITY_DETAIL           = 'view_salesforce_opportunity_detail';

    // TPV for fund loading into X
    const CREATE_BANKING_ACCOUNT_TPV                   = 'create_banking_account_tpv';
    const EDIT_BANKING_ACCOUNT_TPV                     = 'edit_banking_account_tpv';
    const VIEW_BANKING_ACCOUNT_TPV                     = 'view_banking_account_tpv';

    // Whitelist VA to VA payouts based on destination MID
    const EDIT_DESTINATION_MIDS_TO_WHITELIST_VA_TO_VA_PAYOUTS = 'edit_destination_mids_to_whitelist_va_to_va_payouts';

    // Set globally whitelisted payer accounts for rx fund loading
    const SET_RX_GLOBALLY_WHITELISTED_PAYER_ACCOUNTS = 'set_rx_globally_whitelisted_payer_accounts';

    // Media service
    const MEDIA_SERVICE_UPLOAD_FILE     = 'media_service_upload_file';
    const MEDIA_SERVICE_GET_BUCKET      = 'media_service_get_bucket';
    const MEDIA_SERVICE_UPLOAD_PROCESS  = 'media_service_upload_process';

    // Templating Service
    const TEMPLATING_SERVICE_WRITE_NAMESPACES          = 'templating_service_write_namespaces';
    const TEMPLATING_SERVICE_READ_NAMESPACES           = 'templating_service_read_namespaces';
    const TEMPLATING_SERVICE_WRITE_TEMPLATE_CONFIGS    = 'templating_service_write_template_configs';
    const TEMPLATING_SERVICE_READ_TEMPLATE_CONFIGS     = 'templating_service_read_template_configs';
    const TEMPLATING_SERVICE_DELETE_TEMPLATE_CONFIGS   = 'templating_service_delete_template_configs';
    const TEMPLATING_SERVICE_WRITE_ROLE                = 'templating_service_write_role';
    const DUMMY_ROUTE                                  = 'dummy_route';

    const MANAGE_INHERITANCE                  = 'manage_inheritance';
    const MARK_TRANSACTIONS_POSTPAID                   = 'mark_transactions_postpaid';
    const FETCH_MERCHANT_BALANCE_CONFIG                = 'fetch_merchant_balance_config';
    const CREATE_MERCHANT_BALANCE_CONFIG               = 'create_merchant_balance_config';
    const EDIT_MERCHANT_BALANCE_CONFIG                 = 'edit_merchant_balance_config';
    const DELETE_PAYMENT_CONFIG                        = 'delete_payment_config';
    const UPDATE_ENTITY_BALANCE_ID                     = 'update_entity_balance_id';
    const CREATE_REWARD                                = 'create_reward';
    const UPDATE_REWARD                                = 'update_reward';
    const DELETE_REWARD                                = 'delete_reward';
    const GET_ADVERTISER_LOGO                          = 'get_advertiser_logo';
    const REWARD_BATCH_MAIL                            = 'reward_batch_mail';
    const PG_ROUTER_ORDER_SYNC                         = 'pg_router_order_sync';
    const GET_IRCTC_SETTLEMENT_FILE                    = 'get_irctc_settlement_file';
    const CREATE_TRANSACTION_FEE_BREAKUP               = 'create_transaction_fee_breakup';
    const MANAGE_CARE_SERVICE_CALLBACK                 = 'manager_care_service_callback';
    const CARE_SERVICE_DARK_PROXY                      = 'care_service_dark_proxy';
    const MANAGE_FRESHCHAT                             = 'manage_freshchat';
    const BULK_UPDATE_CHARGEBACK_POC                   = 'bulk_update_chargeback_poc';
    const BULK_UPDATE_WHITELISTED_DOMAIN               = 'bulk_update_whitelisted_domain';
    const ED_MERCHANT_SEARCH                           = 'ed_merchant_search';

    const QUICKLINK_CREATE                             = 'quicklink_create';

    const CREATE_DEBIT_NOTE                            = 'create_debit_note';

    const TOKEN_REGISTRATION_ACTIONS = 'token_registration_actions';

    //Permission to update merchant risk attributes
    const EDIT_MERCHANT_RISK_ATTRIBUTES = 'edit_merchant_risk_attributes';

    //partner KYC permissions
    const EDIT_ACTIVATE_PARTNER               = 'edit_activate_partner';
    const PARTNER_ACTIONS                     = 'partner_actions';
    const ASSIGN_PARTNER_ACTIVATION_REVIEWER  = 'assign_partner_activation_reviewer';

    // Perform general purpose read operations e.g. query cache stats, elasticsearch meta etc.
    const DEVELOPERS_READ                              = 'developers_read';

    // Ledger service permissions
    const LEDGER_SERVICE_ACTIONS                       = 'ledger_service_actions';
    const LEDGER_CLIENT_ACTIONS                        = 'ledger_client_actions';
    const LEDGER_VIEW_DASHBOARD                        = 'ledger_view_dashboard';

    // PG Ledger Permissions
    const PG_LEDGER_ACTIONS                            = 'pg_ledger_actions';

    // Settlement Service permissions
    const SETTLEMENT_SERVICE_MERCHANT_CONFIG_EDIT = 'settlement_service_merchant_config_edit';

    const GET_MERCHANT_RISK_DATA = 'get_merchant_risk_data';

    const BULK_FRAUD_NOTIFY = 'bulk_fraud_notify';

    const WEBSITE_CHECKER = 'website_checker';

    const BULK_HITACHI_CHARGEBACK = 'bulk_hitachi_chargeback';

    // Recon service permission
    const RECON_OPERATION                              = 'recon_operation';
    const RECON_ADMIN_OPERATION                        = 'recon_admin_operation';

    // Metro
    const METRO_PROJECT_CREATE                        = 'metro_project_create';
    const METRO_PROJECT_CREDENTIALS_CREATE            = 'metro_project_credentials_create';
    const METRO_PROJECT_TOPIC_UPDATE                  = 'metro_project_topic_update';

    // Retry payouts on payout service
    const RETRY_PAYOUTS_ON_SERVICE  = 'retry_payouts_on_service';

    // Merchant Risk Notes
    const CREATE_MERCHANT_RISK_NOTES    = 'create_merchant_risk_notes';
    const GET_MERCHANT_RISK_NOTES       = 'get_merchant_risk_notes';
    const DELETE_MERCHANT_RISK_NOTES    = 'delete_merchant_risk_notes';

    const CALLBACK_SLOT_CONFIG_VIEW                    = 'callback_slot_config_view';
    const CALLBACK_SLOT_CONFIG_EDIT                    = 'callback_slot_config_edit';

    const CLICK_TO_CALL_TIMING_CONFIG_VIEW = 'click_to_call_timing_config_view';
    const CLICK_TO_CALL_TIMING_CONFIG_EDIT = 'click_to_call_timing_config_edit';

    const TICKET_CONFIG_EDIT = 'ticket_config_edit';
    const TICKET_CONFIG_VIEW = 'ticket_config_view';

    const FAQ_CONFIG_EDIT  = 'faq_config_edit';
    const FAQ_CONFIG_VIEW  = 'faq_config_view';

    // Payment Fraud
    const GET_FRAUD_ATTRIBUTES          = 'get_fraud_attributes';
    const SAVE_PAYMENT_FRAUD            = 'save_payment_fraud';

    const NACH_BATCH_UPLOAD     = 'nach_batch_upload';
    const EMANDATE_BATCH_UPLOAD = 'emandate_batch_upload';

    // Razorpay Trusted Badge
    const UPDATE_TRUSTED_BADGE_STATUS       = 'update_trusted_badge_status';

    const BULK_TOKENISATION       = 'bulk_tokenisation';

    const ECOLLECT_ICICI_BATCH_UPLOAD                = 'ecollect_icici_batch_upload';
    const ECOLLECT_RBL_BATCH_UPLOAD                  = 'ecollect_rbl_batch_upload';
    const ECOLLECT_YESBANK_BATCH_UPLOAD              = 'ecollect_yesbank_batch_upload';
    const VIRTUAL_BANK_ACCOUNT_BATCH_UPLOAD          = 'virtual_bank_account_batch_upload';

    // Admin action permission for X Ops Team
    const ENABLE_DOWNTIME_NOTIFICATION_X_DASHBOARD   = 'enable_downtime_notification_x_dashboard';

    const CAPITAL_COLLECTIONS_ADMIN             = 'capital_collections_admin';
    const PAYMENTS_PROMOTION_COUPON_APPLY       = 'payments_promotion_coupon_apply';
    const PAYMENTS_PROMOTION_COUPON_DELETE      = 'payments_promotion_coupon_delete';
    const PAYMENTS_PROMOTION_COUPON_UPDATE      = 'payments_promotion_coupon_update';
    const MERCHANT_ADD_CREDITS_BULK             = 'merchant_add_credits_bulk';
    const MERCHANT_EMAIL_ADDITIONAL_CREATE      = 'merchant_email_additional_create';
    const MERCHANT_EMAIL_ADDITIONAL_DELETE      = 'merchant_email_additional_delete';
    const PAYMENT_PROMOTION_EVENT_UPDATE        = 'payment_promotion_event_update';
    const PAYMENTS_OFFER_BULK_CREATE            = 'payments_offer_bulk_create';
    const PAYMENTS_OFFER_BULK_DEACTIVATE        = 'payments_offer_bulk_deactivate';
    const CAPITAL_SETTLEMENT_ONDEMAND_PRICING   = 'capital_settlement_ondemand_pricing';

    const MERCHANT_PRICING_BULK_CREATE                          = 'merchant_pricing_bulk_create';
    const PAYMENTS_REFUNDS_RETRY_FUND_TRANSFERS_SCROOGE_SOURCE  = 'payments_refunds_retry_fund_transfers_scrooge_source';
    const PAYMENTS_REFUNDS_RETRY_FUND_TRANSFERS_SCROOGE_CUSTOM  = 'payments_refunds_retry_fund_transfers_scrooge_custom';
    const CREATE_CREDIT_BUREAU_REPORTS                          = 'create_credit_bureau_reports';
    const MERCHANT_DISPUTE_BULK_EDIT                            = 'merchant_dispute_bulk_edit';
    const MERCHANT_ACTIVATION_ARCHIVE                           = 'merchant_activation_archive';
    const MERCHANT_BANK_ACCOUNT_DETAIL_CREATE                   = 'merchant_bank_account_detail_create';
    const MERCHANT_BANK_ACCOUNT_DETAIL_UPDATE                   = 'merchant_bank_account_detail_update';
    const MERCHANT_SETTLEMENT_BANK_ACCOUNT_CREATE               = 'merchant_settlement_bank_account_create';
    const MERCHANT_SETTLEMENT_BANK_ACCOUNT_UPDATE               = 'merchant_settlement_bank_account_update';

    const MERCHANT_SETTLEMENT_BANK_ACCOUNT_DELETE       = 'merchant_settlement_bank_account_delete';
    const MERCHANT_BANK_ACCOUNT_EDIT                    = 'merchant_bank_account_edit';
    const MERCHANT_BANK_ACCOUNT_TEST_GENERATE           = 'merchant_bank_account_test_generate';
    const MERCHANT_BANK_ACCOUNT_UPDATE                  = 'merchant_bank_account_update';
    const PAYMENTS_REFUND_EDIT_BULK                     = 'payments_refund_edit_bulk';
    const SET_CONFIG_KEYS                               = 'set_config_keys';
    const DELETE_CONFIG_KEY                             = 'delete_config_key';
    const PAYMENTS_BUY_PRICING_PLAN_RULE_ADD            = 'payments_buy_pricing_plan_rule_add';
    const PAYMENTS_BUY_PRICING_PLAN_RULE_FORCE_DELETE   = 'payments_buy_pricing_plan_rule_force_delete';
    const RECON_SERVICE_REQUEST                         = 'recon_service_request';

    const RECON_FILE_UPLOAD                     = 'recon_file_upload';
    const PAYMENTS_REFUND_FAILED_VERIFY_BULK    = 'payments_refund_failed_verify_bulk';
    const PAYMENTS_REFUND_SCROOGE_CREATE        = 'payments_refund_scrooge_create';
    const PAYMENTS_REFUND_SCROOGE_CREATE_BULK   = 'payments_refund_scrooge_create_bulk';
    const MERCHANT_PRICING_PLAN_RULE_ADD        = 'merchant_pricing_plan_rule_add';
    const MERCHANT_PRICING_PLAN_RULE_DELETE     = 'merchant_pricing_plan_rule_delete';
    const MERCHANT_BANK_ACCOUNT_STATUS_CHANGE   = 'merchant_bank_account_status_change';
    const PAYMENTS_TERMINAL_FETCH_BY_ID         = 'payments_terminal_fetch_by_id';
    const PAYMENTS_TERMINAL_FETCH_MULTIPLE      = 'payments_terminal_fetch_multiple';
    const PROXY_MERCHANT_GET_TERMINALS          = 'proxy_merchant_get_terminals';
    const INSTANT_ACTIVATION                    = 'instant_activation';
    const MDR_ADJUSTMENTS                       = 'mdr_adjustments';
    const MERCHANT_ACTIVATION                   = 'merchant_activation';
    const MERCHANT_CONFIG_INHERITANCE           = 'merchant_config_inheritance';
    const MERCHANT_ONBOARDING                   = 'merchant_onboarding';
    const MERCHANT_STATUS_ACTIVATION            = 'merchant_status_activation';
    const PRICING_RULE                          = 'pricing_rule';

    const BULK_REGENERATE_API_KEYS              = 'bulk_regenerate_api_keys';

    const ADD_MERCHANT_EMAIL                    = 'add_merchant_email';
    const MANAGE_TOKEN_IINS                     = 'manage_token_iins';

    // Permission to allow use of master DB instead of slave
    const USE_MASTER_DB_CONNECTION              = 'use_master_db_connection';

    //relay permissions
    const RELAY_READ_PERMISSION = 'relay_permission_read';
    const RELAY_READ_WRITE_PERMISSION = 'relay_permission_read_write';

    const MANAGE_CAMPAIGNHQ_OPERATIONS          = 'manage_campaignhq_operations';
    const MERCHANT_GET_OAUTH_TOKEN              = 'merchant_get_oauth_token';

    const SET_MERCHANT_SLA_FOR_ON_HOLD_PAYOUTS  = 'set_merchant_sla_for_on_hold_payouts';

    const SET_BLACKLISTED_VPA_REGEXES_FOR_MERCHANTS  = 'set_blacklisted_vpa_regexes_for_merchants';

    //cmma admin permission
    const CMMA_PROCESS_VIEW         = 'cmma_process_view';
    const CMMA_PROCESS_EDIT         = 'cmma_process_edit';
    const CMMA_USER_TASK_VIEW       = 'cmma_user_task_view';
    const CMMA_SERVICE_PROXY_ACCESS = 'cmma_service_proxy_access';
    const CMMA_LEADS_SOP            = 'leads_sop';

    const DEBUG_NOCODE_ROUTES                   = 'debug_nocode_routes';

    const VIEW_TAX_STATES                      = 'view_tax_states';

    //FE premissions
    const VIEW_PUBLIC_PROFILE                   = 'view_public_profile';
    const VIEW_MANAGE_TEAM                      = 'view_manage_team';
    const VIEW_ALL_ROLES                        = 'view_all_roles';
    const VIEW_DEVELOPER_CONTROLS               = 'view_developer_controls';
    const VIEW_BANKING                          = 'view_banking';
    const CREATE_BANKING                        = 'create_banking';
    const VIEW_BILLING                          = 'view_billing';
//    const VIEW_WORKFLOW                         = 'view_workflow';
//    const VIEW_REPORTING                        = 'view_reporting';
    const UPDATE_SECURITY_SETTINGS              = 'update_security_settings';
    const UPDATE_BUSINESS_SETTINGS              = 'update_business_settings';

//    const EDIT_WORKFLOW    = 'edit_workflow';
    const INTEGRATE_SHOPIFY_TOOL                = 'integrate_shopify_tool';
    const VIEW_KYC                              = 'view_kyc';
    const FILL_KYC                              = 'fill_kyc';
    const DOWNLOAD_REPORTING                    = 'download_reporting';
    const CREATE_LOW_BALANCE_ALERTS             = 'create_low_balance_alerts';
    const VIEW_PAYOUTS_REPORT                   = 'view_payouts_report';
    const HAS_APP_ACCESS                        = 'has_app_access';
    const ADMIN_BULK_ASSIGN_ROLE                = 'admin_bulk_assign_role';

    const VIEW_PRIVILEGES                       = 'view_privileges';
    const FETCH_MISSING_BAS                     = 'fetch_missing_bas';

    // Full access to all roles
    const CUSTOMER_SUPPORT_FULL_ACCESS          = 'customer_support_full_access';

    const MERCHANT_BULK_UPLOAD_MIQ              = 'merchant_batch_upload_miq';

    // These permissions are intentionally not mapped to any route, this is purely for FE to consume,
    // actual route authorization is done at the microservice layer.
    const ACCOUNTS_RECEIVABLE_ADMIN             = 'accounts_receivable_admin';
    const BILL_PAYMENTS_VIEW                    = 'bill_payments_view';
    const BILL_PAYMENTS_CREATE_ACCOUNT          = 'bill_payments_create_account';
    const BILL_PAYMENTS_FETCH_BILL              = 'bill_payments_fetch_bill';
    const VIEW_FINANCEX_REPORT                  = 'view_financex_report';
    const CREATE_FINANCEX_REPORT                = 'create_financex_report';

    const MOB_SERVICE_READ                      = 'mob_service_read';
    const MOB_SERVICE_WRITE                     = 'mob_service_write';

    // Banking Account Service permissions
    const BANKING_ACCOUNT_READ                              = 'banking_account_read';
    const BANKING_ACCOUNT_WRITE                             = 'banking_account_write';

    const MERCHANT_ONBOARDING_WRITE             = 'merchant_onboarding_write';

    const MERCHANT_USER_WRITE                   = 'merchant_user_write';

    const EDIT_BALANCE_MANAGEMENT_CONFIG        = 'edit_balance_management_config';

    const BULK_DISPUTE_INGESTION_FOR_BANK = 'bulk_dispute_ingestion_for_bank';
    const DISPUTES_DCS_CONFIG_GET         = 'disputes_dcs_config_get';
    const DISPUTES_DCS_CONFIG_UPDATE      = 'disputes_dcs_config_update';

    public static $actionMap = [
        Merchant\Action::ARCHIVE                            => self::EDIT_MERCHANT_ARCHIVE,
        Merchant\Action::UNARCHIVE                          => self::EDIT_MERCHANT_UNARCHIVE,
        Merchant\Action::SUSPEND                            => self::EDIT_MERCHANT_SUSPEND,
        Merchant\Action::UNSUSPEND                          => self::EDIT_MERCHANT_UNSUSPEND,
        Merchant\Action::LIVE_DISABLE                       => self::EDIT_MERCHANT_DISABLE_LIVE,
        Merchant\Action::LIVE_ENABLE                        => self::EDIT_MERCHANT_ENABLE_LIVE,
        Merchant\Action::LOCK                               => self::EDIT_MERCHANT_LOCK_ACTIVATION,
        Merchant\Action::UNLOCK                             => self::EDIT_MERCHANT_UNLOCK_ACTIVATION,
        Merchant\Action::EDIT_COMMENT                       => self::EDIT_MERCHANT_COMMENTS,
        Merchant\Action::HOLD_FUNDS                         => self::EDIT_MERCHANT_HOLD_FUNDS,
        Merchant\Action::RELEASE_FUNDS                      => self::EDIT_MERCHANT_RELEASE_FUNDS,
        Merchant\Action::ENABLE_RECEIPT_EMAILS              => self::EDIT_MERCHANT_ENABLE_RECEIPT,
        Merchant\Action::DISABLE_RECEIPT_EMAILS             => self::EDIT_MERCHANT_DISABLE_RECEIPT,
        Merchant\Action::ENABLE_INTERNATIONAL               => self::EDIT_MERCHANT_ENABLE_INTERNATIONAL,
        Merchant\Action::DISABLE_INTERNATIONAL              => self::EDIT_MERCHANT_DISABLE_INTERNATIONAL,
        Merchant\Action::FORCE_ACTIVATE                     => self::EDIT_MERCHANT_FORCE_ACTIVATION,
        Merchant\Action::SET_RECEIPT_EMAIL_EVENT_AUTHORIZED => self::EDIT_MERCHANT_RECEIPT_EMAIL_EVENT,
        Merchant\Action::SET_RECEIPT_EMAIL_EVENT_CAPTURED   => self::EDIT_MERCHANT_RECEIPT_EMAIL_EVENT
    ];

    /**
     * Permissions that are exposed on the merchant side.
     *
     * @var array
     */
    public static $merchantPermissions = [
        self::CREATE_PAYOUT,
    ];

    public static function isMerchantPermission(string $name): bool
    {
        return (in_array($name, self::$merchantPermissions, true) === true);
    }
}
