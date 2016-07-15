<?php

namespace RZP\Listeners;

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
}
