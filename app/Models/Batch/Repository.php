<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Base\BuilderEx;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;

class Repository extends Base\Repository
{
    protected $entity = 'batch';

    protected $proxyFetchParamRules = [
        Entity::TYPE        => 'sometimes|string|custom',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::STATUS      => 'sometimes|in:created,partially_processed,processed,failed',
        Entity::GATEWAY     => 'sometimes|string|max:30',
        Entity::TYPE        => 'sometimes|string|required_with:sub_type',
        Entity::SUB_TYPE    => 'sometimes|string',
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

    protected function addQueryParamTypes(BuilderEx $query, array $params)
    {
        $typeAttribute = $this->dbColumn(Entity::TYPE);

        $query->whereIn($typeAttribute, $params[Entity::TYPES]);
    }

    protected function addQueryParamId(BuilderEx $query, array $params)
    {
        $id = $params[Entity::ID];

        $idColumn = $this->dbColumn(Entity::ID);

        Entity::verifyIdAndStripSign($id);

        $query->where($idColumn, $id);
    }

    /**
     * @param $query
     * @param $params
     *
     *  For Type PaymentLink, merging all batches from new
     *  batch service and API database. Hence not using count and skip
     *  while extracting from db. Custom pagination has been added in BatchMicroService class
     *  For Admin Auth, we are having different admin fetch entity for fileStore and Batch.
     *  Hence forwarding the skip and count.
     */
    protected function addQueryParamSkip($query, $params)
    {
        if ($this->ignoreParamCountAndSkip($params))
        {
            $this->trace->info(TraceCode::GET_BATCHES_IGNORE_COUNT_SKIP, ["[Batch\Repository]-addQueryParamSkip", $query, $params]);
        }
        else
        {
            parent::addQueryParamSkip($query, $params);
        }
    }

    /**
     * @param $query
     * @param $params
     *
     * Same as above comments
     */
    protected function addQueryParamCount($query, $params)
    {
        if ($this->ignoreParamCountAndSkip($params))
        {
            $this->trace->info(TraceCode::GET_BATCHES_IGNORE_COUNT_SKIP, ["[Batch\Repository]-addQueryParamCount", $query, $params]);
        }
        else
        {
            parent::addQueryParamCount($query, $params);
        }
    }

    private function ignoreParamCountAndSkip($params): bool
    {
        return (($this->auth->isAdminAuth() === false)
                && (isset($params['type']))
                && ($this->app->batchService->isMigratedBatchType($params['type']) === true)
                && ($this->app->batchService->shouldBatchServiceBeCalled()));
    }
}
