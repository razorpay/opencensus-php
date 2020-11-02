<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Terminal;

class TerminalsServiceMigrateJob extends Job
{

    /**
     * @var int
     */
    public $timeout = 100;

    protected $terminalId;

    protected $service;

    const QueueConfigKey = 'terminals_service_migrate';

    public function __construct(string $mode, string $terminalId)
    {
        parent::__construct($mode);

        $this->terminalId = $terminalId;

        $this->queueConfigKey = self::QueueConfigKey;
    }

    public function handle()
    {
        parent::handle();

        $data = [
            Terminal\Entity::ID => $this->terminalId,
        ];

        try
        {
            $this->delete();

            $this->trace->info(TraceCode::TERMINALS_SERVICE_MIGRATE_JOB_STARTED, $data);

            (new Terminal\Service)->migrateTerminalCreateOrUpdate($this->terminalId);

            $this->trace->info(TraceCode::TERMINALS_SERVICE_MIGRATE_JOB_SUCCESS, $data);

        }
        catch (\Exception $exception)
        {
            $data['code'] = $exception->getCode();

            $data['message'] = $exception->getMessage();

            $repo = new Terminal\Repository;

            $terminal = $repo->findOrFail($this->terminalId);

            $terminal->setSyncStatus(Terminal\SyncStatus::SYNC_FAILED);

            $repo->saveOrFail($terminal, ['shouldSync' => false]);

            $this->trace->error(TraceCode::TERMINALS_SERVICE_MIGRATE_JOB_FAILED, $data);
        }
    }
}
