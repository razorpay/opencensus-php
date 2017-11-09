<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Settlement;

class Core extends Base\Core
{
    public function notifyMarketplaceMerchantViaWebhook($reconciledRows)
    {
        $settlementCore = new Settlement\Core;

        foreach ($reconciledRows as $reconciledRow)
        {
            $entity = $reconciledRow['entity'];

            $fireWebhook = $reconciledRow['fire_webhook'];

            // Allow only the settlement entities
            if ($entity->getEntityName() !== Constants\Entity::SETTLEMENT)
            {
                continue;
            }

            // Proceed only if the settlement was made to a linked account
            if ($entity->merchant->isLinkedAccount() === false)
            {
                continue;
            }

            // Proceed only the settlement has successfully processed
            if ($entity->isStatusProcessed() === false)
            {
                continue;
            }

            if ($fireWebhook === false)
            {
                return;
            }

            $setlTxns = $entity->setlTransactions;

            $paymentTxns = [];

            foreach($setlTxns as $txn)
            {
                // For linked accounts, the only source of payment is through a transfer from the parent
                // Notify parent about the settlement
                if ($txn->isTypePayment() === true)
                {
                    $paymentTxns[] = $txn;
                }
            }

            if (empty($paymentTxns) === false)
            {
                $settlementCore->sendSettlementProcessedWebhook($entity, $paymentTxns);
            }
        }
    }
}