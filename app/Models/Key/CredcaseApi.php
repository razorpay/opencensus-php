<?php

namespace RZP\Models\Key;

use Exception;
use Request;
use RZP\Constants\Metric;
use Razorpay\Trace\Logger;
use Rzp\Credcase\Apikey\V1\ApiKeyAPIClient;
use Rzp\Common\Mode\V1\Mode;
use Rzp\Credcase\Apikey\V1\ApiKeyGetRequest;
use Rzp\Credcase\Apikey\V1\ApiKeyListRequest;
use Rzp\Credcase\Apikey\V1\ConsumerApiKeyAPIClient;
use Rzp\Credcase\Apikey\V1\ConsumerApiKeyGetRequest;
use Rzp\Credcase\Apikey\V1\ConsumerApiKeyListRequest;
use Rzp\Credcase\Apikey\V1\TwirpError;
use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;
use RZP\Trace\TraceCode;
use Throwable;
use Twirp\Context;

class CredcaseApi
{
    const OWNER_TYPE = 'merchant';
    const DOMAIN = 'razorpay';
    protected $app;
    private $context;

    /**
     * @var Logger
     */
    private $trace;

    /**
     * @var ApiKeyAPIClient
     */
    private $client;

    /**
     * @var ConsumerApiKeyAPIClient
     */
    private $consumerClient;

    private $readKeySplitzExperiment;
    private $adminReadKeySplitzExperiment;

    public function __construct($app)
    {
        $this->app = $app;
        $this->trace  = app('trace');
        $config       = app('config')->get('services.credcase');
        $httpClient   = app('credcase_http_client');
        $this->client = new ApiKeyAPIClient($config['host'], $this->trace, $httpClient);
        $this->consumerClient = new ConsumerApiKeyAPIClient($config['host'], $this->trace, $httpClient);
        $this->readKeySplitzExperiment = $config['read_key_splitz'];
        $this->adminReadKeySplitzExperiment = $config['admin_read_key_splitz'];
        // Set default headers twirp context.
        $auth          = 'Basic ' . base64_encode($config['user'] . ':' . $config['password']);
        $headers       = ['Authorization' => $auth, 'X-Request-ID' => Request::getTaskId()];
        $this->context = Context::withHttpRequestHeaders([], $headers);
    }

