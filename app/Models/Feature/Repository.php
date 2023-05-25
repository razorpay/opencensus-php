<?php

namespace RZP\Models\Feature;

use Razorpay\Trace\Logger;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Models\Feature\Metric as FeatureMetric;
use RZP\Models\Merchant;
use RZP\Exception;
use Illuminate\Support\Collection;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Dcs\Features\Constants as DcsFeaturesConstants;
use RZP\Services\Dcs\Features\Service;
use RZP\Models\Base\EsRepository;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Settlement\OndemandFundAccount;
use RZP\Trace\TraceCode;
use function PHPUnit\Framework\isEmpty;

class Repository extends Base\Repository
{
    use CacheQueries;

    protected $entity = 'feature';

    protected $appFetchParamRules = array(
        Entity::ENTITY_ID   => 'sometimes|string|max:14',
        Entity::ENTITY_TYPE => 'sometimes|string|max:255',
        Entity::NAME        => 'sometimes|string|max:25'
    );

    public function fetchByEntityTypeAndEntityId(string $entityType, string $entityId, string $mode = null)
    {
        $cacheTtl = $this->getCacheTtl();
        $cacheTags = Entity::getCacheTagsForEntities($entityType, $entityId);

        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);

        $apiResponse = $query->where(Entity::ENTITY_TYPE, $entityType)
            ->where(Entity::ENTITY_ID, $entityId)
            ->remember($cacheTtl)
            ->cacheTags($cacheTags)
            ->get();

        $dcs = $this->app['dcs'];
        $dcsResponse = $dcs->getDcsEnabledFeatures($entityType, $entityId, $mode);

