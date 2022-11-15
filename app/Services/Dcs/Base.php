<?php

namespace RZP\Services\Dcs;

use RZP\Constants\Mode;
use RZP\Exception;
use Razorpay\Dcs\Kv\V1\Model\V1AuditLog;
use Razorpay\Dcs\Kv\V1\Model\V1Field;
use Razorpay\Dcs\Kv\V1\Model\V1GetEntityAggregateRequest;
use Razorpay\Dcs\Kv\V1\Model\V1GetRequest;
use Razorpay\Dcs\Kv\V1\Model\V1Key;
use Razorpay\Dcs\Kv\V1\Model\V1PatchRequest;
use Razorpay\Dcs\Kv\V1\Model\V1Query;
use Illuminate\Foundation\Application;
use Psr\Http\Client\NetworkExceptionInterface;
use Razorpay\Dcs\Client;
use Razorpay\Dcs\Config\UserCredentials;
use Razorpay\Dcs\Config\Config;

use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Models\FundTransfer\Redaction;
use RZP\Tests\Functional\Fixtures\Entity\Key;
use RZP\Trace\TraceCode;

class Base
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    protected $razorx;

    protected $client;
    /**
     * @var mixed
     */
    private $cache;

    /**
     * DCS Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->trace   = $app['trace'];

        $this->request = $app['request'];

        $this->auth    = $app['basicauth'];

        $this->config  = $app['config']->get('applications.dcs');

        $this->repo = $app['repo'];

        $this->razorx = $app['razorx'];

        $this->cache = $this->app['cache'];

        $this->initializeClientWithMode('test');
        $this->initializeClientWithMode('live');
    }

    public function initializeClientWithMode($mode) {
        $creds  = new UserCredentials();
        $cache = new Cache();
        $config = new Config($cache);

        $creds->setUsername($this->config[$mode]['username'])
            ->setPassword($this->config[$mode]['password']);

        $config->setServerURL($this->config[$mode]['url'])
            ->setMock($this->config['mock'])
            ->setUserCreds($creds);

        $this->client[$mode] = new Client($config);
    }

    /**
     * @param array $data
     * @param string $entityId
     * @param $value
     * @param $featureName
     * @param string $mode
     * @return \Razorpay\Dcs\Kv\V1\Model\V1PutResponse
     * @throws Exception\ServerErrorException
     */
    public function patch(array $data, string $entityId, $value, $featureName, string $mode = Mode::TEST)
    {
        $request = new V1PatchRequest();
        $key = new V1Key();
        $key->setEntityId($entityId)
            ->setEntity($data['entity'])
            ->setNamespace($data['namespace'])
            ->setDomain($data['domain'])
            ->setObjectName($data['object_name']);
        $request->setKey($key);

        $request->setValue($value);
        $fieldmask = new V1Field();
        $fieldmask->setName($featureName);
        $request->setFieldmasks([$fieldmask]);
        $audit_log = new V1AuditLog();
        $audit_log->setAction('PATCH')
            ->setChangeApprovedBy("api@razorpay.com")
            ->setChangeBy("api@razorpay.com")
            ->setChangeReason("api_php_dcs_features")
            ->setChangeValue($value);
        $request->setAuditLog($audit_log);

        try {
            return $this->client[$mode]->patch($request);
        }catch (\Exception $e) {
            $this->throwServerRequestException($e);
        }
        return null;
    }

    /**
     * @param array $data
     * @param string $entityId
     * @param string $name
     * @param string $mode
     * @return \Razorpay\Dcs\Kv\V1\Model\V1GetResponse
     * @throws Exception\ServerErrorException
     */
    public function fetch(array $data, string $entityId, string $name, $mode = Mode::TEST)
    {
        $request = new V1GetRequest();
        $key = new V1Key();
        $key->setEntityId($entityId)
            ->setEntity($data['entity'])
            ->setNamespace($data['namespace'])
            ->setDomain($data['domain'])
            ->setObjectName($data['object_name']);

        $q = new V1Query();
        $q->setKey($key);
        $fieldmask = new V1Field();
        $fieldmask->setName($name);
        $q->setFieldmasks([$fieldmask]);
        $request->setQueries([$q]);
        try {
            return $this->client[$mode]->get($request);
        }catch (\Exception $e) {
           $this->throwServerRequestException($e, false);
        }

        return null;
    }

    /**
     * @param array $data
     * @param string $entityId
     * @param string $name
     * @param string $mode
     * @return \Razorpay\Dcs\Kv\V1\Model\V1GetResponse
     * @throws Exception\ServerErrorException
     */
    public function fetchMultiple(array $data, array $entityIds, string $name, $mode = Mode::TEST)
    {
        $request = new V1GetRequest();
        $queries = [];
        foreach ($entityIds as $id) {
            $key = new V1Key();
            $key->setEntityId($id)
                ->setEntity($data['entity'])
                ->setNamespace($data['namespace'])
                ->setDomain($data['domain'])
                ->setObjectName($data['object_name']);

            $query = new V1Query();
            $query->setKey($key);
            $fieldmask = new V1Field();
            $fieldmask->setName($name);
            $query->setFieldmasks([$fieldmask]);
            $queries[] = $query;
        }
        $request->setQueries($queries);
        try {
            return $this->client[$mode]->get($request);
        }catch (\Exception $e) {
            $this->throwServerRequestException($e, false);
        }

        return null;
    }

    /**
     * @param $data
     * @param string $entityId
     * @param string $mode
     * @return \Razorpay\Dcs\Kv\V1\Model\V1GetEntityAggregateResponse
     */
    public function aggregateFetch(array $data, $mode = 'live')
    {
        $request = new V1GetEntityAggregateRequest();
        $key = new V1Key();
        $key->setEntityId($data['entity_id'])
            ->setEntity($data['entity'])
            ->setNamespace($data['namespace'])
            ->setDomain($data['domain'])
            ->setObjectName($data['object_name']);

        $request->setKey($key);
        try {
            return $this->client[$mode]->getEntityAggregateByEntityId($request);
        }catch (\Exception $e) {
            $this->throwServerRequestException($e, false);
        }

        return null;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param \Exception $e
     * @param bool $throwException
     * @return void
     * @throws Exception\ServerErrorException
     */
    protected function throwServerRequestException(\Exception $e,bool $throwException =  true)
    {
        $errorCode = 'SERVER_ERROR_DCS_SERVICE_FAILURE';

        if ($e instanceof NetworkExceptionInterface)
        {
            $errorCode = 'SERVER_ERROR_DCS_SERVICE_TIMEOUT';
        }

        $this->trace->traceException(
            $e,
            Trace::CRITICAL,
            TraceCode::SERVER_ERROR_DCS_SERVICE_FAILURE);
        if ($throwException === true)
        {
             throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
        }
    }

    /**
     * @param string $key
     * @return array
     */
    public function toKeyMap(string $key)
    {
        $values = explode("/", $key);
        $len = count($values);
        if($len < 4)
        {
            return null;
        }

        $data['object_name'] = $values[$len-1];
        $data['entity'] = $values[2];
        $data['domain'] = implode('/', array_splice($values,3, $len-4));
        $data['namespace'] = implode('/', [$values[0], $values[1]]);

        return $data;
    }
}
