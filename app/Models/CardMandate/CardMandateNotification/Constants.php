<?php

namespace RZP\Models\CardMandate\CardMandateNotification;

class Constants
{
    const MANDATE_HQ_AMOUNT              = 'amount';
    const MANDATE_HQ_STATUS              = 'status';
    const MANDATE_HQ_NOTIFICATION_ID     = 'notification_id';
    const MANDATE_HQ_SUCCESS             = 'success';

    const MANDATE_HQ_STATUS_CREATED       = 'created';
    const MANDATE_HQ_STATUS_PENDING       = 'pending';
    const MANDATE_HQ_STATUS_DEBIT_PENDING = 'debit_pending';
    const MANDATE_HQ_STATUS_COMPLETED     = 'completed';
    const MANDATE_HQ_STATUS_FAILED        = 'failed';
}
