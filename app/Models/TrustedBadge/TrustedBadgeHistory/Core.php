<?php

namespace RZP\Models\TrustedBadge\TrustedBadgeHistory;

use RZP\Diag\EventCode;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function insertHistory($attributes): void
    {
        $trustedBadgeHistory = new Entity;

        $trustedBadgeHistory->build($attributes);

        $this->repo->saveOrFail($trustedBadgeHistory);
    }

    /**
     * Determine if the merchant has opted-out for the first time.
     *
     * @param string $merchantId
     * @return bool
     */
    public function isFirstTimeOptout(string $merchantId): bool
    {
        $count = $this->repo->trusted_badge_history->fetchHistoryCount($merchantId, '', Entity::OPTOUT);

        return $count === 1;
    }

    /**
     * Determine if the merchant has become eligible for the first time.
     *
     * @param string $merchantId
     * @return bool
     */
    public function isFirstTimeEligible(string $merchantId): bool
    {
        $count = $this->repo->trusted_badge_history->fetchHistoryCount($merchantId, Entity::ELIGIBLE);

        return $count === 1;
    }

    /**
     * Determine if the merchant has become eligible again for the first time after being eligible
     * before, then opting out of RTB & becoming ineligible after that.
     *
     * @param string $merchantId
     * @return bool
     */
    public function isFirstTimeEligibleAfterOptout(string $merchantId): bool
    {
        $count = $this->repo->trusted_badge_history->fetchHistoryCount($merchantId, Entity::ELIGIBLE, Entity::OPTOUT);

        return $count === 2;
    }
}
