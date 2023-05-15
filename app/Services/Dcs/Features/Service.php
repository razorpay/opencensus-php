<?php

namespace RZP\Services\Dcs\Features;

use Razorpay\Dcs\Kv\V1\ApiException;
use Razorpay\Trace\Logger;
use RZP\Constants\HyperTrace;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Base\Collection;
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
    private $desc;

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
     * @return void
     * @throws Exception\ServerErrorException
     * @throws \Exception|\Throwable
     */
    public function editFeature(Entity $entity, string $variant, bool $isAssignment, $mode = Mode::TEST)
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

    public function getDcsEnabledFeatures(string $entityType, string $entityId, string $mode = null) : \Illuminate\Support\Collection
    {
        $res = collect();
        if ($mode === null || $mode = '')
        {
            $mode = $this->getMode();
        }

        $dimension = [
            'feature_name' => 'many',
            'mode' => $mode,
            'function' => __FUNCTION__
        ];
        try {
            $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_TOTAL, $dimension);
            $dcsFeatures = array_keys(DcsConstants::dcsReadEnabledFeaturesByEntityType($entityType, true,
                $this->app->runningUnitTests(), $this->app->isEnvironmentProduction()));

            if( sizeof($dcsFeatures) === 0)
            {
                return $res;
            }
            $response = $this->fetchByEntityIdAndFeatureNames($entityId, $dcsFeatures,
                ($mode === null) ? $this->getAppMode() : $mode, true, $entityType);
            $res = collect($response);
        } catch (\Throwable $e) {
            $this->trace->count(FeatureMetric::DCS_FEATURE_FETCH_FAILURE_TOTAL, $dimension);
            $this->trace->traceException($e, Logger::ERROR, TraceCode::DCS_READ_FEATURES_FAILURE);
        }

        return $res;
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
     * @return Entity
     * @throws ApiException
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function fetchByEntityIdAndName(string $entityId, string $apiFeatureName, $mode = Mode::TEST)
    {
        $featureName = DcsConstants::dcsFeatureNameFromAPIName($apiFeatureName);
        $actualDcsFeatureName = Utility::extractActualDcsName($featureName);
        $key = DcsConstants::$featureToDCSKeyMapping[$featureName];
        $data = DataFormatter::toKeyMapWithOutId($key);
        $response = Tracer::inspan(['name' => HyperTrace::DCS_FETCH_FEATURE],
            function() use ($entityId, $actualDcsFeatureName,$apiFeatureName, $data, $mode) {
                $res = null;

                $response = $this->client($mode)->fetchMultiple($data, [$entityId], [$actualDcsFeatureName]);
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
                        Entity::ENTITY_ID => $entityId,
                    ];

                    if ($features[$actualDcsFeatureName] === true) {
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
     * @throws ApiException
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function fetchByEntityIdAndFeatureNames(string $entityId, array $featureNames, $mode = Mode::TEST,
                                                   $aggregate = false, $entityType = "")
    {
        $response = Tracer::inspan(['name' => HyperTrace::DCS_FETCH_FEATURES_AGGREGATE],
            function() use ($entityId, $featureNames, $aggregate, $entityType, $mode) {
                $data = [] ;
                $res = [];
                foreach ($featureNames as $dcsFeatureName) {
                    $key = DcsConstants::$featureToDCSKeyMapping[$dcsFeatureName];
                    $data[$key][] = Utility::extractActualDcsName($dcsFeatureName);
                }

                $response = $this->client($mode)->fetchMultipleKeysWithID($data, $entityId, $aggregate, $entityType);
                if ($response === null) {
                    return $res;
                }
                $kvs = $response->getKvs() == null ? [] : $response->getKvs();

                foreach ($kvs as $kv) {
                    $kvkey = $kv->getKey();
                    $keyFeatures = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($kvkey));
                    $dcsKey = DataFormatter::convertDCSKeyToClassName($kvkey);
                    foreach ($data[DataFormatter::convertDCSKeyToStringWithOutEntityId($kvkey)] as $featureName) {
                        if ($keyFeatures[$featureName] === true) {
                            $entity_data = [
                                Entity::NAME => DcsConstants::apiFeatureNameFromDcsName($featureName, $dcsKey),
                                Entity::ENTITY_TYPE => Type::getAPIEntityTypeFromDCSType($kvkey->getEntity()),
                                Entity::ENTITY_ID => $entityId,
                            ];

                            $entity = (new Entity)->build($entity_data);
                            $entity->generateAndSetUniqueId();
                            $entity->setEntityType(Type::getAPIEntityTypeFromDCSType($kvkey->getEntity()));
                            $entity->setEntityId($entityId);
                            $res[] = $entity;
                        }
                    }
                }

                return $res;
            });
        Tracer::addAttribute('feature_names' , $featureNames);
        Tracer::addAttribute('mode' , $mode);
        Tracer::addAttribute('entity_id' , $entityId);
        Tracer::addAttribute('function', __FUNCTION__);

        return $response;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param array $entityIds
     * @param string $apiFeatureName
     * @param string $mode
     * @return array
     * @throws ApiException|Exception\ServerErrorException
     */
    public function fetchByEntityIdsAndName(array $entityIds, string $apiFeatureName, $mode = Mode::TEST)
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
     * @return array
     * @throws \Exception
     */
    public function fetchByEntityIdAndEntityType(string $entityType, string $entityId, $mode = Mode::TEST)
    {
        $data = [];
        $data[SDKConstants::ENTITY_ID] = $entityId ;
        $data[SDKConstants::ENTITY] = $entityType ;
        return $this->handleAggregateQueries($data, $mode);
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $apiFeatureName
     * @param string $mode
     * @return array
     */
    public function fetchByFeatureName(string $apiFeatureName, $mode = Mode::TEST)
    {
        $featureName = DcsConstants::dcsFeatureNameFromAPIName($apiFeatureName);
        $key = DcsConstants::$featureToDCSKeyMapping[$featureName];
        $data = DataFormatter::toKeyMapWithOutId($key);
        return $this->handleAggregateQueries($data, $mode);
    }

    private function handleAggregateQueries($data, $mode)
    {
        $key = $this->getAggregateKey($data);

        $response = Tracer::inspan(['name' => HyperTrace::DCS_FETCH_FEATURES_AGGREGATE],
            function() use ($key, $data, $mode) {

                $response = $this->client($mode)->aggregateFetch($data);

                $res = [];
                if ($response === null) {
                    return $res;
                }
                $kvs = $response->getKvs() == null ? [] : $response->getKvs();
                foreach ($kvs as $index => $kv) {
                    $data = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($kv->getKey()));

                    $dcsKey = $kv->getKey();
                    foreach ($data as $featureName => $enabled) {
                        if ($enabled === true) {
                            $buildData = [
                                Entity::NAME => DcsConstants::apiFeatureNameFromDcsName($featureName, $dcsKey),
                                Entity::ENTITY_TYPE => Type::getAPIEntityTypeFromDCSType($kv->getKey()->getEntity()),
                                Entity::ENTITY_ID => $kv->getKey()->getEntityId(),
                            ];

                            $entity = (new Entity)->build($buildData);
                            $entity->setEntityType(Type::getAPIEntityTypeFromDCSType($kv->getKey()->getEntity()));
                            $entity->setEntityId($kv->getKey()->getEntityId());
                            $res[] = $entity;
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

    public static function isDcsFeature($featureName)
    {
        // seems to be wrong
        return key_exists($featureName, DcsConstants::$featureToDCSKeyMapping) || key_exists($featureName, DcsConstants::$apiFeatureNameToDCSFeatureName);
    }

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

    protected function getAuditInfo()
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
}
