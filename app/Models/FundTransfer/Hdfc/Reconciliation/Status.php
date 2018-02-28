<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

class Status
{
    /**
     * Status : Executed
     */
    const SETTLED       = 'E';

    /**
     * Status : Rejected
     */
    const CANCELLED     = 'R';
}
