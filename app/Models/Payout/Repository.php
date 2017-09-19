<?php

namespace RZP\Models\Payout;

use RZP\Models\Base;
use RZP\Exception;

class Repository extends Base\Repository
{
    protected $entity = 'payout';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID        => 'sometimes|alpha_num',
        Entity::CUSTOMER_ID        => 'sometimes|string|max:19',
        Entity::DESTINATION        => 'sometimes|string|max:20',
        Entity::METHOD             => 'sometimes|string',
    ];

    public function fetchCreatedPayouts($timestamp, $method)
    {
        return $this->newQuery()
                    ->with('destination')
                    ->where(Entity::CREATED_AT, '<', $timestamp)
                    ->where(Entity::STATUS, '=', Status::CREATED)
                    ->where(Entity::METHOD, '=', $method)
                    ->orderBy(Entity::ID)
                    ->get();
    }

    public function updateStatus(Base\PublicCollection $payouts, string $status)
    {
        if ($payouts->count() === 0)
        {
            return;
        }

        $IdsToUpdate = $payouts->getIds();

        $updatedCount = $this->newQuery()
                             ->whereIn(Entity::ID, $IdsToUpdate)
                             ->update([
                                    Entity::STATUS  => $status
                                ]);

        $expectedCount = count($IdsToUpdate);

        if ($updatedCount !== $expectedCount)
        {
            throw new Exception\LogicException(
                'Failed to update expected number of payout records.',
                null,
                [
                    'expected' => $expectedCount,
                    'updated'  => $updatedCount,
                ]);
        }

        return $updatedCount;
    }

    public function addQueryParamDestination($query, $params)
    {
        $destinationId = $params[Entity::DESTINATION];

        Entity::stripSignWithoutValidation($destinationId);

        $query->where(Entity::DESTINATION_ID, $destinationId);
    }
}
