<?php

namespace RZP\Services;

use App;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Offer\Metric;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;

class OffersEngine
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $mode;

    protected $request;

    protected $headers;

    protected $auth;

    // Headers
    const ACCEPT            = 'Accept';
    const CONTENT_TYPE      = 'Content-Type';
    const X_TASK_ID         = 'X-Task-Id';
    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';

    const DEFAULT_REQUEST_TIMEOUT   = 60;

    // Offers Engine APIs
    const OffersEngineCreateOffer = 'v1/offers';

    const OffersEngineUpdateOffer = 'v1/offers/%s';

    // Requests/responses will be logged by default or if value for path mentioned here is true.
    const REQUEST_LOGGER_MAP = [];

    const RESPONSE_LOGGER_MAP = [];

    /**
     * Offers Engine constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.offers_engine');

        $this->mode = (isset($app['rzp.mode']) === true) ? $app['rzp.mode'] : Mode::LIVE;

        $this->baseUrl = $this->config['base_url'][$this->mode];

        $this->request = $app['request'];

        $this->key = $this->config['offers_engine_username'][$this->mode];

        $this->secret = $this->config['offers_engine_password'][$this->mode];

        $this->auth = app('basicauth');

        $this->setHeaders();
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param int $timeout
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     * @throws \Throwable
     */
    public function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        int $timeout = self::DEFAULT_REQUEST_TIMEOUT)
    {
        $request = $this->generateRequest($endpoint, $method, $data, $timeout);

        return $this->sendOffersEngineRequest($request, $endpoint);
    }

    public function shouldLogResponse(string $endpoint, string $method) :bool
    {
        $mapKey = $method.'_'.$endpoint;
        $logResponse = true;
        if(isset(self::RESPONSE_LOGGER_MAP[$mapKey]))
        {
            $logResponse = self::RESPONSE_LOGGER_MAP[$mapKey];
        }
        return $logResponse;
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';
        $headers[self::X_TASK_ID]     = $this->app['request']->getTaskId();
        $headers[self::X_PASSPORT_JWT_V1] = $this->auth->getPassportJwt($this->baseUrl);
        $headers['X-User-Type'] = 'advertiser';
        $headers['X-Api-Decomp'] = 'shadow';

        $this->headers = $headers;
    }

    /**
     * @param array $request
     * @param string $endpoint
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     * @throws \Throwable
     */
    protected function sendOffersEngineRequest(array $request, string $endpoint)
    {
        $this->traceRequest($request, $endpoint);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);

            $parsedResponse = $this->parseAndReturnResponse($response);

            $logResponse = $this->shouldLogResponse($endpoint, $request['method']);
            if($logResponse === true)
            {
                $this->trace->info(TraceCode::OFFERS_ENGINE_RESPONSE,
                    [
                        "response" => $parsedResponse ?? [],
                        "statusCode" => $response->status_code,
                    ]);
            }

            if ($response->status_code === 400)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, $parsedResponse);
            }
            else if ($response->status_code >= 400)
            {
                // don't throw error if update call doesn't have an offer
                if ($parsedResponse['message'] !== "SERVER_ERROR_DB_FETCH_ERROR")
                {
                    throw new ServerErrorException(
                        TraceCode::OFFERS_ENGINE_REQUEST_FAILURE,
                        ErrorCode::SERVER_ERROR,
                        $parsedResponse
                    );
                }
                // return empty response for update call offer unavailable
                $parsedResponse = [];
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->count(Metric::OFFERS_ENGINE_REQUEST_FAILURE);
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::OFFERS_ENGINE_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);

            throw $e;
        }

        return $parsedResponse;
    }

    protected function parseAndReturnResponse($response)
    {
        $responseArray = json_decode($response->body, true);

        return $responseArray ?? [];
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request, string $endpoint)
    {
        $logRequest = $this->shouldLogRequest($endpoint, $request['method']);

        if($logRequest === true)
        {
            $traceRequest = $request;

            unset($traceRequest['options']['auth']);

            unset($traceRequest['headers'][self::X_PASSPORT_JWT_V1]);

            $this->trace->info(TraceCode::OFFERS_ENGINE_REQUEST, $traceRequest);
        }
    }

    public function shouldLogRequest(string $endpoint, string $method) :bool
    {
        $logRequest = true;

        $mapKey = $method.'_'.$endpoint;

        if(isset(self::REQUEST_LOGGER_MAP[$mapKey]))
        {
            $logRequest = self::REQUEST_LOGGER_MAP[$mapKey];
        }

        return $logRequest;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data, int $timeout): array
    {
        $url = $this->baseUrl . $endpoint;

        // json encode if data is must, else ignore
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => $timeout,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        $this->setHeaders();

        $headers = $this->headers;

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    /**
     * @throws \Exception|\Throwable
     */
    public function createOffer(array $input)
    {
        return $this->sendRequest(self::OffersEngineCreateOffer, Requests::POST, $input);
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function updateOffer($id, array $input)
    {
        $endpoint = sprintf(self::OffersEngineUpdateOffer, $id);

        return $this->sendRequest($endpoint, Requests::PATCH, $input);
    }
}
