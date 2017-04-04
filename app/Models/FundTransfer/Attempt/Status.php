<?php

namespace RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED       = 'created';
    const FAILED        = 'failed';
    const PROCESSED     = 'processed';

    const PENDING_RECONCILIATION = [self::CREATED];
}