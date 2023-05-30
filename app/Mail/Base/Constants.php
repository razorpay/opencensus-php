<?php

namespace RZP\Mail\Base;

use RZP\Models\Merchant\Preferences;
use RZP\Models\Merchant\Constants as MERCHANT_CONSTANTS;

class Constants
{
    const SUPPORT                       = 'support';
    const X_SUPPORT                     = 'x_support';
    const SCORECARD                     = 'scorecard';
    const BANKING_SCORECARD             = 'banking_scorecard';
    const REFUNDS                       = 'refunds';
    const SETTLEMENTS                   = 'settlements';
    const INVOICES                      = 'invoices';
    const NOTIFICATIONS                 = 'notifications';
    const REPORTS                       = 'reports';
    const CARE                          = 'care';
    const ERRORS                        = 'errors';
    const DEVELOPERS                    = 'developers';
    const AFFORDABILITY                 = 'affordability';
    const FINOPS                        = 'finops';
    const DEVOPS_BEAM                   = 'devops_beam';
    const ALERTS                        = 'alerts';
    const EMI                           = 'emi';
    const CAPTURE                       = 'capture';
    const ADMIN                         = 'admin';
    const ACTIVATION                    = 'activation';
    const SUBSCRIPTIONS                 = 'subscriptions';
    const SUBSCRIPTIONS_APPS            = 'subscriptions_apps';
    const IRCTC                         = 'irctc';
    const EMANDATE                      = 'emandate';
    const DISPUTES                      = 'disputes';
    const NOREPLY                       = 'noreply';
    const RECON                         = 'recon';
    const MERCHANT_ONBOARDING           = 'merchant_onboarding';
    const GATEWAY_POD                   = 'gateway_pod';
    const SETTLEMENT_ALERTS             = 'settlement_alert';
    const BEAM_FAILURE                  = 'beam_failure';
    const CREDITS_ALERTS                = 'credit_alerts';
    const FRESHDESK                     = 'freshdesk';
    const PARTNERSHIPS                  = 'partnerships';
    const APPROVALS_OAUTH               = 'approvals_oauth';
    const BANK_DISPUTE_FILE             = 'dispute_file_upload';
    const LINKED_ACCOUNT_REVERSAL       = 'linked_account_reversal';
    const RAZORPAY_X                    = 'razorpay_x';
    const CAPITAL_SUPPORT               = 'capital_support';
    const CAPITAL_OPS                   = 'capital_ops';
    const CAPITAL_CREDIT                = 'capital_credit';
    const BANKING_ACCOUNT               = 'banking_account';
    const NACH                          = 'nach';
    const PARTNER_ON_BOARDING           = 'partner_on_boarding';
    const RAZORPAY_HELP_DESK            = 'help_desk';
    const TECH_SETTLEMENTS              = 'tech_settlements';
    const FINANCE                       = 'finance';
    const PARTNER_NOTIFICATIONS         = 'partner_notifications';
    const PARTNER_PAYMENTS              = 'partner_payments';
    const PARTNER_OPS                   = 'partner_ops';
    const DOWNTIME_NOTIFICATION_CARD          = 'downtime_notification_card';
    const DOWNTIME_NOTIFICATION_UPI           = 'downtime_notification_upi';
    const DOWNTIME_NOTIFICATION_NETBANKING    = 'downtime_notification_netbanking';
    const DOWNTIME_NOTIFICATION_WALLET        = 'downtime_notification_wallet';
    const PARTNER_SUBMERCHANT_INVITE    = 'partner_submerchant_invite';
    const PARTNER_SUBMERCHANT_INVITE_INTERNAL    = 'partner_submerchant_invite_internal';
    const PARTNER_SUBMERCHANT_REFERRAL_INVITE    = 'partner_submerchant_referral_invite';
    const NBPLUS_TECH                   = 'nbplus_tech';
    const BANKING                       = 'banking';
    const OWNER                         = 'owner';
    const SECURITY_ALERTS               = 'security_alerts';
    const BANKING_POD_TECH              = 'banking_pod_tech';
    const CROSS_BORDER_TECH             = 'cross_border_tech';
    const PARTNER_COMMISSIONS           = 'partner_commissions';
    const CROSS_BORDER                  = 'cross_border';

    const PARTNER_ON_BOARDING_REPLY = 'partner_on_boarding_reply';

    const TEST_MODE_PREFIX          = '[Test Mode] ';

