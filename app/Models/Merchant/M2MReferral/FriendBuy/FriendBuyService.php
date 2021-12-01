<?php

namespace RZP\Models\Merchant\M2MReferral\FriendBuy;
use App;
use Request;
use RZP\Trace\TraceCode;
use Cache;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Http\FormRequest;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class FriendBuyService
{

    const REQUEST_TIMEOUT = 4000;

    const REQUEST_CONNECT_TIMEOUT = 2000;

    const CACHE_AUTHORIZATION_KEY = 'friendbuy_authorization';

    private $config;

    /**
     * @var Trace
     */
    protected $trace;

    protected $app;

    protected $friendBuyClient;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = app('trace');

        $this->config = config(Constants::APPLICATIONS_FRIEND_BUY);

        $mock = $this->config['mock'];

        if ($mock === true)
        {
            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $this->config['response'] ?? Constant::SUCCESS;

            $this->friendBuyClient = new FriendBuyClientMock($mockStatus);
        }
        else
        {
            $this->friendBuyClient = (new FriendBuyClient());
        }
    }


    public function getAuthToken()
    {
        $token = null;
        try
        {
            $token = Cache::get(self::CACHE_AUTHORIZATION_KEY);
            if (empty($token) === false and $token->isExpired())
            {
                $token = null;
            }
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
            $request = new AuthorizationRequest($this->config[Constants::AUTH][Constants::KEY], $this->config[Constants::AUTH][Constants::SECRET]);

            $token = $this->friendBuyClient->getAuthorization($request);

            $this->putAuthToken($token);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL,
                                         TraceCode::FRIEND_BUY_AUTHORIZATION_FAILED,
                                         [
                                             "method" => "getAuthorization"
                                         ]
            );
        }

        return $token;
    }

    public function postMtuEvent(MtuEventRequest $request): ?EventResponse
    {
        $token = $this->getAuthorization();

        $response = null;

        if (empty($token) === false)
        {
            try
            {
                $request->authToken = $token;

                $response = $this->friendBuyClient->postEvent($request);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Trace::CRITICAL,
                                             TraceCode::FRIEND_BUY_SERVICE_REQUEST_FAILED,
                                             [
                                                 Constants::PAYLOAD => $request->toArray(),
                                                 Constants::PATH    => $request->path
                                             ]
                );
            }
        }

        return $response;
    }

    public function postSignupEvent(SignUpEventRequest $request): ?EventResponse
    {
        $token = $this->getAuthorization();

        $response = null;

        if (empty($token) === false)
        {
            try
            {
                $request->authToken = $token;

                $response = $this->friendBuyClient->postEvent($request);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Trace::CRITICAL,
                                             TraceCode::FRIEND_BUY_SERVICE_REQUEST_FAILED,
                                             [
                                                 Constants::PAYLOAD => $request->toArray(),
                                                 Constants::PATH    => $request->path
                                             ]
                );
            }
        }

        return $response;
    }

    public function generateReferralLink(ReferralLinkRequest $request): ?ReferralLinkResponse
    {
        $token = $this->getAuthorization();

        $response = null;

        if (empty($token) === false)
        {
            try
            {
                $request->authToken = $token;

                $response = $this->friendBuyClient->generateReferralLink($request);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Trace::CRITICAL,
                                             TraceCode::FRIEND_BUY_SERVICE_REQUEST_FAILED,
                                             [
                                                 Constants::PAYLOAD => $request->toArray(),
                                                 Constants::PATH    => $request->path
                                             ]
                );
            }
        }

        return $response;
    }
    public function validateSignature(FormRequest $request)
    {
       return $this->friendBuyClient->validateSignature($request);
    }
}
