<?php

namespace RZP\Services\Settlements;

use RZP\Exception;

class Reminder extends Base
{
    const EXECUTION_TRIGGER         = '/twirp/rzp.settlements.execution.v1.ExecutionService/Trigger';

    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Trigger execution upon receiving reminder
     * @param array $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function executionReminder(array $input) : array
    {
        return $this->makeRequest(self::EXECUTION_TRIGGER, $input, self::SERVICE_REMINDER);
    }
}
