<?php

namespace RZP\Models\Payout\DualWrite;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\PayoutsStatusDetails\Entity;

class PayoutStatusDetails extends Base
{
    public function dualWritePSPayoutStatusDetails(string $payoutId)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_STATUS_DETAILS_INIT,
            ['payout_id' => $payoutId]
        );

        /** @var array $psPayoutStatusDetails */
        $psPayoutStatusDetails = $this->getAPIPayoutStatusDetailsFromPayoutService($payoutId);

        /** @var PublicCollection $apiPayoutStatusDetails */
        $apiPayoutStatusDetails = $this->repo->payouts_status_details->fetchPayoutStatusDetailsByPayoutId($payoutId);

        /**
         * @var  $id                    string
         * @var  $apiPayoutStatusDetail Entity
         */
        foreach ($apiPayoutStatusDetails as $apiPayoutStatusDetail)
        {
            $id = $apiPayoutStatusDetail->getId();

            if (array_key_exists($id, $psPayoutStatusDetails) === true)
            {
                $apiPayoutStatusDetail->setRawAttributes($psPayoutStatusDetails[$id]->getAttributes());

                $this->repo->payouts_status_details->saveOrFail($apiPayoutStatusDetail);

                unset($psPayoutStatusDetails[$id]);
            }
        }

        foreach ($psPayoutStatusDetails as $payoutStatusDetails)
        {
            $this->repo->payouts_status_details->saveOrFail($payoutStatusDetails);
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_STATUS_DETAILS_DONE,
            ['payout_id' => $payoutId]
        );
    }

    public function getAPIPayoutStatusDetailsFromPayoutService(string $payoutId)
    {
        $payoutServiceStatusDetails = $this->repo->payouts_status_details->getPayoutServiceStatusDetails($payoutId);

        if (count($payoutServiceStatusDetails) === 0)
        {
            return [];
        }

        $psStatusDetails = [];

        foreach ($payoutServiceStatusDetails as $payoutServiceStatusDetail)
        {
            // converts the stdClass object into associative array.
            $this->attributes = get_object_vars($payoutServiceStatusDetail);

            $this->processModifications();

            $entity = new Entity;

            $entity->setRawAttributes($this->attributes, true);

            // Explicitly setting the connection.
            $entity->setConnection($this->mode);

            // This will ensure that updated_at columns are not overridden by saveOrFail.
            $entity->timestamps = false;

            $psStatusDetails[$entity->getId()] = $entity;
        }

        return $psStatusDetails;
    }
}
