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

    /**
     * These responds to the config class name that should be used,
     * to fetch the config from config/queue.php
     */

    const ES        = 'es';
    const DASHBOARD = 'dashboard';
    const WEBHOOK   = 'webhook';

    protected $mock;

    public function __construct()
    {
        parent::__construct();

        $this->mock = Config::get('queue.mock');
    }

    public function dispatchOn(Job $job, string $configClass, array $configArray = [])
    {
        //TODO : Add a usage link, making it more implicit to be used by other services
        $this->setQueueConnectionAndName($job, $configClass, $configArray);

        $this->dispatch($job);
    }

    protected function setQueueConnectionAndName(Job $job, string $configClass, array $configArray)
    {
        //TODO : Remove it after tested on prod
        if ($this->mode !== Mode::TEST)
        {
            return;
        }

        $queueNameConfig = 'queue.' . $configClass . '.' . $this->mode;

        if (empty($configArray) === false)
        {
            $queueNameConfig .= '.' . implode($configArray, '.');
        }

        $queueConnectionConfig = 'queue.' . $configClass . '.connection';

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
