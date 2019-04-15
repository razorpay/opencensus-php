<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Constants\Entity as EntityConstatns;

final class Constants
{
    // *** FTA related constants *** //

    const REMARKS           = 'remarks';

    const FTA_STATUS        = 'fta_status';

    const FAILURE_REASON    = 'failure_reason';

    const VPA_ID            = 'vpa_id';

    const MODE              = 'mode';

    const UTR               = 'utr';

    const FTA_ID            = 'fta_id';

    const IMPS_STATUS_CHECK_DISPATCH_TIME = 10;

    const MAX_AGE_ATTEMPT_STATUS_DISPATCH_AGE = 1800;

    const DEFAULT_STATUS_CHECK_DISPATCH_TIME = 180;

    const ALLOWED_PRODUCTS_ON_FTS = [
        EntityConstatns::REFUND,
    ];
}
