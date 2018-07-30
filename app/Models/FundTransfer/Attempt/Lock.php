<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;

class Lock extends Base\Core
{
    const REQUEST_TIMEOUT = 30;

    protected $keySuffix = 'attempt_';

    protected $mutex;

    protected $channel;

    public function __construct(string $channel)
    {
        parent::__construct();

        $this->channel = $channel;

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Gets lock on attempts provided. if lock is already acquired for the same attempt then ignores it
     *
     * @param PublicCollection $attempts
     *
     * @return PublicCollection
     */
    public function lockAttempts(PublicCollection $attempts): PublicCollection
    {
        $attemptIds = $attempts->pluck(Entity::ID);

        //
        // Lock time is calculated based on the total number of attempts and request timeout
        // Additional 10 seconds of offset is added
        //
        $mutexTimeout = ($attempts->count() * self::REQUEST_TIMEOUT) + 10;

        // Get attempts ids to lock
        $lockedAttemptIds = $this->mutex->acquireMultiple(
            $attemptIds, $mutexTimeout, $this->keySuffix);

        $this->trace->info(
            TraceCode::LOCKED_FUND_TRANSFER_ATTEMPTS,
            [
                'channel'                => $this->channel,
                'attempt_ids_locked'     => $lockedAttemptIds['locked'],
                'attempt_ids_not_locked' => $lockedAttemptIds['unlocked'],
            ]);

        // Lock all payments by payment ids
        $lockedAttempts = $attempts->whereIn(Entity::ID, $lockedAttemptIds['locked']);

        // Return final locked attempts
        return $lockedAttempts;
    }

    /**
     * Release the lock on attempt
     *
     * @param Entity $attempt
     */
    public function releaseAttempt(Entity $attempt)
    {
        $this->mutex->release($attempt->getId() . $this->keySuffix);
    }

    public function releaseAttempts(PublicCollection $attempts)
    {
        $this->mutex->releaseMultiple($attempts->getIds(), $this->keySuffix);
    }
}
