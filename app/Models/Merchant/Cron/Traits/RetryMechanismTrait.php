<?php


namespace RZP\Models\Merchant\Cron\Traits;


use RZP\Exception\Cron\CronConfigIntegrityException;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Metrics;

trait RetryMechanismTrait
{
    public function process() : bool
    {
        $attemptsDone = $this->getAttempts();
        $safeAttempts = 0;

        while ($attemptsDone < $this->getRetryLimit() and $safeAttempts <= Constants::MAX_RETRIES_ALLOWED)
        {
            $success = parent::process();

            if($success === true)
            {
                return $success;
            }

            $attemptsDone = $this->getAttempts();
            $safeAttempts++;
        }

        return false;
    }

    abstract function getRetryLimit() : int;

    abstract function getAttempts(): int;
}
