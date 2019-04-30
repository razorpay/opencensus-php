<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicCollection;

class Lock extends Base\Core
{
    const REQUEST_TIMEOUT = 30;

    protected $keySuffix = '';

    protected $mutex;

    protected $channel;

    /**
     * Channel is optional here since the place that lock is being called from,
     * it's difficult to get the channel and pass it along. Also, it's not really
     * required in the flow. It's used only for the logging.
     * If at all we need channel, we can get it from the entity wherever possible.
     *
     * @param string $channel
     */
    public function __construct(string $channel = '')
    {
        parent::__construct();

        $this->channel = $channel;

        $this->mutex = $this->app['api.mutex'];
    }

    public function acquireLockAndProcessAttempts(PublicCollection $attempts, callable $handle)
    {
        $attemptIds = $attempts->pluck(Entity::ID);

        //
        // Lock time is calculated based on the total number of attempts and request timeout (30)
        // Additional 10 seconds of offset is added
        //
        $mutexTimeout = ($attempts->count() * 30) + 10;

        // Get attempts ids to lock
        $lockedAttemptIds = $this->mutex->acquireMultiple($attemptIds, $mutexTimeout);

        $this->trace->info(
            TraceCode::LOCKED_FUND_TRANSFER_ATTEMPTS,
            [
                'channel'                => $this->channel,
                'attempt_ids_locked'     => $lockedAttemptIds['locked'],
                'attempt_ids_not_locked' => $lockedAttemptIds['unlocked'],
            ]);

        // Lock all attempts by its ids
        $lockedAttempts = $attempts->whereIn(Entity::ID, $lockedAttemptIds['locked']);

        if(empty($lockedAttemptIds['locked']) === true)
        {
            $response = array();
            
            $response[0] = [
                "success" => 0,
                "failed"  => 0,
            ];

            $response[1] = $lockedAttempts;

            return $response;
        }

        try
        {
            $response = $handle($lockedAttempts);
        }
        finally
        {
            $this->mutex->releaseMultiple($attempts->getIds(), $this->keySuffix);
        }

        return $response;
    }

    public function acquireLockAndProcessAttempt(Entity $attempt, callable $handle)
    {
        $attempts = (new PublicCollection)->push($attempt);

        $this->acquireLockAndProcessAttempts($attempts, $handle);
    }
}
