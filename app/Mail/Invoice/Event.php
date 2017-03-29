<?php

namespace RZP\Mail\Invoice;

class Event
{
    const INVOICE_ISSUED   = 'invoice_issued';
    const INVOICE_EXPIRED  = 'invoice_expired';
    const INVOICE_EXPIRING = 'invoice_expiring';
    const INVOICE_PAID     = 'invoice_paid';
}
