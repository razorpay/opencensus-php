<?php

namespace RZP\Services\UpiPayment;

/**
 * Defines all the UPI Service Constants
 */
class Constant
{
    // Trace Parameters
    const GATEWAY       = 'gateway';
    const ID            = 'id';
    const AMOUNT        = 'amount';
    const CURRENCY      = 'currency';
    const CPS_ROUTE     = 'cps_route';
    const FLOW          = 'flow';
    const TYPE          = 'type';
    const BILLING_LABEL = 'billing_label';

    const MAX_RETRY = 2;
}
