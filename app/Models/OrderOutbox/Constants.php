<?php

namespace RZP\Models\OrderOutbox;

class Constants
{
    const ORDER_OUTBOX = "order_outbox";

    //Events
    const ORDER_STATUS_PAID_EVENT          = "order_status_paid_event";
    const ORDER_AMOUNT_PAID_EVENT          = "order_amount_paid_event";

    const OUBTOX_ENTRIES_COUNT      = "outbox_entry_count";

    const ORDER_UPDATE_MUTEX        = "_order_update_pg_router";

    // Cron
    const MAX_RETRY_COUNT                       = 10;
    const OUTBOX_RETRY_DEFAULT_END_TIME         = 120;
    const OUTBOX_RETRY_DEFAULT_START_TIME       = 3600;
    const DEFAULT_LIMIT                         = 100;
}
