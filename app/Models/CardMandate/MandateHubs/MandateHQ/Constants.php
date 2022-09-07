<?php


namespace RZP\Models\CardMandate\MandateHubs\MandateHQ;


class Constants
{
    const CURRENCY                = 'currency';
    const AMOUNT                  = 'amount';
    const ID                      = 'id';

    const METHOD                  = 'method';
    const CARD                    = 'card';
    const CARD_ID                 = 'id';
    const CARD_LAST4              = 'last4';
    const CARD_NETWORK            = 'network';
    const CARD_TYPE               = 'type';
    const CARD_ISSUER             = 'issuer';
    const CARD_INTERNATIONAL      = 'international';
    const CARD_NUMBER             = 'number';
    const CARD_NAME               = 'name';
    const CARD_EXPIRY_MONTH       = 'expiry_month';
    const CARD_EXPIRY_YEAR        = 'expiry_year';
    const DEBIT_TYPE              = 'debit_type';
    const BUSINESS                = 'business';
    const MAX_AMOUNT              = 'max_amount';
    const MCC                     = 'mcc';
    const START_TIME              = 'start_time';
    const END_TIME                = 'end_time';
    const FREQUENCY               = 'frequency';
    const CALLBACK_URL            = 'callback_url';
    const REDIRECT_URL            = 'redirect_url';
    const STATUS                  = 'status';
    const TOTAL_CYCLES            = 'total_cycles';
    const INTERVAL                = 'interval';
    const PAUSED_BY               = 'paused_by';
    const CANCELLED_BY            = 'cancelled_by';
    const SKIP_SUMMARY_PAGE       = 'skip_summary_page';
    const TOKEN                   = 'token';

    const NOTES = 'notes';

    const RECURRING_DEBIT_TYPE = 'recurring_debit_type';
    const PAYMENT_STATUS       = 'status';
    const NOTIFICATION_ID      = 'notification_id';

    const AUTHENTICATION                 = 'authentication';
    const AUTHENTICATION_STATUS          = 'status';
    const AUTHENTICATION_ECI             = 'eci';
    const AUTHENTICATION_IS_3DS_ENROLLED = 'is_3ds_enrolled';
    const AUTHENTICATION_CAVV            = 'cavv';
    const AUTHENTICATION_CAVV_ALGORITHM  = 'cavv_algorithm';
    const AUTHENTICATION_XID             = 'xid';
    const AUTHENTICATION_STATUS_3DS      = 'status_3ds';

    const AUTHORIZATION               = 'authorization';
    const AUTHORIZATION_STATUS        = 'status';
    const AUTHORIZATION_AUTHORIZED_AT = 'authorized_at';
    const AUTHORIZATION_GATEWAY       = 'gateway';
    const AUTHORIZATION_AUTH_CODE     = 'auth_code';
    const AUTHORIZATION_RRN           = 'rrn';

    const FAILURE_CODE        = 'failure_code';
    const FAILURE_DESCRIPTION = 'failure_description';
    const CAPTURED_AT         = 'captured_at';

    const NOTIFICATION_TYPE                          = 'type';
    const PAYMENT_ID                                 = 'payment_id';
    const NOTIFICATION_PRE_DEBIT_DETAILS             = 'pre_debit_details';
    const NOTIFICATION_PRE_DEBIT_DETAILS_AMOUNT      = 'amount';
    const NOTIFICATION_PRE_DEBIT_DETAILS_PURPOSE     = 'purpose';
    const NOTIFICATION_PRE_DEBIT_DETAILS_DEBIT_DAY   = 'debit_day';
    const NOTIFICATION_PRE_DEBIT_DETAILS_DEBIT_MONTH = 'debit_month';

    const WEBHOOK_ENTITY                       = 'entity';
    const WEBHOOK_EVENT                        = 'event';
    const WEBHOOK_CONTAINS                     = 'contains';
    const WEBHOOK_PAYLOAD                      = 'payload';
    const WEBHOOK_CREATED_AT                   = 'created_at';
    const WEBHOOK_PAYLOAD_ENTITY               = 'entity';
    const WEBHOOK_ENTITY_NOTIFICATION          = 'mandate.notification';
    const WEBHOOK_ENTITY_MANDATE               = 'mandate';
    const WEBHOOK_EVENT_ACTIVATED              = 'mandate.activated';
    const WEBHOOK_EVENT_PAUSED                 = 'mandate.paused';
    const WEBHOOK_EVENT_RESUMED                = 'mandate.resumed';
    const WEBHOOK_EVENT_CANCELLED              = 'mandate.cancelled';
    const WEBHOOK_EVENT_COMPLETED              = 'mandate.completed';
    const WEBHOOK_EVENT_UPDATED                = 'mandate.updated';
    const WEBHOOK_EVENT_NOTIFICATION_DELIVERED = 'notification.delivered';
    const WEBHOOK_EVENT_NOTIFICATION_FAILED    = 'notification.failed';
    const WEBHOOK_EVENT_NOTIFICATION_APPROVED  = 'notification.2fa_approved';
    const WEBHOOK_EVENT_NOTIFICATION_REJECTED  = 'notification.2fa_rejected';

    const METHOD_CARD                = 'card';
    const DEBIT_TYPE_VARIABLE_AMOUNT = 'variable_amount';
    const FREQUENCY_AS_PRESENTED     = 'as_presented';
    const MAX_AMOUNT_DEFAULT         = 1500000;

    const RECURRING_DEBIT_TYPE_INITIAL    = 'initial';
    const RECURRING_DEBIT_TYPE_SUBSEQUENT = 'subsequent';

    const NOTIFICATION_TYPE_PRE_DEBIT = 'pre-debit';

    const MANDATE_HQ_REDIRECT_ROUTE_NAME        = 'payment_mandate_hq_redirect_authenticate';

    static $WebhookEvents = [
        self::WEBHOOK_EVENT_ACTIVATED,
        self::WEBHOOK_EVENT_PAUSED,
        self::WEBHOOK_EVENT_RESUMED,
        self::WEBHOOK_EVENT_CANCELLED,
        self::WEBHOOK_EVENT_COMPLETED,
        self::WEBHOOK_EVENT_UPDATED,
        self::WEBHOOK_EVENT_NOTIFICATION_DELIVERED,
        self::WEBHOOK_EVENT_NOTIFICATION_FAILED,
        self::WEBHOOK_EVENT_NOTIFICATION_APPROVED,
        self::WEBHOOK_EVENT_NOTIFICATION_REJECTED,
    ];

    static $WebhookEntities = [
        self::WEBHOOK_ENTITY_NOTIFICATION,
        self::WEBHOOK_ENTITY_MANDATE,
    ];
}
