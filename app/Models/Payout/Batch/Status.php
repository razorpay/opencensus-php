<?php

namespace RZP\Models\Payout\Batch;

use RZP\Models\Batch\Status as BatchServiceStatus;

class Status
{
    const ACCEPTED = 'accepted';

    const PROCESSING = 'processing';

    const PROCESSED = 'processed';

    const FAILED = 'failed';

    public static $statusMapBetweenPayoutsBatchAndBatchService = [
        BatchServiceStatus::CREATED             => self::ACCEPTED,
        BatchServiceStatus::PARTIALLY_PROCESSED => self::PROCESSING,
        BatchServiceStatus::PROCESSED           => self::PROCESSED,
        BatchServiceStatus::FAILED              => self::FAILED,
    ];
}