    const MAIL_ADDRESSES = [
        self::SUPPORT                   => 'support@razorpay.com',
        self::X_SUPPORT                 => 'x.support@razorpay.com',
        self::SCORECARD                 => 'scorecard@razorpay.com',
        self::BANKING_SCORECARD         => 'x.scorecard@razorpay.com',
        self::REFUNDS                   => 'refunds@razorpay.com',
        self::SETTLEMENTS               => 'settlements@razorpay.com',
        self::INVOICES                  => 'invoices@razorpay.com',
        self::SUBSCRIPTIONS             => 'subscriptions@razorpay.com',
        self::SUBSCRIPTIONS_APPS        => 'payment-apps-subscriptions@razorpay.com',
        self::NOTIFICATIONS             => 'notifications@razorpay.com',
        self::REPORTS                   => 'reports@razorpay.com',
        self::CARE                      => 'care@razorpay.com',
        self::ERRORS                    => 'errors@razorpay.com',
        self::DEVELOPERS                => 'developers@razorpay.com',
        self::ALERTS                    => 'alerts@razorpay.com',
        self::EMI                       => 'emifiles@razorpay.com',
        self::CAPTURE                   => 'capturefiles@razorpay.com',
        self::ADMIN                     => 'admin@razorpay.com',
        self::ACTIVATION                => 'activationsteam@razorpay.com',
        self::IRCTC                     => 'support@razorpay.com',
        self::EMANDATE                  => 'emandate@razorpay.com',
        self::DISPUTES                  => 'disputes@razorpay.com',
        self::NOREPLY                   => 'no-reply@razorpay.com',
        self::RECON                     => 'pgrecon@razorpay.com',
        self::SETTLEMENT_ALERTS         => 'settlement.alerts@razorpay.com',
        self::CREDITS_ALERTS            => 'credit.alerts@razorpay.com',
        self::FRESHDESK                 => 'rzr05py08emsp@razorpay.com',
        self::PARTNERSHIPS              => 'partnerships@razorpay.com',
        self::APPROVALS_OAUTH           => 'approvals.oauth@razorpay.com',
        self::BANK_DISPUTE_FILE         => 'chargebacks@razorpay.com',
        self::MERCHANT_ONBOARDING       => 'support@razorpay.com',
        self::LINKED_ACCOUNT_REVERSAL   => 'refunds@razorpay.com',
        self::GATEWAY_POD               => 'pod.gateway@razorpay.com',
        self::CAPITAL_SUPPORT           => 'capital.support@razorpay.com',
        self::CAPITAL_OPS               => 'capital-operations@razorpay.com',
        self::CAPITAL_CREDIT            => 'capital-credit@razorpay.com',
        self::NACH                      => 'nach@razorpay.com',
        self::PARTNER_ON_BOARDING       => 'partnercommunication@razorpay.com',
        self::PARTNER_ON_BOARDING_REPLY => 'kzgpFWFVZU@razorpay.com',
        self::RAZORPAY_HELP_DESK        => 'helpdesk@razorpay.com',
        self::TECH_SETTLEMENTS          => 'tech.settlements@razorpay.com',
        self::FINANCE                   => 'finance@razorpay.com',
        self::PARTNER_PAYMENTS          => 'partner-payments@razorpay.com',
        self::PARTNER_NOTIFICATIONS     => 'partner.notifications@razorpay.com',
        self::PARTNER_OPS               => 'partner.ops@razorpay.com',
        self::DOWNTIME_NOTIFICATION_CARD       => 'downtime-notifications-card@razorpay.com',
        self::DOWNTIME_NOTIFICATION_UPI        => 'downtime-notifications-upi@razorpay.com',
        self::DOWNTIME_NOTIFICATION_NETBANKING => 'downtime-notifications-netbanking@razorpay.com',
        self::DOWNTIME_NOTIFICATION_WALLET     => 'downtime-notifications-wallet@razorpay.com',
        self::BANKING_ACCOUNT           => 'x.support@razorpay.com',
        self::PARTNER_SUBMERCHANT_INVITE => 'partnercommunication@razorpay.com',
        self::PARTNER_SUBMERCHANT_REFERRAL_INVITE => 'partnercommunication@razorpay.com',
        self::PARTNER_SUBMERCHANT_INVITE_INTERNAL => ['tarun.rajaputhran@razorpay.com', 'arun.rajendran@razorpay.com', 'satyajit.paul@razorpay.com'],
        self::NBPLUS_TECH               => 'tech.onlinepayments.nbplus@razorpay.com',
        self::SECURITY_ALERTS           => 'security-alerts@razorpay.com',
        self::BANKING_POD_TECH          => 'payments-banking-tech@razorpay.com',
        self::CROSS_BORDER_TECH         => 'payments-cross-border-engineering@razorpay.com',
        self::PARTNER_COMMISSIONS       => 'partners-commissions@razorpay.com',
        self::AFFORDABILITY             => 'tech-onlinepayments-affordability@razorpay.com',
        self::FINOPS                    => 'finances.recon@razorpay.com',
        self::DEVOPS_BEAM               => 'devops+beam@razorpay.com',
        self::CROSS_BORDER              => 'cross-border@razorpay.com',
    ];

