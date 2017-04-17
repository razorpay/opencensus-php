<?php

namespace RZP\Jobs;

use Config;
use RZP\Constants\Mode;
use Illuminate\Foundation\Bus\DispatchesJobs;

trait DispatchRouter
{
    use DispatchesJobs;

    protected function dispatchOn(Job $job, array $configArray)
    {
        $this->setQueueConnectionAndName($job, $configArray);

        $this->dispatch($job);
    }

    protected function setQueueConnectionAndName(Job $job, array $configArray)
    {
        //TODO : Remove it after tested on prod
        if ($configArray[1] !== Mode::TEST)
        {
            return;
        }

        $mock = Config::get('queue.mock');

        $queueNameConfig = 'queue.' . implode($configArray, '.');

        $queueConnectionConfig = 'queue.' . $configArray[0] . '.connection';

        if ($mock === true)
        {
            $queueConnectionConfig = 'queue.default';
        }

        $queueName = Config::get($queueNameConfig);

        $queueConnection = Config::get($queueConnectionConfig);

        $job->onConnection($queueConnection)->onQueue($queueName);
    }
}
