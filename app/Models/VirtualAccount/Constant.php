<?php

namespace RZP\Models\VirtualAccount;

class Constant
{
    // in hours
    /*
     * Special org level default expiry for ECMS org
     */
    const ECMS_CHALLAN_DEFAULT_EXPIRY_IN_HOURS = 3*24;

    /*
     * Special merchant level default expiry for HDFC LIFE
     */
    const HDFC_LIVE_VA_OFFSET_DEFAULT_CLOSE_BY_HOURS = 5 * 24;

    const VA_EXPIRY_OFFSET = 'va_expiry_offset';

    const FETCH_LIMIT = 100;
    const IDEMPOTENCY_KEY             = 'idempotency_key';
    const VIRTUAL_ACCOUNT_ID          = 'virtual_account_id';

    const BATCH_ERROR                 = 'error';
    const BATCH_ERROR_CODE            = 'code';
    const BATCH_ERROR_DESCRIPTION     = 'description';
    const BATCH_SUCCESS               = 'success';
    const BATCH_HTTP_STATUS_CODE      = 'http_status_code';

    // Terminal Caching
    const TERMINAL_CACHE_PREFIX       = "TERMINAL_CACHE_V1";
    const TERMINAL_CACHE_TTL          = 30 * 60; // In seconds.
}