    /**
     * @param string $ownerId
     * @param boolean $expired
     *
     * @throws ServerErrorException
     * @throws Exception
     */
    public function list(string $ownerId, string $mode, bool $expired): ?\Rzp\Credcase\Apikey\V1\ApiKeyListResponse
    {
        $apiKeyListRequest = new ApiKeyListRequest;
        $apiKeyListRequest->setOwnerId($ownerId);
        $apiKeyListRequest->setOwnerType(static::OWNER_TYPE);
        $apiKeyListRequest->setDomain(static::DOMAIN);
        $apiKeyListRequest->setMode($this->convertModeToEnum($mode));
        $apiKeyListRequest->setIsExpired($expired);

        $debug = ["owner_id" => $ownerId, "owner_type" => static::OWNER_TYPE, "domain" => static::DOMAIN];
        try {
            $response = $this->client->List($this->context, $apiKeyListRequest);
            return $response;
        } catch (Throwable $e) {
            $routeName = $this->app['request.ctx']->getRoute() ?? null;
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [
                'route_name' => $routeName,
                'error_message' => $e->getMessage()
            ]);
            throw new ServerErrorException('failed to complete request',
                ErrorCode::SERVER_ERROR_CREDCASE_REQUEST_FAILED, $debug, $e);
        }
    }

    public function findById($keyId, $expired = false): ?\Rzp\Credcase\Apikey\V1\ApiKeyResponse
    {
        $apiKeyGetRequest = new ApiKeyGetRequest();
        $apiKeyGetRequest->setId($keyId);
        if (!$expired){
            $apiKeyGetRequest->setNotExpired(true);
        }

        $debug = ["key_id" => $keyId, "owner_type" => static::OWNER_TYPE, "domain" => static::DOMAIN];
        try {
            $response = $this->client->Get($this->context, $apiKeyGetRequest);
            return $response;
        } catch (Throwable $e) {
            if($e instanceof TwirpError && $e->getErrorCode() == 'not_found'){
                return null;
            }
            $routeName = $this->app['request.ctx']->getRoute() ?? null;
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [
                'route_name' => $routeName,
                'error_message' => $e->getMessage()
            ]);
            throw new ServerErrorException('failed to complete request',
                ErrorCode::SERVER_ERROR_CREDCASE_REQUEST_FAILED, $debug, $e);
        }
    }

    public function getKeyByMerchantAndId($merchantId, $keyId): ?\Rzp\Credcase\Apikey\V1\ApiKeyResponse
    {
        $consumerApiKeyGetRequest = new ConsumerApiKeyGetRequest();
        $consumerApiKeyGetRequest->setOwnerType(static::OWNER_TYPE);
        $consumerApiKeyGetRequest->setOwnerId($merchantId);
        $consumerApiKeyGetRequest->setId($keyId);

        $debug = ["owner_id" => $merchantId, "owner_type" => static::OWNER_TYPE, "domain" => static::DOMAIN];
        try {
            $response = $this->consumerClient->GetKey($this->context, $consumerApiKeyGetRequest);
            return $response;
        } catch (Throwable $e) {
            if($e instanceof TwirpError && $e->getErrorCode() == 'not_found'){
                return null;
            }
            $routeName = $this->app['request.ctx']->getRoute() ?? null;
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [
                'route_name' => $routeName,
                'error_message' => $e->getMessage()
            ]);
            throw new ServerErrorException('failed to complete request',
                ErrorCode::SERVER_ERROR_CREDCASE_REQUEST_FAILED, $debug, $e);
        }
    }


    public function getKeysForMerchants($ownerIds, $mode, $count, $expired = false): ?\Rzp\Credcase\Apikey\V1\ApiKeyListResponse
    {
        $consumerApiKeyListRequest = new ConsumerApiKeyListRequest();
        $consumerApiKeyListRequest->setMode($this->convertModeToEnum($mode));
        $consumerApiKeyListRequest->setDomain(static::DOMAIN);
        $consumerApiKeyListRequest->setIsExpired($expired);
        $consumerApiKeyListRequest->setOwnerType(static::OWNER_TYPE);
        $consumerApiKeyListRequest->setOwnerIds($ownerIds);
        $consumerApiKeyListRequest->setCount($count);
        $consumerApiKeyListRequest->setSkip(0);

        $debug = ["owner_id" => $ownerIds, "owner_type" => static::OWNER_TYPE, "domain" => static::DOMAIN];
        try {
            $response = $this->consumerClient->GetKeys($this->context, $consumerApiKeyListRequest);
            return $response;
        } catch (Throwable $e) {
            $routeName = $this->app['request.ctx']->getRoute() ?? null;
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [
                'route_name' => $routeName,
                'error_message' => $e->getMessage()
            ]);
            throw new ServerErrorException('failed to complete request',
                ErrorCode::SERVER_ERROR_CREDCASE_REQUEST_FAILED, $debug, $e);
        }
    }

    public function getKeysDualwriteVariant($merchantId, $mode, $routeName, $functionName, $type)
    {
        try
        {
            $experimentId = $this->getSplitzExperimentForOperation($type);
            $this->trace->info(
                TraceCode::SPLITZ_REQUEST,
                [
                    Constants::MODE => $mode,
                    Constants::ROUTE => $routeName,
                    Constants::MERCHANT_ID => $merchantId,
                    Constants::FUNCTION_NAME => $functionName,
                    Constants::SPLITZ_EXPERIMENT => $experimentId,
                ]);

            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $experimentId,
                'request_data'  => json_encode(['mode' => $mode, 'route' => $routeName, 'function' => $functionName]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = 'disable';

            if(isset($response['response']) and isset($response['response']['variant']))
            {
                $this->trace->info(
                    TraceCode::SPLITZ_RESPONSE,
                    [
                        Constants::SPLITZ_VARIANT => $response['response']['variant']['name'],
                    ]);
                $variant = $response['response']['variant']['name'];
            }

            return $variant === 'enable';
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::KEY_DUAL_WRITE_SPLITZ_FAILED);
            return false;
        }
    }

    /**
     * @param string $mode
     */
    protected function convertModeToEnum($mode){
        return match ($mode) {
            "test" => 1,
            "live" => 2,
            default => 0,
        };
    }

    /*
    * @param string $type
    */
    protected function getSplitzExperimentForOperation($type){
        return match ($type) {
            "read" => $this->readKeySplitzExperiment,
            "admin_read" => $this->adminReadKeySplitzExperiment,
            default => "",
        };
    }
}
