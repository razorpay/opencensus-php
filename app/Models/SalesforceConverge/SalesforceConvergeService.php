<?php

namespace RZP\Models\SalesforceConverge;

use App;
use Request;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Cache;
use Razorpay\Trace\Logger as Trace;

class SalesforceConvergeService
{

//    const REQUEST_TIMEOUT = 4000;
//
//    const REQUEST_CONNECT_TIMEOUT = 2000;

    const CACHE_AUTHORIZATION_KEY = 'salesforce_converge_auth_token';

    private $config;

    /**
     * @var Trace
     */
    protected $trace;

    protected $app;

    protected $salesforceConvergeClient;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = app('trace');

        $this->config = config(Constants::APPLICATIONS_SALESFORCE_CONVERGE);

        $mock = $this->config['mock'];

        if ($mock === true)
        {
            $this->salesforceConvergeClient = (new SalesforceConvergeClientMock());
        }
        else
        {
            $this->salesforceConvergeClient = (new SalesforceConvergeClient());
        }

    }


    public function getAuthToken()
    {
        $token = null;
        try
        {
            $token = Cache::get(self::CACHE_AUTHORIZATION_KEY, null);
        }
        catch (\Throwable $ex)
        {
            // If cache fetch fails(say, the cache service is down), do not fail
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::REDIS_KEY_FETCH,
                ['key' => self::CACHE_AUTHORIZATION_KEY]
            );
        }

        return $token;
    }

    public function putAuthToken($token)
    {
        try
        {
            $token = Cache::put(self::CACHE_AUTHORIZATION_KEY, $token);
        }
        catch (\Throwable $ex)
        {
            // If cache put fails(say, the cache service is down), do not fail
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::REDIS_KEY_SET,
                ['key' => self::CACHE_AUTHORIZATION_KEY]
            );
        }
    }

    public function forgetAuthToken()
    {
        try
        {
            Cache::forget(self::CACHE_AUTHORIZATION_KEY);
        }
        catch (\Throwable $ex)
        {
            // If cache put fails(say, the cache service is down), do not fail
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::REDIS_KEY_FETCH,
                ['key' => self::CACHE_AUTHORIZATION_KEY]
            );
        }
    }

    public function getAuthorization()
    {
        $token = $this->getAuthToken();

        if (empty($token) === false)
        {
            return $token;
        }

        try
        {
            $request = new AuthorizationRequest($this->config);

            $token = $this->salesforceConvergeClient->getAuthorization($request);

            $this->putAuthToken($token);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL,
                                         TraceCode::SALESFORCE_CONVERGE_AUTH_REQUEST_FAILED,
                                         [
                                             "method" => "getAuthorization"
                                         ]
            );
        }

        return $token;
    }

    public function pushUpdatesToSalesforce(SalesforceMerchantUpdatesRequest $request): ?bool
    {
        $retVal = false;

        try {

            $token = $this->getAuthorization();

            $response = null;

            if (empty($token) === false) {
                try {

                    $response = $this->salesforceConvergeClient->pushUpdates($request, $token);

                } catch (\Throwable $e) {

                    if ($e->getError()->getInternalErrorCode() == ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED) {
                        $this->forgetAuthToken();
                        return $this->pushUpdatesToSalesforce($request);
                    }

                }
            }

            if (empty($response) === false) {
                $retVal = $response->isSuccess();
                if ($retVal === true) {
                    $this->trace->info(TraceCode::SALESFORCE_CONVERGE_SERVICE_REQUEST_SUCCESS, [$request->merchant_id]);
                }
            }

        } catch (\Throwable $e) {

            $this->trace->traceException($e, Trace::CRITICAL,
                TraceCode::SALESFORCE_CONVERGE_SERVICE_REQUEST_EXCEPTION,
                [
                    Constants::PAYLOAD => (array) $request,
                    Constants::PATH    => $request->getPath(),
                    Constants::ERROR   => $e->getMessage()
                ]
            );

        }

        return $retVal;
    }
}
