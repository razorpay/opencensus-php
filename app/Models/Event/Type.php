<?php

namespace RZP\Models\Event;

use RZP\Models\Base;
use RZP\Exception;

/**
 * The events whether they are enabled or disabled are store in bit format.
 * See this link for a guide on bitwise operations:
 * http://stackoverflow.com/questions/47981/how-do-you-set-clear-and-toggle-a-single-bit-in-c-c
 */
class Type
{
    const PAYMENT_AUTHORIZED = 'payment.authorized';
    const PAYMENT_FAILED     = 'payment.failed';
    const PAYMENT_CAPTURED   = 'payment.captured';
    const ORDER_PAID         = 'order.paid';
}
