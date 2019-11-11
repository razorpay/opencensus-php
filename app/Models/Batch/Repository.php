<?php

namespace RZP\Models\Batch;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

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

    protected function addQueryOrder($query)
    {
        $query->orderBy($this->dbColumn(Entity::CREATED_AT), 'desc')
              ->orderBy($this->dbColumn(Entity::ID), 'desc');
    }

    private function ignoreParamCountAndSkip($params): bool
    {
        return (($this->auth->isAdminAuth() === false)
                && (isset($params['type']))
                && ($this->app->batchService->isMigratingBatchType($params['type']) === true)
                && ($this->app->batchService->shouldBatchServiceBeCalled()));
    }

    private function buildQueryFromParams(array $params, string $merchantId = null, bool $useSlave = false)
    {
        // Process params (sanitization, validation, modification, etc.)
        $this->processFetchParams($params);

        $expands = $this->getExpandsForQueryFromInput($params);

        $query = $this->newQuery();

        if ($useSlave === true)
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection());
        }

        $query = $query->with($expands);

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        $this->setEsRepoIfExist();

        // Splits the params into mysqlParams and esParams. Check methods doc on
        // how that happens.
        list($mysqlParams, $esParams) = $this->getMysqlAndEsParams($params);

        // If we find that there are es params then we do es search.
        // Currently (as commented in getMysqlAndEsParams method) we raise bad
        // request error if we get mix of MySQL and es params. Later we might support
        // such thing.
        if (count($esParams) > 0)
        {
            return $this->runEsFetch($esParams, $merchantId, $expands);
        }

        // If above doesn't happen we build query for mysql fetch and return the
        // result.
        $query = $this->buildFetchQuery($query, $mysqlParams);

        return $query;
    }

    /**
     * @param array $params
     * @param string|null $merchantId
     * @return mixed
     *
     * SELECT batches.id AS batch_id,
     * batches.gateway,
     * batches.processing,
     * batches.status,
     * batches.total_count,
     * batches.processed_count,
     * batches.success_count,
     * batches.failure_count,
     * batches.failure_reason,
     * files.id AS input_file_id,
     * files.name AS input_file_name,
     * files.size AS input_file_size,
     * 'outputfiles' AS output_files,
     * batches.attempts,
     * batches.processed_at,
     * batches.created_at,
     * batches.updated_at
     * FROM `batches`
     * INNER JOIN `files` ON `batches`.`id` = `files`.`entity_id`
     * WHERE `batches`.`type` = 'reconciliation'
     * AND `files`.`type` = 'reconciliation_batch_input'
     * GROUP BY `batches`.`id`,
     * `files`.`id`
     * ORDER BY `batches`.`created_at` DESC,
     * `batches`.`id` DESC
     * LIMIT 1000
     */
    public function getReconBatchesWithFiles(array $params, string $merchantId = null)
    {
        $query = $this->buildQueryFromParams($params, $merchantId, true);

        $batchIdColumn          = $this->dbColumn(Entity::ID);

        $batchTypeColumn        = $this->dbColumn(Entity::TYPE);

        $batchGatewayColumn     = $this->dbColumn(Entity::GATEWAY);

        $batchProcessingColumn  = $this->dbColumn(Entity::PROCESSING);

        $batchStatusColumn      = $this->dbColumn(Entity::STATUS);

        $batchTotalCount        = $this->dbColumn(Entity::TOTAL_COUNT);

        $batchProcessedCount    = $this->dbColumn(Entity::PROCESSED_COUNT);

        $batchSuccessCount      = $this->dbColumn(Entity::SUCCESS_COUNT);

        $batchFailureCount      = $this->dbColumn(Entity::FAILURE_COUNT);

        $batchFailureReason     = $this->dbColumn(Entity::FAILURE_REASON);

        $batchAttempts          = $this->dbColumn(Entity::ATTEMPTS);

        $batchProcessedAt       = $this->dbColumn(Entity::PROCESSED_AT);

        $batchUpdatedAt         = $this->dbColumn(Entity::UPDATED_AT);

        $batchCreatedAt         = $this->dbColumn(Entity::CREATED_AT);

        $fileIdColumn           = $this->repo->file_store->dbColumn(FileStore\Entity::ID);

        $fileNameColumn         = $this->repo->file_store->dbColumn(FileStore\Entity::NAME);

        $fileSizeColumn         = $this->repo->file_store->dbColumn(FileStore\Entity::SIZE);

        $fileEntityIdColumn     = $this->repo->file_store->dbColumn(FileStore\Entity::ENTITY_ID);

        $fileTypeColumn         = $this->repo->file_store->dbColumn(FileStore\Entity::TYPE);

        $params1 = $batchIdColumn . ' as batch_id,' . $batchGatewayColumn . ',' . $batchProcessingColumn
                   . ',' . $batchStatusColumn . ',' . $batchTotalCount . ',' . $batchProcessedCount
                   . ',' . $batchSuccessCount . ',' . $batchFailureCount . ',' . $batchFailureReason;

        $params2 = $fileIdColumn . ' as input_file_id, '
                   . $fileNameColumn . ' as input_file_name, '
                   . $fileSizeColumn . ' as input_file_size, '
                   . '\'outputfiles\' as output_files';

        $params3 = $batchAttempts . ',' . $batchProcessedAt . ',' . $batchCreatedAt . ',' . $batchUpdatedAt;

        return $query->selectRaw($params1 . ', ' . $params2 . ', ' . $params3)
                     ->join(Table::FILE_STORE, $batchIdColumn, '=', $fileEntityIdColumn)
                     ->where($batchTypeColumn, '=', Batch\Type::RECONCILIATION)
                     ->where($fileTypeColumn, '=', FileStore\Type::RECONCILIATION_BATCH_INPUT)
                     ->groupBy($batchIdColumn, $fileIdColumn)
                     ->get();
    }

    public function getReconFilesCountByGateway(array $params)
    {
        $fileEntityIdColumn    = $this->repo->file_store->dbColumn(FileStore\Entity::ENTITY_ID);

        $fileIdColumn          = $this->repo->file_store->dbColumn(FileStore\Entity::ID);

        $fileTypeColumn        = $this->repo->file_store->dbColumn(FileStore\Entity::TYPE);

        $batchTypeColumn       = $this->dbColumn(Entity::TYPE);

        $batchIdColumn         = $this->dbColumn(Entity::ID);

        $batchGatewayColumn    = $this->dbColumn(Entity::GATEWAY);

        $batchStatusColumn     = $this->dbColumn(Entity::STATUS);

        $batchTotalCount       = $this->dbColumn(Entity::TOTAL_COUNT);

        $batchProcessedCount   = $this->dbColumn(Entity::PROCESSED_COUNT);

        $batchSuccessCount     = $this->dbColumn(Entity::SUCCESS_COUNT);

        $batchFailureCount     = $this->dbColumn(Entity::FAILURE_COUNT);

        $query = $this->newQuery();

        $query->selectRaw($batchGatewayColumn.','.$batchStatusColumn.','
                 .'COUNT('.$fileIdColumn.') as num_of_files,SUM('.$batchTotalCount.') as total_count,SUM('
                 .$batchProcessedCount.')as processed_count,SUM('.$batchSuccessCount.') as success_count,SUM('
                 .$batchFailureCount.') as failure_count')
              ->join(Table::FILE_STORE, $batchIdColumn, '=', $fileEntityIdColumn)
              ->where($batchTypeColumn, '=', Batch\Type::RECONCILIATION)
              ->where($fileTypeColumn, '=', FileStore\Type::RECONCILIATION_BATCH_INPUT);

        $this->buildQueryWithParams($query, $params);

        $query->groupBy([$batchGatewayColumn, $batchStatusColumn]);

        return $query->get();
    }
}