    const CURLEC_MAIL_ADDRESSES = [
        self::NOREPLY                   => 'no-reply@curlec.com',
        self::SUPPORT                   => 'no-reply@curlec.com',
    ];

    const DEFAULT_MAIL_ADDRESSES = self::MAIL_ADDRESSES;

    // this map stores the mail addresses used by org, this is required only if a org uses custom
    // mail addresses, by deafult rzp mail org addresses are used
    const ORG_MAIL_ADDRESSES_MAP = [
        'rzp'           => self::MAIL_ADDRESSES,
        'curlec'        => self::CURLEC_MAIL_ADDRESSES,
    ];

    const MERCHANT_CUSTOM_MAIL_ADDRESSES = [
        Preferences::MID_BOB_FIN => 'noreply@bobfinancial.com',
    ];

    const HEADERS = [
        self::SUPPORT                 => 'Team Razorpay',
        self::X_SUPPORT               => 'Team RazorpayX',
        self::SCORECARD               => 'Razorpay Scorecard',
        self::BANKING_SCORECARD       => 'Razorpay Banking Scorecard',
        self::REFUNDS                 => 'Refunds File',
        self::SETTLEMENTS             => 'Settlements File',
        self::INVOICES                => 'Razorpay Invoices',
        self::REPORTS                 => 'Team Razorpay',
        self::CARE                    => 'Team Razorpay',
        self::NOREPLY                 => 'Team Razorpay',
        self::ALERTS                  => 'Razorpay Webhook Support',
        self::ACTIVATION              => 'Razorpay Activations Team',
        self::IRCTC                   => 'Razorpay IRCTC Files',
        self::EMANDATE                => 'Razorpay EMandate',
        self::DISPUTES                => 'Razorpay Risk Team',
        self::RECON                   => 'Reconciliation Summary',
        self::BEAM_FAILURE            => 'Beam Request Failure',
        self::FRESHDESK               => 'Team Razorpay',
        self::PARTNERSHIPS            => 'Partnerships',
        self::APPROVALS_OAUTH         => 'Approvals OAuth',
        self::BANK_DISPUTE_FILE       => 'Bank Dispute File',
        self::MERCHANT_ONBOARDING     => 'support@razorpay.com',
        self::LINKED_ACCOUNT_REVERSAL => 'Linked Account Refunds File',
        self::RAZORPAY_X              => 'RazorpayX',
        self::PARTNER_ON_BOARDING     => 'Razorpay Partner Program',
        self::CAPITAL_SUPPORT         => 'Razorpay Capital',
        self::CAPITAL_OPS             => 'Capital Ops Team',
        self::CAPITAL_CREDIT          => 'Capital Credit',
        self::NACH                    => 'Razorpay Nach',
        self::PARTNER_NOTIFICATIONS   => 'Partner Notifications',
        self::PARTNER_PAYMENTS        => 'Partner Payments',
        self::PARTNER_OPS             => 'Partner Ops',
        self::RAZORPAY_HELP_DESK      => 'Team Razorpay',
        self::BANKING_ACCOUNT         => 'Team RazorpayX',
        self::PARTNER_SUBMERCHANT_INVITE => 'Razorpay Partner Program',
        self::SECURITY_ALERTS         => 'Team Razorpay',
        self::PARTNER_COMMISSIONS     => 'Razorpay Partnerships',
    ];

    const CURLEC_HEADERS = [
        self::NOREPLY                 => 'Team Curlec',
        self::SUPPORT                 => 'Team Curlec',
    ];

