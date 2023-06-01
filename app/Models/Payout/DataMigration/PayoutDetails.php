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
        $additionalInfo = $payoutDetails->getAdditionalInfo();

        /*
         * Making the change this way because saving json_encode(null) in sql db stores something that is not considered
         * null, hence only encoding it for non null values. Ref -
         * https://razorpay.slack.com/archives/C0213P0GQPR/p1684419932480349?thread_ts=1684208329.163069&cid=C0213P0GQPR
         */
        if (empty($additionalInfo) === false)
        {
            $additionalInfo = json_encode($additionalInfo);
        }
        else
        {
            $additionalInfo = null;
        }

        return [
            Entity::ID                                       => Entity::generateUniqueId(),
            PayoutsDetails\Entity::PAYOUT_ID                 => $payoutDetails->getPayoutId(),
            PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG => $payoutDetails->getQueueIfLowBalanceFlag(),
            PayoutsDetails\Entity::TDS_CATEGORY_ID           => $payoutDetails->getTdsCategoryId(),
            PayoutsDetails\Entity::TAX_PAYMENT_ID            => $payoutDetails->getTaxPaymentId(),
            PayoutsDetails\Entity::ADDITIONAL_INFO           => $additionalInfo,
            PayoutsDetails\Entity::CREATED_AT                => $payoutDetails->getCreatedAt(),
            PayoutsDetails\Entity::UPDATED_AT                => $payoutDetails->getUpdatedAt(),
        ];
    }
}
