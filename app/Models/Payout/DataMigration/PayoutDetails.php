<?php

namespace RZP\Models\Payout\DataMigration;

use RZP\Models\Payout\Entity;
use RZP\Models\PayoutsDetails;

class PayoutDetails
{
    public function getPayoutServicePayoutDetailsForApiPayout(Entity $payout)
    {
        /** @var PayoutsDetails\Entity $payoutDetails */
        $payoutDetails = $payout->payoutsDetails;

        if ($payoutDetails === null)
        {
            return [];
        }

        return [$this->createPayoutServicePayoutDetails($payoutDetails)];
    }

    //TODO: This has to be updated based on the PS side changes which are not yet done at this point of time.
    protected function createPayoutServicePayoutDetails(PayoutsDetails\Entity $payoutDetails)
    {
        return [
            Entity::ID                                       => Entity::generateUniqueId(),
            PayoutsDetails\Entity::PAYOUT_ID                 => $payoutDetails->getPayoutId(),
            PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG => $payoutDetails->getQueueIfLowBalanceFlag(),
            PayoutsDetails\Entity::TDS_CATEGORY_ID           => $payoutDetails->getTdsCategoryId(),
            PayoutsDetails\Entity::TAX_PAYMENT_ID            => $payoutDetails->getTaxPaymentId(),
            PayoutsDetails\Entity::ADDITIONAL_INFO           => json_encode($payoutDetails->getAdditionalInfo()),
            PayoutsDetails\Entity::CREATED_AT                => $payoutDetails->getCreatedAt(),
            PayoutsDetails\Entity::UPDATED_AT                => $payoutDetails->getUpdatedAt(),
        ];
    }
}
