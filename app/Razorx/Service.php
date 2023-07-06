<?php


namespace App\Razorx;

use Auth;
use App\Base;
use App\Trace\TraceCode;
use App\Admin\ApiRequestAny;

class Service extends Base\Service
{
    protected $trace;

    protected $app;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];
    }

    public function updateExperiments(array $data): array
    {
        $features = [
            'slot_booking',
            'coupons',
            'is_announcement',
            'is_banner',
            'capital_announcement',
            'capital_banner',
            'announcements_early_settlements_1',
            'checkout_survey',
            'sellerapp_plus',
            'disable-view-reports',
            'mobile_hotjar_survey',
            'show_commission_balance',
            'custom_notes',
            'capture_settings_revamp',
            'sellerapp_PL_batch_upload',
            'nps_survey_banner',
            'hide_company_name',
            'batch_cancel',
            'rev_up_chennai_announcement',
            'disable_va_creation_bank_account',
            'registered_onboarding_auto_kyc',
            'emandate_nonzero_amount',
            'card_recurring_payments_blocked',
            'allow_yesbank_va_on_x',
            'instant_refunds_default_pricing_v2',
            'covid_19_donation_show',
            'ir_pricing_v2_rollout_1',
            'ir_pricing_v2_rollout_2',
            'ir_pricing_v2_rollout_3',
            'ir_pricing_v2_rollout_4',
            'support_dashboard_rzpsolutions',
            'capital_freshdesk_integration',
            'mobile_signup_care_changes_active',
            'instrument_requests_smart_dashboard',
            'hide_call_slots_10_12_2',
            'settlement_ux_revamp_p2',
            'validate_user_2fa_status',
            'pause_resume_enabled',
            'batch_scheduling_options',
            'emandate_subscription',
            'upi_caw',
            'rx_payout_links_ms',
            'upi_subscription',
            'rx_bulk_approvals',
            'offer_on_subscription',
            'subscription_offers_reports',
            'capital_loans_announcement_aug2020',
            'instrument_request_merchant_dashboard',
            'settlement_ux_revamp_p2',
            'rx_accounting_payouts_ask_clientID',
            'enable_pl_batch_upload',
            'skip_workflow_payout_specific_feature',
            'low_balance_alert_frequency_30M',
            'rbl_migration_banner',
            'app_switcher',
            'subscription_expiry',
            'rbl_migration_banner',
            'qr_code',
            'caw_tpv',
            'whatsapp_notification_enablement',
            'partner_app_store',
            'bulk_payouts_improvements_rollout',
            'block_bank_account_update_merchant_dashboard',
            'whats-new-dec-2020',
            'AnnouncementIconJan2021',
            'disable_tpv_flow_for_banking_account_fund_loading',
            'enable_tpv_fe',
            'shopify_gtm_notification_cohorts',
            'es_ondeman_restricted_cohorts',
            'pl_swith_v2',
            'dashboard_show_nps_survey',
            'cred_pay_amex_notification',
            'caw_recurring_charge_axis',
            'rx_enable_amazonpay_wallet_payout',
            'nitro_hyderabad_v2',
            'nitro_hyderabad_v3',
            'nitro_midmarket_mumbai_v1',
            'upi_intent_notification',
            'onboarding_v2',
            'capture_settings_revamp',
            'pl_description_required',
            'pp_description_required',
            'merchant_tnc',
            'rzp_merchant_tnc',
            'comdel_hdfc_test',
            'show_csat_survey',
            'rx_shopify_pl',
            'enable_may_dashboard_notification_retention_1',
            'enable_may_dashboard_notification_retention_2',
            'enable_may_dashboard_notification_retention_3',
            'enable_may_dashboard_notification_retention_4',
            'enable_my_dashboard_notification_remarketing',
            'enable_my_dashboard_notification_remarketing_1',
            'instant-activations-functionality',
            'mandatory_aadhar_ekyc',
            'rx_vendor_portal_rollout',
            'payments_extra_refund_details',
            'recurring_more_account_type',
            'mtu_coupon_code',
            'dispute_presentment',
            'product_recommendation',
            'auto_refresh_experiment',
            'csm_experince_survey',
            'inv_create_flow_ux',
            'loans_collections_dashboard',
            'bvs_get_gst_details',
            'status_page_enable',
            'KARZA_BANK_ACCOUNT_VERIFICATION',
            'show_L1_Form_on_login',
            'auto-open-L1-form',
            'auto-open-L2-form',
            'mob_welcome_ca_card',
            'smart_collect_search_v1',
            'missed_order_pl_banner',
            'additional_domain_whitelist_self_serve',
            'remove_presignup_functionality',
            'rx_ca_self_serve_flow',
            'support_call',
            'rx_non_self_serve_ca_flow',
            'add_on_card_onboarding',
            'zoho_cashflow',
            'lite_onboarding',
            'stores',
            'ftx_2021',
            'gstin_self_serve',
            'rx_icici_auto_kyc',
            'updated_lite_onboarding',
            'show_multiple_vas_on_x',
            'rx_tally_accrual',
            'gstin_sync',
            'llpin_sync',
            'cin_sync',
            'bvs_in_sync',
            'free_credit_recovery_banner',
            'rx_skip_payroll_payouts',
            'show_activation_form_full_view',
            'magic_bulk_address_live',
            'magic_cod_orders_automation_live',
            'loans_allow_custom_amount_repayment',
            'rx_payout_link_workflow',
            'capital_xca_pay_now',
            'fee_credit_self_serve',
            'refund_credit_self_serve',
            'refund_source_fallback_enabled',
            'reserve_bal_self_serve',
            'adharEkyc_for_reg_businessTypes',
            'rx_search_enhancement_phase1',
            'rx_slack_integration_gtm',
            'rx_accounting_gtm',
            'rx_payout_link_workflow_ga',
            'pb_direct_plugin_links',
            'aadharEkyc_for_trust_society_ngo',
            'rx_tds_in_payouts_rollout',
            'capital_enable_physical_card',
            'rx_custom_access_control_enabled',
            'rx_self_serve_workflow',
            'rx_self_serve_workflow_for_icici_2fa',
            'easy_onboarding',
            'capital_virtual_card',
            'capital_addon_cards_status_tracker',
            'set_pref_corporate_cards',
            'capital_addon_cards_statement_export',
            'optimizer_onboarding',
            'capital_cli_banner',
            'rx_receivables',
            'capital_cards_unbilled_transactions',
            'capital_cards_statement',
            'capital_founders_card',
            'capital_business_rewards',
            'capital_last_day_repayment',
            'pp_magic_setting',
            'capital_cards_nach_payment',
            'rx_vendor_balances_rollout',
            '1cc_shopify_magic_enable',
            '1cc_wooc_magic_enable',
            'enable_workbox',
            'pp_onboarding_redirection_exp',
            'rx_todo_v1',
            'optimizer_emandate',
            'rx_bill_payments',
            'batch_service_recurring_charge_bulk',
            'rx_finance_x',
            'rx_auto_tds_accrual',
            'rx_mtp_downtime_enabled',
            'rx_accounting_onboarding_banner',
            'hide_PI_details',
            'rx_custom_access_control_disabled',
            'rx_vp_reports',
            'rx_ba_sync_survey',
            'sync_call_for_fresh_balance',
            'issuinghq_wallet_dashboard_enabled',
            'sync_call_for_fresh_balance',
            'dedicated_terminal_qr_code',
            'issuinghq_wallet_bulk_actions_enabled',
            'issuinghq_wallet_fundstab_enabled'
        ];

        $experimentsResults = $this->getBulkTreatment($features);

        foreach ($experimentsResults as $result => $val)
        {
            $data['experiments'][$result] = $val;
        }

        return $data;
    }

    public function getBulkTreatment(array $features)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_RAZORX_EXPERIMENTS_BULK_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        $featureString = implode(', ', $features);

        list($error, $data) = $request->send("razorx/bulkevaluate?features=$featureString", 'GET');

        if (empty($error) === false)
        {
            $data = [];

            $this->trace->info(TraceCode::BULK_RAZORX_CALL_FAILED, [
                "error" => $error
            ]);

            foreach ($features as $feature)
            {
                $data[$feature] = ['result' => 'control'];
            }
        }

        $endTime  = microtime(true) * 1000;
        $duration = $endTime - $startTime;

        $this->trace->info(TraceCode::GET_RAZORX_EXPERIMENTS_BULK_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']

        ]);

        return $data;
    }
}

