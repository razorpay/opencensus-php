<?php

namespace RZP\Models\Reminders;

use RZP\Trace\TraceCode;

class TerminalCreatedWebhookReminderProcessor extends ReminderProcessor
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(string $entity, string $namespace, string $id, array $input)
    {
        $this->trace->info(TraceCode::TERMINAL_CREATED_WEBHOOK_REMINDER_CALLBACK,
            [
                'terminal_id'    => $id,
                'input'          => $input,
            ]
        );

        $terminal = null;

        try {
            
            $terminal = $this->repo->$entity->findOrFail($id);

            $this->app['events']->fire('api.terminal.created', ['main' => $terminal]);
        }
        catch(\Throwable $e)
        {
            $this->trace->info(TraceCode::TERMINAL_CREATED_WEBHOOK_REMINDER_CALLBACK_FAILED,
                [
                    'terminal_id'    => $id,
                    'input'          => $input,
                ]
            );

            return ['success' => false];
        }

        $this->handleInvalidReminder(); 
    }
}
