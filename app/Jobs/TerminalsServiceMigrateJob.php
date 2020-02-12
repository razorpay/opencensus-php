<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Terminal;

class TerminalsServiceMigrateJob extends Job
{
    protected $terminalId;

    protected $service;

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
            $this->trace->info(TraceCode::TERMINALS_SERVICE_MIGRATE_JOB_STARTED, [$this->terminalId]);

            $terminal = (new Terminal\Repository)->findOrFail($this->terminalId);

            (new Terminal\Service)->migrateTerminal($terminal);

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
