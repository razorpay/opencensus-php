<?php

namespace RZP\Models\Workflow\Observer;

class Constants
{
    const SCHEDULED_SETTLEMENT                  =   'schedule_assign';

    const APPROVE                               =   'approve';

    const WORKFLOW_VS_OBSERVER= [

        self::SCHEDULED_SETTLEMENT      => ScheduleSettlementObserver::class,

    ];
}
