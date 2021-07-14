<?php

namespace RZP\Jobs\Kafka;

use RZP\Jobs\Job as BaseJob;

class Job extends BaseJob
{
    /**
     * @var array
     */
    protected $payload;

    /**
     * @param string $mode
     * @param array $payload
     */
    public function __construct(array $payload, string $mode = null)
    {
        parent::__construct($mode);
        $this->payload = $payload;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setTaskId(string $taskId)
    {
        $this->taskId = $taskId;
    }
}
