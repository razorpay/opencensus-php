<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

class Status
{
    const PAID      = 'Paid';
    const CANCELLED = 'Cancelled';
    const AWAITING  = 'Awaiting Liquidation';
}