        return $apiResponse->concat($dcsResponse)->unique(Entity::NAME);
    }

    public function findByEntityTypeEntityIdAndNameOrFail(string $entityType, string $entityId, string $featureName)
    {
        if (DcsFeaturesConstants::isDcsReadEnabledFeature($featureName, false, "", $this->app->isEnvironmentProduction()) === true)
        {
            $dimension = [
                'feature_name' => $featureName,
                'mode' => $this->getAppMode(),
                'function' => __FUNCTION__
            ];
            try
            {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_TOTAL, $dimension);
                $dcs = $this->app['dcs'];
                $res = $dcs->fetchByEntityIdAndName($entityId, $featureName, $this->getAppMode());
                if (empty($res) === true)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, []);
                }
                return $res;
            }
            catch(\Throwable $e)
            {
                if ($e->getCode() === ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND)
                {
                    throw $e;
                }
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_FAILURE_TOTAL, $dimension);
                $this->trace->traceException($e, Logger::ERROR, TraceCode::DCS_READ_FEATURES_FAILURE);
            }
        }

        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->firstOrFailPublic();
    }

    public function findByEntityTypeEntityIdAndName(string $entityType, string $entityId, string $featureName)
    {
        if (DcsFeaturesConstants::isDcsReadEnabledFeature($featureName,
                false, "", $this->app->isEnvironmentProduction()) === true)
        {
            $dimension = [
                'feature_name' => $featureName,
                'mode' => $this->getAppMode(),
                'function' => __FUNCTION__
            ];

            try
            {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_TOTAL, $dimension);
                $dcs = $this->app['dcs'];
                return $dcs->fetchByEntityIdAndName($entityId, $featureName, $this->getAppMode());
            }
            catch(\Throwable $e)
            {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_FAILURE_TOTAL, $dimension);
                $this->trace->traceException($e, Logger::ERROR, TraceCode::DCS_READ_FEATURES_FAILURE);
            }
        }

        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();
    }

    public function findByEntityIdAndNameOnConnection(string $entityId, string $featureName, string $mode)
    {
        if (DcsFeaturesConstants::isDcsReadEnabledFeature($featureName, false, "", $this->app->isEnvironmentProduction()) === true)
        {
            $dimension = [
                'feature_name' => $featureName,
                'mode' => $this->getAppMode(),
                'function' => __FUNCTION__
            ];

            try
            {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_TOTAL, $dimension);
                $dcs = $this->app['dcs'];
                return $dcs->fetchByEntityIdAndName($entityId, $featureName, $this->getAppMode());
            }
            catch(\Throwable $e)
            {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_FAILURE_TOTAL, $dimension);
                $this->trace->traceException($e, Logger::ERROR, TraceCode::DCS_READ_FEATURES_FAILURE);
            }
        }

        return $this->newQueryWithConnection($mode)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();
    }

    public function findMerchantsHavingFeatures(array $featureNames, $limit = null, $afterId = null)
    {
        $query = $this->newQuery()
                      ->whereIn(Entity::NAME, $featureNames)
                      ->where(Entity::ENTITY_TYPE, 'merchant')
                      ->orderBy(Entity::ID);

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where(Entity::ID, '>', $afterId);
        }

        return $query->get();
    }

    /**
     * @param string $featureName
     * @param int    $skip
     * @param int    $limit
     *
     * @return array
     */
    public function fetchPaginatedPartnerIdsWithFeature(string $featureName, int $skip, int $limit): array
    {
        return $this->newQuery()
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->orderBy(Entity::ID)
                    ->skip($skip)
                    ->take($limit)
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function findMerchantWithFeatures(string $merchantId, array $featureNames)
    {
        $dcsRes = new PublicCollection();
        $apiFeatures = $featureNames;
        $dimension = [
            'feature_name' => 'many',
            'mode' => $this->getAppMode(),
            'function' => __FUNCTION__
        ];

        try
        {
            $dcsFeatures = array_intersect($featureNames, array_keys(
                DcsFeaturesConstants::dcsReadEnabledFeaturesByEntityType(
                    Constants::MERCHANT,false, $this->app->runningUnitTests(), $this->app->isEnvironmentProduction())
            ));
            if (sizeof($dcsFeatures) !== 0) {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_TOTAL, $dimension);
                $dcs = $this->app['dcs'];
                $response = $dcs->fetchByEntityIdAndFeatureNames($merchantId, $dcsFeatures, $this->getAppMode());
                $dcsRes = new PublicCollection($response);
                $apiFeatures = array_diff($featureNames, $dcsFeatures);
                if (sizeof($apiFeatures) === 0)
                {
                    return $dcsRes;
                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_FAILURE_TOTAL, $dimension);
            $this->trace->traceException($e, Logger::ERROR, TraceCode::DCS_READ_FEATURES_FAILURE);
        }
        $apiResponse = $this->newQuery()
            ->select(Entity::NAME)
            ->whereIn(Entity::NAME, $apiFeatures)
            ->where(Entity::ENTITY_TYPE, 'merchant')
            ->where(Entity::ENTITY_ID, $merchantId)
            ->get();
        return $apiResponse->concat($dcsRes)->unique(Entity::NAME);
    }

    public function findMerchantWithFeaturesOnConnection(string $merchantId, array $featureNames, $mode)
    {
        $dcsRes = [];
        $apiFeatures = $featureNames;
        $dimension = [
            'feature_name' => 'many',
            'mode' => $this->getAppMode(),
            'function' => __FUNCTION__
        ];

        try
        {
            $dcsFeatures = array_intersect($featureNames, array_keys(
                DcsFeaturesConstants::dcsReadEnabledFeaturesByEntityType(
                    Constants::MERCHANT, $this->app->runningUnitTests(), $this->app->isEnvironmentProduction())
            ));
            if (sizeof($dcsFeatures) !== 0) {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_TOTAL, $dimension);
                $dcs = $this->app['dcs'];
                $response = $dcs->fetchByEntityIdAndFeatureNames($merchantId, $dcsFeatures, $mode);
                $dcsRes = (new PublicCollection($response))->pluck(Entity::NAME)->toArray();
                $apiFeatures = array_diff($featureNames, $dcsFeatures);
                if (sizeof($apiFeatures) === 0) {
                    return $dcsRes;
                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_FAILURE_TOTAL, $dimension);
            $this->trace->traceException($e, Logger::ERROR, TraceCode::DCS_READ_FEATURES_FAILURE);
        }

        $apiResponse = $this->newQueryWithConnection($mode)
                            ->whereIn(Entity::NAME, $apiFeatures)
                            ->where(Entity::ENTITY_TYPE, 'merchant')
                            ->where(Entity::ENTITY_ID, $merchantId)
                            ->pluck(Entity::NAME)
                            ->toArray();
        return array_unique(array_merge($apiResponse, $dcsRes));
    }

    /**
     * Get a list a merchant ids that have the given features enabled.
     *
     * @param string[] $featureNames
     *
     * @return string[]
     */
    public function findMerchantIdsHavingFeatures(array $featureNames): array
    {
        return $this->newQuery()
            ->select(Entity::ENTITY_ID)
            ->whereIn(Entity::NAME, $featureNames)
            ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
            ->pluck(Entity::ENTITY_ID)
            ->toArray();
    }

    public function fetchMerchantIdsWithFeatureInChunks(string $featureName, $skip, $limit)
    {
        return $this->newQuery()
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->skip($skip)
                    ->take($limit)
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function fetchEntityIdsWithFeatureInChunks(string $featureName,string $entityType, $skip, $limit)
    {
        return $this->newQuery()
            ->where(Entity::NAME, $featureName)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->skip($skip)
            ->take($limit)
            ->pluck(Entity::ENTITY_ID)
            ->toArray();
    }

    public function fetchMerchantIdsWithFeatureWithPagination(string $featureName,
                                                              $skip,
                                                              $limit,
                                                              $from = null,
                                                              $to = null)
    {
        $query = $this->newQuery()
                      ->where(Entity::NAME, $featureName)
                      ->where(Entity::ENTITY_TYPE, Constants::MERCHANT);

        if (empty($from) === false)
        {
            $query->where(Entity::CREATED_AT, '>=', $from);
        }

        if (empty($to) === false)
        {
            $query->where(Entity::CREATED_AT, '<', $to);
        }

        return $query->skip($skip)
                     ->take($limit)
                     ->pluck(Entity::ENTITY_ID)
                     ->toArray();
    }

    public function fetchMerchantIdsWithFeatureAndNoFundAccountInChunks(string $featureName)
    {
        return $this->newQuery()
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->leftJoin(Table::SETTLEMENT_ONDEMAND_FUND_ACCOUNT, OndemandFundAccount\Entity::MERCHANT_ID, Entity::ENTITY_ID)
                    ->where(function ($query) {
                        $query->whereNull(OndemandFundAccount\Entity::FUND_ACCOUNT_ID)
                              ->orWhere(OndemandFundAccount\Entity::FUND_ACCOUNT_ID, '=', '');
                    })
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function getMerchantIdsHavingFeature(string $featureName, array $merchantIds)
    {
        if (DcsFeaturesConstants::isDcsReadEnabledFeature($featureName, false, "", $this->app->isEnvironmentProduction()) === true)
        {
            $dimension = [
                'feature_name' => $featureName,
                'mode' => $this->getAppMode(),
                'function' => __FUNCTION__
            ];

            try
            {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_TOTAL, $dimension);
                $dcs = $this->app['dcs'];
                $res = $dcs->fetchByEntityIdsAndName($merchantIds, $featureName, $this->getAppMode());
                $dcsRes = new PublicCollection($res);
                return $dcsRes->pluck(Entity::ENTITY_ID)->toArray();
            }
            catch(\Throwable $e)
            {
                $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_FAILURE_TOTAL, $dimension);
                $this->trace->traceException($e, Logger::ERROR, TraceCode::DCS_READ_FEATURES_FAILURE);
            }
        }

        return $this->newQuery()
                    ->select(Entity::ENTITY_ID)
                    ->whereIn(Entity::ENTITY_ID, $merchantIds)
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, 'merchant')
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function saveAndSyncIfApplicableOrFail(Entity $feature, array $assignedFeatureNames, bool $shouldSync)
    {
        if ($shouldSync === true)
        {
            $this->saveAndSyncOrFail($feature);
        }
        else
        {
            $feature->getValidator()->validateFeatureIsNotAlreadyAssigned($assignedFeatureNames);
            try
            {
                $this->assignOnDCS($feature, $this->getAppMode());
                $this->repo->saveOrFail($feature);
            }
            catch (\Throwable $e)
            {
                $this->removeOnDCS($feature, $this->getAppMode());
                throw $e;
            }
        }
    }

    public function deleteAndSyncIfApplicableOrFail(Entity $feature, bool $shouldSync)
    {
        $entityType = $feature->getEntityType();
        $entityId = $feature->getEntityId();
        $featureName = $feature->getName();
        $feature = $this->newQuery()
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::NAME, $featureName)
            ->firstOrFailPublic();

        if ($shouldSync === true)
        {
            $this->deleteAndSyncOrFail($feature);
        }
        else
        {
            try
            {
                $this->removeOnDCS($feature, $this->getAppMode());
                $this->deleteOrFail($feature);
            }
            catch (\Throwable $e)
            {
                $this->assignOnDCS($feature, $this->getAppMode());
                throw $e;
            }
        }
    }

    /**
     * Fetch features assigned to an application_id
     *
     * @param string $applicationId
     *
     * @return PublicCollection
     */
    public function getApplicationFeatureNames(string $applicationId): PublicCollection
    {
        $cacheTtl = $this->getCacheTtl();

        $cacheTags = Entity::getCacheTagsForNames(Constants::APPLICATION , $applicationId);

        return new PublicCollection($this->newQuery()
                                         ->where(Entity::ENTITY_TYPE, Constants::APPLICATION )
                                         ->where(Entity::ENTITY_ID, $applicationId)
                                         ->remember($cacheTtl)
                                         ->cacheTags($cacheTags)
                                         ->pluck(Entity::NAME)
                                         ->toArray());
    }

    /**
     * Save feature with sync: Adds features to test and live
     * DB's if they don't already exist
     *
     * @param Entity $entity
     */
    protected function saveAndSyncOrFail(Entity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            $featureName = $entity->getName();
            $entityId    = $entity->getEntityId();

            try
            {
                $this->assignOnDCS($entity, Mode::TEST, true);
                $this->assignOnDCS($entity, Mode::LIVE, true);

                $testEntity = $this->newQueryWithConnection(Mode::TEST)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();

                $liveEntity = $this->newQueryWithConnection(Mode::LIVE)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();

                if ($testEntity === null) {
                    $this->cloneAndSaveToModeOrFail($entity, Mode::TEST);
                }

                if ($liveEntity === null) {
                    $this->cloneAndSaveToModeOrFail($entity, Mode::LIVE);
                }
            }
            catch (\Throwable $e)
            {
                $this->removeOnDCS($entity, Mode::TEST, true);
                $this->removeOnDCS($entity, Mode::LIVE, true);
                throw $e;
            }
        });
    }

    /**
     * Delete a feature with sync: removes the record
     * from both test/live DB's if present
     *
     * @param Entity $entity
     */
    protected function deleteAndSyncOrFail(Entity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity) {
            $featureName = $entity->getName();
            $entityId = $entity->getEntityId();
            try
            {
                $this->removeOnDCS($entity, Mode::TEST, true);
                $this->removeOnDCS($entity, Mode::LIVE, true);

                $testEntity = $this->newQueryWithConnection(Mode::TEST)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();
                $liveEntity = $this->newQueryWithConnection(Mode::LIVE)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();

                if ($testEntity !== null) {
                    $testEntity->deleteOrFail();

                    $this->syncToEs($entity, EsRepository::DELETE, null, Mode::TEST);
                }

                if ($liveEntity !== null) {
                    $liveEntity->deleteOrFail();

                    $this->syncToEs($entity, EsRepository::DELETE, null, Mode::LIVE);
                }
            }
            catch (\Throwable $e)
            {
                $this->assignOnDCS($entity, Mode::TEST, true);
                $this->assignOnDCS($entity, Mode::LIVE, true);
                throw $e;
            }
        });
    }

    private function cloneAndSaveToModeOrFail(Entity $entity, string $mode)
    {
        $modeEntity = clone $entity;
        $modeEntity->setConnection($mode);

        $modeEntity->saveOrFail();

        $this->syncToEs($modeEntity, EsRepository::CREATE, null, $mode);
    }

    /***
     * @param array $entityIds
     * @param string $featureName
     * @return mixed
     * This query is run to get the EntityId which are not in $entityId and have
     * Feature as passed by the $featureName
     * and Entity type is 'merchant'
     */
    public function findMerchantNotInEntityIdHavingFeature(array $entityIds, string $featureName)
    {
        return $this->newQuery()
                    ->whereNotIn(Entity::ENTITY_ID, $entityIds)
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->get();
    }

    public function merchantOnEarlySettlement(Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        $featureList = $this->findMerchantWithFeatures(
            $merchantId,
            [
                Constants::ES_AUTOMATIC,
                Constants::ES_AUTOMATIC_THREE_PM,
            ])
                            ->pluck(Entity::NAME);

        if($featureList->isEmpty() === true)
        {
            return [false, null];
        }

        return [true, $featureList->toArray()];
    }

    private function assignOnDCS(Entity $entity, $mode, $sync = false)
    {
        if (Service::isDcsFeature($entity->getName()) === true)
        {
            $dcs = $this->app['dcs'];
            if ($sync === true)
            {
                $variant = $this->getDcsEditVariant($entity->getName(), Mode::LIVE);
            }
            else
            {
                $variant = $this->getDcsEditVariant($entity->getName(), $mode);
            }

            $dcs->editFeature($entity, $variant, true, $mode);
        }
    }

    private function removeOnDCS(Entity $entity, $mode, $sync = false)
    {
        if (Service::isDcsFeature($entity->getName()) === true)
        {
            $dcs = $this->app['dcs'];
            if ($sync === true)
            {
                $variant = $this->getDcsEditVariant($entity->getName(), Mode::LIVE);
            }
            else
            {
                $variant = $this->getDcsEditVariant($entity->getName(), $mode);
            }
            $dcs->editFeature($entity, $variant, false, $mode);
        }
    }

    private function getAppMode() {
        if(isset($this->app['rzp.mode']) === true)
        {
            return $this->app['rzp.mode'];
        }

        return Mode::TEST;
    }

    public function getDcsEditVariant($featureName, $mode)
    {
        $mode = $mode ?? 'live';
        $flag = $this->app['razorx']->getTreatment($featureName,
            RazorxTreatment::DCS_EDIT_ENABLED,
            $mode);

        $this->trace->info(TraceCode::DCS_RAZORX_EXPERIMENT, [
            'feature_name' => $featureName,
            'razorx_treatment' => RazorxTreatment::DCS_EDIT_ENABLED,
            'razorx_output' => $flag,
            'mode' => $mode,
        ]);
        return $flag;
    }

    public function getDcsAggregateReadVariant($functionName, $mode)
    {
        if($mode === null && isset($this->app['rzp.mode']) === true)
        {
            $mode = $this->app['rzp.mode'];
        }

        $mode = $mode ?? 'live';
        $flag = $this->app['razorx']->getTreatment($functionName,
            RazorxTreatment::DCS_AGGREGATE_READ_ENABLED,
            $mode);
        $this->trace->info(TraceCode::DCS_RAZORX_EXPERIMENT, [
            'feature_name' => $functionName,
            'razorx_treatment' => RazorxTreatment::DCS_AGGREGATE_READ_ENABLED,
            'razorx_output' => $flag,
            'mode' => $mode,
        ]);
        return $flag;
    }
}
