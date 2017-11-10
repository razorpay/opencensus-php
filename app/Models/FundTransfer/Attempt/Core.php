<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Settlement;

class Core extends Base\Core
{
    /**
     * Takes an array of the reconciled rows as an input, each of them having 2 keys
     *   - entity
     *   - fire_webhook
     * Sends a webhook to notify the merchant about the settlement
     *
     * @param array $reconciledRows
     */
    public function notifyMerchantViaWebhook(array $reconciledRows)
    {
        foreach ($reconciledRows as $reconciledRow)
        {
            $entity = $reconciledRow['entity'];

            $fireWebhook = $reconciledRow['fire_webhook'];

            if ($fireWebhook === false)
            {
                continue;
            }

            // Allow only the settlement entities
            if ($entity->getEntityName() !== Constants\Entity::SETTLEMENT)
            {
                continue;
            }

            (new Settlement\Core)->triggerSettlementWebhook($entity);
        }
    }
}