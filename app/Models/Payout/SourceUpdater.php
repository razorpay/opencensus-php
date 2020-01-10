<?php

namespace RZP\Models\Payout;

use RZP\Jobs\PayoutSourceUpdaterJob;
use RZP\Models\PayoutLink\Core as PayoutLinkCore;

/**
 * Class SourceUpdater
 *
 * This class will check the source that created the payout and push status updates to it.
 *
 * @package RZP\Models\Payout
 */

class SourceUpdater
{
    /**
     * Value in Seconds.
     * We need to delay dispatching payout status update to its source, so that we can ensure
     * that the transaction is completed.
     * In case the transaction fails, then the current and previous status of payout will be the same,
     * and the SourceUpdater will reject the push to source
     */
    const DELAY = 5;

    public static function dispatchToQueue(string $mode, Entity $payout, string $previousStatus = null)
    {
        PayoutSourceUpdaterJob::dispatch($mode,
                                         $payout->getPublicId(),
                                         $previousStatus)
                              ->delay(self::DELAY);
    }

    /**
     * Currently there is only one source, which is the payout link app,
     * and so this will be a direct function call to its core.
     * Later there will be multiple sources, and only this code will need changes.
     * Ex: calling a webhook URL based on the source
     * @param Entity $payout
     * @param string $previousPayoutStatus
     */
    public static function handleUpdateFromQueue(Entity $payout, string $previousPayoutStatus)
    {
        $payoutLink = $payout->payoutLink;

        if (($payoutLink !== null) and
            ($payout->getStatus() !== $previousPayoutStatus))
        {
            (new PayoutLinkCore())->payoutUpdateListener($payoutLink, $payout);
        }
    }
}
