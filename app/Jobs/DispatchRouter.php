<?php

namespace RZP\Jobs;

use Config;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Illuminate\Foundation\Bus\DispatchesJobs;

class DispatchRouter extends Base\Core
{
    use DispatchesJobs;

    const ES        = 'es';
    const DASHBOARD = 'dashboard';
    const WEBHOOK   = 'webhook';

    public function __construct()
    {
        parent::__construct();

        $this->mock = Config::get('queue.mock');
    }

    public function dispatchOn(Job $job, array $configArray)
    {
        $this->setQueueConnectionAndName($job, $configArray);

        $this->dispatch($job);
    }

    public function setQueueConnectionAndName(Job $job, array $configArray)
    {
        //TODO : Remove it after tested on prod
        if ($this->mode !== Mode::TEST)
        {
            return;
        }

        $queueNameConfig = 'queue.' . implode($configArray, '.');

        $queueConnectionConfig = 'queue.' . $configArray['class'] . '.connection';

        if ($this->mock === true)
        {
            $queueConnectionConfig = 'queue.default';
        }

        $queueName = Config::get($queueNameConfig);

        $queueConnection = Config::get($queueConnectionConfig);

        if ($queueConnection === null)
        {
            $this->trace->critical(
                TraceCode::QUEUE_INVALID_CONFIG,
                [
                    'queue_connection_config' => $queueConnectionConfig,
                    'queue_name_config'       => $queueNameConfig,
                ]);
        }

        $job->onConnection($queueConnection)->onQueue($queueName);
    }
}
