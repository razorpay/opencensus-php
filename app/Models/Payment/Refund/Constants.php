<?php

namespace RZP\Models\Payment\Refund;

class Constants
{
    const IS_FTA                            = 'is_fta';
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
    const GATEWAY_REFUND_RECORDS_TIME_LIMIT = 864000;

    /**
     * Transaction tracker related constants
     */
    const ID                = 'id';
    const DAYS              = 'days';
    const REFUND            = 'refund';
    const PAYMENT           = 'payment';
    const REFUNDS           = 'refunds';
    const PAYMENTS          = 'payments';
    const ORDER_ID          = 'order_id';
    const LATE_AUTH         = 'late_auth';
    const REFUND_ID         = 'refund_id';
    const PAYMENT_ID        = 'payment_id';
    const MERCHANT_ID       = 'merchant_id';
    const MERCHANT_NAME     = 'merchant_name';
    const PRIMARY_MESSAGE   = 'primary_message';
    const TERTIARY_MESSAGE  = 'tertiary_message';
    const SECONDARY_MESSAGE = 'secondary_message';
}
