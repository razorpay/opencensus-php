<?php

namespace RZP\Models\FundAccount\Validation;

class Status
{
    const CREATED   = 'created';
    const COMPLETED = 'completed';

    // This is here because whenever FTA status is changed from dashboard,
    // it expects all source entities to have "FAILED" status. TODO: Fix.
    const FAILED    = 'failed';

    // This is here because FTA recon expects all source entities to have
    // the same status, and attempts to resolve the constant. TODO: Fix.
    const PROCESSED = 'processed';
}
