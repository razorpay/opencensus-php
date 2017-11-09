<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\FileStore;

class Repository extends Base\Repository
{
    protected $entity = 'batch';

    protected $proxyFetchParamRules = [
        Entity::TYPE        => 'sometimes|string|custom',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::STATUS      => 'sometimes|in:created,processing,processed',
        Entity::GATEWAY     => 'sometimes|string|max:30',
    ];

    protected function validateType($attribute, $value)
    {
        Type::validateType($value);
    }

    /**
     * Finds unprocessed batches to be processed via CRON.
     *
     * We have choose a limit of estimated 10. For now it should work.
     * If needs we'll increase the limit later or change the logic around it.
     *
     * @param integer $limit
     *
     * @return Base\PublicCollection
     */
    public function fetchUnprocessedForCron($limit = 10): Base\PublicCollection
    {
        return $this->newQuery()
                    ->whereIn(Entity::TYPE, Type::$cronGroup)
                    ->where(Entity::STATUS, Status::CREATED)
                    ->where(Entity::PROCESSING, false)
                    ->oldest()
                    ->limit($limit)
                    ->get();
    }

    /**
     * Gets collection of batches for which ufh entity needs to be created i.e.
     * only those batches for which there is no entry in UFH already.
     *
     * @param int|integer $skip
     * @param int|integer $take
     * @param int|null    $createdAtStart
     * @param int|null    $createdAtEnd
     *
     * @return Base\PublicCollection
     */
    public function getBatchesToMigrateToUfh(
        int $skip = 0,
        int $take = 100,
        int $createdAtStart = null,
        int $createdAtEnd = null): Base\PublicCollection
    {
        //
        // Raw SQL:
        //
        // SELECT *
        // FROM batches
        // WHERE
        //      NOT EXISTS (
        //          SELECT 1
        //          FROM files
        //          WHERE
        //              files.entity_id = batches.id
        //              AND files.entity_type = batch
        //          )
        //      AND batches.created_at >= ?
        //      AND batches.created_at <= ?
        //      LIMIT 100
        //      OFFSET 0
        //

        $query = $this->newQuery()
                      ->whereNotExists(function($query)
                        {
                            $idAttr            = $this->dbColumn(Entity::ID);
                            $ufhTableName      = $this->repo->file_store->getTableName();
                            $ufhEntityIdAttr   = $this->repo->file_store->dbColumn(FileStore\Entity::ENTITY_ID);
                            $ufhEntityTypeAttr = $this->repo->file_store->dbColumn(FileStore\Entity::ENTITY_TYPE);

                            $query->selectRaw(1)
                                  ->from($ufhTableName)
                                  ->whereRaw("$ufhEntityIdAttr = $idAttr")
                                  ->where($ufhEntityTypeAttr, $this->entity);
                        });

        $createdAtAttr = $this->dbColumn(Entity::CREATED_AT);

        if ($createdAtStart !== null)
        {
            $query->where($createdAtAttr, '>=', $createdAtStart);
        }

        if ($createdAtEnd !== null)
        {
            $query->where($createdAtAttr, '<=', $createdAtEnd);
        }

        return $query->skip($skip)
                     ->take($take)
                     ->get();
    }
}
