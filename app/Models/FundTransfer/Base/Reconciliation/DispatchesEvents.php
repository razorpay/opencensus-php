<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants\Entity as E;

trait DispatchesEvents
{
    protected function dispatchEventsForSourceAfterRecon(Base\PublicEntity $source)
    {
        // This is a switch instead of magic method calling, intentionally!
        switch ($source->getEntity())
        {
            case E::PAYOUT:
                $this->dispatchEventsForPayoutAfterRecon($source);
                break;
        }
    }

    protected function dispatchEventsForPayoutAfterRecon(Payout\Entity $payout)
    {
        // Todo: Must avoid duplicate webhooks in case reconciliation happens twice, which does/should not for now.
        if ($payout->isStatusProcessedOrReversed() === true)
        {
            // Public event suffix for 'failed' case is 'reversed'.
            $event = $payout->isStatusProcessed() ? 'api.payout.processed' : 'api.payout.reversed';

            $this->app->events->dispatch($event, [$payout]);
        }
    }
}
