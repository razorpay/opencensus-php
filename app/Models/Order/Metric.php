<?php

namespace RZP\Models\Order;

final class Metric
{
    const ORDER_CREATION_AMOUNT_VALIDATION_FAILURE_COUNT = 'order_creation_amount_validation_failure_count';

    const PG_ROUTER_ORDER_SYNC_QUEUE_PUSH_COUNT          = 'pg_router_order_sync_queue_push_count';

    const PG_ROUTER_ORDER_SYNC_QUEUE_CONSUME_COUNT       = 'pg_router_order_sync_queue_consume_count';

    const PG_ROUTER_ORDER_SYNC_RESPONSE_TIME             = 'pg_router_order_sync_response_time';

    const PG_ROUTER_UPDATE_ORDER_SYNC_QUEUE_PUSH_COUNT          = 'pg_router_order_sync_queue_push_count';

    const PG_ROUTER_UPDATE_ORDER_SYNC_QUEUE_CONSUME_COUNT       = 'pg_router_order_sync_queue_consume_count';

}
