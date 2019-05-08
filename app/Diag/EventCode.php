<?php

namespace RZP\Diag;

class EventCode
{
    // order events
    const ORDER_CREATION_INITIATED = 'order.creation.initiated';
    const ORDER_CREATION_PROCESSED = "order.creation.processed";

    // payment flow events
    const PAYMENT_CREATION_INITIATED      = "payment.creation.initiated";
    const PAYMENT_CREATION_PROCESSED      = "payment.creation.processed";
    const PAYMENT_AUTHORIZATION_PROCESSED = "payment.authorization.processed";
    const PAYMENT_CAPTURE_PROCESSED       = "payment.capture.processed";
    const TERMINAL_SELECTION_INITIATED    = "terminal.selection.initiated";
    const TERMINAL_SELECTION_PROCESSED    = "terminal.selection.processed";

}
