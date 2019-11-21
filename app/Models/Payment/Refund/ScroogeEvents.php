<?php

namespace RZP\Models\Payment\Refund;

// Refund status update events for scrooge refunds
class ScroogeEvents
{
    const FAILED_EVENT                 = 'failed_event';
    const PROCESSED_EVENT              = 'processed_event';
    const FILE_INIT_EVENT              = 'file_init_event';
    const FEE_ONLY_REVERSAL_EVENT      = 'fee_only_reversal_event';
    const PROCESSED_TO_FILE_INIT_EVENT = 'processed_to_file_init_event';
}
