<?php

namespace RZP\Services\FavService;

use App;
use RZP\Constants;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Http\RequestHeader;
use \WpOrg\Requests\Response;
use RZP\Http\Request\Requests;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Edge\Passport\Passport;
use RZP\Error\PublicErrorDescription;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\Models\FundAccount\Validation\Entity as FavEntity;
use RZP\Models\FundAccount\Validation\Metric as FavMetric;

class Base
{
    protected $app;

    protected $trace;

    protected $mode;

    protected $config;

    protected $key;

    protected $secret;

    protected $baseUrl;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * BasicAuth entity
     * @var BasicAuth
     *
     */
    protected $auth;

    const VERSION = '/v1';

    const X_REQUEST_ID  = 'X-Request-ID';

    const TYPE     = 'type';
    const CONSUMER = 'consumer';
    const PASSPORT = 'passport';

    const NAME               = 'name';
    const APP_USER_ID_HEADER = 'App-User-Id';

    public function __construct($app = null)
    {
        if (empty($app) === true)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->mode = $app['rzp.mode'] ?? 'live';

        $this->config = $app['config']->get('applications.payouts_service');

        $this->baseUrl = $this->config['url'];

        $this->key = $this->config[$this->mode]['payout_key'];

        $this->secret = $this->config[$this->mode]['payout_secret'];

        $this->auth = $this->app['basicauth'];

        $this->repo = $this->app['repo'];
    }

    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    public function makeRequestAndGetContent(array $input, string $action, string $method, array $headers = []) :array
    {
        $request = $this->getRequest($input, $action, $method, $headers);

        $this->traceFavServiceRequest($request);

        $response = $this->sendRequest($request);

        $this->traceFavServiceResponse($response);

        $responseArray = json_decode($response->body,true);

        $this->checkResponseForError($responseArray);

        return $responseArray;
    }

    public function sendRequest(array $request)
    {
        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                strtoupper($request['method']),
                $request['options']);

