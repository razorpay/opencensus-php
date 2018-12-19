<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants\Entity as E;

trait FiresWebhook
{
    protected function fireWebhookForSourceAfterRecon(Base\PublicEntity $source)
    {
        // This is a switch instead of magic method calling, intentionally!
        switch ($source->getEntity())
        {
            case E::PAYOUT:
                $this->fireWebhookForPayoutAfterRecon();
                break;
        }
    }

    protected function fireWebhookForPayoutAfterRecon(Payout\Entity $payout)
    {
        if ($payout->isStatusProcessedOrFailed() === true)
        {
            // Public event suffix for processed case is succeeded.
            $event = $payout->isStatusProcessed() ? 'api.payout.succeeded' : 'api.payout.failed';

            $this->app->events->fire($event, [$payout]);
        }
    }
}
