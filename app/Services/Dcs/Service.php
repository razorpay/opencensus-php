<?php

namespace RZP\Services\Dcs;

use Google\Protobuf\Internal\DescriptorPool;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Models\Feature\Entity;
use RZP\Models\Feature\Constants as FeatureConstants;
use Razorpay\Trace\Logger as Trace;
use Psr\Http\Client\NetworkExceptionInterface;

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

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.dcs');
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param Entity $entity
     * @param string $mode
     * @return void
     * @throws Exception\ServerErrorException
     */
    public function assignFeature(Entity $entity, $mode = Mode::TEST) {
        $key = Constants::$featureToDCSKeyMapping[$entity->getName()];
        $data = $this->toKeyMap($key);

        if($this->isDCSNewFeature($entity->getName()) === true)
        {
            $this->handleNewFeatures($entity, true, $mode);
            return;
        }

        $this->trace->info(TraceCode::DCS_ASSIGN_REQUEST_RECEIVED, [
            'feature_name' => $entity->getName(),
            'request_data' => $data,
            'key' => $key,
            'mode' => $mode,
        ]);

        $features = new Constants::$featureToDCSClass[$entity->getName()](
            [$entity->getName() => true]
        );

        $value = $features->serializeToString();

        $this->patch($data, $entity->getEntityId(), base64_encode($value), $entity->getName(), $mode);
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param Entity $entity
     * @param string $mode
     * @return void
     * @throws Exception\ServerErrorException
     */
    public function removeFeature(Entity $entity, $mode = Mode::TEST) {
        $key = Constants::$featureToDCSKeyMapping[$entity->getName()];
        $data = $this->toKeyMap($key);

        $this->trace->info(TraceCode::DCS_REMOVE_REQUEST_RECEIVED, [
            'feature_name' => $entity->getName(),
            'request_data' => $data,
            'id' =>  $entity->getEntityId(),
            'key' => $key,
            'mode' => $mode,
        ]);

        if($this->isDCSNewFeature($entity->getName()) === true)
        {
            $this->handleNewFeatures($entity, false, $mode);
            return;
        }

        $features = new Constants::$featureToDCSClass[$entity->getName()](
            [$entity->getName() => !FeatureConstants::$featureValueMap[$entity->getName()]]
        );

        $value = $features->serializeToString();

        $this->patch($data, $entity->getEntityId(), base64_encode($value), $entity->getName(), $mode);
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityId
     * @param string $featureName
     * @param string $mode
     * @return array
     * @throws Exception\ServerErrorException
     */
    public function fetchByEntityIdAndName(string $entityId, string $featureName, $mode = Mode::TEST) {
        $key = Constants::$featureToDCSKeyMapping[$featureName];
        $data = $this->toKeyMap($key);
        $res = [];
        $response = $this->fetch($data, $entityId, $featureName, $mode);
        $kvs =  $response->getKvs();
        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'feature_name' => $featureName,
            'request_data' => $data,
            'id' =>  $entityId,
            'key' => $key,
            'mode' => $mode,
        ]);

        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();
            $value = base64_decode($kv->getValue());
            $features = new Constants::$featureToDCSClass[$featureName];

            $features->mergeFromString($value);

            $data = [
                Entity::NAME => $featureName,
                Entity::ENTITY_TYPE => $key->getEntity(),
                Entity::ENTITY_ID => $entityId,
            ];
            $func = 'get'. camel_case($featureName);
            $result = $features->$func();
           if ($result === true){
               $entity = (new Entity)->build($data);
               $entity->setEntityId($entityId);
               $res = $entity;
               break;
           }
        }

        return $res;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param array $entityIds
     * @param string $featureName
     * @param string $mode
     * @return array
     * @throws Exception\ServerErrorException
     */
    public function fetchByEntityIdsAndName(array $entityIds, string $featureName, $mode = Mode::TEST) {
        $key = Constants::$featureToDCSKeyMapping[$featureName];
        $data = $this->toKeyMap($key);
        $res = [];
        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'feature_name' => $featureName,
            'request_data' => $data,
            'ids' =>  $entityIds,
            'key' => $key,
            'mode' => $mode,
        ]);
        $response = $this->fetchMultiple($data, $entityIds, $featureName, $mode);
        if ($response === null) {
            return $res;
        }
        $kvs =  $response->getKvs();
        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();
            $value = base64_decode($kv->getValue());
            $features = new Constants::$featureToDCSClass[$featureName];

            $features->mergeFromString($value);

            $data = [
                Entity::NAME => $featureName,
                Entity::ENTITY_TYPE => $key->getEntity(),
                Entity::ENTITY_ID => $key->getEntityId(),

            ];
            $func = 'get'. camel_case($featureName);
            $result = $features->$func();
            if ($result === true){
                $entity = (new Entity)->build($data);
                $entity->setEntityId($key->getEntityId());
                $res[] = $entity;
            }
        }

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
    public function fetchByEntityIdAndEntityType(string $entityType, string $entityId, $mode = Mode::TEST) {
        $data = [];
        $data['entity_id'] = $entityId ;
        $data['entity'] = $entityType ;
        $key = $this->getAggregateKey($data);
        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'entityType' => $entityType,
            'request_data' => $data,
            'id' =>  $entityId,
            'key' => $key,
            'mode' => $mode,
        ]);
        $response = $this->aggregateFetch($key, $mode);
        $res = [];
        if ($response === null) {
            return $res;
        }
        $kvs =  $response->getKvs() == null ? []: $response->getKvs();
        foreach ($kvs as $index => $kv)
        {
            $data = [];
            $constants = new Constants();
            $key_str = $constants::convertDCSKeyToString($kv->getKey());
            if (key_exists($key_str, Constants::$dcsKeyToDCSClass)){
                $features = new Constants::$dcsKeyToDCSClass[$key_str];
                $features->mergeFromString(base64_decode($kv->getValue()));
                $pool = DescriptorPool::getGeneratedPool();
                $this->desc = $pool->getDescriptorByClassName($constants->convertDCSKeyToClassName($kv->getKey()));
                foreach ($this->desc->getField() as $field) {
                    $getter = $field->getGetter();
                    $data[$field->getName()] = $features->$getter();
                }

                foreach ($data as $featureName => $enabled){
                    $buildData = [
                        Entity::NAME => $featureName,
                        Entity::ENTITY_TYPE => $entityType,
                        Entity::ENTITY_ID => $entityId,
                    ];
                    if ($enabled === true)
                    {
                        $entity = (new Entity)->build($buildData);
                        $entity->setEntityId($entityId);
                        $res[] = $entity;
                    }
                }
            }
        }

        return $res;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $featureName
     * @param string $mode
     * @return array
     */
    public function fetchByFeatureName(string $featureName, $mode = Mode::TEST) {
        $key = Constants::$featureToDCSKeyMapping[$featureName];
        $data = $this->toKeyMap($key);
        $aggregateKey = $this->getAggregateKey($data);

        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'feature_name' => $featureName,
            'request_data' => $data,
            'key' => $key,
            'aggregate_key' => $aggregateKey,
            'mode' => $mode,
        ]);

        $response = $this->aggregateFetch($aggregateKey, $mode);
        $res = [];
        if ($response === null) {
            return $res;
        }
        $kvs =  $response->getKvs() == null ? []: $response->getKvs();
        foreach ($kvs as $index => $kv)
        {
            $data = [];
            $constants = new Constants();
            $key_str = $constants::convertDCSKeyToString($kv->getKey());
            if (key_exists($key_str, Constants::$dcsKeyToDCSClass)){
                $features = new Constants::$dcsKeyToDCSClass[$key_str];
                $features->mergeFromString(base64_decode($kv->getValue()));
                $pool = DescriptorPool::getGeneratedPool();
                $this->desc = $pool->getDescriptorByClassName($constants->convertDCSKeyToClassName($kv->getKey()));
                foreach ($this->desc->getField() as $field) {
                    $getter = $field->getGetter();
                    $data[$field->getName()] = $features->$getter();
                }

                $enabled = $data[$featureName];

                if ($enabled === true)
                {
                    $data = [
                        Entity::NAME => $featureName,
                        Entity::ENTITY_TYPE => $kv->getKey()->getEntity(),
                        Entity::ENTITY_ID => $kv->getKey()->getEntityId(),
                    ];

                    $entity = (new Entity)->build($data);
                    $entity->setEntityId($kv->getKey()->getEntityId());
                    $res[] = $entity;
                }
            }
        }

        return $res;
    }

    private function getAggregateKey(array $data){
        $data['namespace'] = (key_exists('namespace', $data) &&
            ($data['namespace'] !== null || $data['namespace'] !== '')) ? $data['namespace'] : '';

        $data['domain'] = (key_exists('domain', $data) &&
            ($data['domain'] !== null || $data['domain'] !== '')) ? $data['domain'] : '';

        $data['entity'] = (key_exists('entity', $data) &&
            ($data['entity'] !== null || $data['entity'] !== '')) ? $data['entity'] : '';

        $data['entity_id'] = (key_exists('entity_id', $data) &&
            ($data['entity_id'] !== null || $data['entity_id'] !== '')) ? $data['entity_id'] : '';

        $data['object_name'] = (key_exists('object_name', $data) &&
            ($data['object_name'] !== null || $data['object_name'] !== '')) ? $data['object_name'] : '';

      return $data;
    }

    public function isDcsEnabled($featureName){
        $mode = $this->app['rzp.mode'] ?? 'live';
        $flag = $this->app['razorx']->getTreatment($featureName,
            RazorxTreatment::DCS_ENABLED,
            $mode);
        $this->trace->info(TraceCode::DCS_RAZORX_EXPERIMENT, [
            'feature_name' => $featureName,
            'razorx_treatment' => RazorxTreatment::DCS_ENABLED,
            'razorx_output' => $flag,
            'mode' => $mode,
        ]);
        return $flag === 'on';
    }

    public static function isDCSNewFeature($featureName){
        return key_exists($featureName, Constants::$newDcsFeaturesAndServiceMapping) === true;
    }

    public static function isDcsFeature($featureName){
        return key_exists($featureName, Constants::$featureToDCSKeyMapping);
    }

    public function handleNewFeatures(Entity $entity, $value , $mode = Mode::TEST)
    {
        $key = Constants::$featureToDCSKeyMapping[$entity->getName()];
        $data = $this->toKeyMap($key);

        $this->trace->info(TraceCode::DCS_ASSIGN_REQUEST_RECEIVED, [
            'feature_name' => $entity->getName(),
            'request_data' => $data,
            'key' => $key,
            'mode' => $mode,
            'new_usecase' => true
        ]);

        $svc = new ExternalService\Service($this->app);
        $req = $svc->buildExternalRequest($key, $entity->getEntityId(), $entity->getName(), $value, $mode);
        $svcName = Constants::$newDcsFeaturesAndServiceMapping[$entity->getName()];
        $res = $svc->action($svcName, $req, $mode);
        $this->handleResponse($res);
    }

    public function handleResponse($response) {
        if ($response['success'] !== true) {
            $description = ($response['error'] !== null && $response['error']['description'] !== null) ?
                $response['error']['description']: "Error in DCS Client Service Request";
            $ex = new Exception\ServerErrorException($description,
                'SERVER_ERROR_DCS_SERVICE_FAILURE',
                "failure response from external service");

            $this->trace->traceException($ex);
            throw $ex;
        }
        return $response;
    }
}
