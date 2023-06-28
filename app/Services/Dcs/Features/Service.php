<?php

namespace RZP\Services\Dcs\Features;

use Razorpay\Dcs\Kv\V1\ApiException;
use Razorpay\Trace\Logger;
use RZP\Constants\HyperTrace;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Services\Dcs\Cache;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Feature\Metric as FeatureMetric;
use RZP\Services\Dcs\ExternalService;
use RZP\Services\Dcs\ExternalService\Constants;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Feature\Entity;
use Razorpay\Dcs\DataFormatter;
use RZP\Services\Dcs\Features\Constants as DcsConstants;
use Razorpay\Dcs\Constants as SDKConstants;
use RZP\Trace\Tracer;

class Service extends Base
{

    /**
     * @var mixed|null
     */
    private mixed $desc;

    public function __construct($app = null)
    {
        $this->app = $app ?? App::getFacadeRoot();
        parent::__construct($this->app);
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param Entity $entity
     * @param string $variant
     * @param bool $isAssignment
     * @param string $mode
     * @throws Exception\ServerErrorException
     * @throws \Exception|\Throwable
     */
    public function editFeature(Entity $entity, string $variant, bool $isAssignment, string $mode = Mode::TEST)
    {
        $dcsFeatureName = DcsConstants::dcsFeatureNameFromAPIName($entity->getName());
        if ($isAssignment === true)
        {
            $action  = 'assign';
        }
        else
        {
            $action  = 'remove';
        }
        $dimension = [
            Entity::ENTITY_TYPE     => $entity->getEntity(),
            Entity::NAME            => $entity->getName(),
            'variant'               => $variant,
            'mode'                  => $mode,
            'action'                => $action
        ];

        $actualDcsFeatureName = Utility::extractActualDcsName($dcsFeatureName);
        // if there is any exception will be thrown to caller
        try {
            $this->trace->count(Metric::DCS_FEATURE_EDIT_TOTAL, $dimension);

            if (str_starts_with($variant, 'on_direct_dcs'))
            {
                $key = DCSConstants::$featureToDCSKeyMapping[$dcsFeatureName];
                $data = DataFormatter::toKeyMapWithOutId($key);
                $request = [ $actualDcsFeatureName => $isAssignment];
                $this->trace->info(TraceCode::DCS_SERVICE_REQUEST, [
                    'action' => 'assign',
                    'featureDetails' => $request,
                    'requestData' => $data,
                    'key' => $key,
                    'mode' => $mode,
                ]);

                $value = DataFormatter::marshal($request, DataFormatter::convertDCSKeyToClassName(DataFormatter::convertKeyStringToDCSKey($key)));

                    // TODO change it to what ever client it is base on mode
                $res = $this->client($mode)->patch($data, $entity->getEntityId(), $value, [$actualDcsFeatureName], $this->getAuditInfo());

                $this->trace->info(TraceCode::DCS_SERVICE_SUCCESSFUL_RESPONSE, [
                    'action' => 'assign',
                    'responseEntityId' => $entity->getEntityId(),
                    'featureName' => $actualDcsFeatureName,
                    'success' => true,
                    'mode' => $mode,
                ]);
            }
            elseif (str_starts_with($variant, 'on_client'))
            {
                $this->handleDcsFeatures($entity, $isAssignment, $mode);
            }
            elseif (key_exists($dcsFeatureName, array_merge(DcsConstants::$dcsNewMerchantFeatures, DcsConstants::$dcsNewOrgFeatures)) === true)
            {
                $ex = new Exception\ServerErrorException('dcs service is disabled, please check with dcs team',
                    ErrorCode::BAD_REQUEST_DCS_DISABLED,
                    "dcs service is disabled, please check with dcs team");
                $this->trace->traceException($ex);

                throw $ex;
            }
            $cache_key = $this->app->environment() .'_'. $mode.'_dcs_fetch_by_id_type_'.$entity->getEntityId().'_'.$entity->getEntityType();
            (new Cache())->remove($cache_key);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::DCS_FEATURE_EDIT_FAILURE_TOTAL, $dimension);
            if (key_exists($dcsFeatureName, array_merge(DcsConstants::$dcsNewMerchantFeatures, DcsConstants::$dcsNewOrgFeatures)) === true)
            {
                throw $e;
            }

            if (DcsConstants::isNewFeature($variant) === true)
            {
                // throws exception in-case of some issue
                throw $e;
            }
            elseif (DcsConstants::isReverseShadowFeature($variant) === true)
            {
                // throws exception in-case of some issue
                throw $e;
            }
            elseif (DcsConstants::isShadowFeature($variant) === true)
            {
                // TODO handle shadow edit features failure
                return;
            }
            return;
        }
    }

