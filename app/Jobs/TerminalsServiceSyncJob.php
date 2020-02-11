<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;

class TerminalsServiceSyncJob extends Job
{
    protected $terminalId;

    public function __construct(string $mode, string $terminalId)
    {
        parent::__construct($mode);

        $this->terminalId = $terminalId;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::TERMINALS_SERVICE_QUEUE_WORKER_STARTED, [$this->terminalId]);

            $this->delete();
        }
        catch (\Throwable $throwable)
        {

        }
        finally
        {

        }
    }
}
