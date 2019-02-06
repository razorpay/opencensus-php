<?php

namespace RZP\Models\FundAccount\Validation;

class Status
{
    const CREATED   = 'created';
    const COMPLETED = 'completed';

    // This is here because FTA recon expects all source entities to have
    // the same status, and attempts to resolve the constant. TODO: Fix.
    const PROCESSED = 'processed';
}
