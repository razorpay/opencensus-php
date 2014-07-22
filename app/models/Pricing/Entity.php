<?php

namespace Models\Pricing;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID = 'id';
    const GATEWAY = 'gateway';
    const PLAN = 'plan';
    const PAYMENT_METHOD = 'payment_method';
    const PAYMENT_METHOD_SUBTYPE = 'payment_method_subtype';
    const PERCENT_RATE = 'percent_rate';
    const FIXED_RATE = 'fixed_rate';
    const EXPIRED_AT = 'expired_at';
}
