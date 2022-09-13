<?php

namespace RZP\Models\Reminders;

use RZP\Trace\TraceCode;

class SettlementReminderProcessor extends ReminderProcessor
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(string $entity, string $namespace, string $id, array $data)
    {
        $this->trace->info(TraceCode::SETTLEMENT_REMINDER_CALLBACK,
            [
                'entity'    => $entity,
                'entity_id' => $id,
                'namespace' => $namespace,
                'data'      => $data,
            ]
        );

        if( (strcmp((string)$entity,'entity_scheduler'))==0){
            $this->trace->info(TraceCode::SETTLEMENT_REMINDER_CALLBACK_RECEIVED,
                               [
                                   'entity'    => $entity,
                                   'entity_id' => $id,
                                   'namespace' => $namespace,
                                   'data'      => $data,
                                   'msg'       => 'entity scheduler trigger'
                               ]
            );
            return app('settlements_reminder')->entitySchedulerReminder($data,$id);
        }

        return app('settlements_reminder')->executionReminder($data);
    }
}
