<?php

namespace RZP\Models\Merchant\AccessMap;

use Generator;

use RZP\Modules\Migrate\Source;
use RZP\Modules\Migrate\Record;
use \RZP\Models\Merchant\MerchantApplications;

class MigrateImpersonationSource implements Source
{
    const CHUNK_SIZE_IDS = 5000;

    /** {@inheritDoc} */
    public function getParallelOpts(array $opts): Generator
    {
        // 1A. Has mids?
        $ids = $opts['ids'] ?? null;
        if ($ids !== null)
        {
            foreach (array_chunk($ids, self::CHUNK_SIZE_IDS) as $ids)
            {
                yield ['ids' => $ids];
            }

            return;
        }

        // 1B. Else queries table for all entities in chunks and returns mids.
        $afterId = '';
        $repo = app('repo');
        while (true)
        {

            $entities = $repo->merchant_access_map->getAllMappingsByApplicationTypeWithTrashed(
                MerchantApplications\Entity::MANAGED,
                $afterId,
                self::CHUNK_SIZE_IDS);

            if ($entities->count() === 0)
            {
                break;
            }

            $afterId = $entities->last()->getId();

            yield ['ids' => array_pluck($entities, Entity::ID)];
        }
    }

    /** {@inheritDoc} */
    public function iterate(array $opts): Generator
    {
        /** @var \RZP\Base\RepositoryManager */
        $repo = app('repo');

        // Returns entities against given mids..
        $ids = $opts['ids'] ?? null;
        if ($ids !== null)
        {
            $entities = $repo->useSlave(function() use ($repo, $ids)
            {
                return $repo->merchant_access_map->findManyWithTrashed($ids);
            });
            foreach ($entities as $entity)
            {
                yield new Record($entity->getId(), $entity);
            }

            return;
        }
    }

    /** {@inheritDoc} */
    public function find(Record $record): ?Record
    {
        $id = $record->value->getId();
        return $repo->merchant_access_map->findWithTrashed($id);
    }

    /** {@inheritDoc} */
    public function getAction(Record $record): string
    {
        return is_null($record->value->getDeletedAt()) ? 'create' : 'delete';
    }
}
