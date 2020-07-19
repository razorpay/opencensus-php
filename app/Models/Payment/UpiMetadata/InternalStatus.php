<?php

namespace RZP\Models\Payment\UpiMetadata;

class InternalStatus
{
    const REMINDER_PENDING_FOR_PRE_DEBIT        = 'reminder_pending_for_pre_debit';
    const REMINDER_IN_PROGRESS_FOR_PRE_DEBIT    = 'reminder_in_progress_for_pre_debit';
    const PRE_DEBIT_INITIATED                   = 'pre_debit_initiated';

    const REMINDER_PENDING_FOR_AUTHORIZE        = 'reminder_pending_for_authorize';
    const REMINDER_IN_PROGRESS_FOR_AUTHORIZE    = 'reminder_in_progress_for_authorize';
    const AUTHORIZE_INITIATED                   = 'authorize_initiated';

    const AUTHORIZED                            = 'authorized';
    const FAILED                                = 'failed';
}
