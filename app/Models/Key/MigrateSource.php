<?php

namespace RZP\Models\Key;

use Generator;
use DB;

use RZP\Modules\Migrate\Source;
use RZP\Modules\Migrate\Record;

class MigrateSource implements Source
{
    const CHUNK_SIZE_IDS = 5000;

    /** {@inheritDoc} */
    public function getParallelOpts(array $opts): Generator
    {
        // 1A. Has ids?
        $ids = $opts['ids'] ?? null;
        if ($ids !== null)
        {
            foreach (array_chunk($ids, self::CHUNK_SIZE_IDS) as $ids)
            {
                yield ['ids' => $ids];
            }

            return;
        }

        // 1B. Has mids?
        $mids = $opts['mids'] ?? null;
        if ($mids !== null)
        {
            foreach (array_chunk($mids, self::CHUNK_SIZE_IDS) as $mids)
            {
                yield ['mids' => $mids];
            }

            return;
        }

        // 1C. Else queries keys table for all ids in chunks and returns.
        // Because id of keys are not ordered, can not use after_id approach.
        $iter = 0;
        while (true)
        {
            $offset   = $iter * self::CHUNK_SIZE_IDS;
            $limit    = self::CHUNK_SIZE_IDS;
            $results  = DB::select(DB::RAW("SELECT id FROM `keys` order by created_at limit $offset, $limit"));
            $entities = Entity::hydrate($results);

            if ($entities->count() === 0)
            {
                break;
            }

            $iter++;

            yield ['ids' => $entities->getIds()];
        }
    }

    /** {@inheritDoc} */
    public function iterate(array $opts): Generator
    {
        /** @var Repository */
        $repo = app('repo')->key;

        // 1A. Either returns keys against given ids..
        $ids = $opts['ids'] ?? null;
        if ($ids !== null)
        {
            $key_ids = "'" . implode("','", $ids) . "'";
            $results = DB::select(DB::RAW("SELECT * FROM `keys` WHERE id in ($key_ids)"));
            $keys    = Entity::hydrate($results);

            foreach ($keys as $key)
            {
                yield new Record($key->getId(), $key);
            }

            return;
        }

        // 1B. Or returns keys against given merchant ids.
        $mids = $opts['mids'] ?? null;
        if ($mids !== null)
        {
            $select_mids = "'" . implode("','", $mids) . "'";
            $results = DB::select(DB::RAW("SELECT * FROM `keys` WHERE merchant_id in ($select_mids)"));
            $keys = Entity::hydrate($results);

            foreach ($keys as $key)
            {
                yield new Record($key->getId(), $key);
            }

            return;
        }
    }

    /** {@inheritDoc} */
    public function find(Record $targetRecord): ?Record
    {
        // TODO: To implement for recon.
        return null;
    }
}
