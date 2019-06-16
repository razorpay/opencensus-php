<?php

namespace RZP\Models\Plan\Subscription\Version;

use App;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const SUBSCRIPTION_ID        = 'subscription_id';
    const TOKEN_ID               = 'token_id';
    const PLAN_ID                = 'plan_id';
    const SCHEDULE_ID            = 'schedule_id';
    const STATUS                 = 'status';
    const QUANTITY               = 'quantity';
    const CUSTOMER_NOTIFY        = 'customer_notify';
    const TOTAL_COUNT            = 'total_count';
    const CANCEL_AT              = 'cancel_at';
    const START_AT               = 'start_at';
    const END_AT                 = 'end_at';
    const CURRENT_START          = 'current_start';
    const CURRENT_END            = 'current_end';
    const CYCLE_ID               = 'cycle_id';
    const CURRENT_PAYMENT_ID     = 'current_payment_id';
    const CYCLE_NUMBER           = 'cycle_number';

    const SUBSCRIPTION         = 'subscription';

    protected $entity           = 'subscription_version';
}
