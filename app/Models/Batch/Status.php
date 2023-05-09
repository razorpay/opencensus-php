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
    const CANCELLED           = 'cancelled';
    const SCHEDULED           = 'scheduled';
    const VALIDATED           = 'validated';
    const VALIDATING          = 'validating';
    const VALIDATION_FAILED   = 'validation_failed';

    //
    // Additional constants used as values of STATUS
    // header in output file.
    //
    const SUCCESS             = 'success';
    const FAILURE             = 'failure';

    // Used as External status for Refund Batch
    const PROCESSING          = 'processing';

    const BATCH_STATUSES = [
        self::CREATED,
        self::PARTIALLY_PROCESSED,
        self::FAILED,
        self::PROCESSED,
        self::CANCELLED,
        self::SCHEDULED,
    ];

    const BATCH_STATUSES_VALID_FOR_CANCEL = [
        self::PARTIALLY_PROCESSED,
        self::PROCESSED,
        self::SCHEDULED,
    ];

    const REFUND_BATCH_STATUSES_VALID_FOR_CANCEL = [
        self::CREATED,
        self::SCHEDULED,
    ];

    public static function validateStatus(string $type)
    {
        if (in_array($type, self::BATCH_STATUSES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }
}
