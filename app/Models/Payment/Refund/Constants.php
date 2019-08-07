<?php

namespace RZP\Models\Payment\Refund;

class Constants
{
    const IS_FTA = 'is_fta';
    const ENTITIES = 'entities';
    const DB_FETCH_LIMIT = 'limit';
    const REFUND_IDS = 'refund_ids';
    const MAX_REFUND_RETRY_ATTEMPTS = 3;
    const MAX_REFUND_VERIFY_REQUESTS = 20;
    const GATEWAY_ENTITY = 'gateway_entity';
    const REFUND_REFERENCE1 = 'refund_reference1';
    /**
     * We get the last 10 days refunds created of a gateway.
     * We run the cron for this once a day.
     */
    const GATEWAY_REFUND_RECORDS_TIME_LIMIT = 864000;
    const SCROOGE_TAGGING_LIVE_TIMESTAMP = 1552646209;
    const SPEED_CHANGE_TIME = 'speed_change_time';
}
