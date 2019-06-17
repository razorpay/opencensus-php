<?php

namespace RZP\Models\Plan\Subscription\UpdateRequest;

use App;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const SUBSCRIPTION_ID       = 'subscription_id';
    const PLAN_ID               = 'plan_id';
    const QUANTITY              = 'quantity';
    const TOTAL_COUNT           = 'total_count';
    const START_AT              = 'start_at';
    const CUSTOMER_NOTIFY       = 'customer_notify';
    const SCHEDULE_CHANGE_AT    = 'schedule_change_at';
    const VERSION_ID            = 'version_id';

    protected $entity = 'subscription_update_request';
}
