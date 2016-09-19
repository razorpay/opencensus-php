<?php

namespace RZP\Models\Payment\BatchRefund;

class BatchRefundStatus
{
    const CREATED                   = 'CREATED';
    const IN_PROGRESS               = 'IN_PROGRESS';
    const FAILURE                   = 'FAILURE';
    const FAILED                    = 'FAILED';
    const PROCESSED                 = 'PROCESSED';
}
