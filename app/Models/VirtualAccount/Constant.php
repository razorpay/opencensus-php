<?php

namespace RZP\Models\VirtualAccount;

class Constant
{
    // in days
    const ECMS_CHALLAN_DEFAULT_EXPIRY = 3;

    const ECMS_VA_EXPIRY_OFFSET_SETTING_KEY = 'va_expiry_offset';

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
