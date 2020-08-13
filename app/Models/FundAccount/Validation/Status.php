<?php

namespace RZP\Models\FundAccount\Validation;

class Status
{
    const CREATED   = 'created';
    const COMPLETED = 'completed';

    const FAILED    = 'failed';

    // This is here because FTA recon expects all source entities to have
    // the same status, and attempts to resolve the constant. TODO: Fix.
    const PROCESSED = 'processed';

    public static function hasFinalStatus(Entity $fav)
    {
        return in_array($fav->getStatus(), [self::FAILED, self::COMPLETED]) === true;
    }
}
