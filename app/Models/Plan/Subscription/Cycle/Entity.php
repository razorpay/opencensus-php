<?php

namespace RZP\Models\Plan\Subscription\Cycle;

use App;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const SUBSCRIPTION_ID      = 'subscription_id';
    const PAYMENT_ID           = 'payment_id';
    const INVOICE_ID           = 'invoice_id';
    const INVOICE_AMOUNT       = 'invoice_amount';
    const STATUS               = 'status';
    const AUTH_ATTEMPTS        = 'auth_attempts';
    const START                = 'start';
    const END                  = 'end';
    const CYCLE_NUMBER         = 'cycle_number';
    const SUBSCRIPTION         = 'subscription';

    protected $entity           = 'subscription_cycle';
}
