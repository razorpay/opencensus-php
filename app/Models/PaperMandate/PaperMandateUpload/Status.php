<?php

namespace RZP\Models\PaperMandate\PaperMandateUpload;

class Status
{
    // still processing
    const PENDING  = 'pending';
    // internal error
    const FAILED   = 'failed';
    const ACCEPTED = 'accepted';
    const REJECTED = 'rejected';

    public static function isValidType($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)));
    }
}
