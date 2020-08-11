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

    // mpan tokenization migration cron
    const TOKENIZATION_SUCCESS_COUNT            = 'tokenization_success_count';
    const TOKENIZATION_FAILED_COUNT             = 'tokenization_failed_count';
    const TOKENIZATION_SUCCESS_TERMINAL_IDS     = 'tokenization_success_terminal_ids';
    const TOKENIZATION_FAILED_TERMINAL_IDS      = 'tokenization_failed_terminal_ids';
}
