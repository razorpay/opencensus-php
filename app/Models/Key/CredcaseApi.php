<?php

namespace RZP\Models\Key;

use Exception;
use Request;
use RZP\Constants\Metric;
use Razorpay\Trace\Logger;
use Rzp\Credcase\Apikey\V1\ApiKeyAPIClient;
use Rzp\Common\Mode\V1\Mode;
use Rzp\Credcase\Apikey\V1\ApiKeyListRequest;
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

    private $readKeySplitzExperiment;

    public function __construct($app)
    {
        $this->app = $app;
        $this->trace  = app('trace');
        $config       = app('config')->get('services.credcase');
        $httpClient   = app('credcase_http_client');
        $this->client = new ApiKeyAPIClient($config['host'], $this->trace, $httpClient);
        $this->readKeySplitzExperiment = $config['read_key_splitz'];
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
    public function list(string $ownerId, string $mode, bool $expired): \Rzp\Credcase\Apikey\V1\ApiKeyListResponse
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

    public function getKeysDualwriteVariant($merchantId, $mode, $routeName, $type)
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
                    Constants::SPLITZ_EXPERIMENT => $experimentId,
                ]);

            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $experimentId,
                'request_data'  => json_encode(['mode' => $mode, 'route' => $routeName]),
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

            return 'disable';
        }
    }

    /**
     * @param string $mode
     */
    private function convertModeToEnum($mode){
        return match ($mode) {
            "test" => 1,
            "live" => 2,
            default => 0,
        };
    }

    /*
    * @param string $type
    */
    private function getSplitzExperimentForOperation($type){
        return match ($type) {
            "read" => $this->readKeySplitzExperiment,
            default => "",
        };
    }
}