    public function getDcsEnabledFeatures(string $entityType, string $entityId, string $mode = null) : PublicCollection
    {
        if ($mode === null || $mode = '')
        {
            $mode = $this->getMode();
        }

        $dimension = [
            'feature_name' => 'many',
            'mode' => $mode,
            'function' => __FUNCTION__
        ];

        try
        {
            $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_TOTAL, $dimension);
            return $this->fetchByEntityIdAndEntityType($entityId, $entityType, $mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_FAILURE_TOTAL, $dimension);
            $this->trace->traceException($e, Logger::ERROR, TraceCode::DCS_READ_FEATURES_FAILURE);
        }

        return new PublicCollection();
    }

    protected function getMode()
    {
        return $this->app['rzp.mode'] ?? Mode::LIVE;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityId
     * @param string $apiFeatureName
     * @param string $mode
     * @return Entity|null
     * @throws ApiException
     * @throws BadRequestException
     * @throws ServerErrorException
     */
    public function fetchByEntityIdAndName(string $entityId, string $apiFeatureName, string $mode = Mode::TEST): ?Entity
    {
        $featureName = DcsConstants::dcsFeatureNameFromAPIName($apiFeatureName);
        $actualDcsFeatureName = Utility::extractActualDcsName($featureName);
        $key = DcsConstants::$featureToDCSKeyMapping[$featureName];
        $data = DataFormatter::toKeyMapWithOutId($key);

        $response = Tracer::inspan(['name' => HyperTrace::DCS_FETCH_FEATURE],
            function() use ($entityId, $actualDcsFeatureName,$apiFeatureName, $data, $mode)
            {
                $res = null;

                $response = $this->client($mode)->fetchMultiple($data, [$entityId], [$actualDcsFeatureName]);
                if ($response === null)
                {
                    return null;
                }

                $kvs =  $response->getKvs() == null ? []: $response->getKvs();
                foreach ($kvs as $kv)
                {
                    $key = $kv->getKey();
                    $features = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

                    $data = [
                        Entity::NAME => $apiFeatureName,
                        Entity::ENTITY_TYPE => Type::getAPIEntityTypeFromDCSType($key->getEntity()),
                        Entity::ENTITY_ID => $entityId,
                    ];

                   if ($features[$actualDcsFeatureName] === true)
                   {
                       $entity = (new Entity)->build($data);
                       $entity->setEntityType(Type::getAPIEntityTypeFromDCSType($key->getEntity()));
                       $entity->setEntityId($entityId);
                       $res = $entity;
                       break;
                   }
                }

                return $res;
            });

        Tracer::addAttribute('dcs_feature_name' , $actualDcsFeatureName);
        Tracer::addAttribute('api_feature_name' , $featureName);
        Tracer::addAttribute('mode' , $mode);
        Tracer::addAttribute('entity_id' , $entityId);
        Tracer::addAttribute('function', __FUNCTION__);

        return $response;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityId
     * @param array $featureNames
     * @param string $mode
     * @param bool $aggregate
     * @param string $entityType
     * @return array
     */
    public function fetchByEntityIdAndFeatureNames(string $entityId, array $featureNames, string $mode = Mode::TEST,
                                                   bool   $aggregate = false, string $entityType = "", bool $cacheDisabled = false): array
    {
        return Tracer::inspan(['name' => HyperTrace::DCS_FETCH_FEATURES_AGGREGATE],
            function() use ($cacheDisabled, $entityId, $featureNames, $aggregate, $entityType, $mode)
            {
                $data = [] ;
                $res = [];
                foreach ($featureNames as $dcsFeatureName)
                {
                    $key = DcsConstants::$featureToDCSKeyMapping[$dcsFeatureName];
                    $data[$key][] = Utility::extractActualDcsName($dcsFeatureName);
                }

                $response = $this->client($mode)->fetchMultipleKeysWithID($data, $entityId, $aggregate,
                    $entityType, $cacheDisabled);

                $kvs = $response->getKvs() == null ? [] : $response->getKvs();

                foreach ($kvs as $kv)
                {
                    $kvkey = $kv->getKey();
                    $keyFeatures = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($kvkey));
                    $keyStr = DataFormatter::convertDCSKeyToStringWithOutEntityId($kvkey);
                    if(key_exists($keyStr, $data) === true)
                    {
                        $fields = $data[$keyStr];
                    }
                    foreach ($fields as $featureName)
                    {
                        if (key_exists($featureName, $keyFeatures) && $keyFeatures[$featureName] === true)
                        {
                            $res[] = DcsConstants::apiFeatureNameFromDcsName($featureName, $keyStr);
                        }
                    }
                }

                Tracer::addAttribute('feature_names' , $res);
                Tracer::addAttribute('mode' , $mode);
                Tracer::addAttribute('entity_id' , $entityId);
                Tracer::addAttribute('function', __FUNCTION__);
                return $res;
            });
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param array $entityIds
     * @param string $apiFeatureName
     * @param string $mode
     * @return array
     * @throws ApiException
     * @throws BadRequestException
     * @throws ServerErrorException
     */
    public function fetchByEntityIdsAndName(array $entityIds, string $apiFeatureName, string $mode = Mode::TEST): array
    {
        $featureName = DcsConstants::dcsFeatureNameFromAPIName($apiFeatureName);
        $actualDcsFeatureName = Utility::extractActualDcsName($featureName);
        $key = DcsConstants::$featureToDCSKeyMapping[$featureName];
        $data = DataFormatter::toKeyMapWithOutId($key);

        $response = Tracer::inspan(['name' => HyperTrace::DCS_FETCH_FEATURES_AGGREGATE],
            function() use ($data, $entityIds, $apiFeatureName, $actualDcsFeatureName, $mode) {
                $res = [];
                $response = $this->client($mode)->fetchMultiple($data, $entityIds, [$actualDcsFeatureName]);
                if ($response === null) {
                    return $res;
                }
                $kvs = $response->getKvs() == null ? [] : $response->getKvs();
                foreach ($kvs as $kv) {
                    $key = $kv->getKey();

                    $features = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

                    $data = [
                        Entity::NAME => $apiFeatureName,
                        Entity::ENTITY_TYPE => Type::getAPIEntityTypeFromDCSType($key->getEntity()),
                        Entity::ENTITY_ID => $key->getEntityId(),
                    ];

                    if ($features[$actualDcsFeatureName] === true) {
                        $entity = (new Entity)->build($data);
                        $entity->setEntityType(Type::getAPIEntityTypeFromDCSType($key->getEntity()));
                        $entity->setEntityId($key->getEntityId());
                        $res[] = $entity;
                    }
                }

                return $res;
            });
        Tracer::addAttribute('request_data' , $data);
        Tracer::addAttribute('mode' , $mode);
        Tracer::addAttribute('dcs_feature_name' , $actualDcsFeatureName);
        Tracer::addAttribute('api_feature_name' , $featureName);
        Tracer::addAttribute('function', __FUNCTION__);
        return $response;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $mode
     * @return PublicCollection
     * @throws \Exception
     */
    public function fetchByEntityIdAndEntityType(string $entityId,
                                                 string $entityType,
                                                 string $mode = Mode::TEST): PublicCollection
    {
        $response = new PublicCollection();

        $env = $this->app->environment();

        $cacheKey = $env .'_'. $mode.'_dcs_fetch_by_id_type_'. $entityId.'_'.$entityType;

        $enabled_features = $this->cache->get($cacheKey);

        if ($enabled_features === null)
        {
            $dcsFeatures = DcsConstants::dcsReadEnabledFeaturesByEntityType($entityType, true,
                $this->app->runningUnitTests(), $this->app->isEnvironmentProduction());

            if(sizeof($dcsFeatures) === 0)
            {
                $this->cache->set($cacheKey, [], 30);
                return $response;
            }


            $enabled_features = $this->fetchByEntityIdAndFeatureNames($entityId, array_keys($dcsFeatures), $mode,
                true, $entityType, true);

            $this->cache->set($cacheKey, $enabled_features, 30);
        }

        foreach ($enabled_features as $feature_name)
        {
            $attributes = [
                Entity::NAME => $feature_name,
                Entity::ENTITY_TYPE => $entityType,
                Entity::ENTITY_ID => $entityId,
            ];
            $entity = new Entity();
            $entity->forceFill($attributes);
            $entity->setEntityType($entityType);
            $response->push($entity);
        }

        return $response;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $apiFeatureName
     * @param string $mode
     * @param string $entityType
     * @return PublicCollection
     * @throws ApiException
     * @throws ServerErrorException
     */
    public function fetchByFeatureName(string $apiFeatureName,
                                       string $entityType = Type::MERCHANT,
                                       string $mode = Mode::TEST): PublicCollection
    {
        $response = new PublicCollection();
        $featureName = DcsConstants::dcsFeatureNameFromAPIName($apiFeatureName);
        $key = DcsConstants::$featureToDCSKeyMapping[$featureName];
        $data = DataFormatter::toKeyMapWithOutId($key);

        $env = $this->app->environment();

        $cacheKey = $env .'_'. $mode.'_dcs_fetch_by_name'.'_'.$apiFeatureName;

        $enabled_ids = $this->cache->get($cacheKey);

        if ($enabled_ids === null &&
            DcsConstants::isDcsReadEnabledFeature($apiFeatureName, false, $key, $this->app->isEnvironmentProduction()))
        {
            $enabled_ids = [];
            $features_with_id = $this->handleAggregateQueries($data, $mode, true);

            foreach ($features_with_id as $id => $features)
            {
                foreach ($features as $feature)
                {
                    if ($feature === $apiFeatureName)
                    {
                        $enabled_ids[] = $id;
                    }
                }
            }

            $this->cache->set($cacheKey, $enabled_ids, 30);
        }

        foreach ($enabled_ids as $entityId)
        {
            $attributes = [
                Entity::NAME => $featureName,
                Entity::ENTITY_TYPE => $entityType,
                Entity::ENTITY_ID => $entityId,
            ];
            $entity = new Entity();
            $entity->forceFill($attributes);
            $response->push($entity);
        }

        return $response;
    }

    /**
     * @param string $apiFeatureName
     * @param string $offset
     * @param string $limit
     * @param string $mode
     * @return array
     * @throws ApiException
     * @throws ServerErrorException
     */
    public function fetchEntityIdsByFeatureNameInChunks(string $apiFeatureName,
                                                string $offset, string $limit,
                                                string $mode = Mode::TEST): array
    {
        $featureName = DcsConstants::dcsFeatureNameFromAPIName($apiFeatureName);
        $key = DcsConstants::$featureToDCSKeyMapping[$featureName];

        $data = DataFormatter::toKeyMapWithOutId($key);

        $enabled_ids = [];
        $response = $this->handleAggregateQueriesWithOffsetAndLimit($data, $offset, $limit ,$mode);
        $features_with_id = $response['response'];

        foreach ($features_with_id as $id => $features)
        {
            foreach ($features as $feature)
            {
                if ($feature === $apiFeatureName)
                {
                    $enabled_ids[] = $id;
                }
            }
        }

        return ['enabled_ids' => $enabled_ids, 'returned_offset' => $response['returned_offset']];
    }

    /**
     * @throws ApiException
     * @throws ServerErrorException
     */
    private function handleAggregateQueries($data, $mode, $disableCache = false): array
    {
        $key = $this->getAggregateKey($data);

        $response = Tracer::inspan(['name' => HyperTrace::DCS_FETCH_FEATURES_AGGREGATE],
            function() use ($disableCache, $key, $data, $mode) {

        $response = $this->client($mode)->aggregateFetch($key, $disableCache);

                $res = [];
                if ($response === null) {
                    return $res;
                }
                $kvs = $response->getKvs() == null ? [] : $response->getKvs();
                foreach ($kvs as $index => $kv)
                {
                    $data = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($kv->getKey()));

            $dcsKey = $kv->getKey();
            $keyStr = DataFormatter::convertDCSKeyToStringWithOutEntityId($dcsKey);
            foreach ($data as $featureName => $enabled)
            {
                if (($enabled === true) &&
                        (DcsConstants::isValidDcsKeyAndName($keyStr, $featureName) === true))
                {
                    // this tries to fetch api name from dcs_name and key if it is missing we will catch the exception
                    // and log DCS_MISSING_FIELD log, we can identify anf fix it
                    try
                    {
                        $res[$dcsKey->getEntityId()][] = DcsConstants::apiFeatureNameFromDcsName($featureName, $keyStr);
                    }
                    catch (\Throwable)
                    {
                        $this->trace->info(TraceCode::DCS_MISSING_FIELD, [
                            'dcs_field_name' => $res,
                            'key' => $keyStr,
                            'mode' => $mode,
                        ]);
                    }
                }
            }
        }
            return $res;
        });

        Tracer::addAttribute('key' , $key);
        Tracer::addAttribute('mode' , $mode);
        Tracer::addAttribute('function' , __FUNCTION__);
        Tracer::addAttribute('response_count' , sizeof($response));

        return $response;
    }

    /**
     * @throws ApiException
     * @throws ServerErrorException
     */
    private function handleAggregateQueriesWithOffsetAndLimit($data, $offset, $limit, $mode): array
    {
        $key = $this->getAggregateKey($data);

        $response = Tracer::inspan(['name' => HyperTrace::DCS_FETCH_FEATURES_AGGREGATE],
            function() use ($key, $data, $mode, $offset, $limit) {

                $dcsResponse = $this->client($mode)->aggregateFetchWithOffsetAndLimit($key, $offset, $limit);

                $res = [];
                $kvs = $dcsResponse->getKvs() == null ? [] : $dcsResponse->getKvs();
                $returnedOffset = $dcsResponse->getOffset();

                foreach ($kvs as $index => $kv)
                {
                    $data = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($kv->getKey()));

                    $dcsKey = $kv->getKey();
                    $keyStr = DataFormatter::convertDCSKeyToStringWithOutEntityId($dcsKey);
                    foreach ($data as $featureName => $enabled)
                    {
                        if (($enabled === true) &&
                            (DcsConstants::isValidDcsKeyAndName($keyStr, $featureName) === true))
                        {
                            // this tries to fetch api name from dcs_name and key if it is missing we will catch the exception
                            // and log DCS_MISSING_FIELD log, we can identify anf fix it
                            try
                            {
                                $res[$dcsKey->getEntityId()][] = DcsConstants::apiFeatureNameFromDcsName($featureName, $keyStr);
                            }
                            catch (\Throwable)
                            {
                                $this->trace->info(TraceCode::DCS_MISSING_FIELD, [
                                    'dcs_field_name' => $res,
                                    'key' => $keyStr,
                                    'mode' => $mode,
                                ]);
                            }
                        }
                    }
                }
                return ['response'=> $res, "returned_offset" => $returnedOffset];
            });

        Tracer::addAttribute('key' , $key);
        Tracer::addAttribute('mode' , $mode);
        Tracer::addAttribute('function' , __FUNCTION__);
        Tracer::addAttribute('response_count' , sizeof($response));

        return $response;
    }

    public function getAggregateKey(array $data)
    {
        $data[SDKConstants::NAMESPACE] = (key_exists(SDKConstants::NAMESPACE, $data) &&
            ($data[SDKConstants::NAMESPACE] !== null || $data[SDKConstants::NAMESPACE] !== '')) ? $data[SDKConstants::NAMESPACE] : '';

        $data[SDKConstants::DOMAIN] = (key_exists(SDKConstants::DOMAIN, $data) &&
            ($data[SDKConstants::DOMAIN] !== null || $data[SDKConstants::DOMAIN] !== '')) ? $data[SDKConstants::DOMAIN] : '';

        $data[SDKConstants::ENTITY] = (key_exists(SDKConstants::ENTITY, $data) &&
            ($data[SDKConstants::ENTITY] !== null || $data[SDKConstants::ENTITY] !== '')) ? $data[SDKConstants::ENTITY] : '';

        $data[SDKConstants::ENTITY_ID] = (key_exists(SDKConstants::ENTITY_ID, $data) &&
            ($data[SDKConstants::ENTITY_ID] !== null || $data[SDKConstants::ENTITY_ID] !== '')) ? $data[SDKConstants::ENTITY_ID] : '';

        $data[SDKConstants::OBJECT_NAME] = (key_exists(SDKConstants::OBJECT_NAME, $data) &&
            ($data[SDKConstants::OBJECT_NAME] !== null || $data[SDKConstants::OBJECT_NAME] !== '')) ? $data[SDKConstants::OBJECT_NAME] : '';

      return $data;
    }

    public static function isDcsFeature($featureName): bool
    {
        // seems to be wrong
        return key_exists($featureName, DcsConstants::$featureToDCSKeyMapping) || key_exists($featureName, DcsConstants::$apiFeatureNameToDCSFeatureName);
    }

    /**
     * @throws ServerErrorException
     */
    public function handleDcsFeatures(Entity $entity, $value , $mode = Mode::TEST)
    {
        $dcsFeatureName = DcsConstants::dcsFeatureNameFromAPIName($entity->getName());
        $actualDcsFeatureName = Utility::extractActualDcsName($dcsFeatureName);
        $key = DcsConstants::$featureToDCSKeyMapping[$dcsFeatureName];
        $data = DataFormatter::toKeyMapWithOutId($key);

        $this->trace->info(TraceCode::DCS_EXTERNAL_REQUEST_RECEIVED, [
            'featureName' => $dcsFeatureName,
            'entityID'  => $entity->getEntityId(),
            'entityType' => $entity->getEntity(),
            'requestData' => $data,
            'key' => $key,
            'value' => $value,
            'mode' => $mode,
            'newUseCase' => true
        ]);

        $svc = new ExternalService\Service($this->app);

        $req = $svc->buildExternalRequest($key, $entity->getEntityId(), [$actualDcsFeatureName => $value], $mode);

        $svcName = Constants::$newDcsConfigurationServiceMapping[$key];

        $res = $svc->action($svcName, $req, $mode);

        $this->trace->info(TraceCode::DCS_EXTERNAL_RESPONSE_RECEIVED, [
            'featureName' => $actualDcsFeatureName,
            'entityID'  => $entity->getEntityId(),
            'entityType' => $entity->getEntity(),
            'requestData' => $req,
            'key' => $key,
            'value' => $value,
            'response' => $res,
            'mode' => $mode,
            'newUseCase' => true
        ]);

       return $svc->handleResponse($res);
    }

    protected function getAuditInfo(): array
    {
        if($this->auth->isAdminAuth() === true)
        {
             $request[SDKConstants::CHANGE_BY] = $this->auth->getAdmin()->getEmail();
             $request[SDKConstants::CHANGE_APPROVED_BY] = $this->auth->getAdmin()->getEmail();
             $request[SDKConstants::CHANGE_REASON] = 'added from admin dashboard';
        }
        else
        {
            $request[SDKConstants::CHANGE_BY] = 'api@razorpay.com';
            $request[SDKConstants::CHANGE_REASON] = 'api proxy request';
            $request[SDKConstants::CHANGE_APPROVED_BY] = 'api@razorpay.com';
        }

        return $request;
    }

    public function fetchEntityIdsByFeatureName(string $apiFeatureName, string $entityType, $mode = Mode::LIVE): array
    {
        $res = $this->fetchByFeatureName($apiFeatureName,$entityType, $mode);
        return $res->pluck(Entity::ENTITY_ID)->toArray();
    }
}
