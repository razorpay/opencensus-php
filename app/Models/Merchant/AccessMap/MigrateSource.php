<?php

namespace RZP\Models\Merchant\AccessMap;

use Generator;

use RZP\Modules\Migrate\Source;
use RZP\Modules\Migrate\Record;

class MigrateSource implements Source
{
    const CHUNK_SIZE_IDS = 5000;

    /** {@inheritDoc} */
    public function getParallelOpts(array $opts): Generator
    {
        // 1A. Has mids?
        $mids = $opts['mids'] ?? null;
        if ($mids !== null)
        {
            foreach (array_chunk($mids, self::CHUNK_SIZE_IDS) as $mids)
            {
                yield ['mids' => $mids];
            }

            return;
        }

        // 1B. Else queries table for all entities in chunks and returns mids.
        $afterMid = '';
        while (true)
        {
            $entities = Entity::distinct()
                ->select(Entity::MERCHANT_ID)
                ->where(Entity::MERCHANT_ID, '>', $afterMid)
                ->where(Entity::ENTITY_TYPE, Entity::APPLICATION)
                ->orderBy(Entity::MERCHANT_ID)
                ->take(self::CHUNK_SIZE_IDS)
                ->get();

            if ($entities->count() === 0)
            {
                break;
            }

            $afterMid = $entities->last()->getMerchantId();

            yield ['mids' => array_pluck($entities, Entity::MERCHANT_ID)];
        }
    }

    /** {@inheritDoc} */
    public function iterate(array $opts): Generator
    {
        /** @var \RZP\Base\RepositoryManager */
        $repo = app('repo');

        // Returns entities against given mids..
        $mids = $opts['mids'] ?? null;
        if ($mids !== null)
        {
            $entities = $repo->useSlave(function() use ($repo, $mids)
            {
                return $repo->merchant_access_map->findManyByMerchantIds($mids);
            });
            foreach ($entities as $entity)
            {
                yield new Record($entity->getId(), $entity);
            }

            return;
        }
    }

    /** {@inheritDoc} */
    public function find(Record $targetRecord): ?Record
    {
        // Not needed to implement.
        return null;
    }
}

