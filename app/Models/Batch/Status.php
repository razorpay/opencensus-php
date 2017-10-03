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

    //
    // Additional constants used as values of STATUS
    // header in output file.
    //
    const SUCCESS = 'success';
    const FAILURE = 'failure';
}
