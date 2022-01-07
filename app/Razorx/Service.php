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
            'paymentpages_mli',
            'show_commission_balance',
            'custom_notes',
            'capture_settings_revamp',
            'sellerapp_PL_batch_upload',
            'nps_survey_banner',
            'new_pp_success_modal',
            'hide_company_name',
            'batch_cancel',
            'rev_up_chennai_announcement',
            'disable_va_creation_bank_account',
            'registered_onboarding_auto_kyc',
            'enable_payment_page_receipt',
            'emandate_nonzero_amount',
            'card_recurring_payments_blocked',
            'allow_yesbank_va_on_x',
            'rx_creation_flows_v2',
            'instant_refunds_default_pricing_v2',
            'covid_19_donation_show',
            'fee_bearer_self_serve',
            'ir_pricing_v2_rollout_1',
            'ir_pricing_v2_rollout_2',
            'ir_pricing_v2_rollout_3',
            'ir_pricing_v2_rollout_4',
            'view_fd_tickets',
            'support_dashboard_rzpsolutions',
            'capital_freshdesk_integration',
            'show_new_grievance_flow',
            'show_schedule_callback',
            'ticket_creation_flow_revamp',
            'ticket_creation_flow_revamp_dashboard',
            'razorpay_chat_bot',
            'mobile_signup_care_changes_active',
            'hide_call_slots_10_12_2',
            'website_self_serve',
            'settlement_ux_revamp_p2',
            'rx_scheduled_payouts_rollout',
            'validate_user_2fa_status',
            'enable_payment_buttons',
            'pause_resume_enabled',
            'batch_scheduling_options',
            'emandate_subscription',
            'upi_caw',
            'rx_payout_links_inactive',
            'rx_payout_links_ms',
            'upi_subscription',
            'rx_bulk_approvals',
            'offer_on_subscription',
            'subscription_offers_reports',
            'rx_payout_links_onboarding_revamp',
            'rx_tax_payments_payout',
            'rx_payout_links_new_information_flow',
            'capital_loans_announcement_aug2020',
            'schedule_callback_category',
            'instrument_request_merchant_dashboard',
            'settlement_ux_revamp_p2',
            'rx_view_only_update',
            'rx_accounting_payouts_active',
            'rx_accounting_payouts_ask_clientID',
            'enable_pl_batch_upload',
            'skip_workflow_payout_specific_feature',
            'low_balance_alert_frequency_30M',
            'rx_home_v2_existing',
            'bank_account_update_merchant_dashboard',
            'app_switcher',
            'subscription_expiry',
            'qr_code_coming_soon',
            'rbl_migration_banner',
            'qr_code',
            'caw_tpv',
            'show_rx_vp_announcement',
            'whatsapp_notification_enablement',
            'rx_vp_inline_recommendation',
            'partner_app_store',
            'show_rx_vp_announcement_2',
            'bulk_payouts_improvements_rollout',
            'block_bank_account_update_merchant_dashboard',
            'rx_opfin_announcement_v2',
            'whats-new-dec-2020',
            'aov_functionality',
            'rx_opfin_sso_announcement',
            'AnnouncementIconJan2021',
            'TicketSystemSupport',
            'rx_tax_payments_announcement',
            'disable_tpv_flow_for_banking_account_fund_loading',
            'rx_opfin_sso_announcement_xdashboard',
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
            'rx_disable_taxpayment_payoutflow',
            'bvs_personal_pan_validation',
            'onboarding_v2',
            'capture_settings_revamp',
            'pl_description_required',
            'pp_description_required',
            'merchant_tnc',
            'rzp_merchant_tnc',
            'support_details_2FA',
            'comdel_hdfc_test',
            'rx_icici_ca_onboarding',
            'show_csat_survey',
            'rx_mobile_app_announcement',
            'rx_tally_payouts_enabled',
            'self_serve_credits',
            'rx_shopify_pl',
            'enable_may_dashboard_notification_retention_1',
            'enable_may_dashboard_notification_retention_2',
            'enable_may_dashboard_notification_retention_3',
            'enable_may_dashboard_notification_retention_4',
            'enable_my_dashboard_notification_remarketing',
            'enable_my_dashboard_notification_remarketing_1',
            'instant-activations-functionality',
            'mandatory_aadhar_ekyc',
            'rx_email_integration_rollout',
            'rx_vendor_portal_rollout',
            'rx_taxpayments_tin_change',
            'route_batch_upload',
            'email_self_serve',
            'payments_extra_refund_details',
            'mandatory_gstin_input',
            'sync_experiment',
            'recurring_more_account_type',
            'optimizer_add_provider',
            'mtu_coupon_code',
            'auto_open_mtu_coupon',
            'dispute_presentment',
            'product_recommendation',
            'auto_refresh_experiment',
            'csm_experince_survey',
            'inv_create_flow_ux',
            'loans_collections_dashboard',
            'rx_ca_portal',
            'pp_success_page',
            'bvs_get_gst_details',
            'status_page_enable',
            'pp_hostedpage_new_footer',
            'rx_gst_payments',
            'KARZA_BANK_ACCOUNT_VERIFICATION',
            'show_L1_Form_on_login',
            'auto-open-L1-form',
            'auto-open-L2-form',
            'mob_welcome_ca_card',
            'smart_collect_search_v1',
            'switch_onboarding_card',
            'additional_domain_whitelist_self_serve',
            'auto_pl',
            'l2_allowed',
            'remove_presignup_functionality',
            'transaction_limit_update_self_serve',
            'rx_ca_self_serve_flow',
            'rx_home_v2',
            'support_call',
            'rx_ca_self_serve_flow_neo',
            'rx_non_self_serve_ca_flow',
            'add_on_card_onboarding',
            'zoho_cashflow',
            'dashboard_super_checkout',
            'lite_onboarding',
            'stores',
            'mandatory_email_on_l1',
            'non_mandatory_email_on_l1',
            'non_mandatory_email_verification_on_l2',
            'pp_donation_goal_tracker',
            'ftx_2021',
            'gstin_self_serve',
            'gstin_self_serve_add',
            'gstin_self_serve_edit',
            'rx_icici_auto_kyc',
            'rx_icici_auto_kyc',
            'updated_lite_onboarding',
            'optimizer_currency',
            'show_multiple_vas_on_x',
            'rx_undo_payout_feature',
            'rx_command_palette',
            'rx_tally_accrual',
            'rx_cohesive_pl_flows',
            'free_credit_recovery_banner',
            'rx_mask_payroll_payouts',
            'show_activation_form_full_view',
            'magic_bulk_address_live'
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

