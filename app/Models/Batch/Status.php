<?php

namespace RZP\Models\Batch;

class Status
{
    /**
     * Batch entity statuses meaning the following
     * - CREATED - Status when batch entity is first created
     * - PARTIALLY_PROCESSED - Status when batch input file has been partially processed
     *                         Not all batch types will go to this state though if there
     *                         are partial failures
     * - FAILED - Status when there is an unhandled exception while generating the file
     * - PROCESSED - Terminal processing state for the batch. Once in this state batch
     *               can't be processed any further.
     */
    const CREATED             = 'created';
    const PARTIALLY_PROCESSED = 'partially_processed';
    const FAILED              = 'failed';
    const PROCESSED           = 'processed';

    //
    // Additional constants used as values of STATUS
    // header in output file.
    //
    const SUCCESS = 'success';
    const FAILURE = 'failure';
}
