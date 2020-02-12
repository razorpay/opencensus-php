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

        $data = [
            Terminal\Entity::ID => $this->terminalId,
        ];

        try
        {
            $this->trace->info(TraceCode::TERMINALS_SERVICE_MIGRATE_JOB_STARTED, $data);

            $terminal = (new Terminal\Repository)->findOrFail($this->terminalId);

            (new Terminal\Service)->migrateTerminal($terminal);

            $this->trace->info(TraceCode::TERMINALS_SERVICE_MIGRATE_JOB_SUCCESS, $data);

        }
        catch (\Exception $exception)
        {
            $data['code'] = $exception->getCode();

            $data['message'] = $exception->getMessage();

            $this->trace->error(TraceCode::Terminals_SERVICE_MIGRATE_JOB_FAILED, $data);
        }
        finally
        {
            $this->delete();
        }
    }
}
