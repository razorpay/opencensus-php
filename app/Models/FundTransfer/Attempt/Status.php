<?php

namespace RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED       = 'created';
    const INITIATED     = 'initiated';
    const FAILED        = 'failed';
    const PROCESSED     = 'processed';

    const PENDING_RECONCILIATION = self::INITIATED;
}