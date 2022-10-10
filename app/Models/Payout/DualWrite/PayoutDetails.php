<?php

namespace RZP\Models\Payout\DualWrite;

use RZP\Trace\TraceCode;
use RZP\Models\PayoutsDetails\Entity;

class PayoutDetails extends Base
{
    protected $columnsToUnset = [
        Entity::ID
    ];

    public function dualWritePSPayoutDetails(string $id)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_DETAILS_INIT,
            ['payout_id' => $id]
        );

        $psPayoutsDetails = $this->getAPIPayoutsDetailsFromPayoutService($id);

        if (empty($psPayoutsDetails) === true)
        {
            return null;
        }

        /** @var Entity $apiPayoutsDetails */
        $apiPayoutsDetails = $this->repo->payouts_details->find($id);

        if (empty($apiPayoutsDetails) === true)
        {
            $this->repo->payouts_details->saveOrFail($psPayoutsDetails);

            return $psPayoutsDetails;
        }

        $apiPayoutsDetails->setRawAttributes($psPayoutsDetails->getAttributes());

        $this->repo->saveOrFail($apiPayoutsDetails);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_DETAILS_DONE,
            ['payout_id' => $id]
        );

        return $apiPayoutsDetails;
    }

    public function getAPIPayoutsDetailsFromPayoutService(string $payoutId)
    {
        $payoutServicePayoutDetails = $this->repo->payouts_details->getPayoutServicePayoutDetails($payoutId);

        if (count($payoutServicePayoutDetails) === 0)
        {
            return null;
        }

        $psPayoutDetails = $payoutServicePayoutDetails[0];

        // converts the stdClass object into associative array.
        $this->attributes = get_object_vars($psPayoutDetails);

        $this->processModifications();

        $entity = new Entity;

        $entity->setRawAttributes($this->attributes, true);

        // Explicitly setting the connection.
        $entity->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $entity->timestamps = false;

        return $entity;
    }
}
