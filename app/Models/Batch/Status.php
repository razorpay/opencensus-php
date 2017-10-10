<?php

namespace RZP\Models\Batch;

class Status
{
    //
    // Batch entity statuses
    //
    const CREATED    = 'created';
    const PROCESSING = 'processing';
    const PROCESSED  = 'processed';
    const FAILED     = 'failed';

    //
    // Additional constants used as values of STATUS
    // header in output file.
    //
    const SUCCESS = 'success';
    const FAILURE = 'failure';

    /**
     * Terminal states for batch processing, Batch cannot be retried
     * once iit has these statuses
     */
    const TERMINAL_STATUSES = [
        self::PROCESSED,
        self::FAILED
    ];
}
