<?php

namespace RZP\Models\OrderOutbox;

use RZP\Base\RuntimeManager;
use RZP\Models\Base;
use RZP\Models\OrderOutbox;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new OrderOutbox\Core;
    }

    //order outbox cron retries outbox entries for order update
    public function retryOrderUpdate(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $response = $this->core->retryOrderUpdate($input);

        return $response;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(600);

        RuntimeManager::setMaxExecTime(600);
    }

    public function createOrderOutboxPartition()
    {
        return $this->core->createOrderOutboxPartition();
    }
}

