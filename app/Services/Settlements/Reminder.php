<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
use RZP\Trace\TraceCode;

class Reminder extends Base
{
    const EXECUTION_TRIGGER         = '/twirp/rzp.settlements.execution.v1.ExecutionService/Trigger';
    const ENTITY_SCHEDULER_TRIGGER  = '/twirp/rzp.settlements.entity_scheduler.v1.EntityScheduler/Trigger';

    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Trigger execution upon receiving reminder
     * @param array $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function executionReminder(array $input) : array
    {
        return $this->makeRequest(self::EXECUTION_TRIGGER, $input, self::SERVICE_REMINDER);
    }

    public function entitySchedulerReminder(array $data,string $id) : array
    {
        $input=$data;
        $input['id']=$id;
        $this->trace->info(TraceCode::SETTLEMENT_REMINDER_CALLBACK_RECEIVED,
                           [
                               'entity'    => $input,
                               'msg'       => 'entity scheduler trigger received calling nss'
                           ]
        );
        return $this->makeRequest(self::ENTITY_SCHEDULER_TRIGGER, $input, self::SERVICE_REMINDER);
    }
}
