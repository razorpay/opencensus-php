<?php

namespace RZP\Services\FavService;

use App;
use Throwable;
use RZP\Constants;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Edge\Passport\Passport;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\Exception\BadRequestValidationFailureException;
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

    protected $timeout = 60;

    protected $connectTimeout = 10;

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

    const X_REQUEST_ID = 'X-Request-ID';

    const TYPE = 'type';

    const CONSUMER = 'consumer';

    const PASSPORT = 'passport';

    const NAME = 'name';
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

        $this->config = $app['config']->get('applications.fav_service');

        $this->baseUrl = $this->config['url'];

        $this->key = $this->config[$this->mode]['fav_key'];

        $this->secret = $this->config[$this->mode]['fav_secret'];

        $this->auth = $this->app['basicauth'];

        $this->repo = $this->app['repo'];

        $this->timeout = $this->config[$this->mode]['timeout'] ?? $this->timeout;

        $this->connectTimeout = $this->config[$this->mode]['connect_timeout'] ?? $this->connectTimeout;
    }

    /**
     * @throws Throwable
     * @throws ServerErrorException
     * @throws BadRequestValidationFailureException
     * @throws Requests_Exception
     * @throws BadRequestException
     */
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

    /**
     * @throws Throwable
     * @throws Requests_Exception
     */
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
        catch (Throwable $ex)
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

    public function getRequest(array $input, string $action, string $method, array $headers = []): array
    {
        $request = [
            'url'       => $this->getUrl($action),
            'method'    => $method,
            'headers'   => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                self::X_REQUEST_ID           => $this->app['request']->getId(),
            ],
            'content'   => empty($input) ? $input: json_encode($input),
            'options'   => [
                'auth'     => $this->getAuthDetails(),
                'timeout'  => $this->timeout,
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

    public function traceFavServiceRequest(array $request): void
    {
        $traceRequest = $request;

        unset($traceRequest['options']['auth']);

        unset($traceRequest['headers'][Passport::PASSPORT_JWT_V1 ]);

        $this->trace->info(TraceCode::FAV_SERVICE_REQUEST, $traceRequest);
    }

    public function traceFavServiceResponse($response): void
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

    /**
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function checkResponseForError($response): void
    {
        if (is_null($response) === true)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR
            );
        }

        // Check for new error format with 'details' array
        if (isset($response['details']) === true &&
            is_array($response['details']) === true &&
            empty($response['details']) === false)
        {
            // Get the first error detail from the details array
            $errorDetail = $response['details'][0];
            $errorCode = strtoupper($errorDetail['code'] ?? '');

            switch ($errorCode)
            {
                case 'FAV000001': // Input validation failed (400)
                    throw new Exception\BadRequestValidationFailureException(
                        $errorDetail['description'] ?? $response['message'] ?? 'Validation failed',
                        $errorDetail['field'] ?? ''
                    );

                case 'FAV000003': // Entity not found (404)
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_NOT_FOUND,
                        null,
                        null,
                        $errorDetail['description'] ?? $response['message'] ?? 'Entity not found'
                    );

                case 'FAV000008': // Missing authentication (401)
                case 'FAV000009': // Invalid authentication (403)
                case 'FAV000010': // Not authorized (403)
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR,
                        null,
                        null,
                        $errorDetail['description'] ?? $response['message'] ?? 'Authentication failed'
                    );

                case 'FAV000011': // Too many requests (429)
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR,
                        null,
                        null,
                        $errorDetail['description'] ?? $response['message'] ?? 'Too many requests'
                    );

                default:
                    throw new Exception\ServerErrorException(
                        $errorDetail['description'] ?? $response['message'] ?? 'Unknown error occurred',
                        ErrorCode::SERVER_ERROR,
                        $response
                    );
            }
        }

        // If response has error but not in expected format, throw generic server error
        if (isset($response['code']) === true && $response['code'] !== 0)
        {
            $this->trace->error(
                TraceCode::FAV_SERVICE_FAILURE_API_RESPONSE,
                [
                    'response' => $response
                ]
            );

            throw new Exception\ServerErrorException(
                $response['message'] ?? 'Unknown error occurred',
                ErrorCode::SERVER_ERROR,
                $response
            );
        }
    }

    public function getHeadersWithJwt(): array
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
