<?php

namespace RZP\Models\Batch;

use RZP\Exception;

class Status
{
    //
    // Batch entity statuses meaning the following:
    //
    // CREATED             - Batch entity is created
    // PARTIALLY_PROCESSED - Batch input file has been partially processed
    //                       Note: Not all batch types will go to this state
    //                             though if there are partial failures
    // FAILED              - There was an unhandled error while processing the
    //                       batch. This generally happens even before a single
    //                       entry/row of batch has been processed.
    // PROCESSED           - Terminal state for the batch.
    //
    const CREATED             = 'created';
    const PARTIALLY_PROCESSED = 'partially_processed';
    const FAILED              = 'failed';
    const PROCESSED           = 'processed';

    //
    // Additional constants used as values of STATUS
    // header in output file.
    //
    const SUCCESS             = 'success';
    const FAILURE             = 'failure';

    const BATCH_STATUSES = [
        self::CREATED,
        self::PARTIALLY_PROCESSED,
        self::FAILED,
        self::PROCESSED,
    ];

    public static function validateStatus(string $type)
    {
        if (in_array($type, self::BATCH_STATUSES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }
}
