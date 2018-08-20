<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;

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
        $settlementCore = new Settlement\Core;

        foreach ($reconciledRows as $reconciledRow)
        {
            // Entity could be of class Settlement, Refund etc
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

            $settlementCore->triggerSettlementWebhook($entity);
        }
    }

    /**
     * Creates FundTransferAttempt entity for enach payments. Channel is default to YESBANK
     * @param Base\Entity $source - currently refund entity
     * @param $channel = Settlement\Channel::YESBANK
    */
    public function createFundTransferAttempt(
        Base\Entity $source,
        string $channel = Settlement\Channel::YESBANK,
        string $purpose = Purpose::REFUND)
    {
        $fundTransferAttempt = new Entity;

        $fundTransferAttempt->merchant()->associate($source->merchant);

        $fundTransferAttempt->source()->associate($source);

        $fundTransferAttempt->bankAccount()->associate($source->bankAccount);

        $values = [
            Entity::INITIATE_AT     => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::CHANNEL         => $channel,
            Entity::VERSION         => Version::V3,
            Entity::STATUS          => Status::CREATED,
            Entity::PURPOSE         => $purpose,
        ];

        $fundTransferAttempt->fillAndGenerateId($values);

        $this->repo->saveOrFail($fundTransferAttempt);
    }
}
