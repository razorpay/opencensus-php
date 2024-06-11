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

    const TS_TIDB_TABLE = 'terminalslive.terminals';

    const TS_TERMINAL_ID = 'terminals.terminal_id';

    const TS_DELETED_AT = 'terminals.deleted_at';

    const TS_GATEWAY_TERMINAL_ID = 'terminals.identifiers->\'$.gateway_terminal_id\'';

    const TS_METHODS = 'terminals.methods';

    const TS_NETBANKING_TPV = 'terminals.features->\'$.netbanking.Corporate\'';

    const TS_NETBANKING_CORPORATE = 'terminals.features->\'$.netbanking.Tpv\'';
    const TS_TERMINAL_GATEWAY = 'terminals.gateway';

    const TS_TERMINAL_ACQUIRER = 'terminals.gateway_acquirer';

    const TERMINALS_DUAL_WRITE_REMOVAL = 'terminals_dual_write_removal';
}
