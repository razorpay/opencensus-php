<?php

namespace RZP\Services\Dcs\Features;

use Razorpay\Dcs\Kv\V1\ApiException;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Feature\Metric as FeatureMetric;
use RZP\Services\Dcs\ExternalService;
use RZP\Services\Dcs\ExternalService\Constants;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Feature\Entity;
use Razorpay\Dcs\DataFormatter;
use RZP\Services\Dcs\Features\Constants as DcsConstants;
use Razorpay\Dcs\Constants as SDKConstants;

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
     * @throws \Exception
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

        // if there is any exception will be thrown to caller
        try {
            $this->trace->count(Metric::DCS_FEATURE_EDIT_TOTAL, $dimension);

            if (str_starts_with($variant, 'on_direct_dcs'))
            {
                $key = DCSConstants::$featureToDCSKeyMapping[$dcsFeatureName];
                $data = DataFormatter::toKeyMapWithOutId($key);
                $request = [ $dcsFeatureName => $isAssignment];
                $this->trace->info(TraceCode::DCS_SERVICE_REQUEST, [
                    'action' => 'assign',
                    'featureDetails' => $request,
                    'requestData' => $data,
                    'key' => $key,
                    'mode' => $mode,
                ]);

                $value = DataFormatter::marshal($request, DataFormatter::convertDCSKeyToClassName(DataFormatter::convertKeyStringToDCSKey($key)));

                    // TODO change it to what ever client it is base on mode
                $res = $this->client($mode)->patch($data, $entity->getEntityId(), $value, [$dcsFeatureName], self::getDefaultAuditInfo());
                $this->trace->info(TraceCode::DCS_SERVICE_SUCCESSFUL_RESPONSE, [
                    'action' => 'assign',
                    'responseEntityId' => $entity->getEntityId(),
                    'featureName' => $dcsFeatureName,
                    'success' => true,
                    'mode' => $mode,
                ]);
            }
            elseif (str_starts_with($variant, 'on_client'))
            {
                $this->handleDcsFeatures($entity, $isAssignment, $mode);
            }
            elseif (self::isDcsNewFeature($dcsFeatureName) === true)
            {
                $ex = new Exception\ServerErrorException('dcs service is disabled, please check with dcs team',
                    ErrorCode::BAD_REQUEST_DCS_DISABLED,
                    "dcs service is disabled, please check with dcs team");
                $this->trace->traceException($ex);

                throw $ex;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->count(Metric::DCS_FEATURE_EDIT_FAILURE_TOTAL, $dimension);
            if (self::isDcsNewFeature($dcsFeatureName) === true)
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

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityId
     * @param string $apifeatureName
     * @param string $mode
     * @return array
     * @throws ApiException
     */
    public function fetchByEntityIdAndName(string $entityId, string $apiFeatureName, $mode = Mode::TEST)
    {
        $featureName = DcsConstants::dcsFeatureNameFromAPIName($apiFeatureName);

        $key = DcsConstants::$featureToDCSKeyMapping[$featureName];
        $data = DataFormatter::toKeyMapWithOutId($key);
        $res = [];

        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'feature_name' => $featureName,
            'request_data' => $data,
            'id' =>  $entityId,
            'key' => $key,
            'mode' => $mode,
        ]);

        $response = $this->client($mode)->fetchMultiple($data, [$entityId], [$featureName]);
        if ($response === null) {
            return $res;
        }

        $kvs =  $response->getKvs() == null ? []: $response->getKvs();
        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();
            $features = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

            $data = [
                Entity::NAME => $apiFeatureName,
                Entity::ENTITY_TYPE => $key->getEntity(),
                Entity::ENTITY_ID => $entityId,
            ];

           if ($features[$featureName] === true){
               $entity = (new Entity)->build($data);
               $entity->setEntityType($key->getEntity());
               $entity->setEntityId($entityId);
               $res = $entity;
               break;
           }
        }

        $this->trace->info(TraceCode::DCS_FETCH_RESPONSE_RECEIVED, [
            'feature_name' => $featureName,
            'response_data' => $res,
            'id' =>  $entityId,
            'key' => $key,
            'mode' => $mode,
        ]);

        return $res;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityId
     * @param array $featureNames
     * @param string $mode
     * @return array
     * @throws ApiException
     */
    public function fetchByEntityIdAndFeatureNames(string $entityId, array $featureNames, $mode = Mode::TEST)
    {
        $data = [] ;
        $res = [];
        foreach ($featureNames as $featureName)
        {
            $key = DcsConstants::$featureToDCSKeyMapping[$featureName];
            $data[$key][] = DcsConstants::dcsFeatureNameFromAPIName($featureName);
        }

        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'feature_names' => $featureNames,
            'id' =>  $entityId,
            'data' => $data,
            'key' => $key,
            'mode' => $mode,
        ]);

        $response = $this->client($mode)->fetchMultipleKeysWithID($data, $entityId);
        if ($response === null) {
            return $res;
        }
        $kvs =  $response->getKvs() == null ? []: $response->getKvs();
        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();

            $features = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

           foreach ($data[DataFormatter::convertDCSKeyToStringWithOutEntityId($key)] as $featureName)
           {
               if ($features[$featureName] === true)
               {
                   $data = [
                       Entity::NAME => DcsConstants::dcsFeatureNameFromAPIName($featureName),
                       Entity::ENTITY_TYPE => $key->getEntity(),
                       Entity::ENTITY_ID => $entityId,
                   ];

                   $entity = (new Entity)->build($data);
                   $entity->setEntityType($key->getEntity());
                   $entity->setEntityId($entityId);
                   $res[] = $entity;
               }
           }
        }

        $this->trace->info(TraceCode::DCS_FETCH_RESPONSE_RECEIVED, [
            'feature_names' => $featureNames,
            'response_data' => $res,
            'id' =>  $entityId,
            'key' => $key,
            'mode' => $mode,
        ]);

        return $res;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param array $entityIds
     * @param string $apiFeatureName
     * @param string $mode
     * @return array
     * @throws ApiException
     */
    public function fetchByEntityIdsAndName(array $entityIds, string $apiFeatureName, $mode = Mode::TEST)
    {
        $featureName = DcsConstants::dcsFeatureNameFromAPIName($apiFeatureName);
        $key = DcsConstants::$featureToDCSKeyMapping[$featureName];
        $data = DataFormatter::toKeyMapWithOutId($key);
        $res = [];

        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'feature_name' => $featureName,
            'request_data' => $data,
            'ids' =>  $entityIds,
            'key' => $key,
            'mode' => $mode,
        ]);

        $response = $this->client($mode)->fetchMultiple($data, $entityIds, [$featureName]);
        if ($response === null) {
            return $res;
        }
        $kvs =  $response->getKvs() == null ? []: $response->getKvs();
        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();

            $features = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

            $data = [
                Entity::NAME => $apiFeatureName,
                Entity::ENTITY_TYPE => $key->getEntity(),
                Entity::ENTITY_ID => $key->getEntityId(),
            ];

            if ($features[$featureName] === true){
                $entity = (new Entity)->build($data);
                $entity->setEntityType($key->getEntity());
                $entity->setEntityId($key->getEntityId());
                $res[] = $entity;
            }
        }

        $this->trace->info(TraceCode::DCS_FETCH_RESPONSE_RECEIVED, [
            'feature_name' => $featureName,
            'request_data' => $data,
            'response' => $res,
            'mode' => $mode,
        ]);

        return $res;
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
        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'request_data' => $data,
            'key' => $key,
            'mode' => $mode,
        ]);

        $response = $this->client($mode)->aggregateFetch($data);

        $res = [];
        if ($response === null) {
            return $res;
        }
        $kvs =  $response->getKvs() == null ? []: $response->getKvs();
        foreach ($kvs as $index => $kv)
        {
            $data = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($kv->getKey()));

            foreach ($data as $featureName => $enabled){
                if ($enabled === true)
                {
                    $buildData = [
                        Entity::NAME => DcsConstants::apiFeatureNameFromDcsName($featureName),
                        Entity::ENTITY_TYPE => $kv->getKey()->getEntity(),
                        Entity::ENTITY_ID => $kv->getKey()->getEntityId(),
                    ];
                    $entity = (new Entity)->build($buildData);
                    $entity->setEntityType($kv->getKey()->getEntity());
                    $entity->setEntityId($kv->getKey()->getEntityId());
                    $res[] = $entity;
                }
            }
        }

        $this->trace->info(TraceCode::DCS_FETCH_RESPONSE_RECEIVED, [
            'response' => $res,
            'key' => $key,
            'mode' => $mode,
            ]);

        return $res;
    }

    private function getAggregateKey(array $data)
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
        return key_exists($featureName, DcsConstants::$featureToDCSKeyMapping);
    }

    public static function isDcsNewFeature($featureName)
    {
        return key_exists($featureName, DcsConstants::$dcsNewFeatures);
    }

    public function handleDcsFeatures(Entity $entity, $value , $mode = Mode::TEST)
    {
        $dcsFeatureName = DcsConstants::dcsFeatureNameFromAPIName($entity->getName());
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

        $req = $svc->buildExternalRequest($key, $entity->getEntityId(), [$dcsFeatureName => $value], $mode);

        $svcName = Constants::$newDcsConfigurationServiceMapping[$key];

        $res = $svc->action($svcName, $req, $mode);

        $this->trace->info(TraceCode::DCS_EXTERNAL_RESPONSE_RECEIVED, [
            'featureName' => $dcsFeatureName,
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

    private static function getDefaultAuditInfo()
    {
        $request[SDKConstants::CHANGE_BY] = 'api@razorpay.com';
        $request[SDKConstants::CHANGE_REASON] = 'api proxy request';
        $request[SDKConstants::CHANGE_APPROVED_BY] = 'api@razorpay.com';
        return $request;
    }
}
