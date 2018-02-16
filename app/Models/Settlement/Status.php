<?php

namespace RZP\Models\Settlement;

use RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED       = 'created';
    const INITIATED     = Attempt\Status::INITIATED;
    const FAILED        = 'failed';
    const PROCESSED     = 'processed';
}
