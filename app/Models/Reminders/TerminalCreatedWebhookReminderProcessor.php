<?php

namespace RZP\Models\Reminders;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;

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

            $terminal->setStatus(Status::ACTIVATED);

            $this->repo->saveOrFail($terminal);

            $this->app['events']->dispatch('api.terminal.created', ['main' => $terminal]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::TERMINAL_CREATED_WEBHOOK_REMINDER_CALLBACK_FAILED,
                ['terminal_id' => $id]
            );

            throw new ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR_REMINDER_CALLBACK_FAILED,
                []);

        }

        return ['success' => true];
    }
}
