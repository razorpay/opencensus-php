<?php


namespace RZP\Http;


class RouteTeamMap
{

    /**
     * Follows the notation <BU>_<TeamName>
     */
    const TEAM_PAYMENTS_DASHBOARD = 'payments_dashboard';
    const TEAM_PAYMENTS_CARE      = 'payments_care';
    const TEAM_PAYMENTS_GROWTH    = 'payments_growth';
    const TEAM_PAYMENTS_RISK      = 'payments_risk';
    const TEAM_UNKNOWN            = 'unknown_unknown';

    /**
     * @return string comma separated list of team names for the particular route
     * used primarily for identifying a the team/pod which owns a particular API route.
     */
    public static function getTeamNamesForRoute($route): string
    {
        if (array_key_exists($route, self::$routeTeamMap) === false)
        {
            return self::TEAM_UNKNOWN;
        }

        return implode(',', self::$routeTeamMap[$route]);
    }

    protected static $routeTeamMap = [
        'workflow_action_get_multiple'              => [self::TEAM_PAYMENTS_CARE],
        'merchant_submit_support_call_request'      => [self::TEAM_PAYMENTS_CARE],
        'fd_reserve_balance_ticket'                 => [self::TEAM_PAYMENTS_CARE],
        'action_request_execute'                    => [self::TEAM_PAYMENTS_CARE],
        'fd_fetch_ticket'                           => [self::TEAM_PAYMENTS_CARE],
        'fd_fetch_tickets'                          => [self::TEAM_PAYMENTS_CARE],
        'role_create'                               => [self::TEAM_PAYMENTS_CARE],
        'permission_get_multiple'                   => [self::TEAM_PAYMENTS_CARE],
        'fd_create_ticket'                          => [self::TEAM_PAYMENTS_CARE],
        'workflow_get'                              => [self::TEAM_PAYMENTS_CARE],
        'freshchat_get_chat_timings_config_proxy'   => [self::TEAM_PAYMENTS_CARE],
        'freshdesk_otp_send'                        => [self::TEAM_PAYMENTS_CARE],
        'permission_get_by_type'                    => [self::TEAM_PAYMENTS_CARE],
        'role_get_multiple'                         => [self::TEAM_PAYMENTS_CARE],
        'role_edit'                                 => [self::TEAM_PAYMENTS_CARE],
        'workflow_create'                           => [self::TEAM_PAYMENTS_CARE],
        'care_service_dashboard_proxy'              => [self::TEAM_PAYMENTS_CARE],
        'workflow_observer_data_update'             => [self::TEAM_PAYMENTS_CARE],
        'freshdesk_create_ticket'                   => [self::TEAM_PAYMENTS_CARE],
        'freshdesk_raise_grievance'                 => [self::TEAM_PAYMENTS_CARE],
        'fd_fetch_converations'                     => [self::TEAM_PAYMENTS_CARE],
        'freshdesk_fetch_tickets'                   => [self::TEAM_PAYMENTS_CARE],
        'freshchat_extract_report_cron'             => [self::TEAM_PAYMENTS_CARE],
        'get_faqs'                                  => [self::TEAM_PAYMENTS_CARE],
        'permission_get_roles'                      => [self::TEAM_PAYMENTS_CARE],
        'fd_reserve_balance_ticket_status'          => [self::TEAM_PAYMENTS_CARE],
        'action_diff_get'                           => [self::TEAM_PAYMENTS_CARE],
        'freshchat_retrieve_report_cron'            => [self::TEAM_PAYMENTS_CARE],
        'get_merchant_support_option_flags'         => [self::TEAM_PAYMENTS_CARE],
        'workflow_get_multiple'                     => [self::TEAM_PAYMENTS_CARE],
        'can_merchant_submit_support_call_request'  => [self::TEAM_PAYMENTS_CARE],
        'fd_post_ticket_grievance'                  => [self::TEAM_PAYMENTS_CARE],
        'fd_post_ticket_reply'                      => [self::TEAM_PAYMENTS_CARE],
        'action_comment_create'                     => [self::TEAM_PAYMENTS_CARE],
        'workflow_action_update'                    => [self::TEAM_PAYMENTS_CARE],
        'workflow_action_close'                     => [self::TEAM_PAYMENTS_CARE],
        'workflow_action_details'                   => [self::TEAM_PAYMENTS_CARE],
        'fd_consume_webhook'                        => [self::TEAM_PAYMENTS_CARE],
        'workflow_observer_data_fetch'              => [self::TEAM_PAYMENTS_CARE],
        'action_checker_create'                     => [self::TEAM_PAYMENTS_CARE],
        'role_get'                                  => [self::TEAM_PAYMENTS_CARE],
        'role_add_permissions'                      => [self::TEAM_PAYMENTS_CARE],
        'role_delete'                               => [self::TEAM_PAYMENTS_CARE],
        'permission_create'                         => [self::TEAM_PAYMENTS_CARE],
        'permission_get'                            => [self::TEAM_PAYMENTS_CARE],
        'permission_delete'                         => [self::TEAM_PAYMENTS_CARE],
        'permission_edit'                           => [self::TEAM_PAYMENTS_CARE],
        'freshchat_get_chat_timings_config'         => [self::TEAM_PAYMENTS_CARE],
        'freshchat_get_chat_holidays_config'        => [self::TEAM_PAYMENTS_CARE],
        'freshchat_put_chat_timings_config'         => [self::TEAM_PAYMENTS_CARE],
        'workflow_update'                           => [self::TEAM_PAYMENTS_CARE],
        'invitation_create'                         => [self::TEAM_PAYMENTS_DASHBOARD],
        'invitation_delete'                         => [self::TEAM_PAYMENTS_DASHBOARD],
        'invitation_edit'                           => [self::TEAM_PAYMENTS_DASHBOARD],
        'invitation_action'                         => [self::TEAM_PAYMENTS_DASHBOARD],
        'invitation_fetch'                          => [self::TEAM_PAYMENTS_DASHBOARD],
        'invitation_resend'                         => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_2fa_change_setting'               => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_bank_account_change_status'       => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_bank_account_update'              => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_bank_account_create'              => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_billing_label_suggestions'        => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_billing_label_update'             => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_edit_config'                      => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_features_fetch'                   => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_fetch_config'                     => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_fetch_users'                      => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_gstin_self_serve_status'          => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_gstin_self_serve_update'          => [self::TEAM_PAYMENTS_DASHBOARD],
        'proxy_merchant_get_support_details'        => [self::TEAM_PAYMENTS_DASHBOARD],
        'proxy_merchant_create_support_details'     => [self::TEAM_PAYMENTS_DASHBOARD],
        'proxy_merchant_edit_support_details'       => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_change_password'                      => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_reset_password_token'                 => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_trigger_2fa_otp'                      => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_update_contact'                       => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_update_contact_merchant'              => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_verify_contact'                       => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_otp_verify'                           => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_verify_second_factor_auth'            => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_fetch_admin'                          => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_gst_edit'                         => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_gst_fetch'                        => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_resend_otp_2fa'                       => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_login_2fa_setup_mobile'               => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_login'                                => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_login_2fa_setup_verify_mobile'        => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_oauth_login'                          => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_oauth_register'                       => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_reset_password_create'                => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_2fa_change_setting'                   => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_account_unlock'                       => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_merchant_mapping_action'              => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_update_contact_admin'                 => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_account_lock_unlock_admin'            => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_fetch_for_merchant'                   => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_edit'                             => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_opt_in_whatsapp'                      => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_opt_in_status_whatsapp'               => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_opt_out_whatsapp'                     => [self::TEAM_PAYMENTS_DASHBOARD],
        'user_details'                              => [self::TEAM_PAYMENTS_DASHBOARD],
        'merchant_activation_details'               => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_save'                  => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_upload_file'           => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_update_website'        => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_update_website_status' => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_business_categories'   => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_business_details'      => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_company_search'        => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_needs_clarification'   => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_files'                 => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_upload_file_admin'     => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_update'                => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_status'                => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_activation_status_change_log'     => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_get_rejection_reasons'            => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_tnc_details'                      => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_tnc_save'                         => [self::TEAM_PAYMENTS_GROWTH],
        'merchants_risk_service'                    => [self::TEAM_PAYMENTS_GROWTH],
        'merchants_risk_admin'                      => [self::TEAM_PAYMENTS_GROWTH],
        'bvs_service_dashboard'                     => [self::TEAM_PAYMENTS_GROWTH],
        'bvs_service_admin'                         => [self::TEAM_PAYMENTS_GROWTH],
        'bvs_validation_artifact_details'           => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_analytics'                        => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_document_delete'                  => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_document_upload'                  => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_document_fetch'                   => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_document_admin_fetch'             => [self::TEAM_PAYMENTS_GROWTH],
        'add_additional_website'                    => [self::TEAM_PAYMENTS_GROWTH],
        'delete_additional_websites'                => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_autokyc_soft_limit'               => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_autokyc_hard_limit'               => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_autokyc_escalation'               => [self::TEAM_PAYMENTS_GROWTH],
        'merchant_onboarding_escalations'           => [self::TEAM_PAYMENTS_GROWTH],
        'fetch_merchant_escalation'                 => [self::TEAM_PAYMENTS_GROWTH],
    ];
}