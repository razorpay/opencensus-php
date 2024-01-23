<?php

namespace RZP\Models\Terminal;

class Constants
{
    const SKIP_INSTRUMENT_EVENT_RULES_TRIGGER_ROUTES = ['merchant_sub_create', 'merchant_sub_create_batch'];

    const BULK_TERMINAL_WRITE_ROUTES = ['terminal_bank_bulk'];

    const ATTEMPTS = "attempts";

    const ACTION = "action";

    const TASK_ID = "task_id";

    const CREATE_OR_UPDATE = "create_or_update";

    const DELETE = "delete";

    const TERMINAL_SYNC_FAILURES_EVENT = "prod-terminal-sync-failures-event";
}
