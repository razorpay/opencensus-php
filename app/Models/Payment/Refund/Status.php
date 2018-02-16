<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED   = 'created';
    const INITIATED = Attempt\Status::INITIATED;
    const PROCESSED = 'processed';
    const FAILED    = 'failed';
}