    const SUBJECT_GLOBAL = [
        'MY' => [
            self::SETTLEMENTS => 'Curlec Settlement Notification'
        ],
        'IN' => [
            self::SETTLEMENTS => 'Razorpay Settlement Notification'
        ]
    ];

    const HEADERS_GLOBAL = [
      'MY' => [
          self::PARTNER_COMMISSIONS => 'Curlec Partnerships',
          self::PARTNER_ON_BOARDING => 'Curlec Partner Program',
          self::REPORTS => 'Team Curlec',
      ],
      'IN' => [
          self::PARTNER_COMMISSIONS => 'Razorpay Partnerships',
          self::PARTNER_ON_BOARDING => 'Razorpay Partner Program',
          self::REPORTS => 'Team Razorpay',
      ]
    ];

    const MAIL_ADDRESSES_GLOBAL = [
        'MY' => [
            self::PARTNER_COMMISSIONS => 'success@curlec.com',
            self::PARTNER_ON_BOARDING => 'success@curlec.com',
            self::PARTNER_ON_BOARDING_REPLY => 'success@curlec.com',
            self::REPORTS => 'success@curlec.com',
            self::NOREPLY => 'no-reply@curlec.com',
        ],
        'IN' => [
            self::PARTNER_COMMISSIONS => 'partners-commissions@razorpay.com',
            self::PARTNER_ON_BOARDING => 'partnercommunication@razorpay.com',
            self::PARTNER_ON_BOARDING_REPLY => 'kzgpFWFVZU@razorpay.com',
            self::REPORTS => 'reports@razorpay.com',
            self::NOREPLY => 'no-reply@razorpay.com',
        ]
    ];

    const PARTNER_ONBOARDER_EMAIL_TEMPLATE_MAP = [
        'MY' => [
            MERCHANT_CONSTANTS::AGGREGATOR => 'emails.mjml.merchant.partner.onboarded.my_aggregator',
            MERCHANT_CONSTANTS::PURE_PLATFORM => 'emails.mjml.merchant.partner.onboarded.my_pure_platform',
            MERCHANT_CONSTANTS::RESELLER => 'emails.mjml.merchant.partner.onboarded.my_reseller'
        ],
        'IN' => [
            MERCHANT_CONSTANTS::AGGREGATOR => 'emails.mjml.merchant.partner.onboarded.aggregator',
            MERCHANT_CONSTANTS::PURE_PLATFORM => 'emails.mjml.merchant.partner.onboarded.pure_platform',
            MERCHANT_CONSTANTS::RESELLER => 'emails.mjml.merchant.partner.onboarded.reseller'
        ]
    ];

    const PARTNER_ONBOARDED_SUBJECT_MAP = [
        'MY' => [
            MERCHANT_CONSTANTS::PURE_PLATFORM => 'You’re just a step away from becoming a Curlec Partner',
            MERCHANT_CONSTANTS::RESELLER => 'Welcome to Curlec Partner Program',
            MERCHANT_CONSTANTS::AGGREGATOR => 'Welcome to Curlec Partner Program',
        ],
        'IN' => [
            MERCHANT_CONSTANTS::PURE_PLATFORM => 'You’re just a step away from becoming a Razorpay Partner',
            MERCHANT_CONSTANTS::RESELLER => 'Welcome to Razorpay Partner Program',
            MERCHANT_CONSTANTS::AGGREGATOR => 'Welcome to Razorpay Partner Program',
        ]
    ];

    const DEFAULT_HEADERS = self::HEADERS;

    // this map stores the headers for the mail addresses used by org,
    // this is required only if a org uses custom branding
    // by deafult rzp mail org mail addresses and headers are used
    const ORG_HEADERS_MAP = [
        'rzp'       => self::DEFAULT_HEADERS,
        'curlec'    => self::CURLEC_HEADERS,
    ];

    public static function getSenderEmailForOrg(string $orgCode, string $type) : string
    {
        $mailAddresses = self::ORG_MAIL_ADDRESSES_MAP[$orgCode] ?? self::DEFAULT_MAIL_ADDRESSES;

        return $mailAddresses[$type];
    }

    public static function getSenderNameForOrg(string $orgCode, string $type) : string
    {
        $headers = self::ORG_HEADERS_MAP[$orgCode] ?? self::DEFAULT_HEADERS;

        return $headers[$type];
    }
}
