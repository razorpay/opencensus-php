<?php

namespace RZP\Models\Payment\Fraud\Constants;

use RZP\Error\ErrorCode;

class Notification
{
    const EMAIL              = 'email';
    const SMS                = 'sms';
    const WHATSAPP           = 'whatsapp';
    const FRESHDESK_TICKET   = 'freshdesk_ticket';
    const SLACK              = 'slack';
    const HANDLER            = 'handler';
    const PROVIDER           = 'provider';
    const TEMPLATE           = 'template';
    const TEMPLATE_NAME      = 'template_name';
    const SETTINGS           = 'settings';
    const NOTIFY_INTERVAL    = 'notify_interval';

    // Email providers
    const FRESHDESK         = 'freshdesk';
    const MAILGUN           = 'mailgun';

    const INSTANT_NOTIFY     = 0;

    // Format: fraud_notification_<fraud_type>_<channel>_merchant_<mid>
    // Ex: fraud_notification_domain_mismatch_email_merchant_1234
    const FRAUD_NOTIFICATION_REDIS_KEY = 'fraud_notification_%s_%s_merchant_%s';

    const DOMAIN_MISMATCH               = 'domain_mismatch';
    const DOMAIN_MISMATCH_MOBILE_SIGNUP = 'domain_mismatch_mobile_signup';

    const FRAUD_NOTIFICATIONS = [
        self::DOMAIN_MISMATCH => [
            self::HANDLER   => \RZP\Models\Payment\Fraud\Notifications\DomainMismatch::class,
            self::SETTINGS  => [
                self::EMAIL => [
                    // ttl = 1 day = 24*60*60 = 86400 seconds
                    self::NOTIFY_INTERVAL => 86400,
                    self::HANDLER         => \RZP\Mail\Payment\Fraud\DomainMismatch::class,
                    self::PROVIDER        => self::FRESHDESK,
                ],
                self::SMS => [
                    // ttl = 7 day = 7*24*60*60 = 604800 seconds
                    self::NOTIFY_INTERVAL => 604800,
                    self::TEMPLATE        => 'sms.risk.url_mismatch_email_signup',
                ],
                self::WHATSAPP => [
                    // ttl = 7 day = 7*24*60*60 = 604800 seconds
                    self::NOTIFY_INTERVAL   => 604800,
                    self::TEMPLATE_NAME     => 'whatsapp_risk_url_mismatch_email_signup',
                    self::TEMPLATE          => 'Hi {merchantName}, payment attempted on your merchant ID - {merchant_id} from web domain - {referer_domain} has failed as it is not registered. Please contact risk-notification@razorpay.com for more details.',
                ],
            ],
        ],
        self::DOMAIN_MISMATCH_MOBILE_SIGNUP => [
            self::HANDLER   => \RZP\Models\Payment\Fraud\Notifications\DomainMismatchMobileSignup::class,
            self::SETTINGS  => [
                self::FRESHDESK_TICKET => [
                    // ttl = 1 day = 24*60*60 = 86400 seconds
                    self::NOTIFY_INTERVAL => 86400,
                ],
                self::SMS => [
                    // ttl = 7 day = 7*24*60*60 = 604800 seconds
                    self::NOTIFY_INTERVAL => 604800,
                    self::TEMPLATE        => 'sms.risk.url_mismatch_mobile_signup_v1',
                ],
                self::WHATSAPP => [
                    // ttl = 7 day = 7*24*60*60 = 604800 seconds
                    self::NOTIFY_INTERVAL   => 604800,
                    self::TEMPLATE_NAME     => 'whatsapp_risk_url_mismatch_mobile_signup',
                    self::TEMPLATE          => 'Hi {merchantName}, payment attempted on your merchant ID - {merchant_id} from web domain - {referrer_domain} has failed as it is not registered. Please check link {supportTicketsUrl} and help us with the required clarification.'
                ],
            ],
        ]
    ];

    const ERR_CODE_FRAUD_TYPE_MAP = [
        ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH => self::DOMAIN_MISMATCH,
    ];

    public static function getFraudTypeByErrorCode(string $errCode): ?string
    {
        if (isset(self::ERR_CODE_FRAUD_TYPE_MAP[$errCode]) === false)
        {
            return null;
        }

        return self::ERR_CODE_FRAUD_TYPE_MAP[$errCode];
    }

    public static function getSettingsForFraudType(string $fraudType): ?array
    {
        if (isset(self::FRAUD_NOTIFICATIONS[$fraudType]) === false)
        {
            return null;
        }

        return self::FRAUD_NOTIFICATIONS[$fraudType][self::SETTINGS];
    }

    public static function getHandlerForFraudType(string $fraudType): ?string
    {
        if (isset(self::FRAUD_NOTIFICATIONS[$fraudType]) === false)
        {
            return null;
        }

        return self::FRAUD_NOTIFICATIONS[$fraudType][self::HANDLER];
    }
}
