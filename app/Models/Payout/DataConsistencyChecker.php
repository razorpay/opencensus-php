<?php

namespace RZP\Models\Payout;

use RZP\Models\Reversal;
use RZP\Trace\TraceCode;
use RZP\Models\PayoutsStatusDetails;

class DataConsistencyChecker extends Core
{
    public function mergePayoutDetails(array $payouts, array $payoutStatusDetails, array $reversals) : array
    {
        $payoutIdPayoutStatusDetailsMap = $this->convertEntitiesToArrayMapByKey(
            $payoutStatusDetails, PayoutsStatusDetails\Entity::PAYOUT_ID);

        $payoutIdReversalMap = $this->convertEntitiesToMapByKey($reversals, Reversal\Entity::PAYOUT_ID);

        foreach ($payouts as $payout)
        {
            $payout[Entity::STATUS_DETAILS] = $payoutIdPayoutStatusDetailsMap[$payout[Entity::ID]] ?? null;
            $payout[Entity::REVERSAL]       = $payoutIdReversalMap[$payout[Entity::ID]] ?? null;
        }

        return $payouts;
    }

    protected function convertEntitiesToMapByKey(array $entityArray, string $key) : array
    {
        $entityMapByKey = array();

        foreach ($entityArray as $entity)
        {
            $entityKey = Entity::stripDefaultSign($entity[$key]);

            $entityMapByKey[$entityKey] = $entity;
        }

        return $entityMapByKey;
    }

    protected function convertEntitiesToArrayMapByKey(array $entityArray, string $key) : array
    {
        $entityMapArrByKey = array();

        foreach ($entityArray as $entity)
        {
            $entityKey = Entity::stripDefaultSign($entity[$key]);

            if (array_key_exists($entityKey, $entityMapArrByKey) == false)
            {
                $entityMapArrByKey[$entityKey] = [];
            }

            array_push($entityMapArrByKey[$entityKey], $entity);
        }

        return $entityMapArrByKey;
    }
}
