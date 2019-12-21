<?php

namespace RZP\Models\Payout;

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
    protected $payout;

    public function __construct(Entity $payout)
    {
        $this->payout = $payout;
    }

    /**
     * Currently there is only one source, which is the payout link app,
     * and so this will be a direct function call to its core.
     * Later there will be multiple sources, and only this code will need changes.
     * Ex: calling a webhook URL based on the source
     */
    public function update()
    {
        $payoutLink = $this->payout->payoutLink;

        if ($payoutLink !== null)
        {
            (new PayoutLinkCore())->payoutUpdateListener($payoutLink->getId(), $this->payout->getStatus());
        }

    }
}
