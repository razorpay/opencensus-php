<?php

namespace RZP\Gateway\Base;

class VerifyResult
{
    const STATUS_MATCH = 'status_match';
    const STATUS_MISMATCH = 'status_mismatch';
    const REFUND_AMOUNT_MISMATCH = 'refund_amount_mismatch';
    const REFUND_STATUS_MISMATCH = 'refund_status_mismatch';
}
