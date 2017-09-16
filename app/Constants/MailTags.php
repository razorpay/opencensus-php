<?php

namespace RZP\Constants;

class MailTags
{
    const HEADER                           = 'X-Mailgun-Tag';

    /**
     * Defines tags associated with emails, stored in the X-Mailgun-Tag header
     */

    const KOTAK_BENEFICIARY_MAIL           = 'kotak_beneficiary_mail';
    const KOTAK_SETTLEMENT_FILES           = 'kotak_settlement_files';
    const KOTAK_PAYOUT_SUMMARY             = 'kotak_payout_summary';

    const ICICI_SETTLEMENT_FILES           = 'icici_settlement_files';
    const AXIS_SETTLEMENT_FILES            = 'axis_settlement_files';

    const SETTLEMENT_FAILURE_EMAIL         = 'settlement_failure_email';

    const HDFC_NETBANKING_REFUNDS_MAIL     = 'hdfc_netbanking_refunds_mail';
    const AXIS_NETBANKING_REFUNDS_MAIL     = 'axis_netbanking_refunds_mail';
    const AIRTEL_MONEY_REFUNDS_MAIL        = 'airtel_money_refunds_mail';
    const ICICI_NETBANKING_REFUNDS_MAIL    = 'icici_netbanking_refunds_mail';
    const FEDERAL_NETBANKING_REFUNDS_MAIL  = 'axis_netbanking_refunds_mail';
    const KOTAK_NETBANKING_REFUNDS_MAIL    = 'kotak_netbanking_refunds_mail';
    const RBL_NETBANKING_REFUNDS_MAIL      = 'rbl_netbanking_refunds_mail';
    const INDUSIND_NETBANKING_REFUNDS_MAIL = 'indusind_netbanking_refunds_mail';

    const PAYU_MONEY_REFUNDS_MAIL          = 'payu_money_refunds_mail';
    const ICICI_UPI_REFUNDS_MAIL           = 'icici_upi_refunds_mail';
    const BATCH_REFUNDS_FILE               = 'batch_refunds_file';
    const BATCH_PAYMENT_LINK_FILE          = 'batch_payment_link_file';

    const PAYMENT_SUCCESSFUL               = 'payment_successful';
    const REFUND_SUCCESSFUL                = 'refund_successful';
    const PAYMENT_FAILED                   = 'payment_failed';
    const FAILED_TO_AUTHORIZED             = 'failed_to_authorized';
    const CARD_SAVING                      = 'card_saving';

    const INVOICE                          = 'invoice';
    const ECOD                             = 'ecod';

    const SUBSCRIPTION_AUTHENTICATED       = 'subscription_authenticated';
    const SUBSCRIPTION_CHARGED             = 'subscription_charged';
    const SUBSCRIPTION_PENDING             = 'subscription_pending';
    const SUBSCRIPTION_HALTED              = 'subscription_halted';
    const SUBSCRIPTION_CANCELLED           = 'subscription_cancelled';
    const SUBSCRIPTION_CARD_CHANGED        = 'subscription_card_changed';
    const SUBSCRIPTION_COMPLETED           = 'subscription_completed';

    const DAILY_FILE                       = 'daily_file';
    const DAILY_REPORT                     = 'daily_report';
    const AUTH_REMINDER                    = 'auth_reminder';
    const HOLIDAY_NOTIFICATION             = 'holiday_notification';
    const WEBHOOK                          = 'webhook';

    const EMI_FILE                         = 'emi_file';

    const SCORECARD                        = 'scorecard';
    const CRITICAL_ERROR                   = 'critical_error';

    const ACCOUNT_CHANGED                  = 'account_changed';
    const FORGOT_PASSWORD                  = 'forgot_password';
    const ADMIN_CREATE                     = 'admin_create';
    const WELCOME                          = 'welcome';
    const ACCOUNT_ACTIVATED                = 'account_activated';

    const ICICI_FILES                      = 'icici_files';

    // Heimdall Email Tags
    const ADMIN_INVITE_MERCHANT            = 'admin_invite_merchant';

    const MERCHANT_INVITATION_MAIL         = 'merchant_invitation_mail';

    // Merchant Activation Email Tags
    const NOTIFY_ACTIVATION_SUBMISSION     = 'notify_activation_submission';
    const CONFIRM_ACTIVATION_SUBMISSION    = 'confirm_activation_submission';

    // OAuth email tags
    const OAUTH_APP_AUTHORIZED             = 'oauth_app_authorized';

    /**
     * Email tags that should respond to the mailgun failure webhook
     * @var array Email tags
     * @static
     */
    public static $setlNotifyTags = [
        self::KOTAK_BENEFICIARY_MAIL,
        self::HDFC_NETBANKING_REFUNDS_MAIL,
        self::AXIS_NETBANKING_REFUNDS_MAIL,
        self::ICICI_NETBANKING_REFUNDS_MAIL,
        self::RBL_NETBANKING_REFUNDS_MAIL,
        self::INDUSIND_NETBANKING_REFUNDS_MAIL,
    ];
}