            return $response;
        }
        catch (Requests_Exception $e)
        {

            /** @var Route $route */
            $route = $this->app['api.route'];

            $routeName = $route->getCurrentRouteName();

            if ($this->checkRequestTimeout($e) === true)
            {
                $errorCode = TraceCode::FAV_SERVICE_REQUEST_TIMEOUT;

                $this->trace->count(FavMetric::FAV_SERVICE_TIME_OUT_EXCEPTION, [
                    Constants\Metric::LABEL_ROUTE_NAME => $routeName,
                    Constants\Metric::LABEL_ERROR_CODE => $e->getCode(),
                ]);
            }
            else
            {
                $errorCode = TraceCode::FAV_SERVICE_REQUEST_FAILED;

                $this->trace->count(FavMetric::FAV_SERVICE_REQUEST_FAILED, [
                    Constants\Metric::LABEL_ROUTE_NAME => $routeName,
                    Constants\Metric::LABEL_ERROR_CODE => $e->getCode(),
                ]);
            }

            $this->trace->traceException($e, Logger::ERROR, $errorCode);

            throw $e;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::FAV_SERVICE_REQUEST_FAILED
            );

            /** @var Route $route */
            $route = $this->app['api.route'];

            $routeName = $route->getCurrentRouteName();

            $this->trace->count(FavMetric::FAV_SERVICE_REQUEST_FAILED, [
                Constants\Metric::LABEL_ROUTE_NAME => $routeName,
                Constants\Metric::LABEL_ERROR_CODE => $ex->getCode(),
            ]);

            throw $ex;
        }
    }

    public function getRequest(array $input, string $action, string $method, array $headers = [])
    {
        $request = [
            'url' => $this->getUrl($action),
            'method' => $method,
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                self::X_REQUEST_ID           => $this->app['request']->getId(),
            ],
            'content' => empty($input) ? $input: json_encode($input),
            'options' => [
                'auth'            => $this->getAuthDetails(),
                'timeout'         => self::TIMEOUT,
            ]
        ];

        $request['headers'] = array_merge($request['headers'], $headers);

        return $request;
    }

    public function getUrl(string $uri): string
    {
        $url = $this->baseUrl . self::VERSION . $uri;

        return $url;
    }

    public function getAuthDetails(): array
    {
        return [
            $this->key,
            $this->secret,
        ];

    }

    public function traceFavServiceRequest(array $request)
    {
        $traceRequest = $request;

        unset($traceRequest['options']['auth']);

        unset($traceRequest['headers'][Passport::PASSPORT_JWT_V1 ]);

        $this->trace->info(TraceCode::FAV_SERVICE_REQUEST, $traceRequest);
    }

    public function traceFavServiceResponse($response)
    {
        $this->trace->info(TraceCode::FAV_SERVICE_RESPONSE, [
            'response'    => $response->body,
            'status_code' => $response->status_code
        ]);

        if (($response->status_code >= 500) and
            ($response->status_code <= 599))
        {
            $responseArray = json_decode($response->body,true);

            if (empty($responseArray) === true)
            {
                /** @var Route $route */
                $route = $this->app['api.route'];

                $routeName = $route->getCurrentRouteName();

                $this->trace->count(FavMetric::SERVER_ERROR_FAV_SERVICE_REQUEST_FAILED, [
                    Constants\Metric::LABEL_STATUS_CODE => $response->status_code,
                    Constants\Metric::LABEL_ROUTE_NAME  => $routeName,
                ]);
            }
        }
    }

    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param Requests_Exception $e The caught requests exception
     *
     * @return boolean              true/false
     */
    public function checkRequestTimeout(Requests_Exception $e)
    {
        if ($e->getType() === 'curlerror')
        {
            $curlErrNo = curl_errno($e->getData());

            if ($curlErrNo === 28)
            {
                return true;
            }
        }
        return false;
    }

    public function checkResponseForError($response)
    {
        if (is_null($response) === true)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR
            );
        }

        if (isset($response[FavEntity::ERROR]) === true)
        {
            if(isset($response[FavEntity::ID]) === true)
            {
                return;
            }

            $this->trace->error(
                TraceCode::FAV_SERVICE_FAILURE_API_RESPONSE,
                [
                    'response' => $response
                ]
            );

            $error = $response[FavEntity::ERROR];

            if (strtoupper($error[Error::PUBLIC_ERROR_CODE]) === ErrorCode::BAD_REQUEST_VALIDATION_FAILURE)
            {
                throw new Exception\BadRequestValidationFailureException(
                    $error[Error::DESCRIPTION],
                    $error[Error::FIELD]);
            }
            else if (strtoupper($error[Error::PUBLIC_ERROR_CODE]) === ErrorCode::BAD_REQUEST_ERROR)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    $error[Error::FIELD], null,
                    $error[Error::DESCRIPTION]);
            }
            else
            {
                throw new Exception\ServerErrorException(
                    $error[Error::DESCRIPTION],
                    ErrorCode::SERVER_ERROR,
                    $response
                );
            }
        }
    }

    public function getHeadersWithJwt()
    {
        $jwt = $this->app['basicauth']->getPassportJwt($this->baseUrl);

        /** @var BasicAuth $ba */
        $ba = $this->app['basicauth'];

        $headers = [];

        if ($ba->isPrivilegeAuth() === true)
        {
            $passport = $ba->getPassport();

            if (array_key_exists(self::CONSUMER, $passport) === true)
            {
                if ($passport[self::CONSUMER][self::TYPE] === BasicAuth::PASSPORT_CONSUMER_TYPE_USER)
                {
                    $this->trace->info(TraceCode::PASSPORT_EDIT_FOR_PRIVILEGE_AUTH_WITH_USER_CLAIMS,
                        [
                            self::PASSPORT => $ba->getPassport(),
                        ]);

                    $baTemp = clone $ba;

                    $baTemp->setPassportConsumerClaims(BasicAuth::PASSPORT_CONSUMER_TYPE_APPLICATION,
                        $ba->getInternalApp(),
                        true,
                        [self::NAME => $ba->getInternalApp()]);

                    $jwt = $baTemp->getPassportJwt($this->baseUrl);

                    $headers[self::APP_USER_ID_HEADER] = $ba->getUser()->getId();
                }
            }
        }

        $headers[Passport::PASSPORT_JWT_V1] = $jwt;

        return $headers;
    }
}
