<?php


namespace RZP\Models\Mpan;


class Constants
{
    const COUNT                       = 'count';

    const ISSUE_MPANS_OPERATION       = 'issue_mpans';

    const MPAN_ISSUE_MUTEX_RESOURCE   = 'mpan_issue_mutex_resource';

    const MPAN_ISSUE_MUTEX_TTL        = 60;

    const MASTERCARD                  = 'MasterCard';
    const VISA                        = 'Visa';
    const RUPAY                       = 'RuPay';

    // new batch service related constants
    const BATCH_ERROR                 = 'error';
    const BATCH_ERROR_CODE            = 'code';
    const BATCH_ERROR_DESCRIPTION     = 'description';
    const BATCH_SUCCESS               = 'success';
    const BATCH_HTTP_STATUS_CODE      = 'http_status_code';

}
