<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Constants\Entity as EntityConstants;

final class Constants
{
    // *** FTA related constants *** //

    const REMARKS             = 'remarks';

    const FTA_STATUS          = 'fta_status';

    const FAILURE_REASON      = 'failure_reason';

    const VPA_ID              = 'vpa_id';

    const MODE                = 'mode';

    const UTR                 = 'utr';

    const FTA_ID              = 'fta_id';

    const INTERNAL_ERROR      = 'internal_error';

    const BANK_PROCESSED_TIME = 'bank_processed_time';

    const NEFT_END_HOUR = 18;

    const NEFT_START_HOUR = 8;

    Const NEFT_END_MINUTE = 15;

    const RTGS_REVISED_END_HOUR = 17;

    const RTGS_REVISED_START_HOUR = 8;

    const RTGS_REVISED_END_MINUTE = 30;

    const IMPS_STATUS_CHECK_DISPATCH_TIME = 10;

    const MAX_AGE_ATTEMPT_STATUS_DISPATCH_AGE = 1800;

    const DEFAULT_STATUS_CHECK_DISPATCH_TIME = 180;

    const ALLOWED_PRODUCTS_ON_FTS = [
        EntityConstants::REFUND,
        EntityConstants::PAYOUT,
        EntityConstants::FUND_ACCOUNT_VALIDATION,
    ];

    const VIRTUAL_ACCOUNT_IFSC = [
        'YESB0CMSNOC'
    ];

    const DISABLE              = 'disable';
}
