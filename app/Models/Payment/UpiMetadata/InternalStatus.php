<?php

namespace RZP\Models\Payment\UpiMetadata;

class InternalStatus
{
    // Initial recurring payment statuses
    const PENDING_FOR_AUTHENTICATE              = 'pending_for_authenticate';
    const AUTHENTICATE_INITIATED                = 'authenticate_initiated';
    const PENDING_FOR_AUTHORIZE                 = 'pending_for_authorize';

    // Auto recurring payment statutes
    const REMINDER_PENDING_FOR_PRE_DEBIT        = 'reminder_pending_for_pre_debit';
    const REMINDER_IN_PROGRESS_FOR_PRE_DEBIT    = 'reminder_in_progress_for_pre_debit';
    const PRE_DEBIT_INITIATED                   = 'pre_debit_initiated';
    const PRE_DEBIT_FAILED                      = 'pre_debit_failed';

    const REMINDER_SKIPPED_FOR_AUTHORIZE        = 'reminder_skipped_for_authorize';
    const REMINDER_PENDING_FOR_AUTHORIZE        = 'reminder_pending_for_authorize';
    const REMINDER_IN_PROGRESS_FOR_AUTHORIZE    = 'reminder_in_progress_for_authorize';
    const AUTHORIZE_INITIATED                   = 'authorize_initiated';

    // Common statuses
    const AUTHORIZED                            = 'authorized';
    const FAILED                                = 'failed';
}
