<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

final class Metric
{
    const NODAL_RESPONSE_STATUS_CODE = 'nodal_response_status_code';

    const NODAL_FAILURE_STATUS_CODE  = 'nodal_failure_status_code';

    const NODAL_RESPONSE_COUNT       = 'nodal_response_count';

    // Dimensions
    const MODE                       = 'mode';

    const STATUS                     = 'status';

    const CHANNEL                    = 'channel';

    const PRODUCT                    = 'product';

    const STATUS_CODE                = 'status_code';

    const BANK_FAILURE_CODE          = 'bank_failure_code';

    // Values
    const CATEGORY_FAILED            = 'failed';

    const CATEGORY_PENDING           = 'pending';

    const CATEGORY_SUCCESS           = 'success';

    const REQUEST_TRACE_CODE         = 'request_trace_code';

    const NODAL_RESPONSE_TIME        = 'nodal_response_time';
}
