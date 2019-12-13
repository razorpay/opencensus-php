<?php

namespace RZP\Models\Payment\Refund;

class Constants
{
    const IS_FTA                            = 'is_fta';
    const MOZART                            = 'mozart';
    const ENTITIES                          = 'entities';
    const REFUND_IDS                        = 'refund_ids';
    const DB_FETCH_LIMIT                    = 'limit';
    const GATEWAY_ENTITY                    = 'gateway_entity';
    const SPEED_CHANGE_TIME                 = 'speed_change_time';
    const REFUND_REFERENCE1                 = 'refund_reference1';
    const INSTANT_REFUND_SUPPORT            = 'instant_refund_support';
    const MAX_REFUND_RETRY_ATTEMPTS         = 3;
    const MAX_REFUND_VERIFY_REQUESTS        = 20;
    const SCROOGE_TAGGING_LIVE_TIMESTAMP    = 1552646209;
    /**
     * We get the last 10 days refunds created of a gateway.
     * We run the cron for this once a day.
     */
    const GATEWAY_REFUND_RECORDS_TIME_LIMIT      = 864000;
    /**
     * Refund public status related constants
     */
    const DISPLAY_REFUND_PUBLIC_STATUS           = 'display_refund_public_status';
    const REFUND_PUBLIC_STATUS_FEATURE_ENABLED   = 'refund_public_status_feature_enabled';
    const CARD_TRANSFER_FEATURE_ENABLED_MERCHANT = 'card_transfer_feature_enabled_merchant';
    /**
     * Transaction tracker related constants
     */
    const ID                 = 'id';
    const DAYS               = 'days';
    const RZP_ID             = 'rzp_id';
    const REFUND             = 'refund';
    const PAYMENT            = 'payment';
    const ID_TYPE            = 'id_type';
    const UNKNOWN            = 'unknown';
    const REFUNDS            = 'refunds';
    const NPCI_RRN           = 'npci_rrn';
    const PAYMENTS           = 'payments';
    const ORDER_ID           = 'order_id';
    const LATE_AUTH          = 'late_auth';
    const REFUND_ID          = 'refund_id';
    const PAYMENT_ID         = 'payment_id';
    const MERCHANT_ID        = 'merchant_id';
    const MERCHANT_NAME      = 'merchant_name';
    const PRIMARY_MESSAGE    = 'primary_message';
    const TERTIARY_MESSAGE   = 'tertiary_message';
    const SECONDARY_MESSAGE  = 'secondary_message';
    const MERCHANT_REFERENCE = 'merchant_reference';

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';
    const RESPONSE_DATA          = 'data';

    const ISSUER        = 'issuer';
    const METHOD        = 'method';
    const CARD_TYPE     = 'card_type';
    const NETWORK_CODE  = 'network_code';
    const AMOUNT        = 'amount';

    /**
     * razorx experiments related constants
     */
    const RAZORX_VARIANT_ON = 'on';

    // Used to ack scrooge that update status request came from fta status update
    const FTA_UPDATE = 'fta_update';

    /**
     * Scrooge File Based Refunds request related constants
     */
    const SCROOGE_GT          = 'gt';
    const SCROOGE_ID          = 'id';
    const SCROOGE_GTE         = 'gte';
    const SCROOGE_LTE         = 'lte';
    const SCROOGE_BANK        = 'bank';
    const SCROOGE_SKIP        = 'skip';
    const SCROOGE_COUNT       = 'count';
    const SCROOGE_QUERY       = 'query';
    const SCROOGE_REFUNDS     = 'refunds';
    const SCROOGE_GATEWAY     = 'gateway';
    const SCROOGE_CREATED_AT  = 'created_at';
    const SCROOGE_BASE_AMOUNT = 'base_amount';
}
